<?php

// =====================================================
// User.php — Entité utilisateur
// Gère l'authentification et le profil utilisateur
// avec rôles métier : admin, manager, dev, client
// =====================================================

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    // Rôles Symfony (ROLE_USER, ROLE_ADMIN)
    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    // Nom affiché de l'utilisateur
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $name = null;

    // Rôle métier : admin, manager, dev, client
    #[ORM\Column(length: 20, nullable: true)]
    private ?string $role = 'dev';

    // Avatar — URL ou nom du fichier
    #[ORM\Column(length: 500, nullable: true)]
    private ?string $avatar = null;

    // Date d'inscription
    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    // Token de réinitialisation mot de passe
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $resetToken = null;

    // Code 2FA par email
    #[ORM\Column(length: 10, nullable: true)]
    private ?string $twoFactorCode = null;

    // Expiration du code 2FA
    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $twoFactorExpiresAt = null;

    // 2FA activé ou non
    #[ORM\Column(type: 'boolean')]
    private bool $twoFactorEnabled = false;

    // Expiration du token de réinitialisation
    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $resetTokenExpiry = null;

    // Plan de l'utilisateur (free, pro, enterprise)
    #[ORM\Column(length: 20)]
    private string $plan = 'free';

    // ID client Stripe — pour accéder au portail de facturation
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stripeCustomerId = null;

    // Compte actif ou non
    #[ORM\Column(type: 'boolean')]
    private bool $isActive = true;

    // Refresh token — pour renouveler le JWT sans reconnexion
    #[ORM\Column(length: 500, nullable: true)]
    private ?string $refreshToken = null;

    // Expiration du refresh token — valide 7 jours
    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $refreshTokenExpiry = null;

    // Token temporaire pour télécharger une facture (valide 5 minutes)
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $downloadToken = null;

    // Expiration du token de téléchargement
    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $downloadTokenExpiry = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        if ('admin' === $this->role) {
            $roles[] = 'ROLE_ADMIN';
        }

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(?string $role): static
    {
        $this->role = $role;

        return $this;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): static
    {
        $this->avatar = $avatar;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getResetToken(): ?string
    {
        return $this->resetToken;
    }

    public function setResetToken(?string $resetToken): static
    {
        $this->resetToken = $resetToken;

        return $this;
    }

    public function getResetTokenExpiry(): ?\DateTimeInterface
    {
        return $this->resetTokenExpiry;
    }

    public function setResetTokenExpiry(?\DateTimeInterface $expiry): static
    {
        $this->resetTokenExpiry = $expiry;

        return $this;
    }

    public function getTwoFactorCode(): ?string
    {
        return $this->twoFactorCode;
    }

    public function setTwoFactorCode(?string $code): static
    {
        $this->twoFactorCode = $code;

        return $this;
    }

    public function getTwoFactorExpiresAt(): ?\DateTimeInterface
    {
        return $this->twoFactorExpiresAt;
    }

    public function setTwoFactorExpiresAt(?\DateTimeInterface $expiresAt): static
    {
        $this->twoFactorExpiresAt = $expiresAt;

        return $this;
    }

    public function isTwoFactorEnabled(): bool
    {
        return $this->twoFactorEnabled;
    }

    public function setTwoFactorEnabled(bool $enabled): static
    {
        $this->twoFactorEnabled = $enabled;

        return $this;
    }

    public function getPlan(): string
    {
        return $this->plan;
    }

    public function setPlan(string $plan): static
    {
        $this->plan = $plan;

        return $this;
    }

    public function getStripeCustomerId(): ?string
    {
        return $this->stripeCustomerId;
    }

    public function setStripeCustomerId(?string $id): static
    {
        $this->stripeCustomerId = $id;

        return $this;
    }

    public function getIsActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getRefreshToken(): ?string
    {
        return $this->refreshToken;
    }

    public function setRefreshToken(?string $refreshToken): static
    {
        $this->refreshToken = $refreshToken;

        return $this;
    }

    public function getRefreshTokenExpiry(): ?\DateTimeInterface
    {
        return $this->refreshTokenExpiry;
    }

    public function setRefreshTokenExpiry(?\DateTimeInterface $expiry): static
    {
        $this->refreshTokenExpiry = $expiry;

        return $this;
    }

    public function getDownloadToken(): ?string
    {
        return $this->downloadToken;
    }

    public function setDownloadToken(?string $token): static
    {
        $this->downloadToken = $token;

        return $this;
    }

    public function getDownloadTokenExpiry(): ?\DateTimeInterface
    {
        return $this->downloadTokenExpiry;
    }

    public function setDownloadTokenExpiry(?\DateTimeInterface $expiry): static
    {
        $this->downloadTokenExpiry = $expiry;

        return $this;
    }

    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0".self::class."\0password"] = hash('crc32c', $this->password);

        return $data;
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
    }
}
