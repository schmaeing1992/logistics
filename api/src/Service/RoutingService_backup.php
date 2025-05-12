<?php
// src/Service/RoutingService.php

namespace App\Service;

use App\Entity\Partner;
use App\Repository\PostalCodeRangeRepository;
use App\Repository\PartnerRepository;

class RoutingService
{
    public function __construct(
        private readonly PostalCodeRangeRepository $rangeRepo,
        private readonly PartnerRepository         $partnerRepo  // bleibt fürs Caching o. Ä. erhalten
    ) {}

    /**
     * Liefert den Partner, der im Absender-PLZ-Gebiet abholt.
     *
     * @param string $postalCode  (ggf. alphanumerisch)
     * @param string $country     ISO-3166-ALPHA-2 (z.B. "DE")
     * @return Partner|null
     */
    public function resolvePickupPartner(string $postalCode, string $country = 'DE'): ?Partner
    {
        return $this->rangeRepo->findMatchingPartner($country, $postalCode);
    }

    /**
     * Liefert den Partner, der im Empfänger-PLZ-Gebiet zustellt.
     *
     * @param string $postalCode
     * @param string $country
     * @return Partner|null
     */
    public function resolveDeliveryPartner(string $postalCode, string $country = 'DE'): ?Partner
    {
        return $this->rangeRepo->findMatchingPartner($country, $postalCode);
    }
}
