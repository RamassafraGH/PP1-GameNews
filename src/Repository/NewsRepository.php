<?php

namespace App\Repository;

use App\Entity\News;
use App\Entity\Category;
use App\Entity\Tag;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class NewsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, News::class);
    }

    public function findPublishedNews(): array
    {
        return $this->createQueryBuilder('n')
            ->where('n.status = :status')
            ->setParameter('status', 'published')
            ->orderBy('n.publishedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findFeaturedNews(int $limit = 5): array
    {
        return $this->createQueryBuilder('n')
            ->where('n.status = :status')
            ->setParameter('status', 'published')
            ->orderBy('n.averageRating', 'DESC')
            ->addOrderBy('n.viewCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findBySlug(string $slug): ?News
    {
        return $this->createQueryBuilder('n')
            ->where('n.slug = :slug')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function searchNews(string $query, ?Category $category = null, ?Tag $tag = null, ?\DateTime $dateFrom = null, ?\DateTime $dateTo = null)
    {
        $qb = $this->createQueryBuilder('n')
            ->where('n.status = :status')
            ->setParameter('status', 'published');

        if ($query) {
            $qb->andWhere('n.title LIKE :query OR n.body LIKE :query OR n.subtitle LIKE :query')
               ->setParameter('query', '%' . $query . '%');
        }

        if ($category) {
            $qb->join('n.categories', 'c')
               ->andWhere('c.id = :categoryId')
               ->setParameter('categoryId', $category->getId());
        }

        if ($tag) {
            $qb->join('n.tags', 't')
               ->andWhere('t.id = :tagId')
               ->setParameter('tagId', $tag->getId());
        }

        if ($dateFrom) {
            $qb->andWhere('n.publishedAt >= :dateFrom')
               ->setParameter('dateFrom', $dateFrom);
        }

        if ($dateTo) {
            $qb->andWhere('n.publishedAt <= :dateTo')
               ->setParameter('dateTo', $dateTo);
        }

        return $qb->orderBy('n.publishedAt', 'DESC');
    }

    public function findRecentNews(int $days = 7): array
    {
        $date = new \DateTime();
        $date->modify("-{$days} days");

        return $this->createQueryBuilder('n')
            ->where('n.status = :status')
            ->andWhere('n.publishedAt >= :date')
            ->setParameter('status', 'published')
            ->setParameter('date', $date)
            ->orderBy('n.publishedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}