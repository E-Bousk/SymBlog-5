<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class DiscordAuthenticator extends AbstractGuardAuthenticator
{
    private CsrfTokenManagerInterface $CsrfTokenManager;
    private UrlGeneratorInterface $urlGenerator;
    private DiscordUserProvider $discordUserProvider;

    public function __construct(CsrfTokenManagerInterface $CsrfTokenManager, UrlGeneratorInterface $urlGenerator,DiscordUserProvider $discordUserProvider)
    {
        $this->urlGenerator = $urlGenerator;
        $this->CsrfTokenManager = $CsrfTokenManager;
        $this->discordUserProvider = $discordUserProvider;
    }

    public function supports(Request $request): bool
    {
        return $request->query->has('discord-oauth-provider');
    }

    public function getCredentials(Request $request): array
    {
        $state = $request->query->get('state');

        if (!$state || !$this->CsrfTokenManager->isTokenValid(new CsrfToken('discord-Sym-Oauth', $state))) {
            throw new AccessDeniedException('No way baby !...');
        }

        return [
            'code' => $request->query->get('code')
        ];
    }

    public function getUser($credentials, UserProviderInterface $userProvider): ?User
    {
        if ($credentials === null) {
            return null;
        }
       
        return $this->discordUserProvider->loadUserFromDiscordOauth($credentials['code']);
    }

    public function checkCredentials($credentials, UserInterface $user): bool
    {
       return true;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): JsonResponse
    {
        return new JsonResponse([
            'message' => 'Authentification refusée'
        ], Response::HTTP_UNAUTHORIZED);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $providerKey): RedirectResponse
    {
        return new RedirectResponse($this->urlGenerator->generate('app_user_account_profile_home'));
    }

    public function start(Request $request, AuthenticationException $authException = null): JsonResponse
    {
        return new JsonResponse([
            'message' => 'Authentification requise'
        ], Response::HTTP_UNAUTHORIZED);
    }

    public function supportsRememberMe(): bool
    {
        return false;
    }
}
