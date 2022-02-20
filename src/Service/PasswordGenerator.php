<?php

namespace App\Service;

class PasswordGenerator
{
    public function generateRandomStrongPassword(int $length = 12): string
    {
        if ($length < 1 || $length > 94) {
            return "La taille du mot de passe doit être compris entre 1 et 94 caractères";
        }
        
        $uppercaseLetters = $this->generateCharactersWithCharCodeRange([65, 90]);
        $lowercaseLetters = $this->generateCharactersWithCharCodeRange([97, 122]);
        $numbers = $this->generateCharactersWithCharCodeRange([48, 57]);
        $symbols = $this->generateCharactersWithCharCodeRange([33, 47, 58, 64, 91, 96, 123, 126]);

        $allCharacters = array_merge($uppercaseLetters, $lowercaseLetters, $numbers, $symbols);

        $isArrayShuffled = shuffle($allCharacters);

        if (!$isArrayShuffled) {
            throw new \LogicException("La génération d'un mot de passe aléatoire a échouée, veuillez réessayer.");
        }

        return implode('', array_slice($allCharacters, 0, $length));
    }

    public function generateCharactersWithCharCodeRange(array $range): array
    {
        if (count($range) === 2) {
            return range(chr($range[0]), chr($range[1]));
        } else {
            // $chunkAsciiCodes = array_chunk($range, 2);
            // return array_merge(
            //     range(chr($chunkAsciiCodes[0][0]), chr($chunkAsciiCodes[0][1])),
            //     range(chr($chunkAsciiCodes[1][0]), chr($chunkAsciiCodes[1][1])),
            //     range(chr($chunkAsciiCodes[2][0]), chr($chunkAsciiCodes[2][1])),
            //     range(chr($chunkAsciiCodes[3][0]), chr($chunkAsciiCodes[3][1]))
            // );
            // OU :
            // $chunkAsciiCodes = array_chunk($range, 2);
            // $specialCharactersChunks = array_map(fn($range) => range(chr($range[0]), chr($range[1])), $chunkAsciiCodes);
            // return array_merge(...$specialCharactersChunks);
            // (en une seule ligne) :
            return array_merge(...array_map(fn($range) => range(chr($range[0]), chr($range[1])), array_chunk($range, 2)));
        }
    }
}
