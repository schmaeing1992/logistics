<?php
// src/Repository/SequenceCounterRepository.php

namespace App\Repository;

use App\Entity\SequenceCounter;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SequenceCounter>
 */
class SequenceCounterRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SequenceCounter::class);
    }

    // Hier kannst du eigene Methoden ergänzen, z.B.:
    // public function findNext(string $name): ?SequenceCounter { … }
}
