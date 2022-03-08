<?php

namespace App\Security\Voter;

use App\Entity\Article;
use App\Entity\User;
use PhpParser\Node\Stmt\Const_;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class ArticleVoter extends Voter
{
    public const CREATE = 'create';
    public const EDIT = 'edit';
    public const CAN_CREATE_ROLE = 'ROLE_WRITER';
    public const READ = 'read';

    private RoleHierarchyInterface $roleHierarchy; // Note : pour changer de « security isGranted() »

    public function __construct(RoleHierarchyInterface $roleHierarchy)
    {
        $this->roleHierarchy = $roleHierarchy;
    }

    protected function supports(string $attribute, $subject): bool
    {
        if (
            in_array(
                $attribute,
                [
                    self::CREATE,
                    self::EDIT,
                    self::READ
                ]
            ) === false
        ) {
            return false;
        }

        if ($subject !== null && $subject instanceof Article === false) {
            return false;
        }

        return true;
    }
    
    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        // Note : User must be logged in ... If not, deny access
        if ($user instanceof User === false) {
            return false;
        }

        if ($attribute === self::CREATE) {
            return $this->canCreate($user);
        }

        if ($attribute === self::EDIT && $subject instanceof Article === true) {
            return $this->canEdit($subject, $user);
        }
        
        if ($attribute === self::READ) {
            return $this->canRead();
        }

        throw new \LogicException('The second argument passed to « denyAccessUnlessGranted() » method must be an instance of « Article ».');
    }

    private function canCreate(User $user): bool
    {
        return in_array(self::CAN_CREATE_ROLE, $this->roleHierarchy->getReachableRoleNames($user->getRoles()), true);
    }

    private function canEdit(Article $article, User $user): bool
    {
        return $article->getAuthor() === $user->getAuthor();
    }

    private function canRead(): bool
    {
        return true;
    }
}
