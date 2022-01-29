<?php

namespace App\DataFixtures;

use App\Entity\User;
use Faker\Factory;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserFixtures extends Fixture
{
    private ObjectManager $manager;

    private UserPasswordEncoderInterface $encoder;

    public function __construct(UserPasswordEncoderInterface $encoder)
    {
        $this->encoder = $encoder;
    }

    public function load(ObjectManager $manager): void
    {
        $this->manager = $manager;

        $this->generateUsers(2);

        $this->manager->flush();
    }

    private function generateUsers(int $number): void
    {
        $faker= Factory::create('fr_FR');

        $user = new User();
        $user->setEmail('admin@symfony.com')
            ->setPassword($this->encoder->encodePassword($user, 'root'))
            ->setRoles(['ROLE_ADMIN'])
        ;
        $this->manager->persist($user);
        
        for ($i = 0; $i < $number; $i++) {
            $user = new User();
            $user->setEmail($faker->freeEmail)
                ->setPassword($this->encoder->encodePassword($user, 'password'))
            ;
            $this->manager->persist($user);
        }
    }
}
