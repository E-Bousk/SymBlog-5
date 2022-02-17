<?php

namespace App\DataFixtures;

use Faker\Factory;
use App\Entity\Category;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;

class CategoryFixtures extends Fixture
{
    private ObjectManager $manager;

    public function load(ObjectManager $manager): void
    {
        $this->manager = $manager;

        $this->generateCategories(5);

        $this->manager->flush();
    }

    private function generateCategories(int $number): void
    {
        $faker= Factory::create('fr_FR');
        
        for ($i = 1; $i <= $number; $i++) {
            $category = (new Category())->setName($faker->word);

            $this->addReference("category{$i}", $category);

            $this->manager->persist($category);
        }
    }
}
