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
use Eccube\Plugin\AbstractPluginManager;
use Plugin\StockAlertMail\Entity\StockAlertConfig;
use Psr\Container\ContainerInterface;

class PluginManager extends AbstractPluginManager
{
    public function enable(array $meta, ContainerInterface $container): void
    {
        $entityManager = $container->get('doctrine')->getManager();
        $this->createInitialConfig($entityManager);
    }

    public function uninstall(array $meta, ContainerInterface $container): void
    {
        // テーブルはマイグレーションで管理するため、ここでは何もしない
    }

    private function createInitialConfig(EntityManagerInterface $entityManager): void
    {
        $repository = $entityManager->getRepository(StockAlertConfig::class);

        // 既に設定が存在する場合はスキップ
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
}
