<?php
// src/Repository/PackageRepository.php

namespace App\Repository;

use App\Entity\Package;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PackageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Package::class);
    }

    /**
     * Finde ein Package anhand seiner packageNumber (Business-Key).
     */
    public function findOneByPackageNumber(int $packageNumber): ?Package
    {
        return $this->findOneBy(['packageNumber' => $packageNumber]);
    }
}
