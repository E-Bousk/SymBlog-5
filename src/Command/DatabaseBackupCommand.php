<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Process\Process;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Process\Exception\ProcessFailedException;

class DatabaseBackupCommand extends Command
{
    protected static $defaultName = 'app:backup-db';
    protected static string $defaultDescription = 'Fait une sauvegarde de la base de données';

    private  SymfonyStyle $io;

    private ManagerRegistry $managerRegistry;
    private string $projectDirectory;

    public function __construct(
        ManagerRegistry $managerRegistry,
        string $projectDirectory
    )
    {
        parent::__construct();
        $this->managerRegistry = $managerRegistry;
        $this->projectDirectory = $projectDirectory;
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
        $fileSystem = new Filesystem();

        $backupDirectory = "{$this->projectDirectory}/var/BDD/Backup";

        if ($fileSystem->exists($backupDirectory) === true) {
            $fileSystem->remove($backupDirectory);
        }

        try {
            $fileSystem->mkdir($backupDirectory, 0700);
        } catch (IOException $error) {
            throw new IOException($error);
        }

        /** @var Connection $databaseConnection */
        $databaseConnection = $this->managerRegistry->getConnection();

        [
            'host'     => $databaseHost,
            'port'     => $databasePort,
            'user'     => $databaseUsername,
            'password' => $databasePassword,
            'dbname'   => $databaseName,
        ] = $databaseConnection->getParams();

        $filePathTarget = "--result-file={$backupDirectory}/backup.sql";

        $command = [
            'mysqldump',
            '--host', // -h
            $databaseHost,
            '--port', // -P
            $databasePort,
            '--user', // -u
            $databaseUsername,
            '--password=' . $databasePassword,  // -p
            $databaseName,
            '--databases', // If you want « CREATE DATABASE » statement for an import via PHPMYADMIN for ex.
            $filePathTarget
        ];

        $process = new Process($command);

        $process->setTimeout(90); // Exemple pour spécifier un 'timeout' autrement qu'avec le 5eme argument de « Process() »

        $process->run();

        if ($process->isSuccessful() === false) {
            throw new ProcessFailedException($process);
        }

        $this->io->success("DB backup done !");

        return Command::SUCCESS;        
    }
}
