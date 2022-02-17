<?php

namespace App\Utils;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

trait LogoutUserTrait
{
    public function logoutUser(
        Request $request,
        Session $session,
        TokenStorageInterface $tokenStorage,
        string $flashLabel,
        string $flashMessage,
        bool $isPasswordConfirmed,
        bool $isUserDeauthenticated
    ): JsonResponse
    {
        $tokenStorage->setToken(null);

        $request->getSession()->invalidate();

        $session->getFlashBag()->add($flashLabel, $flashMessage);

        $response = new JsonResponse([
            'is_password_confirmed' => $isPasswordConfirmed,
            'is_deauthenticated' => $isUserDeauthenticated
        ], 320);

        $response->headers->clearCookie('REMEMBERME', '/', null, true, true, 'lax');

        return $response;
    }
}
