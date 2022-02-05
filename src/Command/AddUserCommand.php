<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Utils\CustomCommandValidator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class AddUserCommand extends Command
{
    protected static $defaultName = 'app:add-user';
    protected static string $defaultDescription = 'Créé un utilisateur en base de données.';

    private CustomCommandValidator $validator;

    private EntityManagerInterface $entityManager;
    private UserPasswordEncoderInterface $encoder;
    private UserRepository $userRepository;

    private  SymfonyStyle $io;

    public function __construct(
        CustomCommandValidator $Validator,
        EntityManagerInterface $entityManager,
        UserPasswordEncoderInterface $encoder,
        UserRepository $userRepository
    )
    {
        parent::__construct();
        $this->validator = $Validator;
        $this->entityManager = $entityManager;
        $this->encoder = $encoder;
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
        $this->io->section("Ajout d'un utilisateur en base de donnée");

        $this->enterEmail($input, $output);
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
            ->setPassword($this->encoder->encodePassword($user, $plainPassword))
            ->setRoles($role)
            ->setIsVerified($isVerified)
        ;
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->io->success('Un nouvel utilisateur à été entré en base de données !.');

        return Command::SUCCESS;
    }

    /**
     * Set the User's email entered in CLI ($input) if valid.
     * 
     * @param InputInterface $input 
     * @param OutputInterface $output 
     * @return void 
     */
    private function enterEmail(InputInterface $input, OutputInterface $output): void
    {
        $helper = $this->getHelper('question');

        $emailQuestion = new Question("E-mail de l'utilisateur : ");

        $emailQuestion->setValidator([$this->validator, 'validateEmail']);

        $email = $helper->ask($input, $output, $emailQuestion);

        if ($this->UserAlreadyExists($email)) {
            throw new RuntimeException(sprintf("%s : utilisateur déjà présent en base de donnée", $email));
        }

        $input->setArgument('email', $email);
    }

    /**
     * Check if user already exists in databse with email entered in CLI.
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
            ['ROLE_USER', 'ROLE_ADMIN'],
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
