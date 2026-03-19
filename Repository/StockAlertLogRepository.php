<?php

namespace Plugin\StockAlertMail\Repository;

use Doctrine\Persistence\ManagerRegistry as RegistryInterface;
use Eccube\Repository\AbstractRepository;
use Plugin\StockAlertMail\Entity\StockAlertLog;

class StockAlertLogRepository extends AbstractRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, StockAlertLog::class);
    }
}
