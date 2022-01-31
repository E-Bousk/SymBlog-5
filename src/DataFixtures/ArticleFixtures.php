<?php

namespace App\DataFixtures;

use Faker\Factory;
use App\Entity\Article;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class ArticleFixtures extends Fixture implements DependentFixtureInterface
{
    private ObjectManager $manager;

    private SluggerInterface $slugger;
    private int $pictureCount;


    public function __construct(SluggerInterface $slugger, KernelInterface $kernel)
    {
        $this->slugger = $slugger;
        // Compte le nombre d'images dans le répertoire « to-upload »
        $this->pictureCount = count(glob("{$kernel->getProjectDir()}/public/to-upload/*.*"));
    }
    
    public function load(ObjectManager $manager): void
    {
        $this->manager = $manager;
        
        $this->generateArticles($this->pictureCount);
        
        $this->manager->flush();
    }

    public function getDependencies()
    {
        return [
            PictureFixtures::class,
            AuthorFixtures::class,
            CategoryFixtures::class
        ];
    }

    private function generateArticles(int $number): void
    {
        $faker= Factory::create('fr_FR');
        
        for ($i = 0; $i < $number; $i++) {
            $article = new Article();

            [
                'dateObject' => $dateObject,
                'dateString' => $dateString
            
            ] = $this->generateRandomDateBetweenRange('01/01/2021', '01/01/2022');

            $title = $faker->sentence;
            $picture = $this->getReference("picture{$i}");

            $article->setTitle($title)
                    ->setContent($faker->paragraph)
                    ->setSlug(sprintf('%s-%s', $this->slugger->slug(strtolower($title)), $dateString))
                    ->setCreatedAt($dateObject)
                    // ->setIsPublished(false)
                    ->setAuthor($this->getReference("author" . mt_rand(0, 1)))
                    ->addCategory($this->getReference("category" . mt_rand(1, 3)))
                    ->setPicture($picture)
            ;
            $this->manager->persist($article);

            $picture->setArticle($article);
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
