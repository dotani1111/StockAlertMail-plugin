<?php

namespace Plugin\StockAlertMail\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\ProductClass;

/**
 * 在庫アラート送信済みログ
 *
 * @ORM\Table(name="plg_stock_alert_log")
 * @ORM\Entity(repositoryClass="Plugin\StockAlertMail\Repository\StockAlertLogRepository")
 */
class StockAlertLog
{
    /**
     * @ORM\Column(name="id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\ProductClass")
     * @ORM\JoinColumn(name="product_class_id", referencedColumnName="id", nullable=false)
     */
    private $ProductClass;

    /**
     * @ORM\Column(name="alerted_at", type="datetimetz")
     */
    private $alertedAt;

    public function getId()
    {
        return $this->id;
    }

    public function getProductClass()
    {
        return $this->ProductClass;
    }

    public function setProductClass(ProductClass $productClass)
    {
        $this->ProductClass = $productClass;
        return $this;
    }

    public function getAlertedAt()
    {
        return $this->alertedAt;
    }

    public function setAlertedAt($alertedAt)
    {
        $this->alertedAt = $alertedAt;
        return $this;
    }
}
