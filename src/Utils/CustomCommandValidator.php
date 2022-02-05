<?php

namespace App\Utils;

use Symfony\Component\Console\Exception\InvalidArgumentException;

class CustomCommandValidator
{

    /**
     * Validate entered email in CLI
     * 
     * @param null|string $enteredEmail 
     * @return string 
     * @throws InvalidArgumentException 
     */
    public function validateEmail(?string $enteredEmail): string
    {
        if (empty($enteredEmail)) {
            throw new InvalidArgumentException('Veuillez saisir un e-mail.');
        }

        if (!filter_var($enteredEmail, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("L' e-mail saisi est invalide.");
        }

        [, $domain] = explode('@', $enteredEmail);
        if (!checkdnsrr($domain)) {
            throw new InvalidArgumentException("L' e-mail saisi est invalide.");
        }

        return $enteredEmail;
    }

    /**
     * Validate entered password in CLI
     * 
     * @param null|string $plainPassword 
     * @return string 
     * @throws InvalidArgumentException 
     */
    public function validatePassword(?string $plainPassword): string
    {
        if (empty($plainPassword)) {
            throw new InvalidArgumentException('Veuillez saisir un mot de passe.');
        }

        $passwordRegex = '/^(?=.*[a-zà-ÿ])(?=.*[A-ZÀ-Ý])(?=.*[0-9])(?=.*[^a-zà-ÿA-ZÀ-Ý0-9]).{12,}$/';

        if (!preg_match($passwordRegex, $plainPassword)) {
            throw new InvalidArgumentException("Le mot de passe doit contenir au minimum 12 caractères dont 1 lettre majuscule, 1 lettre minuscule, 1 chiffre, et 1 caractère spécial.");
        }

        return $plainPassword;
    }
}