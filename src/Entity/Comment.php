<?php

namespace App\Entity;

use App\Repository\CommentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CommentRepository::class)]
class Comment
{
    /**
     * Entity Comment
     *
     * Representa un comentario en una noticia. Campos importantes:
     * - content: Contenido del comentario (texto)
     * - createdAt: Fecha de creación (inmutable)
     * - updatedAt: Fecha de última actualización (nullable)
     * - isApproved: Estado de aprobación para moderación
     * - likesCount: Contador de votos positivos
     * - dislikesCount: Contador de votos negativos
     *
     * Relaciones:
     * - author (ManyToOne -> User): Usuario que creó el comentario
     * - news (ManyToOne -> News): Noticia a la que pertenece el comentario
     * - votes (OneToMany -> CommentVote): Votos recibidos, con cascade remove
     * - reports (OneToMany -> Report): Denuncias del comentario, con cascade remove
     *
     * Comportamiento cascade:
     * Al eliminar un comentario, se eliminan automáticamente:
     * - Todos los votos asociados (CommentVote)
     * - Todos los reportes asociados (Report)
     * 
     * Esto evita violaciones de restricciones de clave foránea y mantiene
     * la integridad referencial de la base de datos.
     *
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'El comentario no puede estar vacío')]
    private ?string $content = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column]
    private ?bool $isApproved = false;

    #[ORM\Column]
    private ?int $likesCount = 0;

    #[ORM\Column]
    private ?int $dislikesCount = 0;

    #[ORM\ManyToOne(inversedBy: 'comments')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $author = null;

    #[ORM\ManyToOne(inversedBy: 'comments')]
    #[ORM\JoinColumn(nullable: false)]
    private ?News $news = null;

    /**
     * Colección de votos asociados al comentario.
     * 
     * Configuración:
     * - orphanRemoval: true -> Elimina votos huérfanos automáticamente
     * - cascade: ['remove'] -> Al eliminar el comentario, elimina todos los votos
     * 
     * Esta configuración evita el error ForeignKeyConstraintViolationException
     * al eliminar comentarios que tienen votos asociados.
     */
    #[ORM\OneToMany(
        mappedBy: 'comment', 
        targetEntity: CommentVote::class, 
        orphanRemoval: true,
        cascade: ['remove']
    )]
    private Collection $votes;

    /**
     * Colección de reportes/denuncias asociados al comentario.
     * 
     * Configuración:
     * - cascade: ['remove'] -> Al eliminar el comentario, elimina todos los reportes
     * 
     * Esto garantiza que cuando se elimina un comentario reportado,
     * todos los reportes asociados también se eliminan automáticamente,
     * manteniendo la consistencia de la base de datos.
     */
    #[ORM\OneToMany(
        mappedBy: 'comment', 
        targetEntity: Report::class,
        cascade: ['remove']
    )]
    private Collection $reports;

    public function __construct()
    {
        $this->votes = new ArrayCollection();
        $this->reports = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->isApproved = true;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;
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

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function isApproved(): ?bool
    {
        return $this->isApproved;
    }

    public function setIsApproved(bool $isApproved): static
    {
        $this->isApproved = $isApproved;
        return $this;
    }

    public function getLikesCount(): ?int
    {
        return $this->likesCount;
    }

    public function setLikesCount(int $likesCount): static
    {
        $this->likesCount = $likesCount;
        return $this;
    }

    public function getDislikesCount(): ?int
    {
        return $this->dislikesCount;
    }

    public function setDislikesCount(int $dislikesCount): static
    {
        $this->dislikesCount = $dislikesCount;
        return $this;
    }

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function setAuthor(?User $author): static
    {
        $this->author = $author;
        return $this;
    }

    public function getNews(): ?News
    {
        return $this->news;
    }

    public function setNews(?News $news): static
    {
        $this->news = $news;
        return $this;
    }

    public function getVotes(): Collection
    {
        return $this->votes;
    }

    public function getReports(): Collection
    {
        return $this->reports;
    }
}