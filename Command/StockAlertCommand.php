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
use Eccube\Entity\BaseInfo;
use Eccube\Entity\Master\ProductStatus;
use Eccube\Repository\BaseInfoRepository;
use Eccube\Repository\ProductClassRepository;
use Plugin\StockAlertMail\Entity\StockAlertLog;
use Plugin\StockAlertMail\Repository\StockAlertConfigRepository;
use Plugin\StockAlertMail\Repository\StockAlertLogRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class StockAlertCommand extends Command
{
    protected static $defaultName = 'eccube:plugin:stock-alert-mail';

    private BaseInfo $BaseInfo;

    public function __construct(
        private readonly BaseInfoRepository $baseInfoRepository,
        private readonly ProductClassRepository $productClassRepository,
        private readonly StockAlertConfigRepository $configRepository,
        private readonly StockAlertLogRepository $logRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly MailerInterface $mailer,
        private readonly Environment $twig,
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
        $this->BaseInfo = $this->baseInfoRepository->get();

        $config = $this->configRepository->findOneBy([]);
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
        $toEmails = $this->resolveToEmails($config);
        $body = $this->buildMailBody($config, $newAlertItems, $threshold);
        $subject = $this->buildMailSubject($config);

        try {
            $message = (new Email())
                ->subject($subject)
                ->from(new Address($this->BaseInfo->getEmail01(), $this->BaseInfo->getShopName()))
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

    private function resolveToEmails($config): array
    {
        $alertEmails = $config->getAlertEmails();
        if (!empty($alertEmails)) {
            $validated = array_filter(
                array_map('trim', explode(',', $alertEmails)),
                fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL) !== false
            );
            if (!empty($validated)) {
                return array_values($validated);
            }
        }

        return [$this->BaseInfo->getEmail01()];
    }

    private function buildMailSubject($config): string
    {
        if (!empty($config->getMailSubject())) {
            $subject = strtr($config->getMailSubject(), [
                '{shop_name}' => $this->BaseInfo->getShopName(),
            ]);
        } else {
            $subject = $this->translator->trans('stock_alert_mail.command.mail_subject', ['%shop_name%' => $this->BaseInfo->getShopName()]);
        }

        // プレースホルダー展開後の改行をサニタイズ（ヘッダーインジェクション対策）
        return preg_replace('/[\r\n]+/', ' ', $subject);
    }

    private function buildMailBody($config, array $items, int $threshold): string
    {
        $customBody = $config->getMailBody();
        if (!empty($customBody)) {
            $itemLines = [];
            foreach ($items as $productClass) {
                $name = $productClass->getProduct()->getName();
                if ($productClass->hasClassCategory1()) {
                    $name .= ' ['.$productClass->getClassCategory1()->getName();
                    if ($productClass->hasClassCategory2()) {
                        $name .= ' / '.$productClass->getClassCategory2()->getName();
                    }
                    $name .= ']';
                }
                $itemLines[] = '■ '.$name;
                $itemLines[] = '  '.$this->translator->trans('stock_alert_mail.mail.current_stock', ['%stock%' => $productClass->getStock()]);
                $itemLines[] = '  '.$this->translator->trans('stock_alert_mail.mail.threshold_label', ['%threshold%' => $threshold]);
                $itemLines[] = '';
            }

            return strtr($customBody, [
                '{shop_name}' => $this->BaseInfo->getShopName(),
                '{threshold}' => $threshold,
                '{items}' => implode("\n", $itemLines),
            ]);
        }

        return $this->twig->render('@StockAlertMail/Mail/stock_alert.twig', [
            'BaseInfo' => $this->BaseInfo,
            'lowStockItems' => $items,
            'threshold' => $threshold,
        ]);
    }
}
