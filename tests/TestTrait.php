<?php

namespace App\Tests;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

trait TestTrait
{
    private function createClientAndFollowRedirects(): KernelBrowser
    {
        $client = static::createClient();

        $client->followRedirects();

        return $client;
    }

    private function truncateTableBeforeTest(string $table): void
    {
        $kernel = self::bootKernel();

        $entityManager = $kernel->getContainer()->get('doctrine')->getManager();

        $connection = $entityManager->getConnection()
                                    ->executeQuery("TRUNCATE TABLE `{$table}`")
        ;
        
        $entityManager->getConnection()->close();
    }

    private function clientGoesOnThisPage(string $page): KernelBrowser
    {
        $client = $this->createClientAndFollowRedirects();

        $client->request('GET', $page);

        return $client;
    }

    private function getUriList(
        KernelBrowser $client,
        bool $isPublicRoute,
        ?string $roleRequired = null
    ): array
    {
        $router = $client->getContainer()->get('router');

        $routesWithAllParameters = $router->getRouteCollection()->all();

        $uri = [];

        foreach ($routesWithAllParameters as $routeName => $routeWithAllParameters) 
        {
            if (
                in_array('GET', $routeWithAllParameters->getMethods())
                && $routeWithAllParameters->getDefault('_public_access') === $isPublicRoute
                && $roleRequired === $routeWithAllParameters->getDefault('_role_required')
            ) {
                $uri[] = $routeWithAllParameters->getPath();
            }
        }

        return $uri;
    }

    private function authenticateAnUserWithSpecificRole(KernelBrowser $client, string $role): void
    {
        if (
            $role !== 'ROLE_ADMIN'
            && $role !== 'ROLE_USER'
            && $role !== 'ROLE_UNKNOWN'
        ) {
            throw new \LogicException('The specified role MUST be « ROLE_ », « ROLE_USER », « ROLE_UNKNOW ».');
        }

        if ($role === 'ROLE_ADMIN') {
            $email = 'role_admin@test.fr';
        } else if ($role === 'ROLE_USER') {
            $email = 'role_user@test.fr';
        } else {
            $email = 'role_unknown@test.fr';
        }

        $userRepository = static::$container->get(UserRepository::class);

        $user = $userRepository->findOneBy([
            'email' => $email
        ]);

        if ($user === null) {
            throw new \LogicException('User not found');
        }

        $client->loginUser($user);
    }
}
