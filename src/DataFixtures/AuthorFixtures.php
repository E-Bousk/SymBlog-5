<?php

namespace App\DataFixtures;

use App\Entity\Author;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class AuthorFixtures extends Fixture
{
    private ObjectManager $manager;

    public function load(ObjectManager $manager): void
    {
        $this->manager = $manager;

        $this->generateAuthors(2);

        $this->manager->flush();
    }

    private function generateAuthors(int $number): void
    {
        $faker= Factory::create('fr_FR');

        for ($i = 0; $i < $number; $i++) {
            $author = (new Author())->setName($faker->name);

            $this->addReference("author{$i}", $author);

            $this->manager->persist($author);
        }
    }
}
