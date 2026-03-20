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

namespace Plugin\StockAlertMail\Controller\Admin;

use Eccube\Controller\AbstractController;
use Plugin\StockAlertMail\Repository\StockAlertLogRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Routing\Annotation\Route;

class StockAlertLogController extends AbstractController
{
    public function __construct(
        private readonly StockAlertLogRepository $logRepository,
    ) {
    }

    /**
     * @Route("/%eccube_admin_route%/plugin/stock-alert/log", name="stock_alert_mail_admin_log", methods={"GET"})
     *
     * @Template("@StockAlertMail/admin/log.twig")
     */
    public function index(): array
    {
        $logs = $this->logRepository->findBy([], ['alertedAt' => 'DESC']);

        return [
            'logs' => $logs,
        ];
    }
}
