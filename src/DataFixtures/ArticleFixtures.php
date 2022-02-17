<?php

namespace App\DataFixtures;

use Faker\Factory;
use App\Entity\Article;
use App\Utils\DateTimeImmutableTrait;
use Symfony\Component\Finder\Finder;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\HttpKernel\KernelInterface;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Symfony\Component\String\Slugger\SluggerInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class ArticleFixtures extends Fixture implements DependentFixtureInterface
{
    use DateTimeImmutableTrait;

    private ObjectManager $manager;
    private KernelInterface $kernel;
    private SluggerInterface $slugger;


    public function __construct(SluggerInterface $slugger, KernelInterface $kernel)
    {
        $this->slugger = $slugger;
        $this->kernel = $kernel;
    }
    
    public function load(ObjectManager $manager): void
    {
        $this->manager = $manager;
        
        // Compte le nombre d'images dans le répertoire « to-upload »
        $pictureCount = iterator_count((new Finder)->files()->in("{$this->kernel->getProjectDir()}/public/to-upload/"));

        $this->generateArticles($pictureCount);
        
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
                    ->setAuthor($this->getReference("author" . mt_rand(0, 5)))
                    ->addCategory($this->getReference("category" . mt_rand(1, 5)))
                    ->setPicture($picture)
            ;
            $this->manager->persist($article);

            $picture->setArticle($article);
        }
    }
}
