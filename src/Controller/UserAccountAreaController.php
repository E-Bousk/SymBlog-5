<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\ArticleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * @Route("/user/account/profile", name="app_user_account_profile_")
 */
class UserAccountAreaController extends AbstractController
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @Route("/", name="home", methods={"GET"})
     */
    public function home(ArticleRepository $articleRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var User $user */
        $user = $this->getUser();

        return $this->render('user_account_area/index.html.twig', [
            'user'                 => $user,
            'articlesCreatedCount' => $articleRepository->getCountOfArticlesCreated($user),
            'articlesPublished'    => $articleRepository->getCountOfArticlesPublished($user),
        ]);
    }

    /**
     * @Route("/add-ip", name="add_ip", methods={"GET"})
     */
    public function addUserIpToWhitelist(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if (!$request->isXmlHttpRequest()) {
            throw new HttpException(400, 'The header "X-Requested-With" is missing.');
        }

        /** @var User $user */
        $user = $this->getUser();
        $userIp = $request->getClientIp();

        $user->setWhitelistedIpAddresses($userIp);

        $this->em->flush();

        return $this->json([
            'message' => "Adresse IP ajoutée à la liste blanche.",
            'user_ip' => $userIp
        ]);
    }

    /**
     * @Route("/toogle-checking-ip", name="toogle_checking_ip", methods={"POST"})
     */
    public function toogleGuardCheckingIp(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if (!$request->isXmlHttpRequest()) {
            throw new HttpException(400, 'The header "X-Requested-With" is missing.');
        }

        $switchValue = $request->getContent();

        if (!in_array($switchValue, ['true', 'false'], true)) {
            throw new HttpException(400, 'Expected value is "true" or "false"');
        }

        /** @var User $user */
        $user = $this->getUser();

        // Retourne une vraie valeur booléenne (« $switchValue » est de type string)
        $isSwitchedOn = filter_var($switchValue, FILTER_VALIDATE_BOOLEAN);

        $user->setIsGuardCheckIp($isSwitchedOn);

        $this->em->flush();

        return $this->json([
            'isGuardCheckingIp' => $isSwitchedOn
        ]);
    }
}
