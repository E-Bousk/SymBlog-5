<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Article;
use App\Entity\Picture;
use App\Service\FileUploader;
use App\Security\Voter\ArticleVoter;
use App\Form\CreateAnArticleFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\Translation\TranslatorInterface;

// use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ArticlesController extends AbstractController
{
    /**
     * @Route(
     *      {"en": "/articles/read/{slug}", "fr": "/articles/lire/{slug}"},
     *      name="app_article_read",
     *      methods={"GET"},
     *      defaults={"_public_access": false}
     * )
     * 
     * @param Article $article 
     * @param TranslatorInterface $translator 
     * @return Response 
     */
    public function readArticle(Article $article, TranslatorInterface $translator): Response
    {
        $this->denyAccessUnlessGranted(ArticleVoter::READ);

        $user = $this->getUser();

        $flashMessage1 = $translator->trans(
            'articles.read.info',
            compact('user'),
            'flash_messages'
        );

        $flashMessage2 = $translator->trans(
            'articles.read.success',
            [],
            'flash_messages'
        );

        $this->addFlash('info', $flashMessage1);
        $this->addFlash('success', $flashMessage2);

        return $this->render('articles/read.html.twig', compact('article'));
    }

    /**
     * @Route("/articles/create", name="app_articles_create", methods={"GET", "POST"}, defaults={"_public_access": false})
     * 
     * @param EntityManagerInterface $entityManager 
     * @param FileUploader $fileUploader 
     * @param Request $request 
     * @return Response
     */
    public function createArticle(
        EntityManagerInterface $entityManager,
        FileUploader $fileUploader,
        Request $request
    ): Response
    {
        // $this->denyAccessUnlessGranted('ROLE_USER');
        // Remplacé par :
        $this->denyAccessUnlessGranted(ArticleVoter::CREATE);

        $user = $this->getUser();

        if ($user === null) {
            throw new \LogicException('User cannot be null here...');
        }

        $article = new Article();

        /** @var User $user $form */
        $form = $this->createForm(
            CreateAnArticleFormType::class,
            $article,
            [
                'user_roles' => $user->getRoles() // ‼ À ajouter dans la méthode « configureOptions » du 'form'
            ]
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->persistArticle(
                $form,
                $article,
                $entityManager
            );

            $this->persistPicture(
                $form,
                $article,
                $fileUploader,
                $entityManager
            );

            $entityManager->flush();

            // $this->addFlash('success', 'Article créé !');

            return $this->redirectToRoute('app_user_account_profile_home');
        }

        return $this->render('articles/create.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/articles/edit/{slug}", name="app_article_edit", methods={"GET", "POST"}, defaults={"_public_access": false})
     * 
     * @param Article $article 
     * @param EntityManagerInterface $entityManager 
     * @param FileUploader $fileUploader 
     * @param Request $request 
     * @return Response 
     */
    public function editArticle(
        Article $article,
        EntityManagerInterface $entityManager,
        FileUploader $fileUploader,
        Request $request
    ): Response
    {
        // $this->denyAccessUnlessGranted('ROLE_USER');
        // Remplacé par :
        $this->denyAccessUnlessGranted(ArticleVoter::EDIT, $article);


        $user = $this->getUser();

        if ($user === null) {
            throw new \LogicException('User cannot be null here...');
        }

        // Remplacé par « Voter »
        // $articleAuthor = $article->getAuthor();
        // /** @var User $user */
        // $currentAuthor = $user->getAuthor();
        // if ($articleAuthor !== $currentAuthor) {
        //     throw new AccessDeniedException('WTF ! What are you trying to do ?!');
        // }

        /** @var User $user $form */
        $form = $this->createForm(
            CreateAnArticleFormType::class,
            $article,
            [
                'is_edition' => true // ‼ À ajouter dans la méthode « configureOptions » du 'form'
            ]
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('picture')->getData() !== null) {
                $this->persistPicture(
                    $form,
                    $article,
                    $fileUploader,
                    $entityManager
                );
            }

            $entityManager->flush();

            return $this->redirectToRoute('app_user_account_profile_home');
        }

        return $this->render('articles/create.html.twig', [
            'form'      => $form->createView(),
            'isEdition' => true
        ]);
    }

    private function persistArticle(
        FormInterface $form,
        Article $article,
        EntityManagerInterface $entityManager
    ): void
    {
        $clickedButton = $form->getClickedButton();

        if ($clickedButton === null) {
            throw new \LogicException('No button in Formtype');
        }

        $clickedButtonLabel = $clickedButton->getConfig()->getOptions()['label'];

        if ($clickedButtonLabel === 'Publier un article') {
            $article
                ->setIsPublished(true)
                ->setPublishedAt(new \DateTimeImmutable());
        } elseif ($clickedButtonLabel === 'Sauvegarder cet article en brouillon') {
            $article->setIsPublished(false);
        } else {
            throw new \LogicException('A little joker had fun modifying labels ...');
        }

        $entityManager->persist($article);
    }

    private function persistPicture(
        FormInterface $form,
        Article $article,
        FileUploader $fileUploader,
        EntityManagerInterface $entityManager
    ): void
    {
        $uploadedFile = $form->get('picture')->getData();

        [
            'fileName' => $pictureName,
            'filePath' => $picturePath
        ] = $fileUploader->upload($uploadedFile);

        $picture = new Picture();

        $picture->setArticle($article)
            ->setPictureName($pictureName)
            ->setPicturePath($picturePath)
        ;

        $entityManager->persist($picture);
    }
}
