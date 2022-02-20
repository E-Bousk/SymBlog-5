<?php

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\PasswordGenerator;
use Symfony\Component\HttpFoundation\Response;
use App\Event\UserCreatedFromDiscordOauthEvent;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

class DiscordUserProvider implements UserProviderInterface
{
    private const DISCORD_ACCESS_TOKEN_ENDPOINT ='https://discord.com/api/oauth2/token';
    private const DISCORD_USER_DATA_ENDPOINT = 'https://discord.com/api/users/@me';

    private EventDispatcherInterface $eventDispatcher;
    private HttpClientInterface $httpClient;
    private PasswordGenerator $passwordGenerator;
    private UrlGeneratorInterface $urlGenerator;
    private UserRepository $userRepository;
    private string $discordClientId;
    private string $discordClientSecret;

    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        HttpClientInterface $httpClient,
        PasswordGenerator $passwordGenerator,
        UrlGeneratorInterface $urlGenerator,
        UserRepository $userRepository,
        string $discordClientId,
        string $discordClientSecret
    )
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->httpClient = $httpClient;
        $this->passwordGenerator = $passwordGenerator;
        $this->urlGenerator = $urlGenerator;
        $this->userRepository = $userRepository;
        $this->discordClientId = $discordClientId;
        $this->discordClientSecret = $discordClientSecret;
    }

    public function loadUserFromDiscordOauth(string $code): User
    {
        $accessToken = $this->getAccessToken($code);

        $discordUserData = $this->getUserInformations($accessToken);

        [
            'email'     => $email,
            'id'       => $discordId,
            'username' => $discordUserName
        ] = $discordUserData;

        $user = $this->userRepository->getUserFromDiscordOauth($discordId, $discordUserName, $email);

        if (!$user) {
            $randomPassord = $this->passwordGenerator->generateRandomStrongPassword(20);

            $user = $this->userRepository->createUserFromDiscordOauth($discordId, $discordUserName, $email, $randomPassord);

            $this->eventDispatcher->dispatch(new UserCreatedFromDiscordOauthEvent($email, $randomPassord), UserCreatedFromDiscordOauthEvent::SEND_EMAIL_WITH_PASSWORD);
        }

        return $user;
    }

    public function loadUserByUsername(string $discordId): User
    {
        $user = $this->userRepository->findOneBy([
            'discordId' => $discordId
        ]);

        if (!$user) {
            throw new UsernameNotFoundException('Utilisateur inexistant.');
        }

        return $user;
    }

    public function refreshUser(UserInterface $user): User
    {
        if (!$user instanceof User || !$user->getDiscorId()) {
            throw new UnsupportedUserException();
        }

        $discordId = $user->getDiscorId();

        return $this->loadUserByUsername($discordId);
    }

    public function supportsClass(string $class): bool
    {
        return User::class === $class;
    }

    public function getAccessToken(string $code): string
    {
        $redirectUrl = $this->urlGenerator->generate('app_login', [
            'discord-oauth-provider' => true
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $options = [
            'headers' => [
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/x-www-form-urlencoded'
            ],
            'body'    => [
                'client_id'     => $this->discordClientId,
                'client_secret' => $this->discordClientSecret,
                'code'          => $code,
                'grant_type'    => 'authorization_code',
                'redirect_uri'  => $redirectUrl,
                'scope'         => 'identify email'
            ]
        ];

        $response = $this->httpClient->request('POST', self::DISCORD_ACCESS_TOKEN_ENDPOINT, $options);

        $data = $response->toArray();

        if (!$data['access_token']) {
            throw new ServiceUnavailableHttpException(null, "L'authentification via Discord a échoué, veuillez réessayer.");
        }

        return $data['access_token'];
    }

    public function getUserInformations(string $accessToken): array
    {
        $options = [
            'headers' => [
                'Accept'        => 'application/json',
                'Authorization' => "Bearer {$accessToken}"
            ]
        ];

        $response = $this->httpClient->request('GET', self::DISCORD_USER_DATA_ENDPOINT, $options);

        $data = $response->toArray();

        if (!$data['email'] || !$data['id'] || !$data['username']) {
            throw new ServiceUnavailableHttpException(null, "L' API de Discord semble avoir un problème, ou la structure de la reponse a été modifié, ce qui remet en cause le code");
        } else if (!$data['verified']) {
            throw new HttpException(Response::HTTP_UNAUTHORIZED, "Le compte utilisateur Discord n'est pas vérifié.");
        }

        return $data;
    }
}