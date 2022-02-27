<?php

namespace App\ParamConverter;

use App\Repository\ArticleRepository;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;

class ArticleConverter implements ParamConverterInterface
{
    protected ArticleRepository $articleRepository;

    public function __construct(ArticleRepository $articleRepository)
    {
        $this->articleRepository = $articleRepository;
    }

    public function supports(ParamConverter $configuration): bool
    {
        // dd($configuration);
        return $configuration->getClass() === 'App\Entity\Article';
    }

    public function apply(Request $request, ParamConverter $configuration): bool
    {
        $dateTimeString = $request->get('datetime');

        if (($dateTimeImmutableObject = \DateTimeImmutable::createFromFormat('d-m-Y', $dateTimeString)) === false) {
            throw new NotFoundHttpException('404 error not found 😟');
        }

        try {
            $article = $this->articleRepository
                ->createQueryBuilder('a')
                ->where('a.createdAt BETWEEN :dateTimeMin AND :dateTimeMax')
                ->setParameters([
                    'dateTimeMin' => $dateTimeImmutableObject->format('Y-m-d 00:00:00'),
                    'dateTimeMax' => $dateTimeImmutableObject->format('Y-m-d 23:59:59')
                ])
                ->orderBy('a.createdAt', 'DESC')
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult()
            ;

        } catch (NonUniqueResultException $error) {
            throw new NotFoundHttpException('404 error not found 😟');
        }

        if ($article === null) {
            throw new NotFoundHttpException('404 error not found 😟');
        }

        $request->attributes->set(
            $configuration->getName(),
            $article
        );

        return true;
    }
}