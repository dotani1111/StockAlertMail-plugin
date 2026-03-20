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
use Eccube\Entity\Product;
use Eccube\Entity\ProductClass;
use Eccube\Repository\BaseInfoRepository;
use Plugin\StockAlertMail\Entity\StockAlertConfig;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class StockAlertMailBuilder
{
    private BaseInfo $BaseInfo;

    public function __construct(
        private readonly BaseInfoRepository $baseInfoRepository,
        private readonly TranslatorInterface $translator,
        private readonly Environment $twig,
    ) {
        $this->BaseInfo = $this->baseInfoRepository->get();
    }

    public function getBaseInfo(): BaseInfo
    {
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

        return [$this->BaseInfo->getEmail01()];
    }

    public function buildMailSubject(StockAlertConfig $config): string
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

    public function buildMailBody(StockAlertConfig $config, array $items, int $threshold): string
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

        $pcA = new ProductClass();
        $pcA->setProduct($productA);
        $pcA->setStock(3);

        // ダミー商品B（規格あり）
        $productB = new Product();
        $productB->setName($this->translator->trans('stock_alert_mail.test.dummy_product_b'));

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
}
