<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class ApiRandomUsersFixtures extends Fixture
{
    private HttpClientInterface $httpClient;
    private ObjectManager $manager;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public function load(ObjectManager $manager): void
    {
        $this->manager = $manager;

        $this->generateUsers(5);
    }

    private function generateUsers(int $number): void
    {
        $randomEmails = $this->fetchRandomUserEmail($number);

        for ($i = 0; $i < $number; $i++) {
            $user = new User();
            $user->setEmail($randomEmails[$i]['email'])
                ->setPassword('password') // Le mot de passe est chiffré avec un eventsubscriber (et services.yaml)
                ->setIsVerified((bool)random_int(0, 1))
            ;
            $this->manager->persist($user);
            $this->manager->flush();
        }
    }

    private function fetchRandomUserEmail(int $numberOfResult = 1, string $nationality = 'fr'): array
    {
        $response = $this->httpClient->request('GET', 'https://randomuser.me/api/', [
            'headers' => [
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json'
            ],
            'query' => [
                'format'  => 'json',
                'inc'     => 'email',
                'nat'     => $nationality,
                'results' => $numberOfResult
            ]
        ]);

        $data = $response->toArray();

        if (!array_key_exists('results', $data)) {
            throw new ServiceUnavailableHttpException("La clé « results » n'existe pas dans le tableau de données récupéré. La réponse-type fournie par l'API a peut-être été modifiée.");
        }

        return $data['results'];
    }
}
