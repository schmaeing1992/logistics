<?php
// src/Repository/PostalCodeRangeRepository.php

namespace App\Repository;

use App\Entity\Partner;
use App\Entity\PostalCodeRange;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository für {@see PostalCodeRange}.
 *
 * Findet den Partner, der eine bestimmte Postleitzahl in einem Land bedient.
 * Bei sich überlappenden Bereichen entscheidet das Feld <code>sortOrder</code>
 * (kleinere Zahl ⇒ höhere Priorität).
 */
final class PostalCodeRangeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PostalCodeRange::class);
    }

    /**
     * Liefert den Partner für <code>$zip</code> in <code>$country</code> oder
     * <code>null</code>, falls keine passende Range gefunden wird.
     */
    public function findMatchingPartner(string $country, string $zip): ?Partner
    {
        /** @var PostalCodeRange|null $range */
        $range = $this->createQueryBuilder('r')
            ->join('r.partner', 'p')
            ->andWhere('r.country = :country')
            ->andWhere(':zip BETWEEN r.zipFrom AND r.zipTo')
            ->setParameter('country', strtoupper($country))
            ->setParameter('zip', $zip)
            ->orderBy('r.sortOrder', 'ASC')   // <-- korrektes Feld
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $range?->getPartner();
    }
}
