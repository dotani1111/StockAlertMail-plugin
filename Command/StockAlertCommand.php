<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\StockAlertMail\Command;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\Master\ProductStatus;
use Eccube\Repository\ProductClassRepository;
use Plugin\StockAlertMail\Entity\StockAlertLog;
use Plugin\StockAlertMail\Repository\StockAlertConfigRepository;
use Plugin\StockAlertMail\Repository\StockAlertLogRepository;
use Plugin\StockAlertMail\Service\StockAlertMailBuilder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\Translation\TranslatorInterface;

class StockAlertCommand extends Command
{
    protected static $defaultName = 'eccube:plugin:stock-alert-mail';

    public function __construct(
        private readonly ProductClassRepository $productClassRepository,
        private readonly StockAlertConfigRepository $configRepository,
        private readonly StockAlertLogRepository $logRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly MailerInterface $mailer,
        private readonly StockAlertMailBuilder $mailBuilder,
        private readonly TranslatorInterface $translator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription($this->translator->trans('stock_alert_mail.command.description'));
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $config = $this->configRepository->find(1);
        if ($config === null) {
            $io->error($this->translator->trans('stock_alert_mail.command.config_not_found'));

            return Command::FAILURE;
        }

        $threshold = $config->getThreshold();

        // 全ProductClassを取得（在庫無制限・非公開除く）
        $allItems = $this->productClassRepository->createQueryBuilder('pc')
            ->select('pc')
            ->innerJoin('pc.Product', 'p')
            ->where('pc.stock_unlimited = false')
            ->andWhere('pc.visible = true')
            ->andWhere('p.Status = :status')
            ->setParameter('status', ProductStatus::DISPLAY_SHOW)
            ->orderBy('p.id', 'ASC')
            ->getQuery()
            ->getResult();

        // 既存ログを一括取得してマップ化（N+1回避）
        $existingLogs = $this->logRepository->findAll();
        $logMap = [];
        foreach ($existingLogs as $log) {
            $logMap[$log->getProductClass()->getId()] = $log;
        }

        $newAlertItems = [];

        foreach ($allItems as $productClass) {
            $isLowStock = $productClass->getStock() <= $threshold;
            $log = $logMap[$productClass->getId()] ?? null;

            if ($isLowStock && $log === null) {
                // 閾値以下 かつ 未送信 → アラート対象
                $newAlertItems[] = $productClass;
            } elseif (!$isLowStock && $log !== null) {
                // 在庫が回復 → ログを削除してリセット
                $this->entityManager->remove($log);
            }
        }

        // 回復した商品のログ削除を反映
        $this->entityManager->flush();

        if (empty($newAlertItems)) {
            $io->success($this->translator->trans('stock_alert_mail.command.no_alert_items'));

            return Command::SUCCESS;
        }

        $io->info($this->translator->trans('stock_alert_mail.command.alert_items_found', ['%count%' => count($newAlertItems)]));

        // メール送信
        try {
            $BaseInfo = $this->mailBuilder->getBaseInfo();
            $toEmails = $this->mailBuilder->resolveToEmails($config);
            $body = $this->mailBuilder->buildMailBody($newAlertItems, $threshold);
            $subject = $this->mailBuilder->buildMailSubject();

            $message = (new Email())
                ->subject($subject)
                ->from(new Address($BaseInfo->getEmail01(), $BaseInfo->getShopName()))
                ->text($body);

            foreach ($toEmails as $email) {
                $message->addTo($email);
            }

            $this->mailer->send($message);

            // 送信成功後にアラートログを永続化
            foreach ($newAlertItems as $productClass) {
                $newLog = new StockAlertLog();
                $newLog->setProductClass($productClass);
                $newLog->setAlertedAt(new \DateTime());
                $this->entityManager->persist($newLog);
            }
            $this->entityManager->flush();

            $io->success($this->translator->trans('stock_alert_mail.command.mail_sent', ['%emails%' => implode(', ', $toEmails)]));
        } catch (\Exception $e) {
            $io->error($this->translator->trans('stock_alert_mail.command.mail_failed', ['%message%' => $e->getMessage()]));

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
