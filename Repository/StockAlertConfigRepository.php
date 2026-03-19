<?php

namespace Plugin\StockAlertMail\Repository;

use Doctrine\Persistence\ManagerRegistry as RegistryInterface;
use Eccube\Repository\AbstractRepository;
use Plugin\StockAlertMail\Entity\StockAlertConfig;

class StockAlertConfigRepository extends AbstractRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, StockAlertConfig::class);
    }
}
