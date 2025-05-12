<?php
// src/Repository/PartnerRepository.php
namespace App\Repository;

use App\Entity\Partner;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PartnerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    { parent::__construct($registry, Partner::class); }

    /** Liefert Partner, dessen PLZ-Range die übergebene PLZ abdeckt */
    public function findOneByPostalCode(string $postalCode, string $country = 'DE'): ?Partner
    {
        return $this->createQueryBuilder('p')
            ->join('p.postalCodeRanges', 'r')
            ->andWhere(':pc BETWEEN r.postalCodeFrom AND r.postalCodeTo')
            ->andWhere('r.country = :c')
            ->setParameters(['pc' => $postalCode, 'c' => $country])
            ->setMaxResults(1)
            ->getQuery()->getOneOrNullResult();
    }
}
