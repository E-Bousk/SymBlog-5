<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\YamlEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class AddUsersFromFileCommand extends Command
{
    protected static $defaultName = 'app:add-users-from-file';
    protected static string $defaultDescription = 'Crée des utilisateurs en base de données depuis un fichier CSV, XML ou YAML.';

    private  SymfonyStyle $io;

    private EntityManagerInterface $em;
    private UserRepository $userRepository;

    private string $dataDirectory;


    public function __construct(EntityManagerInterface $em, UserRepository $userRepository, string $dataDirectory)
    {
        parent::__construct();
        $this->em = $em;
        $this->userRepository = $userRepository;
        $this->dataDirectory = $dataDirectory;
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
        $this->createUsers();

        return Command::SUCCESS;
    }

    private function getDatafromFile(): array
    {
        $file = $this->dataDirectory . 'random-user.json';

        $fileExtension = pathinfo($file, PATHINFO_EXTENSION);

        $normalizers = [new ObjectNormalizer()];
        $encoders = [
            new CsvEncoder(),
            new XmlEncoder(),
            new YamlEncoder(),
            new JsonEncoder()
        ];
        $serializer = new Serializer($normalizers, $encoders);

        /** @var string $fileString */
        $fileString = file_get_contents($file);

        $data = $serializer->decode($fileString, $fileExtension);

        if (array_key_exists('results', $data)) {
            return $data['results'];
        }

        return $data;  
    }

    private function createUsers(): void
    {
        $this->io->section('Création des utilisateurs à partir du fichier');

        $userCreated = 0;

        foreach ($this->getDatafromFile() as $row) {
            if (array_key_exists('email', $row) && !empty($row['email'])) {
                $user = $this->userRepository->findOneBy([
                    'email' => $row['email']
                ]);

                if (!$user) {
                    $user = (new User())->setEmail($row['email'])
                        ->setPassword('password')  // Le mot de passe est chiffré avec un eventsubscriber (et services.yaml)
                        ->setIsVerified((bool)random_int(0, 1))
                    ;
                    $this->em->persist($user);

                    $userCreated++;
                }
            }
        }

        $this->em->flush();

        if ($userCreated === 1) {
            $string = "1 utilisateur a été créé en base de données.";
        } elseif ($userCreated > 1){
            $string = "{$userCreated} utilisateurs créés en base de données";
        } else {
            $string = "Aucun utilisateur n'a été créé en base de donnée.";
        }

        $this->io->success($string);
    }
}
