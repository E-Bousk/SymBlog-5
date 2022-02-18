<?php

namespace App\Command;

use App\Utils\CommandTrait;
use App\Repository\UserRepository;
use App\Utils\CustomCommandValidator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ModifyUserRoleCommand extends Command
{
    use CommandTrait;
    
    protected static $defaultName = 'app:modify-user-role';
    protected static string $defaultDescription = 'Modifie le rôle d\'un utilisateur';

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
        $this->setDescription(self::$defaultDescription)
            ->addArgument('email', InputArgument::REQUIRED, "L'email de l'utilisateur.")
            ->addArgument('role', InputArgument::REQUIRED, "Le rôle à donner à l'utilisateur.")
        ;
    }
    
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        $this->io->section("Modification d'un rôle d'un utilisateur");

        $this->enterEmail($input, $output);
        $this->enterRole($input, $output);
    }


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var string $email */
        $email = $input->getArgument('email');

        $user = $this->returnUserOrThrowException($email);

        /** @var string $role */
        $role = $input->getArgument('role');
     
        $user->setRoles([$role]);

        $this->entityManager->flush();

        $this->io->success("L'utilisateur ayant l'ID « {$user->getId()} » et l'e-mail « {$user->getEmail()} » a maintenant le rôle « {$role} » ");

        return Command::SUCCESS;

    }
}
