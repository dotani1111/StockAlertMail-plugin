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

namespace Plugin\StockAlertMail\Tests;

use Eccube\Tests\Web\Admin\AbstractAdminWebTestCase;
use Plugin\StockAlertMail\Entity\StockAlertLog;
use Plugin\StockAlertMail\Repository\StockAlertLogRepository;

class StockAlertLogControllerTest extends AbstractAdminWebTestCase
{
    /** @var StockAlertLogRepository */
    private $logRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->logRepository = $this->entityManager->getRepository(StockAlertLog::class);

        $this->entityManager->createQuery('DELETE FROM Plugin\StockAlertMail\Entity\StockAlertLog l')->execute();
    }

    protected function tearDown(): void
    {
        $this->entityManager->createQuery('DELETE FROM Plugin\StockAlertMail\Entity\StockAlertLog l')->execute();

        parent::tearDown();
    }

    /**
     * 送信履歴一覧ページが正常に表示されること。
     */
    public function testIndex()
    {
        $this->client->request('GET', $this->generateUrl('stock_alert_mail_admin_log'));

        $this->assertTrue($this->client->getResponse()->isSuccessful());
    }

    /**
     * ログが存在しない場合、空メッセージが表示されること。
     */
    public function testIndexShowsEmptyMessage()
    {
        $crawler = $this->client->request('GET', $this->generateUrl('stock_alert_mail_admin_log'));

        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $this->assertStringContainsString('送信履歴はありません', $crawler->filter('.card-body')->text());
    }

    /**
     * ログが存在する場合、商品名が一覧に表示されること。
     */
    public function testIndexShowsLogs()
    {
        $product = $this->createProduct();
        $productClass = $product->getProductClasses()->filter(
            fn ($pc) => !$pc->isStockUnlimited() && $pc->isVisible()
        )->first();

        if (!$productClass) {
            $this->markTestSkipped('対象のProductClassが存在しません。');
        }

        $log = new StockAlertLog();
        $log->setProductClass($productClass);
        $log->setAlertedAt(new \DateTime());
        $this->entityManager->persist($log);
        $this->entityManager->flush();

        $crawler = $this->client->request('GET', $this->generateUrl('stock_alert_mail_admin_log'));

        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $this->assertStringContainsString(
            $product->getName(),
            $crawler->filter('table')->text()
        );
    }
}
