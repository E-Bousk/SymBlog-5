<?php

namespace App\DataFixtures;

use App\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CategoryFixtures extends Fixture
{
    private ObjectManager $manager;

    public function load(ObjectManager $manager): void
    {
        $this->manager = $manager;

        $this->generateCategories(3);

        $this->manager->flush();
    }

    private function generateCategories(int $number): void
    {
        for ($i = 1; $i <= $number; $i++) {
            $category = (new Category())->setName("Catégorie {$i}");

            $this->addReference("category{$i}", $category);

            $this->manager->persist($category);
        }
    }
}
