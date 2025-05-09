<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // hier Deine anderen Fixtures (z.B. Nutzer, Demo-Daten, o.ä.)
    }
}
