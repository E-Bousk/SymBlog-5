<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class OAuthController extends AbstractController
{
    private const DISCORD_ENDPOINT ='https://discord.com/api/oauth2/authorize';

    /**
     * @Route("/oauth/discord", name="app_oauth_discord", methods={"GET"})
     */
    public function loginWithDiscord(
        CsrfTokenManagerInterface $csrfTokenManager,
        UrlGeneratorInterface $urlGenerator
    ): RedirectResponse
    {
        // NOTE: URI configurée dans Discord ('Redirects') : « https://127.0.0.1:8000/login?discord-oauth-provider=1 »

        $redirectUrl = $urlGenerator->generate('app_login', [
            'discord-oauth-provider' => true
        ], UrlGeneratorInterface::ABSOLUTE_URL);
        
        $queryParams = http_build_query([
            'client_id'     => $this->getParameter('app.discord_client_id'),
            'prompt'        => 'consent',
            'redirect_url'  => $redirectUrl,
            'response_type' => 'code',
            'scope'         => 'identify email',
            'state'         => $csrfTokenManager->getToken('discord-Sym-Oauth')->getValue()
        ]);

        // return new RedirectResponse(self::DISCORD_ENDPOINT . '?' . $queryParams);
        return new RedirectResponse(sprintf('%s?%s', self::DISCORD_ENDPOINT, $queryParams));
    }
}
