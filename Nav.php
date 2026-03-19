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
    public static function getNav(): array
    {
        return [
            'plugin' => [
                'id' => 'plugin',
                'name' => 'admin.nav.store',
                'icon' => 'fa-store',
                'children' => [
                    'stock_alert_config' => [
                        'id' => 'stock_alert_config',
                        'name' => 'stock_alert_mail.nav.config',
                        'url' => 'stock_alert_mail_admin_config',
                    ],
                ],
            ],
        ];
    }
}
