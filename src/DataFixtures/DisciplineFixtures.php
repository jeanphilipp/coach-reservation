<?php

namespace App\DataFixtures;

use App\Entity\Discipline;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class DisciplineFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        foreach (['Krav Maga', 'Boxing', 'MMA'] as $name) {
            $discipline = new Discipline();
            $discipline->setName($name);
            $manager->persist($discipline);
        }

        $manager->flush();
    }
}
