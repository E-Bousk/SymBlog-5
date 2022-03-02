<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Article;
use App\Entity\Picture;
use App\Service\FileUploader;
use App\Form\CreateAnArticleFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ArticlesController extends AbstractController
{
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
        $this->denyAccessUnlessGranted('ROLE_USER');

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
                'user_roles' => $user->getRoles() // ‼ À ajouter dans la méthode« configureOptions » du 'form'
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
