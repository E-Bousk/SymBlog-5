<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    /**
     * @Route("/", name="app_home", methods={"GET"}, defaults={"_public_access": true})
     */
    public function index(): Response
    {
        return $this->json([
            'message'    => 'Hello world !',
            'controller' => 'HomeController',
            'action'     => 'index()'
        ]);
    }
}
