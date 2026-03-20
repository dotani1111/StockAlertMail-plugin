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

namespace Plugin\StockAlertMail\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * 在庫アラートメール設定エンティティ
 *
 * @ORM\Table(name="plg_stock_alert_config")
 *
 * @ORM\Entity(repositoryClass="Plugin\StockAlertMail\Repository\StockAlertConfigRepository")
 */
class StockAlertConfig
{
    /**
     * @ORM\Column(name="id", type="integer", options={"unsigned":true})
     *
     * @ORM\Id
     *
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * 在庫アラート閾値（この数以下になったら通知）
     *
     * @ORM\Column(name="threshold", type="integer", options={"default":5})
     */
    private $threshold = 5;

    /**
     * 通知先メールアドレス（カンマ区切り、空の場合はBaseInfoのemail01を使用）
     *
     * @ORM\Column(name="alert_emails", type="string", length=1000, nullable=true)
     */
    private $alertEmails;

    /**
     * メール件名テンプレート（空の場合はデフォルトを使用）
     * 使用可能プレースホルダー: {shop_name}
     *
     * @ORM\Column(name="mail_subject", type="string", length=500, nullable=true)
     */
    private $mailSubject;

    /**
     * メール本文テンプレート（空の場合はデフォルトを使用）
     * 使用可能プレースホルダー: {shop_name}, {threshold}, {items}
     *
     * @ORM\Column(name="mail_body", type="text", nullable=true)
     */
    private $mailBody;

    /**
     * @ORM\Column(name="create_date", type="datetimetz")
     */
    private $createDate;

    /**
     * @ORM\Column(name="update_date", type="datetimetz")
     */
    private $updateDate;

    public function getId()
    {
        return $this->id;
    }

    public function getThreshold()
    {
        return $this->threshold;
    }

    public function setThreshold($threshold)
    {
        $this->threshold = $threshold;

        return $this;
    }

    public function getAlertEmails()
    {
        return $this->alertEmails;
    }

    public function setAlertEmails($alertEmails)
    {
        $this->alertEmails = $alertEmails;

        return $this;
    }

    public function getMailSubject()
    {
        return $this->mailSubject;
    }

    public function setMailSubject($mailSubject)
    {
        $this->mailSubject = $mailSubject;

        return $this;
    }

    public function getMailBody()
    {
        return $this->mailBody;
    }

    public function setMailBody($mailBody)
    {
        $this->mailBody = $mailBody;

        return $this;
    }

    public function getCreateDate()
    {
        return $this->createDate;
    }

    public function setCreateDate($createDate)
    {
        $this->createDate = $createDate;

        return $this;
    }

    public function getUpdateDate()
    {
        return $this->updateDate;
    }

    public function setUpdateDate($updateDate)
    {
        $this->updateDate = $updateDate;

        return $this;
    }
}
