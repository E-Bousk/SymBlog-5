<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SvgController extends AbstractController
{
    /**
     * @Route("/svg", name="app_svg_integration")
     */
    public function index(): Response
    {
        return $this->render('svg/index.html.twig');
    }
}
