<?php

namespace App\EventSubscriber;

use App\Utils\LogoutUserTrait;
use App\Event\AskForPasswordConfirmationEvents;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class AskForPasswordConfirmationSubscriber implements EventSubscriberInterface
{
    use LogoutUserTrait;

    private RequestStack $requetStack;
    private Session $session;
    private TokenStorageInterface $tokenStorage;

    public function __construct(
        RequestStack $requetStack,
        Session $session, 
        TokenStorageInterface $tokenStorage
        )
    {
        $this->requetStack = $requetStack;
        $this->session = $session;
        $this->tokenStorage = $tokenStorage;
    }

    /** @return array<string> */
    public static function getSubscribedEvents(): array
    {
        return [
            AskForPasswordConfirmationEvents::MODAL_DISPLAY      => 'onModalDisplay',
            AskForPasswordConfirmationEvents::PASSWORD_INVALID   => 'onPasswordInvalid',
            AskForPasswordConfirmationEvents::SESSION_INVALIDATE => 'onSessionInvalidate'
        ];
    }

    public function onModalDisplay(AskForPasswordConfirmationEvents $event): void
    {
        $this->sendJsonResponse();
    }

    public function onPasswordInvalid(AskForPasswordConfirmationEvents $event): void
    {
        $this->sendJsonResponse();
    }

    public function onSessionInvalidate(AskForPasswordConfirmationEvents $event): void
    {
        $this->sendJsonResponse(true);
    }

    private function sendJsonResponse(bool $isUserDeauthenticated = false): void
    {
        if ($isUserDeauthenticated) {
            $request = $this->requetStack->getCurrentRequest();

            if (!$request) {
                return;
            }

            $response = $this->logoutUser(
                $request,
                $this->session,
                $this->tokenStorage,
                'danger',
                'Vous avez été déconnecté par mesure de sécurité car 3 mots de passe invalides ont été saisis lors de la confirmation de mot de passe.',
                false,
                true
            );

            $response->send();

             // ‼ Empêche l'exécution du code qui suit l'appel de cette méthode (dans la classe appellante) ‼
            exit();
        }

        $response = new JsonResponse([
            'is_password_confirmed' => false
        ]);

        $response->send();

         // ‼ Empêche l'exécution du code qui suit l'appel de cette méthode (dans la classe appellante) ‼
        exit();
    }
}