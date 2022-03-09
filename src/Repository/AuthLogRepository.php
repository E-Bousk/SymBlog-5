<?php

namespace App\Repository;

use App\Entity\AuthLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\MakerBundle\Str;

/**
 * @method AuthLog|null find($id, $lockMode = null, $lockVersion = null)
 * @method AuthLog|null findOneBy(array $criteria, array $orderBy = null)
 * @method AuthLog[]    findAll()
 * @method AuthLog[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AuthLogRepository extends ServiceEntityRepository
{
    public const BLACKLISTING_DELAY_IN_MINUTES = 15;
    public const MAX_FAILED_AUTH_ATTEMPTS = 5;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuthLog::class);
    }

    /**
     * Add failed authentication attempt.
     * 
     * @param string $emailEntered 
     * @param string $sessionId 
     * @param null|string $userIp 
     * @param bool $isBlacklisted Set to true if e-mail/user IP pair must be blacklisted.
     * @return void 
     */
    public function addFailedAuthAttempt(
        string $emailEntered,
        string $sessionId,
        ?string $userIp,
        bool $isBlacklisted = false
    ): void
    {
        $authAttempt = (new AuthLog($emailEntered, $sessionId, $userIp))->setIsSuccessfulAuth(false);

        if ($isBlacklisted) {
            $authAttempt->setStartOfBlacklisting(new \DateTimeImmutable())
                ->setEndOfBlacklisting(new \DateTimeImmutable(sprintf('+%d minutes', self::BLACKLISTING_DELAY_IN_MINUTES)))
            ;
        }

        $this->_em->persist($authAttempt);
        $this->_em->flush();
    }

    /**
     * Add successful authentication attempt.
     * Reset count of bad authentication attempt.
     * 
     * @param string $emailEntered 
     * @param string $sessionId 
     * @param null|string $userIp 
     * @param bool $isRememberMeAuth Set to true if user is authenticated with 'remember me'.
     * @return void 
     */
    public function addSuccessfulAuthAttempt(
        string $emailEntered,
        string $sessionId,
        ?string $userIp,
        bool $isRememberMeAuth = false
    ): void
    {
        $authAttempt = new AuthLog($emailEntered, $sessionId, $userIp);

        $authAttempt->setIsSuccessfulAuth(true)
            ->setIsRememberMeAuth($isRememberMeAuth)
        ;

        $this->_em->persist($authAttempt);
        $this->_em->flush();

        $this->resetFailedAuth($emailEntered, $userIp);
    }

    /**
     * Return number of recent failed authentication attempts.
     * 
     * @param string $emailEntered 
     * @param null|string $userIp 
     * @return int 
     */
    public function getRecentAttemptFailure(string $emailEntered, ?string $userIp): int
    {
        return $this->createQueryBuilder('af')
            ->select('COUNT(af)')
            ->where('af.authAttemptAt >= :datetime')
            ->andWhere('af.userIp = :userIp')
            ->andWhere('af.emailEntered = :emailEntered')
            ->andWhere('af.isSuccessfulAuth = false')
            ->setParameters([
                'datetime'     => new \DateTimeImmutable(sprintf('-%d minutes', self::BLACKLISTING_DELAY_IN_MINUTES)),
                'userIp'       => $userIp,
                'emailEntered' => $emailEntered
            ])
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /**
     * Return whether or not user will be blacklisted on the next failed attempt.
     * 
     * @param string $emailEntered 
     * @param null|string $userIp 
     * @return bool 
     */
    public function isBlacklistedWithNextAttemptFailure(string $emailEntered, ?string $userIp): bool
    {
        return $this->getRecentAttemptFailure($emailEntered, $userIp) >= self::MAX_FAILED_AUTH_ATTEMPTS - 1;
    }


    /**
     * Return the last blacklist entry of an e-mail/user IP pair if it exists.
     * 
     * @param string $emailEntered 
     * @param null|string $userIp 
     * @return null|AuthLog 
     */
    public function getEmailAndUserIpPairBlacklistedIfExists(string $emailEntered, ?string $userIp): ?AuthLog
    {
        return $this->createQueryBuilder('bl')
            ->select('bl')
            ->where('bl.userIp = :userIp')
            ->andWhere('bl.emailEntered =  :emailEntered')
            ->andWhere('bl.endOfBlacklisting IS NOT NULL')
            ->andWhere('bl.endOfBlacklisting >= :datetime')
            ->setParameters([
                'userIp'       => $userIp,
                'emailEntered' => $emailEntered,
                'datetime'     => new \DateTimeImmutable(sprintf('-%d minutes', self::BLACKLISTING_DELAY_IN_MINUTES)),
            ])
            ->orderBy('bl.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    } 

    /**
     * Return the end of blacklisting (rounded up to the next minute)
     * 
     * @param string $emailEntered 
     * @param null|string $userIp 
     * @return null|string The time (+ 1 minute) with format HHhMM
     */
    public function getEndOfBlacklisting(string $emailEntered, ?string $userIp): ?string
    {
        $blacklisting = $this->getEmailAndUserIpPairBlacklistedIfExists($emailEntered, $userIp);

        if (!$blacklisting || $blacklisting->getEndOfBlacklisting() === null) {
            return null;
        }

        return $blacklisting->getEndOfBlacklisting()->add(new \DateInterval("PT1M"))->format('H\hi');
    }

    /**
     * Reset bad authentication count.
     * 
     * @param string $emailEntered 
     * @param null|string $userIp 
     * @return void 
     */
    private function resetFailedAuth(string $emailEntered, ?string $userIp): void
    {
        $this->createQueryBuilder('rfb')
            ->delete('App\Entity\AuthLog', 'a')
            ->where('a.isSuccessfulAuth = false')
            ->andWhere('a.emailEntered =  :emailEntered')
            ->andWhere('a.userIp = :userIp')
            ->setParameters([
                'emailEntered' => $emailEntered,
                'userIp'       => $userIp,
            ])
            ->getQuery()
            ->execute()
        ;
    }

    public function updateAuthlog(String $email): void
    {
        $lastAuthentication = $this->findOneBy(
            [
                'emailEntered'     => $email,
                'isSuccessfulAuth' => true
            ],
            [
                'id' => 'DESC'
            ]
        );

        if ($lastAuthentication === null) {
            return;
        }

        $lastAuthentication->setDeauthenticatedAt(new \DateTimeImmutable());

        $this->_em->flush();
    }
}
