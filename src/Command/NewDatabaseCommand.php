<?php

namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class NewDatabaseCommand extends Command
{
    protected static $defaultName = 'app:clean-db';
    protected static string $defaultDescription = 'Supprime et recrée la base de données avec sa structure et ses jeux de fausses données';

    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct();
        $this->em = $em;
    }

    protected function configure(): void
    {
        $this->setDescription(self::$defaultDescription);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->section("Suppression de la base de données puis création d'une nouvelle avec structure et données.");

        $this->runSymfonyCommand($input, $output, 'doctrine:database:drop', true);
        $this->runSymfonyCommand($input, $output, 'doctrine:database:create');
        $this->runSymfonyCommand($input, $output, 'doctrine:migrations:migrate');
        $this->runSymfonyCommand($input, $output, 'doctrine:fixtures:load');

        $this->createRememberMeTokenTable();

        $io->success('RAS => Base de données prête avec ses data.');

        return Command::SUCCESS;
    }

    private function runSymfonyCommand(
        InputInterface $input,
        OutputInterface $output,
        string $command,
        bool $forceOption = false
    ): void
    {
        $application = $this->getApplication();

        if (!$application) {
            throw new \LogicException("No application :(");
        }

        $command = $application->find($command);

        if ($forceOption) {
            $input = new ArrayInput([
                '--force' => true
            ]);
        }

        $input->setInteractive(false);

        $command->run($input, $output);
    }

    private function createRememberMeTokenTable(): void
    {
        $sqlQuery = "CREATE TABLE `rememberme_token` (
            `series`   char(88)     UNIQUE PRIMARY KEY NOT NULL,
            `value`    varchar(88)  NOT NULL,
            `lastUsed` datetime     NOT NULL,
            `class`    varchar(100) NOT NULL,
            `username` varchar(200) NOT NULL
        );";

        $this->em->getConnection()->exec($sqlQuery);
    }
}
