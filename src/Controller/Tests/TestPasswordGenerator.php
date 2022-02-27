<?php

namespace App\Controller\Tests;

use App\Service\PasswordGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class TestPasswordGenerator extends AbstractController
{
    /**
     * @Route("/test-password-generator", name="app_test_password_generator")
     */
    public function index(PasswordGenerator $passwordGenerator): void
    {
        $newPasswordGenerated = $passwordGenerator->generateRandomStrongPassword(15);
        if (str_starts_with($newPasswordGenerated, 'La')) {
            dd($newPasswordGenerated);
        }
        dump('Taille du mot de passe = ' . strlen($newPasswordGenerated));
        dd("Mot de passe = « $newPasswordGenerated »");
    }
}
