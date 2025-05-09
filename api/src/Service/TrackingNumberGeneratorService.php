<?php
// src/Service/TrackingNumberGeneratorService.php

namespace App\Service;

use App\Entity\SequenceCounter;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\DBAL\LockMode;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class TrackingNumberGeneratorService
{
    private EntityManagerInterface $em;
    private $repo;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em   = $em;
        $this->repo = $em->getRepository(SequenceCounter::class);
    }

    /**
     * Holt die nächste Sendungsnummer (shipment).
     */
    public function getNextShipmentNumber(): int
    {
        return $this->getNext('shipment');
    }

    /**
     * Allgemeine Methode für Sequenzen.
     *
     * @param string $sequenceName Name der Sequenz (z.B. 'shipment')
     * @return int nächste fortlaufende Nummer
     * @throws ConflictHttpException bei Fehler
     */
    public function getNext(string $sequenceName): int
    {
        $conn = $this->em->getConnection();
        $conn->beginTransaction();
        try {
            // Row fürs Locken laden
            $counter = $this->repo->find($sequenceName);
            if (!$counter) {
                // Erstelle bei Erstaufruf mit Startwert 49100000000
                $counter = new SequenceCounter();
                $counter->setName($sequenceName);
                $counter->setLastValue(49100000000);
                $this->em->persist($counter);
                $this->em->flush();
            }

            // Sperre die Zeile für Update
            $this->em->lock($counter, LockMode::PESSIMISTIC_WRITE);

            // Inkrement
            $next = $counter->getLastValue() + 1;
            $counter->setLastValue($next);

            $this->em->persist($counter);
            $this->em->flush();
            $conn->commit();

            return $next;
        } catch (\Throwable $e) {
            $conn->rollBack();
            throw new ConflictHttpException('Fehler beim Generieren der nächsten Nummer.', $e);
        }
    }
}
