<?php

namespace App\DataFixtures;

use App\Entity\StatusCode;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class StatusCodeFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $codes = [
            ['100', 'In Transit'],
            ['200', 'Delivered'],
            ['300', 'Return Initiated'],
            // … weitere Codes hier ergänzen …
        ];

        foreach ($codes as [$code, $description]) {
            $sc = new StatusCode();
            $sc->setCode($code);
            $sc->setDescription($description);
            $manager->persist($sc);
        }

        $manager->flush();
    }
}
