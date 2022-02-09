<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\Article;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @method Article|null find($id, $lockMode = null, $lockVersion = null)
 * @method Article|null findOneBy(array $criteria, array $orderBy = null)
 * @method Article[]    findAll()
 * @method Article[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ArticleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Article::class);
    }

    public function getCountOfArticlesCreated(User $user): int
    {
        return $this->createQueryBuilder('ac')
            ->select('COUNT(ac)')
            ->innerJoin('App\Entity\Author', 'a', Join::WITH, 'a.id = ac.author')
            ->innerJoin('App\Entity\User', 'u', Join::WITH, 'a.id = u.author')
            ->where('u.id = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    public function getCountOfArticlesPublished(User $user): int
    {
        return $this->createQueryBuilder('ap')
            ->select('COUNT(ap)')
            ->innerJoin('App\Entity\Author', 'a', Join::WITH, 'a.id = ap.author')
            ->innerJoin('App\Entity\User', 'u', Join::WITH, 'a.id = u.author')
            ->where('u.id = :user')
            ->andWhere('ap.isPublished = true')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
