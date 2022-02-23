<?php

namespace App\Controller;

use App\Repository\AuthLogRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    /**
     * @Route("/login", name="app_login", methods={"GET", "POST"}, defaults={"_public_access": true})
     */
    public function login(
        AuthenticationUtils $authenticationUtils,
        AuthLogRepository $authLogRepository,
        Request $request
    ): Response
    {
        // if ($this->getUser()) {
        //     return $this->redirectToRoute('target_path');
        // }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        $userIp = $request->getClientIp();

        $recentFailedLoginCount = 0;

        if ($lastUsername) {
            $recentFailedLoginCount = $authLogRepository->getRecentAttemptFailure($lastUsername, $userIp);
        }

        return $this->render('security/login.html.twig', [
            'recent_failed_login_count' => $recentFailedLoginCount,
            'last_username' => $lastUsername,
            'error'         => $error
        ]);
    }

    /**
     * @Route("/logout", name="app_logout", methods={"GET"}, defaults={"_public_access": true})
     */
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
