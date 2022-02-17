<?php

namespace App\Command;

use App\Repository\UserRepository;
use App\Utils\CustomCommandValidator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DeleteOneUserCommand extends Command
{
    protected static $defaultName = 'app:delete-one-user';
    protected static string $defaultDescription = 'Supprime un utilisateur en base de données';

    private SymfonyStyle $io;
    
    private CustomCommandValidator $validator;
    private UserRepository $userRepository;
    private EntityManagerInterface $em;
    
    public function __construct(CustomCommandValidator $validator, UserRepository $userRepository, EntityManagerInterface $em)
    {
        parent::__construct();
        $this->validator = $validator;
        $this->userRepository = $userRepository;
        $this->em = $em;
    }

    protected function configure(): void
    {
        $this->setDescription(self::$defaultDescription)
            ->addArgument('email', InputArgument::REQUIRED, "L'e-mail de l'utilisateur")
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        $this->io->section("Suppression d'un utilisateur en base de données");

        $this->enterEmail($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var string $email */
        $email = $input->getArgument('email');

        $user = $this->userRepository->findOneBy([
            'email' => $email
        ]);

        if (!$user) {
            throw new RuntimeException("Aucun utilisateur n'est présent en base de données avec l'e-mail suivant : {$email}");
        }

        $userId = $user->getId();

        $this->em->remove($user);
        $this->em->flush();

        $this->io->success("L'utilisateur ayant l'ID {$userId} et l'e-mail {$email} n'existe plus en base de données.");

        return Command::SUCCESS;
    }

    private function enterEmail(InputInterface $input, OutputInterface $output): void
    {
        $helper = $this->getHelper('question');

        $emailQuestion = new Question("E-mail de l'utilisateur : ");

        $emailQuestion->setValidator([$this->validator, 'checkEmailForUserDelete']);

        $email = $helper->ask($input, $output, $emailQuestion);

        $input->setArgument('email', $email);
    }
}
