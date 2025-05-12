<?php
// src/Service/PartnerRoutingService.php

namespace App\Service;

use App\Entity\Partner;
use App\Entity\Shipment;
use App\Repository\PartnerRepository;
use App\Repository\PostalCodeRangeRepository;

/**
 * Zentrale Routing-Logik:
 *
 *  • {@see findDeliveryPartner()}  – ermittelt den Zustell-Partner für
 *    Land + PLZ, oder null falls keine Range greift.
 *  • {@see assignPartners()}       – schreibt booking / pickup / delivery
 *    Partner in eine Shipment-Instanz.
 *
 * Die eigentliche Range-Suche mit Prioritäten übernimmt
 * {@see PostalCodeRangeRepository::findMatchingPartner()}.
 */
class PartnerRoutingService
{
    public function __construct(
        private readonly PostalCodeRangeRepository $rangeRepo,
        private readonly PartnerRepository         $partnerRepo, // im Moment ungenutzt – für künftige Features
    ) {}

    /* ======================================================================
     * Öffentliche API
     * ==================================================================== */

    /**
     * Liefert den Partner, der eine bestimmte PLZ in einem Land bedient.
     * Gibt null zurück, wenn keine passende Range existiert.
     *
     * @param string $country ISO-3166-ALPHA-2 (z. B. "DE", "AT", "CH")
     * @param string $zip     (ggf. alphanumerische) Postleitzahl
     */
    public function findDeliveryPartner(string $country, string $zip): ?Partner
    {
        return $this->rangeRepo->findMatchingPartner($country, $zip);
    }

    /**
     * Befüllt alle drei Partner-Bezüge einer Shipment-Entität:
     *
     *  • bookingPartner   – Partner, der die Sendung bucht / bezahlt
     *  • pickupPartner    – zuständig für die Abhol-PLZ (Versender)
     *  • deliveryPartner  – zuständig für die Zustell-PLZ (Empfänger)
     *
     * Falls für pickup/delivery keine Range gefunden wird, bleiben die
     * Felder null. Der aufrufende Code entscheidet, ob das akzeptiert wird
     * (z. B. 422 „Unroutable“ werfen).
     */
    public function assignPartners(Shipment $shipment, Partner $creatingPartner): void
    {
        /* ---------- buchender / zahlender Partner (immer vorhanden) ------- */
        $shipment->setBookingPartner($creatingPartner);

        /* ---------- Abhol-Partner anhand Sender-Adresse ------------------- */
        $pickupPartner = $this->findDeliveryPartner(
            $shipment->getSenderCountry(),
            $shipment->getSenderPostalCode()
        );
        $shipment->setPickupPartner($pickupPartner);

        /* ---------- Zustell-Partner anhand Empfänger-Adresse -------------- */
        $deliveryPartner = $this->findDeliveryPartner(
            $shipment->getRecipientCountry(),
            $shipment->getRecipientPostalCode()
        );
        $shipment->setDeliveryPartner($deliveryPartner);
    }
}
