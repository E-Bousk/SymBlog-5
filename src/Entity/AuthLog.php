<?php

namespace App\Entity;

use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\AuthLogRepository;

/**
 * @ORM\Entity(repositoryClass=AuthLogRepository::class)
 * @Table(name="auth_logs")
 */
class AuthLog
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\Column(type="datetime_immutable")
     */
    private \DateTimeImmutable $authAttemptAt;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $userIp;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $emailEntered;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $isSuccessfulAuth;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    private ?\DateTimeImmutable $startOfBlacklisting;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    private ?\DateTimeImmutable $endOfBlacklisting;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $isRememberMeAuth;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    private ?\DateTimeImmutable $deauthenticatedAt;

    public function __construct(string $emailEntered, ?string $userIp)
    {
        $this->emailEntered = $emailEntered;
        $this->userIp = $userIp;
        $this->authAttemptAt = new \DateTimeImmutable();
        $this->isRememberMeAuth = false;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAuthAttemptAt(): ?\DateTimeImmutable
    {
        return $this->authAttemptAt;
    }

    public function setAuthAttemptAt(\DateTimeImmutable $authAttemptAt): self
    {
        $this->authAttemptAt = $authAttemptAt;

        return $this;
    }

    public function getUserIp(): ?string
    {
        return $this->userIp;
    }

    public function setUserIp(?string $userIp): self
    {
        $this->userIp = $userIp;

        return $this;
    }

    public function getEmailEntered(): ?string
    {
        return $this->emailEntered;
    }

    public function setEmailEntered(string $emailEntered): self
    {
        $this->emailEntered = $emailEntered;

        return $this;
    }

    public function getIsSuccessfulAuth(): ?bool
    {
        return $this->isSuccessfulAuth;
    }

    public function setIsSuccessfulAuth(bool $isSuccessfulAuth): self
    {
        $this->isSuccessfulAuth = $isSuccessfulAuth;

        return $this;
    }

    public function getStartOfBlacklisting(): ?\DateTimeImmutable
    {
        return $this->startOfBlacklisting;
    }

    public function setStartOfBlacklisting(?\DateTimeImmutable $startOfBlacklisting): self
    {
        $this->startOfBlacklisting = $startOfBlacklisting;

        return $this;
    }

    public function getEndOfBlacklisting(): ?\DateTimeImmutable
    {
        return $this->endOfBlacklisting;
    }

    public function setEndOfBlacklisting(?\DateTimeImmutable $endOfBlacklisting): self
    {
        $this->endOfBlacklisting = $endOfBlacklisting;

        return $this;
    }

    public function getIsRememberMeAuth(): ?bool
    {
        return $this->isRememberMeAuth;
    }

    public function setIsRememberMeAuth(bool $isRememberMeAuth): self
    {
        $this->isRememberMeAuth = $isRememberMeAuth;

        return $this;
    }

    public function getDeauthenticatedAt(): ?\DateTimeImmutable
    {
        return $this->deauthenticatedAt;
    }

    public function setDeauthenticatedAt(?\DateTimeImmutable $deauthenticatedAt): self
    {
        $this->deauthenticatedAt = $deauthenticatedAt;

        return $this;
    }
}
