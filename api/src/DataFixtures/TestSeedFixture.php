<?php
namespace App\DataFixtures;

use App\Entity\ApiKey;
use App\Entity\Partner;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class TestSeedFixture extends Fixture
{
    public function load(ObjectManager $om): void
    {
        /* ----------  Partner ---------- */
        $partner = (new Partner())
            ->setStationNumber(100)
            ->setName('Seed-Partner Camel 24')
            ->setStreet('Am Spielmannsfalter')
            ->setHouseNumber('234')
            ->setPostalCode('41564')
            ->setCity('Kaarst')
            ->setCountry('DE')
            ->setPhone('02131-12345')
            ->setEmail('info@camel-24.de');

        /* ----------  API-Key ---------- */
        $rawToken = bin2hex(random_bytes(32));          // 64-stelliger Token
        $apiKey   = new ApiKey($rawToken, $partner);    // Konstruktor hasht selbst

        $om->persist($partner);
        $om->persist($apiKey);
        $om->flush();

        // hübsch ausgeben, damit du ihn gleich kopieren kannst
        echo PHP_EOL."Seed-API-Key (Station 100): ".$rawToken.PHP_EOL.PHP_EOL;
    }
}
