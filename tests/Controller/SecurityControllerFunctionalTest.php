<?php

namespace App\Tests\Controller;

use App\Tests\TestTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Dotenv\Dotenv;

class SecurityControllerFunctionalTest extends WebTestCase
{
    use TestTrait;

    /**
     * @dataProvider provideInvalidCredentials
     * @param int $attemptNumber
     * @param array $invalidCredentials
     * @param string $flashError
     */
    public function testFourOrMoreUnsuccesfulLogingAttemptsMustDisplayAntiSpamFlashbagIfJavascriptIsDisabled(
        int $attemptNumber,
        array $invalidCredentials,
        string $flashError
    ): void
    {   
        $this->makeBadAuthenticationAttempt($attemptNumber, $invalidCredentials);

        if ($attemptNumber <= 5) {
            $this->assertSelectorTextContains('p[class="alert alert-danger"]', $flashError);
        }
    }

    /**
     * @dataProvider provideInvalidCredentials
     * @param int $attemptNumber
     * @param array $invalidCredentials
     * @param string $flashError
     */
    public function testBruteForceCheckerMustBlockAnyAuthenticationAttemptAfterFiveFailuresWithNoHcaptchaVerification(
        int $attemptNumber,
        array $invalidCredentials,
        string $flashError
    ): void
    {   
        $dotenv = new Dotenv();

        $dotenv->populate([
            'APP_HCAPTCHA_VERIFICATION' => false
        ]);
        
        $this->makeBadAuthenticationAttempt($attemptNumber, $invalidCredentials);

        if ($attemptNumber >= 6) {
            $this->assertSelectorTextContains('p[class="alert alert-danger"]', $flashError);
        }
    }


    private function makeBadAuthenticationAttempt(
        int $attemptNumber,
        array $invalidCredentials
    ): KernelBrowser
    {
        $client = $this->createClientAndFollowRedirects();

        $crawler = $client->request('GET', '/login');

        $this->assertSelectorTextContains('h1', 'M\'authentifier pour accéder à mon espace');
        if ($attemptNumber === 1) {
            $this->truncateTableBeforeTest('auth_logs');
        }
        
        $form = $crawler->filter('form[method="post"]')->form($invalidCredentials);

        $client->submit($form);

        return $client;

    }

    public function provideInvalidCredentials(): \Generator
    {
        yield [
            1,
            [
                'email'    => 'testing_mail@test.com',
                'password' => 'test-password-01'
            ],
            'Identifiants invalides.'
        ];

        yield [
            2,
            [
                'email'    => 'testing_mail@test.com',
                'password' => 'Test-password-02'
            ],
            'Identifiants invalides.'
        ];

        yield [
            3,
            [
                'email'    => 'testing_mail@test.com',
                'password' => 'Test-password-03'
            ],
            'Identifiants invalides.'
        ];

        yield [
            4,
            [
                'email'    => 'testing_mail@test.com',
                'password' => 'Test-password-04'
            ],
            'La vérification anti-spam a échouée, veuillez réessayer.'
        ];

        yield [
            5,
            [
                'email'    => 'testing_mail@test.com',
                'password' => 'Test-password-05'
            ],
            'La vérification anti-spam a échouée, veuillez réessayer.'
        ];

        yield [
            6,
            [
                'email'    => 'testing_mail@test.com',
                'password' => 'Test-password-06'
            ],
            'Il semblerait que vous ayez oublié vos identifiants. Par mesure de sécurité, vous devez patienter'
        ];

        yield [
            7,
            [
                'email'    => 'testing_mail@test.com',
                'password' => 'Test-password-07'
            ],
            'Il semblerait que vous ayez oublié vos identifiants. Par mesure de sécurité, vous devez patienter'
        ];
    }
}
