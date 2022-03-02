<?php

namespace App\EventListener;

use App\Entity\Article;
use App\Entity\User;
use Symfony\Component\Security\Core\Security;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Component\String\Slugger\SluggerInterface;

class ArticleEntityListener
{
    private Security $security;
    private SluggerInterface $slugger;

    public function __construct(Security $security, SluggerInterface $slugger)
    {
        $this->security = $security;
        $this->slugger = $slugger;
    }

    public function prePersist(Article $article, LifecycleEventArgs $args):void
    {
        $user = $this->security->getUser();

        if ($user === null) {
            throw new \LogicException('User cannot be null here...');
        }

        /** @var User $user $author */
        $author = $user->getAuthor();

        if ($author === null) {
            throw new \LogicException('User is not an author...');           
        }

        $article
            ->setAuthor($author)
            ->setCreatedAt(new \DateTimeImmutable())
            ->setSlug($this->getArticleSlug($article))
        ;
    }

    public function getArticleSlug(Article $article): string
    {
        $slug = mb_strtolower(sprintf('%s-%s', $article->getTitle(), time()), 'UTF-8');

        return $this->slugger->slug($slug);
    }
}
