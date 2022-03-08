<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

use function Symfony\Component\String\u;

class SwitchLocaleController extends AbstractController
{
    /**
     * @Route(
     *      {"en": "/locale/update/{locale<fr|en>}", "fr": "/locale/modifier/{locale<fr|en>}"},
     *      name="app_locale_update",
     *      methods={"GET"},
     *      defaults={"_public_access": false, "_role_required": "ROLE_USER"}
     * )
     * 
     * @param string $locale 
     * @param Request $request 
     * @param RouterInterface $router 
     * @return RedirectResponse 
     */
    public function updateLocale(string $locale, Request $request, RouterInterface $router): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $referrer = $request->headers->get('referer');

        if ($referrer === null || u($referrer)->ignoreCase()->startsWith($request->getSchemeAndHttpHost()) === false) {
            return $this->redirectToRoute('app_home');
        }

        $path = parse_url($referrer, PHP_URL_PATH);

        if (is_string($path) === false) {
            throw new BadRequestHttpException();
        }

        try {
            $routeParameters = $router->match($path);
        } catch (\Exception $error) {
            return $this->redirectToRoute('app_home', ['_locale' => $locale]);
        }

        return $this->redirectToRoute(
            $routeParameters['_route'],
            [
                '_locale' => $locale,
                'slug'    => $routeParameters['slug'] ?? null,
                'id'      => $routeParameters['id'] ?? null
            ]
        );
    }
}
