<?php
namespace App\Service;

use App\Entity\Partner;
use App\Repository\PostalCodeRangeRepository;

class ZipPartnerResolver
{
    public function __construct(private PostalCodeRangeRepository $repo) {}

    public function findPartner(string $country, string $zip): ?Partner
    {
        $zip = str_pad($zip, 10, '0', STR_PAD_RIGHT); // alphanum gleich lang
        $qb = $this->repo->createQueryBuilder('r')
            ->join('r.partner', 'p')
            ->where('r.country = :c')
            ->andWhere(':z BETWEEN r.zipFrom AND r.zipTo')
            ->orderBy('r.order', 'ASC')
            ->setMaxResults(1)
            ->setParameter('c', strtoupper($country))
            ->setParameter('z', $zip);

        /** @var ?\App\Entity\PostalCodeRange $range */
        $range = $qb->getQuery()->getOneOrNullResult();
        return $range?->getPartner();
    }
}
