<?php

namespace App\Controller;

use App\Entity\User;
use App\Utils\LogoutUserTrait;
use App\Form\ResetPasswordType;
use App\Repository\ArticleRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Security\AskForPasswordConfirmation;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @Route("/user/account/profile", name="app_user_account_profile_")
 * @IsGranted("ROLE_USER")
 */
class UserAccountAreaController extends AbstractController
{
    use LogoutUserTrait;
    
    private EntityManagerInterface $em;
    private Session $session;
    private AskForPasswordConfirmation $askForPasswordConfirmation;

    public function __construct(EntityManagerInterface $em, Session $session, AskForPasswordConfirmation $askForPasswordConfirmation)
    {
        $this->em = $em;
        $this->session = $session;
        $this->askForPasswordConfirmation = $askForPasswordConfirmation;
    }

    /**
     * @Route("/", name="home", methods={"GET"})
     */
    public function home(ArticleRepository $articleRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $form = $this->createForm(ResetPasswordType::class, null, [
            'action' => $this->generateUrl('app_user_account_profile_modify_password'),
            'attr' => [
                'class' => 'mt-3'
            ]
        ]);

        return $this->render('user_account_area/index.html.twig', [
            'user'                 => $user,
            'articlesCreatedCount' => $articleRepository->getCountOfArticlesCreated($user),
            'articlesPublished'    => $articleRepository->getCountOfArticlesPublished($user),
            'modifyPasswordForm'   => $form->createView()
        ]);
    }

    /**
     * @Route("/add-current-ip", name="add_current_ip", methods={"GET", "POST"})
     */
    public function addCurrentUserIpToWhitelist(Request $request): JsonResponse
    {
        if (!$request->isXmlHttpRequest()) {
            throw new HttpException(400, 'The header "X-Requested-With" is missing.');
        }

        $this->askForPasswordConfirmation->ask();

        /** @var User $user */
        $user = $this->getUser();
        $userIp = $request->getClientIp();

        $user->setWhitelistedIpAddresses(array_unique(array_merge($user->getWhitelistedIpAddresses(), [$userIp])));

        $this->em->flush();

        return $this->json([
            'is_password_confirmed' => true,
            'user_ip'               => implode(' | ', $user->getWhitelistedIpAddresses()),
        ]);
    }

    /**
     * @Route("/toggle-checking-ip", name="toggle_checking_ip", methods={"GET", "POST"})
     */
    public function toogleGuardCheckingIp(Request $request): JsonResponse
    {
        if (!$request->isXmlHttpRequest()) {
            throw new HttpException(400, 'The header "X-Requested-With" is missing.');
        }

        if ($request->headers->get('Switch-Guard-Checking-Ip-Value')) {
            $switchValue = $request->getContent();

            if (!in_array($switchValue, ['true', 'false'], true)) {
                throw new HttpException(400, 'Expected value is "true" or "false"');
            }

            $this->session->set('Switch-Guard-Checking-Ip-Value', $switchValue);
        }

        $this->askForPasswordConfirmation->ask();

        /** @var User $user */
        $user = $this->getUser();

        $switchValue = $this->session->get('Switch-Guard-Checking-Ip-Value');

        if ($switchValue === null) {
            throw new HttpException(400, 'Toggle switch value is missing. Did you forget the « Switch-Guard-Checking-Ip-Value » header while sending your request ?');
        }

        $this->session->remove('Switch-Guard-Checking-Ip-Value');

        // Retourne une vraie valeur booléenne (« $switchValue » est de type string)
        $isSwitchedOn = filter_var($switchValue, FILTER_VALIDATE_BOOLEAN);

        $user->setIsGuardCheckIp($isSwitchedOn);

        $this->em->flush();

        return $this->json([
            'is_password_confirmed' => true,
            'is_guard_checking_ip'  => $isSwitchedOn
        ]);
    }

    /**
     * @Route("/edit-user-ip-whitelist", name="edit_user_ip_whitelist", methods={"POST"})
     */
    public function editUserIpWhitelist(Request $request): JsonResponse
    {
        if (!$request->isXmlHttpRequest()) {
            throw new HttpException(400, 'The header "X-Requested-With" is missing.');
        }

        if ($request->headers->get('User-Ip-Entered')) {
            $data = $request->getContent();

            if (!is_string($data)) {
                throw new HttpException(400, "L'adresse IP saisie est incorrecte.");
            }

            $userIpEnteredArray = array_filter(explode(',', $data), fn($userIpEntered) => filter_var($userIpEntered, FILTER_VALIDATE_IP));

            $this->session->set('User-Ip-Entered', $userIpEnteredArray);
        }

        $this->askForPasswordConfirmation->ask();

        /** @var User $user */
        $user = $this->getUser();

        $userIpEnteredArray = $this->session->get('User-Ip-Entered');

        if ($userIpEnteredArray === null) {
            throw new HttpException(400, "Il n'y a pas d'adresse IP à ajouter. Avez-vous oublié le header « User-Ip-Entered » lors de l'envoie de votre requête ?");
        }

        $this->session->remove('User-Ip-Entered');

        $user->setWhitelistedIpAddresses($userIpEnteredArray);

        $this->em->flush();

        return $this->json([
            'is_password_confirmed' => true,
            'user_ip'               => implode(' | ', $userIpEnteredArray)
        ]);
    }

    /**
     * @Route("/modify-password", name="modify_password", methods={"GET", "POST"})
     */
    public function modifyPassword(
        Request $request,
        TokenStorageInterface $tokenStorage,
        UserPasswordEncoderInterface $encoder,
        ValidatorInterface $validator
    ): JsonResponse
    {
        if (!$request->isXmlHttpRequest()) {
            throw new HttpException(400, 'The header "X-Requested-With" is missing.');
        }

        if ($request->headers->get('Password-Modification')) {
            /** @var string $json */
            $json = $request->getContent();

            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

            if (!array_key_exists('password', $data)) {
                throw new HttpException(400, "le mot de passe doit être saisi.");
            }

            $passwordEntered = $data['password'];

            $violations = $validator->validatePropertyValue(User::class, 'password', $passwordEntered);

            if (count($violations) !== 0) {
                throw new HttpException(400, $violations[0]->getMessage());
            }

            $this->session->set('PASSWORD-ENTERED-MODIFICATION', $passwordEntered);
        }

        $this->askForPasswordConfirmation->ask();

        $passwordEntered = $this->session->get('PASSWORD-ENTERED-MODIFICATION');

        if ($passwordEntered === null) {
            throw new HttpException(400, 'Missing password. Did you forget the "Password-Modification" header when sending request ?');
        }
        
        /** @var User $user */
        $user = $this->getUser();

        $user->setPassword($encoder->encodePassword($user, $passwordEntered));

        $this->em->flush();

        $response = $this->logoutUser(
            $request,
            $this->session,
            $tokenStorage,
            'success',
            'Votre mot de passe a bien été modifié, vous pouvez à présent vous connecter.',
            true,
            true
        );

        return $response;
    }
}
