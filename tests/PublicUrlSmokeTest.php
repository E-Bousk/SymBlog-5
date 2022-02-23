<?php

namespace App\Tests;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class PublicUrlSmokeTest extends WebTestCase
{
    use TestTrait;

    public function getPublicUri(KernelBrowser $client): array
    {
        $router = $client->getContainer()->get('router');

        $routesWithAllParameters = $router->getRouteCollection()->all();

        $publicUri = [];

        foreach ($routesWithAllParameters as $routeName => $routeWithAllParameters) {
            if ($routeWithAllParameters->getDefault('_public_access') === true) {
                $publicUri[] = $routeWithAllParameters->getPath();
            }
        }
        return $publicUri;
    }

    public function testAllPagesAreSuccessfulyLoaded(): void
    {
        $client = $this->createClientAndFollowRedirects();

        $publicUri = $this->getPublicUri($client);

        // $publicUri[] = "/false_URI/to_test"; // used to test

        $countOfPublicUri = count($publicUri);

        $countOfSuccessfulPublicUri = 0;

        $uriNotSuccessfulyLoaded = [];

        foreach ($publicUri as $uri) {
            $client->request('GET', $uri);

            if ($client->getResponse()->getStatusCode() === Response::HTTP_OK) {
                $countOfSuccessfulPublicUri++;
            } else {
                $uriNotSuccessfulyLoaded[] = $uri;
            }
        }

        if (!empty($uriNotSuccessfulyLoaded)) {
            dump($uriNotSuccessfulyLoaded);
        }

        $this->assertSame($countOfPublicUri, $countOfSuccessfulPublicUri);
    }
}
