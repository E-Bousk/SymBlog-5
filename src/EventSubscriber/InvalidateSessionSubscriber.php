<?php

namespace App\EventSubscriber;

use App\Entity\User;
use App\Repository\AuthLogRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class InvalidateSessionSubscriber implements EventSubscriberInterface
{
    private AuthLogRepository $authLogRepository;
    private EntityManagerInterface $entityManager;
    private Security $security;
    private TokenStorageInterface $tokenStorage;
    private UrlGeneratorInterface $urlGenerator ;

    public function __construct(
        AuthLogRepository $authLogRepository,
        EntityManagerInterface $entityManager,
        Security $security,
        TokenStorageInterface $tokenStorage,
        UrlGeneratorInterface $urlGenerator        
    )
    {
        $this->authLogRepository = $authLogRepository;
        $this->entityManager = $entityManager;
        $this->security = $security;
        $this->tokenStorage = $tokenStorage;
        $this->urlGenerator = $urlGenerator;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest'
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if ($request->hasPreviousSession() === false) {
            return;
        }

        $user = $this->security->getUser();

        if ($user === null) {
            return;
        }

        $session = $request->getSession();

        $userCurrentSessionId = $session->getId();

        /** @var User $user */
        $userLastSuccessfulAuth = $this->authLogRepository->findOneBy(
            [
                'emailEntered'     => $user->getEmail(),
                'isSuccessfulAuth' => true
            ],
            [
                'id' => 'DESC'
            ]
        );

        if ($userLastSuccessfulAuth === null) {
            return;
        }

        $userCurrentAuth = $this->authLogRepository->findOneBy(
            [
                'sessionId' => $userCurrentSessionId
            ]
        );

        $userLastSessionId = $userLastSuccessfulAuth->getSessionId();

        if ($userLastSessionId === $userCurrentSessionId) {
            return;
        }

        $this->tokenStorage->setToken();

        $request->getSession()->invalidate();

        $userCurrentAuth->setDeauthenticatedAt(new \DateTimeImmutable());

        $this->entityManager->flush();

        /** @var Session $session */
        $session
            ->getFlashBag()
            ->add(
                'info',
                'Une nouvelle session avec votre compte utilisateur a été créé sur un autre navigateur, votre session est devenue invalide. Si vous n\'êtes pas l\'auteur de cette nouvelle session, pensez à modifier votre mot de passe.'
            )
        ;

        $loginRoute = $this->urlGenerator->generate(
            'app_login',
            [
                '_locale' => $request->getLocale()
            ]
        );

        $event->setResponse(
            new RedirectResponse($loginRoute)
        );
    }
}
