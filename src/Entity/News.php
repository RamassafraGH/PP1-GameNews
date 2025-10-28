<?php

namespace App\Entity;

use App\Repository\NewsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: NewsRepository::class)]
class News
{
    /**
     * Entity News
     *
     * Representa una noticia. Campos clave:
     * - id, title, subtitle, body, slug
     * - featuredImage: nombre de archivo subido
     * - status: 'draft'|'published' -> controlar visibilidad
     * - createdAt, publishedAt, updatedAt
     * - viewCount, averageRating, ratingCount
     *
     * Relaciones importantes:
     * - author (ManyToOne -> User)
     * - categories (ManyToMany -> Category)
     * - tags (ManyToMany -> Tag)
     * - comments (OneToMany -> Comment)
     * - ratings (OneToMany -> NewsRating)
     *
     * Mostrar en la demo: el uso de métodos como `incrementViewCount()` y
     * `isPublished()` para controlar lógica de presentación.
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'El título es obligatorio')]
    #[Assert\Length(max: 255)]
    private ?string $title = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $subtitle = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'El cuerpo es obligatorio')]
    private ?string $body = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $slug = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $featuredImage = null;

    #[ORM\Column(length: 50)]
    private ?string $status = 'draft'; // draft, published

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $publishedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column]
    private ?int $viewCount = 0;

    #[ORM\Column(type: Types::DECIMAL, precision: 3, scale: 2, nullable: true)]
    private ?string $averageRating = null;

    #[ORM\Column]
    private ?int $ratingCount = 0;

    #[ORM\ManyToOne(inversedBy: 'newsCreated')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $author = null;

    #[ORM\ManyToMany(targetEntity: Category::class, inversedBy: 'news')]
    #[Assert\Count(min: 1, minMessage: 'Debe seleccionar al menos una categoría')]
    private Collection $categories;

    #[ORM\ManyToMany(targetEntity: Tag::class, inversedBy: 'news')]
    private Collection $tags;

    #[ORM\OneToMany(mappedBy: 'news', targetEntity: Comment::class, orphanRemoval: true)]
    private Collection $comments;

    #[ORM\OneToMany(mappedBy: 'news', targetEntity: NewsRating::class, orphanRemoval: true)]
    private Collection $ratings;

    public function __construct()
    {
        $this->categories = new ArrayCollection();
        $this->tags = new ArrayCollection();
        $this->comments = new ArrayCollection();
        $this->ratings = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getSubtitle(): ?string
    {
        return $this->subtitle;
    }

    public function setSubtitle(?string $subtitle): static
    {
        $this->subtitle = $subtitle;
        return $this;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function setBody(string $body): static
    {
        $this->body = $body;
        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;
        return $this;
    }

    public function getFeaturedImage(): ?string
    {
        return $this->featuredImage;
    }


    public function setPredefinedImage(string $imageName): static
    {
        $this->featuredImage = '/uploads/news/predefined/' . $imageName;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
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

    public function getPublishedAt(): ?\DateTimeInterface
    {
        return $this->publishedAt;
    }

    public function setPublishedAt(?\DateTimeInterface $publishedAt): static
    {
        $this->publishedAt = $publishedAt;
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

    public function getViewCount(): ?int
    {
        return $this->viewCount;
    }

    public function setViewCount(int $viewCount): static
    {
        $this->viewCount = $viewCount;
        return $this;
    }

    public function incrementViewCount(): static
    {
        $this->viewCount++;
        return $this;
    }

    public function getAverageRating(): ?string
    {
        return $this->averageRating;
    }

    public function setAverageRating(?string $averageRating): static
    {
        $this->averageRating = $averageRating;
        return $this;
    }

    public function getRatingCount(): ?int
    {
        return $this->ratingCount;
    }

    public function setRatingCount(int $ratingCount): static
    {
        $this->ratingCount = $ratingCount;
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

    public function getCategories(): Collection
    {
        return $this->categories;
    }

    public function addCategory(Category $category): static
    {
        if (!$this->categories->contains($category)) {
            $this->categories->add($category);
        }
        return $this;
    }

    public function removeCategory(Category $category): static
    {
        $this->categories->removeElement($category);
        return $this;
    }

    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function addTag(Tag $tag): static
    {
        if (!$this->tags->contains($tag)) {
            $this->tags->add($tag);
        }
        return $this;
    }

    public function removeTag(Tag $tag): static
    {
        $this->tags->removeElement($tag);
        return $this;
    }

    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function getRatings(): Collection
    {
        return $this->ratings;
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }
}