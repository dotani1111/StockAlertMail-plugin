<?php

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
                        'name' => '在庫アラートメール設定',
                        'url' => 'stock_alert_mail_admin_config',
                    ],
                ],
            ],
        ];
    }
}
