<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\SendEmail;
use App\Form\ResetPasswordType;
use App\Form\ForgotPasswordType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;

class ForgotPasswordController extends AbstractController
{
    private EntityManagerInterface $em;
    private SessionInterface $session;
    private UserRepository $userRepository;

    public function __construct(EntityManagerInterface $em, SessionInterface $session, UserRepository $userRepository)
    {
        $this->em = $em;
        $this->session = $session;
        $this->userRepository = $userRepository;
    }

    /**
     * @Route("/forgot-password", name="app_forgot_password", methods={"GET", "POST"}, defaults={"_public_access": true})
     */
    public function sendRecoveryLink(Request $request, SendEmail $sendEmail, TokenGeneratorInterface $tokenGenerator): Response
    {
        $form = $this->createForm(ForgotPasswordType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->userRepository->findOneBy([
                'email' => $form['email']->getData()
            ]);

            // Crée un leurre
            if (!$user) {
                $this->addFlash('success', 'Un email vous a été envoyé pour redéfinir votre mot de passe.');
                return $this->redirectToRoute('app_login');
            }

            $user->setForgotPasswordToken($tokenGenerator->generateToken())
                ->setForgotPasswordTokenRequestedAt(new \DateTimeImmutable())
                ->setForgotPasswordTokenMustBeVerifiedBefore(new \DateTimeImmutable('+15 minutes'))
            ;

            $this->em->flush();

            $sendEmail->send([
                'recipient_email' => $user->getEmail(),
                'subject'         => 'Modification de votre mot de passe',
                'html_template'   => 'forgot_password/forgot_password_email.html.twig',
                'context'         => [
                    'user' => $user
                ]
            ]);

            $this->addFlash('success', 'Un email vous a été envoyé pour redéfinir votre mot de passe.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('forgot_password/forgot_password_step_1.html.twig', [
            'forgotPasswordFormStep1' => $form->createView()
        ]);
    }

    /**
     * @Route("/forgot-password/{id<\d+>}/{token}", name="app_retrieve_credentials", methods={"GET"})
     */
    public function retrieveCredentialsFromTheUrl(string $token, User $user): RedirectResponse
    {
        $this->session->set('Reset-password-Token-URL', $token);
        $this->session->set('Reset-password-User-Email', $user->getEmail());

        return $this->redirectToRoute('app_reset_password');
    }

    /**
     * @Route("/reset-password", name="app_reset_password", methods={"GET", "POST"})
     */
    public function resetPassword(Request $request): Response
    {
        [
            'token' => $token,
            'userEmail' => $userEmail
        ] = $this->getCredentialsFromSession();

        $user = $this->userRepository->findOneBy([
            'email' => $userEmail
        ]);

        if (!$user) {
            return $this->redirectToRoute('app_forgot_password');
        }

        /** @var \DateTimeImmutable $forgotPasswordTokenMustBeVerifiedBefore */
        $forgotPasswordTokenMustBeVerifiedBefore = $user->getForgotPasswordTokenMustBeVerifiedBefore();

        if (($user->getForgotPasswordToken() === null) || ($user->getForgotPasswordToken() !== $token) || ($this->isNotRequestedOnTime($forgotPasswordTokenMustBeVerifiedBefore))) {
            return $this->redirectToRoute('app_forgot_password');
        }

        $form = $this->createForm(ResetPasswordType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setForgotPasswordToken(null)
                ->setForgotPasswordTokenVerifiedAt(new \DateTimeImmutable())
            ;

            $this->em->flush();

            $this->removeCredentialsFromSession();

            $this->addFlash('success', 'Votre mot de passe a été modifié. Vous pouvez à présent vous connceter.');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('forgot_password/forgot_password_step_2.html.twig', [
            'forgotPasswordFormStep2' => $form->createView(),
            'passwordMustBeModifiedBefore' => $this->passwordMustBeModifiedBefore($user)
        ]);
    }

    /**
     * Get user ID and token from session.
     * 
     * @return array 
     */
    private function getCredentialsFromSession(): array
    {
        return [
            'token'     => $this->session->get('Reset-password-Token-URL'),
            'userEmail' => $this->session->get('Reset-password-User-Email')
        ];
    }
    
    /**
     * Check if allotted time is over or not.
     * 
     * @param \DateTimeImmutable $forgotPasswordTokenMustBeVerifiedBefore 
     * @return bool 
     */
    private function isNotRequestedOnTime(\DateTimeImmutable $forgotPasswordTokenMustBeVerifiedBefore): bool
    {
        return (new \DateTimeImmutable() > $forgotPasswordTokenMustBeVerifiedBefore);
    }

    /**
     * Remove user ID and token from session.
     * 
     * @return void 
     */
    private function removeCredentialsFromSession(): void
    {
        $this->session->remove('Reset-password-Token-URL');
        $this->session->remove('Reset-password-User-Email');
    }

    /**
     * Return times before password must be changed.
     * 
     * @param User $user 
     * @return string Time in format HHhMM.
     */
    private function passwordMustBeModifiedBefore(User $user): string
    {
        /** @var \DateTimeImmutable $passwordMustBeModifiedBefore */
        $passwordMustBeModifiedBefore = $user->getForgotPasswordTokenMustBeVerifiedBefore();

        return $passwordMustBeModifiedBefore->format('H\hi');
    }
}