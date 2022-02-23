<?php

namespace App\Tests;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ProtectedUrlSmokeTest extends WebTestCase
{
    use TestTrait;

    public function testAllPagesAreSuccessfulyLoaded(): void
    {
        $client = $this->createClientAndFollowRedirects();

        // Pour tester « ROLE_UNKNOWN », il faut commenter « $roles[] = 'ROLE_USER'; » dans l'entité « User »
        $this->authenticateAnUserWithSpecificRole($client, 'ROLE_USER');

        $protectedUri = $this->getUriList($client, false, 'ROLE_USER');

        // $protectedUri[] = "/false_URI/to_test"; // used to test

        $countOfProtectedUri = count($protectedUri);

        $countOfSuccessfulProtectedUri = 0;

        $uriNotSuccessfulyLoaded = [];

        foreach ($protectedUri as $uri) {
            $client->request('GET', $uri);

            if ($client->getResponse()->getStatusCode() === Response::HTTP_OK) {
                $countOfSuccessfulProtectedUri++;
            } else {
                $uriNotSuccessfulyLoaded[] = $uri;
            }
        }

        if (!empty($uriNotSuccessfulyLoaded)) {
            dump($uriNotSuccessfulyLoaded);
        }

        $this->assertSame($countOfProtectedUri, $countOfSuccessfulProtectedUri);
    }
}