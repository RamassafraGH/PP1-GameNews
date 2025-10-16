<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[UniqueEntity(fields: ['email'], message: 'Este correo ya está registrado')]
#[UniqueEntity(fields: ['username'], message: 'Este nombre de usuario ya existe')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank(message: 'El correo electrónico es obligatorio')]
    #[Assert\Email(message: 'El correo electrónico no es válido')]
    private ?string $email = null;

    #[ORM\Column(length: 50, unique: true)]
    #[Assert\NotBlank(message: 'El nombre de usuario es obligatorio')]
    #[Assert\Length(
        min: 3,
        max: 20,
        minMessage: 'El nombre debe tener al menos {{ limit }} caracteres',
        maxMessage: 'El nombre no puede tener más de {{ limit }} caracteres'
    )]
    #[Assert\Regex(
        pattern: '/^[a-zA-Z0-9_-]+$/',
        message: 'El nombre solo puede contener letras, números, guiones y guiones bajos'
    )]
    private ?string $username = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $profileImage = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $lastLoginAt = null;

    #[ORM\Column]
    private ?bool $isActive = true;

    #[ORM\Column]
    private ?bool $isSubscribedToNewsletter = false;

    #[ORM\Column(nullable: true)]
    private ?int $failedLoginAttempts = 0;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $blockedUntil = null;

    #[ORM\OneToMany(mappedBy: 'author', targetEntity: Comment::class, orphanRemoval: true)]
    private Collection $comments;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: NewsRating::class, orphanRemoval: true)]
    private Collection $newsRatings;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: CommentVote::class, orphanRemoval: true)]
    private Collection $commentVotes;

    #[ORM\OneToMany(mappedBy: 'author', targetEntity: News::class)]
    private Collection $newsCreated;

    #[ORM\OneToMany(mappedBy: 'reporter', targetEntity: Report::class)]
    private Collection $reports;

    public function __construct()
    {
        $this->comments = new ArrayCollection();
        $this->newsRatings = new ArrayCollection();
        $this->commentVotes = new ArrayCollection();
        $this->newsCreated = new ArrayCollection();
        $this->reports = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->roles = ['ROLE_USER'];
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

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;
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
        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function eraseCredentials(): void
    {
        // Si almacenas datos temporales sensibles, límpialos aquí
    }

    public function getProfileImage(): ?string
    {
        return $this->profileImage;
    }

    public function setProfileImage(?string $profileImage): static
    {
        $this->profileImage = $profileImage;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getLastLoginAt(): ?\DateTimeInterface
    {
        return $this->lastLoginAt;
    }

    public function setLastLoginAt(?\DateTimeInterface $lastLoginAt): static
    {
        $this->lastLoginAt = $lastLoginAt;
        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function isSubscribedToNewsletter(): ?bool
    {
        return $this->isSubscribedToNewsletter;
    }

    public function setIsSubscribedToNewsletter(bool $isSubscribedToNewsletter): static
    {
        $this->isSubscribedToNewsletter = $isSubscribedToNewsletter;
        return $this;
    }

    public function getFailedLoginAttempts(): ?int
    {
        return $this->failedLoginAttempts;
    }

    public function setFailedLoginAttempts(?int $failedLoginAttempts): static
    {
        $this->failedLoginAttempts = $failedLoginAttempts;
        return $this;
    }

    public function getBlockedUntil(): ?\DateTimeInterface
    {
        return $this->blockedUntil;
    }

    public function setBlockedUntil(?\DateTimeInterface $blockedUntil): static
    {
        $this->blockedUntil = $blockedUntil;
        return $this;
    }

    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function getNewsRatings(): Collection
    {
        return $this->newsRatings;
    }

    public function getCommentVotes(): Collection
    {
        return $this->commentVotes;
    }

    public function getNewsCreated(): Collection
    {
        return $this->newsCreated;
    }

    public function getReports(): Collection
    {
        return $this->reports;
    }
}