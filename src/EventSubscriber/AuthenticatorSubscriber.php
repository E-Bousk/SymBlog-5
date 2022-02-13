<?php

namespace App\EventSubscriber;

use App\Entity\User;
use App\Repository\AuthLogRepository;
use App\Security\BruteForceChecker;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\SecurityEvents;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Http\Event\LogoutEvent;
use Symfony\Component\Security\Core\AuthenticationEvents;
use Symfony\Component\Security\Http\Event\SwitchUserEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\DeauthenticatedEvent;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Core\Event\AuthenticationFailureEvent;
use Symfony\Component\Security\Core\Event\AuthenticationSuccessEvent;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class AuthenticatorSubscriber implements EventSubscriberInterface
{
    // NOTE : voir « symfony console debug:autowiring log »
    // https://symfony.com/doc/current/logging/channels_handlers.html#monolog-autowire-channels
    private LoggerInterface $securityLogger;
    private RequestStack $requestStack;

    private AuthLogRepository $authLogRepository;
    private BruteForceChecker $bruteForceChecker;

    public function __construct(
        LoggerInterface $securityLogger,
        RequestStack $requestStack,
        AuthLogRepository $authLogRepository,
        BruteForceChecker $bruteForceChecker
    )
    {
        
        $this->securityLogger = $securityLogger;
        $this->requestStack = $requestStack;
        $this->authLogRepository = $authLogRepository;
        $this->bruteForceChecker = $bruteForceChecker;
    }

    /** @return array<string> */
    public static function getSubscribedEvents()
    {
        return [
            AuthenticationEvents::AUTHENTICATION_FAILURE        => 'onSecurityAuthenticationFailure',
            AuthenticationEvents::AUTHENTICATION_SUCCESS        => 'onSecurityAuthenticationSuccess',
            SecurityEvents::INTERACTIVE_LOGIN                   => 'onSecurityInteractiveLogin',
            SecurityEvents::SWITCH_USER                         => 'onSecuritySwitchUser',
            'Symfony\Component\Security\Http\Event\LogoutEvent' => 'onSecurityLogout',
            'security.logout_on_change'                         => 'onSecurityLogoutOnChange'

        ];
    }
    
    public function onSecurityAuthenticationFailure(AuthenticationFailureEvent $event): void
    {
        ['user_IP' => $userIp] = $this->getRouteNameAndUserIP();

        /** @var TokenInterface $securityToken */
        $securityToken = $event->getAuthenticationToken();

        ['email' => $emailEntered] = $securityToken->getCredentials();

        $this->securityLogger->info("Un utilisateur avec l'adresse IP '{$userIp}' a tenté de s'authentifier sans succès avec l'e-mail suivant : '{$emailEntered}'.");

        $this->bruteForceChecker->addFailedAuthAttempt($emailEntered, $userIp);
    }

    public function onSecurityAuthenticationSuccess(AuthenticationSuccessEvent $event): void
    {
       [
           'user_IP'    => $userIp,
           'route_name' => $routeName
       ] = $this->getRouteNameAndUserIP();

       if (empty($event->getAuthenticationToken()->getRoleNames())) {
           $this->securityLogger->info("Un utilisateur anonyme avec l'adresse IP '{$userIp}' vient de se connecter sur la route '{$routeName}'.");
       } else {
           /** @var TokenInterface $securityToken */
           $securityToken = $event->getAuthenticationToken();

           $userEmail = $this->getUserEmail($securityToken);

           $this->securityLogger->info("Un utilisateur anonyme avec l'adresse IP '{$userIp}' vient de se connecter en tant qu'utilisateur. Son e-mail est : '{$userEmail}'.");
       }
    }

    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event): void
    {
        ['user_IP' => $userIp] = $this->getRouteNameAndUserIP();

        /** @var TokenInterface $securityToken */
        $securityToken = $event->getAuthenticationToken();

        $userEmail = $this->getUserEmail($securityToken);

         $request = $this->requestStack->getCurrentRequest();

        if ($request && $request->cookies->get('REMEMBERME')) {
            $this->securityLogger->info("« REMEMBERME » cookie : Un utilisateur anonyme avec l'adresse IP '{$userIp}' vient de se connecter en tant qu'utilisateur. Son e-mail est : '{$userEmail}'.");
            $this->authLogRepository->addSuccessfulAuthAttempt($userEmail, $userIp, true);
        } else {
            $this->securityLogger->info("Un utilisateur anonyme avec l'adresse IP '{$userIp}' vient de se connecter en tant qu'utilisateur. Son e-mail est : '{$userEmail}'.");
            $this->authLogRepository->addSuccessfulAuthAttempt($userEmail, $userIp);
        }
    }

    public function onSecurityLogout(LogoutEvent $event): void
    {
        /** @var RedirectResponse|null $response */
        $response = $event->getResponse();

        /** @var TokenInterface|null $securityToken */
        $securityToken = $event->getToken();

        if (!$response || !$securityToken) {
            return;
        }

        ['user_IP' => $userIp] = $this->getRouteNameAndUserIP();

        $userEmail = $this->getUserEmail($securityToken);

        $targetUrl = $response->getTargetUrl();

        $this->securityLogger->info("L'utilisateur avec l'adresse IP '{$userIp}' et l'e-mail '{$userEmail}' s'est déconnecté et a été redirigé vers l'url suivante : '{$targetUrl}'.");
    }

    public function onSecurityLogoutOnChange(DeauthenticatedEvent $event): void
    {
        // TODO...
    }

    public function onSecuritySwitchUser(SwitchUserEvent $event): void
    {
        // TODO...
    }

    /**
     * Return the user's IP and the name of the route where the user arrived from.
     * 
     * @return array{user_IP: string|null, route_name: mixed}
     */
    private function getRouteNameAndUserIP(): array
    {
        $request = $this->requestStack->getCurrentRequest();

        if (!$request) {
            return [
                'user_IP'    => 'Inconnue',
                'route_name' => 'Inconnue'
            ];
        }

        return [
            'user_IP' => $request->getClientIp() ?? 'Inconnue',
            'route_name' => $request->attributes->get('_route')
        ];
    }

    private function getUserEmail(TokenInterface $securityToken): string
    {
        /** @var User $user */
        $user = $securityToken->getUser();

        return $user->getEmail();
    }
}
