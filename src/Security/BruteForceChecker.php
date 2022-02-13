<?php

namespace App\Security;

use App\Repository\AuthLogRepository;
use Symfony\Component\HttpFoundation\RequestStack;

class BruteForceChecker
{
    private AuthLogRepository $authLogRepository;
    private RequestStack $requestStack;

    public function __construct(AuthLogRepository $authLogRepository, RequestStack $requestStack)
    {
        $this->authLogRepository = $authLogRepository;
        $this->requestStack = $requestStack;
    }

    /**
     * Add a failed authentication attempt. Add blacklisting according on number of failed attempts.
     * 
     * @param string $emailEntered 
     * @param null|string $userIp 
     * @return void 
     */
    public function addFailedAuthAttempt(string $emailEntered, ?string $userIp): void
    {
        if ($this->authLogRepository->isBlacklistedWithNextAttemptFailure($emailEntered, $userIp)) {
            $this->authLogRepository->addFailedAuthAttempt($emailEntered, $userIp, true);
        } else {
            $this->authLogRepository->addFailedAuthAttempt($emailEntered, $userIp);
        }
    }

    /**
     * Return the end of blacklisting or null.
     * 
     * @return null|string 
     */
    public function getEndOfBlacklisting(): ?string
    {
        $request = $this->requestStack->getCurrentRequest();

        if (!$request) {
            return null;
        }

        $userIp = $request->getClientIp();
        $emailEntered = $request->get('email');

        return $this->authLogRepository->getEndOfBlacklisting($emailEntered, $userIp);
    }
}
