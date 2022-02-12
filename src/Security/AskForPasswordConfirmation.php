<?php

namespace App\Security;

use LogicException;
use App\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;
use App\Event\AskForPasswordConfirmationEvents;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class AskForPasswordConfirmation
{
    /** @var Session<mixed> */
    private Session $session;
    private EventDispatcherInterface $eventDispatcher;
    private RequestStack $requestStack;
    private Security $security;
    private UserPasswordEncoderInterface $encoder;

    /** @param Session<mixed> $session */
    public function __construct(
        Session $session,
        EventDispatcherInterface $eventDispatcher,
        RequestStack $requestStack,
        Security $security,
        UserPasswordEncoderInterface $encoder
    )
    {
        $this->session = $session;
        $this->eventDispatcher = $eventDispatcher;
        $this->requestStack = $requestStack;
        $this->security = $security;
        $this->encoder = $encoder;
    }

    /**
     * Display password confirmation modal for sensitive operations
     * and check if password is valid.
     * 
     * @return void 
     */
    public function ask(): void
    {
        $request = $this->requestStack->getCurrentRequest();

        if (!$request) {
            throw new LogicException("An error occurs. You shouldn't see this message");
        }

        if (!$request->headers->get('Confirm-Identity-With-Password')) {
            $this->dispatchDisplayModalEvent();
        }

        $this->dispatchPasswordInvalidEventOrContinue($request);
    }

    /**
     * Dispatch event to trigger modal display
     * 
     * @return void 
     */
    private function dispatchDisplayModalEvent(): void
    {
        $this->eventDispatcher->dispatch(new AskForPasswordConfirmationEvents(), AskForPasswordConfirmationEvents::MODAL_DISPLAY);
    }

    /**
     * Dispatch password invalid event on invalid confirmation password.
     * Continue request on valid confirmation password.
     * 
     * @param Request $request
     * @return void 
     */
    private function dispatchPasswordInvalidEventOrContinue(Request $request): void
    {
        /** @var string $json */
        $json = $request->getContent();

        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        if (!array_key_exists('password', $data)) {
            throw new HttpException(400, "Password must be entered.");
        }

        $passwordEntered = $data['password'];

        /** @var User $user */
        $user = $this->security->getUser();

        if (!$this->encoder->isPasswordValid($user, $passwordEntered)) {
            $this->invalidateSessionOnThreeInvalidConfirmPassword();

            $this->eventDispatcher->dispatch(new AskForPasswordConfirmationEvents, AskForPasswordConfirmationEvents::PASSWORD_INVALID);
        }

        $this->session->remove('Password-Confirmation-Invalid');
    }

    /**
     * Invalidate user's session after 3 invalid passwords entered .
     * 
     * @return void 
     */
    private function invalidateSessionOnThreeInvalidConfirmPassword(): void
    {
        if (!$this->session->get('Password-Confirmation-Invalid')) {
            $this->session->set('Password-Confirmation-Invalid', 1);
        } else {
            $this->session->set('Password-Confirmation-Invalid', $this->session->get('Password-Confirmation-Invalid') + 1);

            if ($this->session->get('Password-Confirmation-Invalid') === 3) {
                $this->session->invalidate();

                $this->session->getFlashBag()->add('danger', 'Vous avez été déconnecté à la suite de trois tentatives infructueuses de saisie du mot de passe.');

                $this->eventDispatcher->dispatch(new AskForPasswordConfirmationEvents, AskForPasswordConfirmationEvents::SESSION_INVALIDATE);
            }
        }
    }
}