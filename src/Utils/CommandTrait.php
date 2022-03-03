<?php

namespace App\Utils;

use App\Entity\User;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Exception\RuntimeException;

trait CommandTrait
{
    /**
     * Set the User's email entered in CLI ($input) if valid.
     * 
     * @param InputInterface $input 
     * @param OutputInterface $output 
     * @return void 
     */
    private function enterEmailWithDnsChecking(InputInterface $input, OutputInterface $output): void
    {
        $helper = $this->getHelper('question');

        $emailQuestion = new Question("E-mail de l'utilisateur : ");

        $emailQuestion->setValidator([$this->validator, 'validateEmailWithDns']);

        $email = $helper->ask($input, $output, $emailQuestion);

        if ($this->UserAlreadyExists($email)) {
            throw new RuntimeException(sprintf("%s : utilisateur déjà présent en base de donnée", $email));
        }

        $input->setArgument('email', $email);
    }

    private function enterEmail(InputInterface $input, OutputInterface $output): void
    {
        $helper = $this->getHelper('question');

        $emailQuestion = new Question("E-mail de l'utilisateur : ");

        $emailQuestion->setValidator([$this->validator, 'validateEmail']);

        $email = $helper->ask($input, $output, $emailQuestion);

        $input->setArgument('email', $email);
    }
    
    /**
     * Check if user already exists in database with email entered in CLI.
     * 
     * @param string $email 
     * @return null|User 
     */
    private function UserAlreadyExists(string $email): ?User
    {
        return $this->userRepository->findOneBy([
            'email' => $email
        ]);
    }
    
    private function returnUserOrThrowException(string $email): User
    {
        $user = $this->userRepository->findOneBy([
            'email' => $email
        ]);

        if (!$user) {
            throw new RuntimeException(sprintf("%s : il n'y a aucun utilisateur avec cette adresse e-mail", $email));
        }

        return $user;
    }

    /**
     * Set password entered in CLI ($input) if valid.
     * 
     * @param InputInterface $input 
     * @param OutputInterface $output 
     * @return void 
     */
    private function enterPassword(InputInterface $input, OutputInterface $output): void
    {
        $helper = $this->getHelper('question');

        $passwordQuestion = new Question("Mot de passe (en clair) de l'utilisateur (Algorithme de hashage ARGON2ID) : ");

        $passwordQuestion->setValidator([$this->validator, 'validatePassword']);
        
        $passwordQuestion->setHidden(true)
                         ->setHiddenFallback(false)
        ;

        $password = $helper->ask($input, $output, $passwordQuestion);

        $input->setArgument('plainPassword', $password);
    }

    /**
     * Set user's role entered in CLI ($input).
     * 
     * @param InputInterface $input 
     * @param OutputInterface $output 
     * @return void 
     */
    private function enterRole(InputInterface $input, OutputInterface $output):void 
    {
        $helper = $this->getHelper('question');

        $roleQuestion = new ChoiceQuestion(
            "Sélection du rôle de l'utilisateur : ",
            [
                'ROLE_USER',
                'ROLE_ADMIN',
                'ROLE_UNKNOWN', // « ROLE_UNKNOWN » = pour les tests fonctionnels
                'ROLE_WRITER'
            ], 
            'ROLE_USER'
        );

        $roleQuestion->setErrorMessage('Rôle utilisateur invalide.');

        $role = $helper->ask($input, $output, $roleQuestion);

        $output->writeln("<info>Rôle utilisateur pris en compte : {$role}.</info>");
        $input->setArgument('role', $role);
    }

    /**
     * Set IsVerified status entered in CLI ($input).
     * 
     * @param InputInterface $input 
     * @param OutputInterface $output 
     * @return void 
     */
    private function enterIsVerified(InputInterface $input, OutputInterface $output):void 
    {
        $helper = $this->getHelper('question');

        $isVerifiedQuestion = new ChoiceQuestion(
            "Sélection du statut du compte utilisateur : ",
            ['INACTIF', 'ACTIF'],
            'INACTIF'
        );
        
        $isVerifiedQuestion->setErrorMessage("Statut d'activation du compte utilisateur invalide.");

        $isVerified = $helper->ask($input, $output, $isVerifiedQuestion);

        $output->writeln("<info>Statut d'activation du compte utilisateur pris en compte : {$isVerified}.</info>");

        $input->setArgument('isVerified', $isVerified);
    }
}
