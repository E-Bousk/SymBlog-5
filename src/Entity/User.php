<?php

namespace App\Entity;

use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\UserRepository;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass=UserRepository::class)
 * @Table(name="users")
 * @UniqueEntity(fields={"email"}, message="Impossible de créer un compte utilisateur avec cette adresse e-mail.")
 */
class User implements UserInterface
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */ 
    private int $id;

    /**
     * @ORM\Column(type="string", length=180, unique=true)
     * @Assert\NotBlank(message="Veuillez saisir votre adresse e-mail.")
     * @Assert\Email(message="L'adresse e-mail {{ value }} n'est pas valide.")
     */
    private string $email;

    /**
     * @ORM\Column(type="json")
     * @var array<string>
     */
    private array $roles = [];

    /**
     * @var string The hashed password
     * @ORM\Column(type="string")
     * @Assert\NotBlank(message="Veuillez saisir un mot de passe.")
     * @Assert\NotCompromisedPassword(message="Ce mot de passe a été divulgué lors d'une fuite de données. Pour votre sécurité, veuillez en utiliser un autre.")
     * @Assert\Regex(pattern="/^(?=.*[a-zà-ÿ])(?=.*[A-ZÀ-Ý])(?=.*[0-9])(?=.*[^a-zà-ÿA-ZÀ-Ý0-9]).{12,}$/", message="Le mot de passe doit contenir au minimum 12 caractères dont 1 lettre majuscule, 1 lettre minuscule, 1 chiffre, et 1 caractère spécial.")
     */
    private string $password;

    /**
     * @ORM\OneToOne(targetEntity=Author::class, cascade={"persist", "remove"})
     */
    private ?Author $author;

    /**
     * @ORM\Column(type="datetime_immutable")
     */
    private \DateTimeImmutable $registeredAt;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    private ?\DateTimeImmutable $accountMustBeVerifiedBefore;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $registrationToken;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $isVerified;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    private ?\DateTimeImmutable $accountVerifiedAt;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $forgotPasswordToken;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    private ?\DateTimeImmutable $forgotPasswordTokenRequestedAt;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    private ?\DateTimeImmutable $forgotPasswordTokenMustBeVerifiedBefore;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    private ?\DateTimeImmutable $forgotPasswordTokenVerifiedAt;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $isGuardCheckIp;

    /**
     * @ORM\Column(type="json")
     * @var array<string|null>
     */
    private array $whitelistedIpAddresses = [];

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $discordId;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $discordUsername;

    public function __construct()
    {
        $this->roles = ['ROLE_USER'];
        $this->registeredAt = new \DateTimeImmutable();
        $this->isGuardCheckIp = false;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUsername(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * Set User's role.
     * 
     * @param array<string> $roles 
     * @return User 
     */
    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Returning a salt is only needed, if you are not using a modern
     * hashing algorithm (e.g. bcrypt or sodium) in your security.yaml.
     *
     * @see UserInterface
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getAuthor(): ?Author
    {
        return $this->author;
    }

    public function setAuthor(?Author $author): self
    {
        $this->author = $author;

        return $this;
    }

    public function getRegisteredAt(): \DateTimeImmutable
    {
        return $this->registeredAt;
    }

    public function setRegisteredAt(\DateTimeImmutable $registeredAt): self
    {
        $this->registeredAt = $registeredAt;

        return $this;
    }

    public function getAccountMustBeVerifiedBefore(): ?\DateTimeImmutable
    {
        return $this->accountMustBeVerifiedBefore;
    }

    public function setAccountMustBeVerifiedBefore(?\DateTimeImmutable $accountMustBeVerifiedBefore): self
    {
        $this->accountMustBeVerifiedBefore = $accountMustBeVerifiedBefore;

        return $this;
    }

    public function getRegistrationToken(): ?string
    {
        return $this->registrationToken;
    }

    public function setRegistrationToken(?string $registrationToken): self
    {
        $this->registrationToken = $registrationToken;

        return $this;
    }

    public function getIsVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): self
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    public function getAccountVerifiedAt(): ?\DateTimeImmutable
    {
        return $this->accountVerifiedAt;
    }

    public function setAccountVerifiedAt(?\DateTimeImmutable $accountVerifiedAt): self
    {
        $this->accountVerifiedAt = $accountVerifiedAt;

        return $this;
    }

    public function getForgotPasswordToken(): ?string
    {
        return $this->forgotPasswordToken;
    }

    public function setForgotPasswordToken(?string $forgotPasswordToken): self
    {
        $this->forgotPasswordToken = $forgotPasswordToken;

        return $this;
    }

    public function getForgotPasswordTokenRequestedAt(): ?\DateTimeImmutable
    {
        return $this->forgotPasswordTokenRequestedAt;
    }

    public function setForgotPasswordTokenRequestedAt(?\DateTimeImmutable $forgotPasswordTokenRequestedAt): self
    {
        $this->forgotPasswordTokenRequestedAt = $forgotPasswordTokenRequestedAt;

        return $this;
    }

    public function getForgotPasswordTokenMustBeVerifiedBefore(): ?\DateTimeImmutable
    {
        return $this->forgotPasswordTokenMustBeVerifiedBefore;
    }

    public function setForgotPasswordTokenMustBeVerifiedBefore(?\DateTimeImmutable $forgotPasswordTokenMustBeVerifiedBefore): self
    {
        $this->forgotPasswordTokenMustBeVerifiedBefore = $forgotPasswordTokenMustBeVerifiedBefore;

        return $this;
    }

    public function getForgotPasswordTokenVerifiedAt(): ?\DateTimeImmutable
    {
        return $this->forgotPasswordTokenVerifiedAt;
    }

    public function setForgotPasswordTokenVerifiedAt(?\DateTimeImmutable $forgotPasswordTokenVerifiedAt): self
    {
        $this->forgotPasswordTokenVerifiedAt = $forgotPasswordTokenVerifiedAt;

        return $this;
    }

    public function getIsGuardCheckIp(): bool
    {
        return $this->isGuardCheckIp;
    }

    public function setIsGuardCheckIp(bool $isGuardCheckIp): self
    {
        $this->isGuardCheckIp = $isGuardCheckIp;

        return $this;
    }

    /** @return array<string|null> */
    public function getWhitelistedIpAddresses(): array
    {
        return $this->whitelistedIpAddresses;
    }

    /** @param array<string|null> $whitelistedIpAddresses */
    public function setWhitelistedIpAddresses(array $whitelistedIpAddresses): self
    {
        $this->whitelistedIpAddresses = $whitelistedIpAddresses;

        return $this;
    }

    public function getDiscorId(): ?string
    {
        return $this->discordId;
    }

    public function setDiscorId(?string $discordId): self
    {
        $this->discordId = $discordId;

        return $this;
    }

    public function getDiscordUsername(): ?string
    {
        return $this->discordUsername;
    }

    public function setDiscordUsername(?string $discordUsername): self
    {
        $this->discordUsername = $discordUsername;

        return $this;
    }

    public function __toString()
    {
        return $this->email;
    }
}
