<?php
// src/Repository/PackageStatusRepository.php

namespace App\Repository;

use App\Entity\PackageStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PackageStatus>
 */
class PackageStatusRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PackageStatus::class);
    }

    // Hier kannst du später eigene Abfrage-Methoden hinzufügen, z.B.:
    // public function findByPackageId(string $packageId): array { ... }
}
