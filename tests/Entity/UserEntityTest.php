<?php

namespace App\Tests\Entity;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserEntityTest extends KernelTestCase
{
    private const EMAIL_NOT_BLANK_CONSTRAINT_MESSAGE = 'Veuillez saisir votre adresse e-mail.';
    private const EMAIL_INVALID_CONSTRAINT_MESSAGE = 'L\'adresse e-mail "invalid@symfony" n\'est pas valide.';
    
    private const EMAIL_VALID_VALUE = "valid_email@symfony.fr";
    private const EMAIL_INVALID_VALUE = "invalid@symfony";
    
    private const PASSWORD_NOT_BLANK_CONSTRAINT_MESSAGE = 'Veuillez saisir un mot de passe.';
    private const PASSWORD_INVALID_CONSTRAINT_MESSAGE = 'Le mot de passe doit contenir au minimum 12 caractères dont 1 lettre majuscule, 1 lettre minuscule, 1 chiffre, et 1 caractère spécial.';
    
    private const PASSWORD_VALID_VALUE = "Valid_Password_1";

    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        $this->validator = $kernel->getContainer()->get('validator');
    }

    public function provideInvalidPassword(): array
    {
        return [
            ['invalid_password_1'], // no uppercase
            ['INVALID_PASSWORD_1'], // no lowercase
            ['Invalid_Password'], // no numbers
            ['InvalidPassword1'], // no special character
            ['InvalidPa_1'] // < 12 characters
        ];
    }

    private function getValidationErrors(User $user, int $numberExpectedErrors): ConstraintViolationList
    {
        $errors = $this->validator->validate($user);

        $this->assertCount($numberExpectedErrors, $errors);

        return $errors;
    }

    public function testUserEntityIsValid(): void
    {
        $user = (new User())->setEmail(self::EMAIL_VALID_VALUE)
                            ->setPassword(self::PASSWORD_VALID_VALUE)
        ;

        $this->getValidationErrors($user, 0);
    }

    public function testUserEntityIsInvalidBecauseNoEmailEntered(): void
    {
        $user = (new User())->setPassword(self::PASSWORD_VALID_VALUE);

        $errors = $this->getValidationErrors($user, 1);

        $this->assertEquals(self::EMAIL_NOT_BLANK_CONSTRAINT_MESSAGE, $errors[0]->getMessage());
    }

    public function testUserEntityIsInvalidBecauseNoPasswordEntered(): void
    {
        $user = (new User())->setEmail(self::EMAIL_VALID_VALUE);

        $errors = $this->getValidationErrors($user, 1);

        $this->assertEquals(self::PASSWORD_NOT_BLANK_CONSTRAINT_MESSAGE, $errors[0]->getMessage());
    }

    public function testUserEntityIsInvalidBecauseInvalidEmailEntered(): void
    {
        $user = (new User())->setEmail(self::EMAIL_INVALID_VALUE)
                            ->setPassword(self::PASSWORD_VALID_VALUE)
        ;

        $errors = $this->getValidationErrors($user, 1);

        $this->assertEquals(self::EMAIL_INVALID_CONSTRAINT_MESSAGE, $errors[0]->getMessage());
    }

    /**
     * @dataProvider provideInvalidPassword
     */
    public function testUserEntityIsInvalidBecauseInvalidPasswordEntered(string $invalidPassword): void
    {
        $user = (new User())->setEmail(self::EMAIL_VALID_VALUE)
                            ->setPassword($invalidPassword)
        ;

        $errors = $this->getValidationErrors($user, 1);

        $this->assertEquals(self::PASSWORD_INVALID_CONSTRAINT_MESSAGE, $errors[0]->getMessage());
    }
}
