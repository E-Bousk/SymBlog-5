<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;

class UserChecker implements UserCheckerInterface
{
    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }



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

        // ‼ NOTE ‼ Idem
        if ($user->getIsGuardCheckIp() && !$this->UserIpIsInWhitelist($user)) {
            throw new CustomUserMessageAccountStatusException("Vous n'êtes pas autorisé à vous authentifier avec cette adresse IP car elle ne figure pas dans la liste blanche des adresses IP autorisées !");
        }
    }

    private function UserIpIsInWhitelist(User $user): bool
    {
        $request = $this->requestStack->getCurrentRequest();

        if (!$request) {
            return false;
        }

        $userIp = $request->getClientIp();

        $userWhitelistIp = $user->getWhitelistedIpAddresses();

        return in_array($userIp, $userWhitelistIp, true);
    }
}