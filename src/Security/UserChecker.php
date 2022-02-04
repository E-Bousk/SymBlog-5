<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserCheckerInterface;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

    }

    public function checkPostAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }
        
        // ‼ NOTE ‼ Si cette exception était placée dans la méthode « checkPreAuth »,
        // Le message d'erreur serait affiché/vu même avec un mot de passe incorrect ‼
        if (!$user->getIsVerified()) {
            throw new CustomUserMessageAccountStatusException("Votre compte n'est pas actif, veuillez consulter vos e-mails pour l'activer avant le {$user->getAccountMustBeVerifiedBefore()->format('d/m/Y à H\hi')}.");
        }
    }
}