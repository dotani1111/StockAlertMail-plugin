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

use Eccube\Common\EccubeNav;

class Nav implements EccubeNav
{
    /**
     * @return array
     */
    public static function getNav(): array
    {
        return [
            'stock_alert_mail' => [
                'name' => 'stock_alert_mail.nav.title',
                'icon' => 'fa-bell',
                'children' => [
                    'stock_alert_mail_config' => [
                        'name' => 'stock_alert_mail.nav.config',
                        'url' => 'stock_alert_mail_admin_config',
                    ],
                    'stock_alert_mail_log' => [
                        'name' => 'stock_alert_mail.nav.log',
                        'url' => 'stock_alert_mail_admin_log',
                    ],
                ],
            ],
        ];
    }
}
