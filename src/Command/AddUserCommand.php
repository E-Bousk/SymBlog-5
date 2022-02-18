<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Utils\CommandTrait;
use App\Utils\CustomCommandValidator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class AddUserCommand extends Command
{
    use CommandTrait; 
    
    protected static $defaultName = 'app:add-user';
    protected static string $defaultDescription = 'Crée un utilisateur en base de données.';

    private  SymfonyStyle $io;

    private CustomCommandValidator $validator;
    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;

    public function __construct(
        CustomCommandValidator $Validator,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository
    )
    {
        parent::__construct();
        $this->validator = $Validator;
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
    }

    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription)
            ->addArgument('email', InputArgument::REQUIRED, "E-mail de l'utilisateur.")
            ->addArgument('plainPassword', InputArgument::REQUIRED, "Mot de passe (en clair) de l'utilisateur.")
            ->addArgument('role', InputArgument::REQUIRED, "Rôle de l'utilisateur.")
            ->addArgument('isVerified', InputArgument::REQUIRED, "Statut du compte de l'utilisateur (actif ou non).")
        ;
    }

    /**
     * Executed after configure() to initialize properties based on the input arguments and options.
     * 
     * @param InputInterface $input 
     * @param OutputInterface $output 
     * @return void 
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);
    }

    /**
     * Executed after initialize() and before execute().
     * Checks if options/arguments are missing and ask for missing values.
     * 
     * @param InputInterface $input 
     * @param OutputInterface $output 
     * @return void 
     */
    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        $this->io->section("Ajout d'un utilisateur en base de données");

        $this->enterEmailWithDnsChecking($input, $output);
        $this->enterPassword($input, $output);
        $this->enterRole($input, $output);
        $this->enterIsVerified($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var string $email */
        $email = $input->getArgument('email');

        /** @var string $plainPassword */
        $plainPassword = $input->getArgument('plainPassword');

        /** @var array<string> $role */
        $role = [$input->getArgument('role')];

        /** @var bool $isVerified */
        $isVerified = $input->getArgument('isVerified') === "INACTIF" ? false : true;

        $user = new User();

        $user->setEmail($email)
            ->setPassword($plainPassword) // Le mot de passe est chiffré avec un eventsubscriber (et services.yaml)
            ->setRoles($role)
            ->setIsVerified($isVerified)
        ;
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->io->success('Un nouvel utilisateur à été entré en base de données !.');

        return Command::SUCCESS;
    }
}
