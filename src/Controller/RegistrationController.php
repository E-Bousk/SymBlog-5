<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Service\SendEmail;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;

class RegistrationController extends AbstractController
{
    /**
     * @Route("/register", name="app_register", methods={"GET", "POST"}, defaults={"_public_access": true})
     */
    public function register(
        Request $request,
        EntityManagerInterface $entityManager,
        TokenGeneratorInterface $tokenGenerator,
        SendEmail $sendEmail
    ): Response
    {
        $user = new User();
        
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setRegistrationToken($tokenGenerator->generateToken())
                ->setAccountMustBeVerifiedBefore((new \DateTimeImmutable())->add(new \DateInterval("P1D")))
                ->setIsVerified(false)
            ;

            $entityManager->persist($user);
            $entityManager->flush();

            $sendEmail->send([
                'recipient_email' => $user->getEmail(),
                'subject'         => "Activez votre compte utilisateur",
                'html_template'   => "registration/register_email_confirmation.html.twig",
                'context'         => [
                    'userID'            => $user->getId(),
                    'registrationToken' => $user->getRegistrationToken(),
                    'tokenLifeTime'     => $user->getAccountMustBeVerifiedBefore()->format('d/m/Y à H: i')
                ]
            ]);

            $this->addFlash('success', 'Votre compte utilisateur a bien été créé. Veuillez consulter vos e-mails pour le valider !');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id<\d+>}/{token}", name="app_verify_account", methods={"GET"})
     */
    public function verifyUserAccount(EntityManagerInterface $entityManager, User $user, string $token): Response
    {
        if ($user->getAccountMustBeVerifiedBefore() === null) {
            return $this->redirectToRoute('app_login');
        }
        
        if (($user->getRegistrationToken() === null) || ($user->getRegistrationToken() !== $token) || ($this->isNotRequestedInTime($user->getAccountMustBeVerifiedBefore()))) {
            throw new AccessDeniedException("Ce token n'est pas/plus valide");
        }

        $user->setIsVerified(true);
        $user->setAccountVerifiedAt(new \DateTimeImmutable());

        $user->setRegistrationToken(null);

        $entityManager->flush();
        $this->addFlash('success', 'Votre compte utilisateur a bien été activé. Vous pouvez dès à présent vous connecter');

        return $this->redirectToRoute('app_login');
    }

    private function isNotRequestedInTime(\DateTimeImmutable $accountMustBeVerifiedBefore): bool
    {
        return new \DateTimeImmutable() > $accountMustBeVerifiedBefore;
    }
}
