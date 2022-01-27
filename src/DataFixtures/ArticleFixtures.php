<?php

namespace App\DataFixtures;

use Faker\Factory;
use App\Entity\Article;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\String\Slugger\SluggerInterface;

class ArticleFixtures extends Fixture
{
    private \Faker\Generator $faker;

    private ObjectManager $manager;

    private SluggerInterface $slugger;

    public function __construct(SluggerInterface $slugger)
    {
        $this->slugger = $slugger;
    }
    
    public function load(ObjectManager $manager): void
    {
        $this->manager = $manager;

        $this->faker = Factory::create();

        $this->generateArticles(7);
        
        $this->manager->flush();
    }

    private function generateArticles(int $number): void
    {
        for ($i = 1; $i < $number; $i++) {
            $article = new Article();

            [
                'dateObject' => $dateObject,
                'dateString' => $dateString
            
            ] = $this->generateRandomDateBetweenRange('01/01/2021', '01/01/2022');

            $title = $this->faker->sentence();

            $article->setTitle($title)
                    ->setContent($this->faker->paragraph())
                    ->setSlug(sprintf('%s-%s', $this->slugger->slug(strtolower($title)), $dateString))
                    ->setCreatedAt($dateObject)
                    // ->setIsPublished(false)
            ;
            $this->manager->persist($article);
        }
    }

    /**
     * Generate random DateTimeImmutable object and related date string
     * between a given start and end date
     * 
     * @param string $start Date with format 'd/m/Y'
     * @param string $end Date with format 'd/m/Y'
     * @return array{dateObject: \DateTimeImmutable, dateString: string} String with "d-m-Y"
     */
    private function generateRandomDateBetweenRange(string $start, string $end): array
    {
        $startDate = \Datetime::createFromFormat('d/m/Y', $start);
        $endDate = \Datetime::createFromFormat('d/m/Y', $end);

        if (!$startDate || !$endDate) {
            throw new HttpException(400, "La date saisie doit être sous le format 'd/m/Y' pour les deux dates.");
        }

        $randomTimestamp = mt_rand($startDate->getTimestamp(), $endDate->getTimestamp());
        $dateTimeImmutable = (new \DateTimeImmutable())->setTimestamp($randomTimestamp);

        return [
            'dateObject' => $dateTimeImmutable,
            'dateString' => $dateTimeImmutable->format('d-m-Y')
        ];
    }
}
