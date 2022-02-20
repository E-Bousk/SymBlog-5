<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(UserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        $user->setPassword($newHashedPassword);
        $this->_em->persist($user);
        $this->_em->flush();
    }

    public function getUserFromDiscordOauth(string $discordId, string $discordUsername, string $email): ?User
    {
        $user = $this->findOneBy([
            'email' => $email
        ]);

        if (!$user) {
            return null;
        }

        if ($user->getDiscorId() !== $discordId) {
            $user = $this->updateUserWithDiscordData($discordId, $discordUsername, $user);
        }

        return $user;
    }

    public function updateUserWithDiscordData(string $discordId, string $discordUsername, User $user): User
    {
        $user->setDiscorId($discordId)
            ->setDiscordUsername($discordUsername)
        ;

        $this->_em->flush();

        return $user;
    }

    public function createUserFromDiscordOauth(string $discordId, string $discordUsername, string $email, string $randomPassword): User
    {
        $user = (new User())->setDiscorId($discordId)
            ->setDiscordUsername($discordUsername)
            ->setEmail($email)
            ->setIsVerified(true)
            ->setPassword($randomPassword)
        ;

        $this->_em->persist($user);
        $this->_em->flush();

        return $user;
    }
}
