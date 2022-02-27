<?php

namespace App\Controller\Tests;

use App\Entity\Article;
use App\Entity\Picture;
use App\Repository\ArticleRepository;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

class TestParamConverter extends AbstractController
{
    /**
     * @Route("/get-article-by-id-without-doctrine-param-converter/{id<\d+>}")
     */
    public function getArticleByIdWithoutDoctrineParamConverter(int $id, ArticleRepository $articleRepository)
    {
        $article = $articleRepository->find($id);

        if ($article === null) {
            throw new NotFoundHttpException('404 error not found 😟');
        }

        dd($article);
    }

    /**
     * @Route("/get-article-by-slug-without-doctrine-param-converter/{slug}")
     */
    public function getArticleBySlugWithoutDoctrineParamConverter(string $slug, ArticleRepository $articleRepository)
    {
        $article = $articleRepository->findOneBy([
            'slug' => $slug
        ]);

        if ($article === null) {
            throw new NotFoundHttpException('404 error not found 😟');
        }

        dd($article);
    }

    /**
     * @Route("/get-article-by-id-with-doctrine-param-converter/{id<\d+>}")
     * 
     * // ‼ NOTE : Spécifie quel 'converter' utiliser, car le custom (« ArticleConverter ») prend la priorité ‼
     * @ParamConverter("article", converter="doctrine.orm")
     */
    public function getArticleByIdWithDoctrineParamConverter(Article $article)
    {
        dd($article);
    }

    /**
     * @Route("/get-article-by-slug-with-doctrine-param-converter/{slug}")
     * @ParamConverter("article", converter="doctrine.orm")
     */
    public function getArticleBySlugWithDoctrineParamConverter(Article $article)
    {
        dd($article);
    }

    /**
     * @Route("/get-article-by-custom-parameter-with-doctrine-param-converter/{article_slug}")
     * @ParamConverter(
     *      "article",
     *      options={
     *         "mapping": {
     *             "article_slug" = "slug"
     *         }
     *      },
     *      converter="doctrine.orm"
     * )
     */
    public function getArticleByCustomParameterWithDoctrineParamConverter(Article $article)
    {
        dd($article);
    }

    /**
     * @Route("/article-by-id/{id<\d+>}/picture-by-id/{picture_id<\d+>}")
     * @Entity(
     *      "picture",
     *      expr="repository.find(picture_id)"
     * )
     * @ParamConverter("article", converter="doctrine.orm")
     */
    public function getArticleByIdAndPictureByIdWithDoctrineParamConverter(Article $article, Picture $picture)
    {
        dd($article, $picture);
    }
   
    /**
     * @Route("/datetime/{startDate}")
     */
    public function getDateTimeImmutableObjectWithDateTimeParamCoverter(\DateTimeImmutable $startDate)
    {
        // /datetime/021-02-27T01:30:00
        dd($startDate);
    }
    
    /**
     * @Route("/get-last-article/{datetime}")
     * 
     * // 'converter' custom : 
     * @ParamConverter("article", converter="ArticleConverter")
     */
    public function getTheLastArticleCreatedOnThatDay(?Article $article)
    {
        // /datetime/2021-02-28T15:00:00
        dd($article);
    }
}
