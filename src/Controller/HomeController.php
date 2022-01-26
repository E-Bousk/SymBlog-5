<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    /**
     * @Route("/", name="home")
     */
    public function index(): Response
    {
        return $this->json([
            'message' => 'Message édité à tord dans le dossier Vendor !',
            'path' => 'src/Controller/HomeController.php',
        ]);
    }
}
