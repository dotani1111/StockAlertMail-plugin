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

namespace Plugin\StockAlertMail\Service;

use Eccube\Entity\BaseInfo;
use Eccube\Entity\ClassCategory;
use Eccube\Entity\MailTemplate;
use Eccube\Entity\Product;
use Eccube\Entity\ProductClass;
use Eccube\Repository\BaseInfoRepository;
use Eccube\Repository\MailTemplateRepository;
use Plugin\StockAlertMail\Entity\StockAlertConfig;
use Plugin\StockAlertMail\PluginManager;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class StockAlertMailBuilder
{
    private ?BaseInfo $BaseInfo = null;

    public function __construct(
        private readonly BaseInfoRepository $baseInfoRepository,
        private readonly MailTemplateRepository $mailTemplateRepository,
        private readonly TranslatorInterface $translator,
        private readonly Environment $twig,
    ) {
    }

    public function getBaseInfo(): BaseInfo
    {
        if ($this->BaseInfo === null) {
            $this->BaseInfo = $this->baseInfoRepository->get();
        }

        return $this->BaseInfo;
    }

    public function resolveToEmails(StockAlertConfig $config): array
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

        return [$this->getBaseInfo()->getEmail01()];
    }

    public function buildMailSubject(): string
    {
        $mailTemplate = $this->findMailTemplate();

        $subjectSuffix = $mailTemplate !== null
            ? trim((string) $mailTemplate->getMailSubject())
            : '';
        if ($subjectSuffix === '') {
            $subjectSuffix = $this->translator->trans('stock_alert_mail.mail.subject_default');
        }

        $subject = '['.$this->getBaseInfo()->getShopName().'] '.$subjectSuffix;

        // 改行をサニタイズ（ヘッダーインジェクション対策）
        return preg_replace('/[\r\n]+/', ' ', $subject);
    }

    public function buildMailBody(array $items, int $threshold): string
    {
        $templateParams = [
            'BaseInfo' => $this->getBaseInfo(),
            'lowStockItems' => $items,
            'threshold' => $threshold,
        ];

        $mailTemplate = $this->findMailTemplate();

        if ($mailTemplate !== null) {
            try {
                return $this->twig->render($mailTemplate->getFileName(), $templateParams);
            } catch (\Twig\Error\Error $e) {
                // テーマ側テンプレートの欠落・構文エラー・変数未定義等はプラグイン付属テンプレートへフォールバック
            }
        }

        return $this->twig->render('@StockAlertMail/Mail/stock_alert.twig', $templateParams);
    }

    /**
     * テストメール用のダミー商品データを生成する。
     *
     * @return ProductClass[]
     */
    public function createDummyItems(): array
    {
        // ダミー商品A（規格なし）
        $productA = new Product();
        $productA->setName($this->translator->trans('stock_alert_mail.test.dummy_product_a'));
        $this->setEntityId($productA, 1);

        $pcA = new ProductClass();
        $pcA->setProduct($productA);
        $pcA->setStock(3);

        // ダミー商品B（規格あり）
        $productB = new Product();
        $productB->setName($this->translator->trans('stock_alert_mail.test.dummy_product_b'));
        $this->setEntityId($productB, 2);

        $cc1 = new ClassCategory();
        $cc1->setName($this->translator->trans('stock_alert_mail.test.dummy_class1'));
        $cc2 = new ClassCategory();
        $cc2->setName($this->translator->trans('stock_alert_mail.test.dummy_class2'));

        $pcB = new ProductClass();
        $pcB->setProduct($productB);
        $pcB->setClassCategory1($cc1);
        $pcB->setClassCategory2($cc2);
        $pcB->setStock(1);

        return [$pcA, $pcB];
    }

    /**
     * エンティティに仮IDを設定する（テスト用ダミーデータ向け）
     */
    private function setEntityId(object $entity, int $id): void
    {
        $ref = new \ReflectionProperty($entity, 'id');
        $ref->setAccessible(true);
        $ref->setValue($entity, $id);
    }

    private function findMailTemplate(): ?MailTemplate
    {
        return $this->mailTemplateRepository->findOneBy([
            'file_name' => PluginManager::MAIL_TEMPLATE_FILE_NAME,
        ]);
    }
}
