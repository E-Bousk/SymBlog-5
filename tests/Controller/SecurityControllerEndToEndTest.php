<?php

namespace App\Tests\Controller;

use App\Tests\TestTrait;
use Symfony\Component\Panther\PantherTestCase;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Symfony\Component\HttpClient\Exception\TimeoutException;

class SecurityControllerEndToEndTest extends PantherTestCase
{
    use TestTrait;

    /**
     * @dataProvider provideInvalidCredentials
     * @param int $attemptNumber
     * @param array $invalidCredentials
     * @param string $flashError
     * @param string $screenshotPath
     */
    public function testHcaptchaChallengeMustAppearAfterThreeUnsuccessfulLogingAttempts(
        int $attemptNumber,
        array $invalidCredentials,
        string $flashError,
        string $screenshotPath
    ): void
    {   
        if ($attemptNumber === 1) {
            $this->truncateTableBeforeTest('auth_logs');
        }

        $client = static::createPantherClient(
            [
                'browser' => static::FIREFOX,
                'external_base_uri' => 'https://127.0.0.1:8000'
            ],
            [],
            [
                'capabilities' => [
                    'acceptInsecureCerts' => true, // for Firefox
                ],
            ]
        );

        $client->followRedirects();

        $crawler = $client->request('GET', '/login');

        $this->assertSelectorTextContains('h1', 'M\'authentifier pour accéder à mon espace');

        $form = $crawler->selectButton('Se connecter')->form($invalidCredentials);

        $client->submit($form);

        if ($attemptNumber !== 4) {
            $this->assertSelectorTextContains('p[class="alert alert-danger"]', $flashError);
        } else {
            // $client->takeScreenshot($screenshotPath);

            try {
                $client->waitFor('iframe', 3);
            } catch (NoSuchElementException $error) {
                throw new NoSuchElementException($error);
            } catch (TimeoutException $error) {
                throw new TimeoutException($error);
            }

            $this->assertSelectorAttributeContains('iframe', 'title', 'widget containing checkbox for hCaptcha security challenge');
        }

        $client->takeScreenshot($screenshotPath);
    }


    public function provideInvalidCredentials(): \Generator
    {
        yield [
            1,
            [
                'email'    => 'mail_to_test@test.com',
                'password' => 'test-password-01'
            ],
            'Identifiants invalides.',
            './var/tests/screenshots/invalid-credentials-01.png'
        ];

        yield [
            2,
            [
                'email'    => 'mail_to_test@test.com',
                'password' => 'Test-password-02'
            ],
            'Identifiants invalides.',
            './var/tests/screenshots/invalid-credentials-02.png'
        ];

        yield [
            3,
            [
                'email'    => 'mail_to_test@test.com',
                'password' => 'Test-password-03'
            ],
            'Identifiants invalides.',
            './var/tests/screenshots/invalid-credentials-03.png'
        ];

        yield [
            4,
            [
                'email'    => 'mail_to_test@test.com',
                'password' => 'Test-password-04'
            ],
            'Identifiants invalides.',
            './var/tests/screenshots/hcpatcha-guardian.png'
        ];
    }
}
