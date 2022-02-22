<?php

namespace App\Tests\Controller;

use App\Tests\TestTrait;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RegistrationControllerTest extends WebTestCase
{
    use TestTrait;

    public function testGetRequestToRegistrationPageReturnSuccessfulResponse(): void
    {
        $this->ClientGoesOnRegisterPage();

        $this->assertResponseIsSuccessful();

        $this->assertSelectorTextContains('h1', 'Créer un compte utilisateur');
    }

    public function testSpamBotsAreNotWelcome(): void
    {
        $client = $this->ClientGoesOnRegisterPage();

        $client->submitForm(
            "S'inscrire",
            [
                'registration_form[email]' => 'testAntiSpamBot@exemple.com',
                'registration_form[password][first]' => 'aaaAAA111@@@',
                'registration_form[password][second]' => 'aaaAAA111@@@',
                'registration_form[agreeTerms]' => true,
                'registration_form[phone]' => 'remplit par un bot',
                'registration_form[faxNumber]' => 'remplit par un bot'
            ]
        );

        $this->assertResponseStatusCodeSame(403, 'Go away, you dirty BOT !!');

        $this->assertRouteSame('app_register');
    }

    public function testMustBeRedirectedToLoginPageIfTheFormIsValid(): void
    {
        // ‼ Démarrer le service de mail lors de ce test ‼

        $client = $this->ClientGoesOnRegisterPage();

        $this->truncateTableBeforeTest('users');

        $client->submitForm(
            "S'inscrire",
            [
                'registration_form[email]' => 'testUserRegistration@exemple.com',
                'registration_form[password][first]' => 'aaaAAA111@@@',
                'registration_form[password][second]' => 'aaaAAA111@@@',
                'registration_form[agreeTerms]' => true,
            ]
        );

        $this->assertResponseIsSuccessful();

        $this->assertRouteSame('app_login');
    }

    private function ClientGoesOnRegisterPage(): KernelBrowser
    {
        $client = $this->createClientAndFollowRedirects();

        $client->request('GET', '/register');

        return $client;
    }
}
