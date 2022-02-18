<?php

namespace App\EventListener;

use App\Entity\User;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserPasswordEncoderEntityListener
{
    private UserPasswordEncoderInterface $encoder;

    public function __construct(UserPasswordEncoderInterface $encoder)
    {
        $this->encoder = $encoder;
    }

    public function prePersist(User $user, LifecycleEventArgs $args):void
    {
        $this->encodeUserPassword($user, $user->getPassword());
    }

    public function preUpdate(User $user, LifecycleEventArgs $args):void
    {
        $userChanges = $args->getEntityChangeSet();

        if (array_key_exists('password', $userChanges)) {
            $this->encodeUserPassword($user, $userChanges['password'][1]);
        }
    }

    private function encodeUserPassword(User $user, string $password): void
    {

        $user->setPassword($this->encoder->encodePassword($user, $password));
    }
}
