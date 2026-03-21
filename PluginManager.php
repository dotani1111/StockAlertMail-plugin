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

namespace Plugin\StockAlertMail;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Common\EccubeConfig;
use Eccube\Entity\MailTemplate;
use Eccube\Plugin\AbstractPluginManager;
use Plugin\StockAlertMail\Entity\StockAlertConfig;
use Psr\Container\ContainerInterface;

class PluginManager extends AbstractPluginManager
{
    /** dtb_mail_template に登録するファイル名 */
    public const MAIL_TEMPLATE_FILE_NAME = 'Mail/stock_alert.twig';

    /** プラグインのデフォルトメール件名（送信時に "[ショップ名] " が先頭に付く） */
    private const MAIL_SUBJECT = '在庫アラート通知';

    public function enable(array $meta, ContainerInterface $container): void
    {
        $entityManager = $container->get('doctrine')->getManager();
        $this->createInitialConfig($entityManager);
        $this->createMailTemplate($entityManager, $container);
    }

    public function uninstall(array $meta, ContainerInterface $container): void
    {
        $entityManager = $container->get('doctrine')->getManager();
        $this->deleteMailTemplate($entityManager, $container);

        $conn = $entityManager->getConnection();
        $schemaManager = $conn->createSchemaManager();

        // FK参照元のlogテーブルを先に削除する
        foreach (['plg_stock_alert_log', 'plg_stock_alert_config'] as $table) {
            if ($schemaManager->tablesExist([$table])) {
                $quotedTable = $conn->quoteIdentifier($table);
                $conn->executeStatement("DROP TABLE {$quotedTable}");
            }
        }
    }

    private function createInitialConfig(EntityManagerInterface $entityManager): void
    {
        $repository = $entityManager->getRepository(StockAlertConfig::class);

        if ($repository->findOneBy([]) !== null) {
            return;
        }

        $config = new StockAlertConfig();
        $config->setThreshold(5);
        $config->setCreateDate(new \DateTime());
        $config->setUpdateDate(new \DateTime());

        $entityManager->persist($config);
        $entityManager->flush();
    }

    private function createMailTemplate(EntityManagerInterface $entityManager, ContainerInterface $container): void
    {
        $repository = $entityManager->getRepository(MailTemplate::class);

        // 既に登録済みの場合はスキップ
        if ($repository->findOneBy(['file_name' => self::MAIL_TEMPLATE_FILE_NAME]) !== null) {
            return;
        }

        // プラグインのデフォルトテンプレートを app/template/default/Mail/ にコピー
        $this->copyTwigTemplate($container);

        $mailTemplate = new MailTemplate();
        $mailTemplate->setName('在庫アラートメール');
        $mailTemplate->setFileName(self::MAIL_TEMPLATE_FILE_NAME);
        $mailTemplate->setMailSubject(self::MAIL_SUBJECT);
        $mailTemplate->setCreateDate(new \DateTime());
        $mailTemplate->setUpdateDate(new \DateTime());

        $entityManager->persist($mailTemplate);
        $entityManager->flush();
    }

    private function deleteMailTemplate(EntityManagerInterface $entityManager, ContainerInterface $container): void
    {
        $repository = $entityManager->getRepository(MailTemplate::class);
        $mailTemplate = $repository->findOneBy(['file_name' => self::MAIL_TEMPLATE_FILE_NAME]);

        if ($mailTemplate !== null) {
            $entityManager->remove($mailTemplate);
            $entityManager->flush();
        }

        $this->removeTwigTemplate($container);
    }

    private function copyTwigTemplate(ContainerInterface $container): void
    {
        $targetPath = $this->getTwigTargetPath($container);

        if (file_exists($targetPath)) {
            return;
        }

        $sourcePath = __DIR__.'/Resource/template/Mail/stock_alert.twig';

        if (!file_exists($sourcePath)) {
            return;
        }

        $targetDir = dirname($targetPath);
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        copy($sourcePath, $targetPath);
    }

    private function removeTwigTemplate(ContainerInterface $container): void
    {
        $targetPath = $this->getTwigTargetPath($container);

        if (file_exists($targetPath)) {
            unlink($targetPath);
        }
    }

    private function getTwigTargetPath(ContainerInterface $container): string
    {
        /** @var EccubeConfig $eccubeConfig */
        $eccubeConfig = $container->get(EccubeConfig::class);

        return $eccubeConfig['eccube_theme_front_dir'].'/'.self::MAIL_TEMPLATE_FILE_NAME;
    }
}
