<?php

namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DeleteInactiveAccountsCommand extends Command
{
    protected static $defaultName = 'app:delete-inactive-accounts';
    protected static string $defaultDescription = 'Supprime les comptes inactifs en base de données';
    
    private SymfonyStyle $io;
    
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

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->DeleteInactiveAccounts();

        return Command::SUCCESS;
    }

    private function DeleteInactiveAccounts(): void
    {
        $this->io->section("Suppression des comptes inactifs en base de données");

        $sqlQuery = "DELETE FROM users
            WHERE is_verified = false
            AND account_must_be_verified_before < NOW()
        ";

        $dbConnection = $this->em->getConnection();
        $statement = $dbConnection->query($sqlQuery);
        $accountsDeleted = $statement->rowCount();

        if ($accountsDeleted === 1) {
            $string = "Un compte inactif a été supprimé de la base de données";
        } elseif ($accountsDeleted > 1) {
            $string = "{$accountsDeleted} comptes inactifs ont été supprimés de la base de données";
        } else {
            $string = "Aucun compte inactif n'a été supprimé de la base de données";
        }

        $this->io->success($string);
    }
}
