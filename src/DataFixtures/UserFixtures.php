<?php

namespace App\DataFixtures;

use Faker\Factory;
use App\Entity\User;
use App\Utils\DateTimeImmutableTrait;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserFixtures extends Fixture implements DependentFixtureInterface
{
    use DateTimeImmutableTrait;

    private ObjectManager $manager;

    private UserPasswordEncoderInterface $encoder;

    public function __construct(UserPasswordEncoderInterface $encoder)
    {
        $this->encoder = $encoder;
    }

    public function load(ObjectManager $manager): void
    {
        $this->manager = $manager;

        $this->generateUsers(5);
        $this->generateInactiveUsers(5);

        $this->manager->flush();
    }

    public function getDependencies()
    {
        return [
            AuthorFixtures::class,
        ];
    }

    private function generateUsers(int $number): void
    {
        $faker= Factory::create('fr_FR');

        $user = new User();
        $user->setEmail('admin@symfony.com')
            ->setPassword($this->encoder->encodePassword($user, 'root'))
            ->setRoles(['ROLE_ADMIN'])
            ->setAuthor($this->getReference("author0"))
            ->setIsVerified(true)
        ;
        $this->manager->persist($user);
        
        for ($i = 1; $i <= $number; $i++) {
            $user = new User();
            $user->setEmail($faker->freeEmail)
                ->setPassword($this->encoder->encodePassword($user, 'password'))
                ->setIsVerified($faker->boolean(50))
                ->setAuthor($this->getReference("author{$i}"))
            ;
            $this->manager->persist($user);
        }
    }

    private function generateInactiveUsers(int $number): void
    {
        $faker= Factory::create('fr_FR');
        
        for ($i = 0; $i < $number; $i++) {

            ['dateObject' => $randomDatetimeImmutable] = $this->generateRandomDateBetweenRange('01/01/2022', '15/02/2022');

            $user = new User();
            $user->setEmail($faker->freeEmail)
                ->setPassword($this->encoder->encodePassword($user, 'password'))
                ->setIsVerified(false)
                ->setAccountMustBeVerifiedBefore($randomDatetimeImmutable)
            ;
            $this->manager->persist($user);
        }
    }
}
