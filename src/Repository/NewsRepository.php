<?php

namespace App\Repository;

use App\Entity\News;
use App\Entity\Category;
use App\Entity\Tag;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * NewsRepository
 *
 * Repositorio con consultas específicas para la entidad `News`.
 * Contiene métodos usados por los controladores para listar, buscar y
 * obtener noticias por identificadores legibles (slug).
 *
 * Puntos para explicar en la demo:
 * - `findPublishedNews()` devuelve noticias publicadas ordenadas por fecha.
 * - `findFeaturedNews()` usa promedio de valoraciones y vistas para destacar.
 * - `findBySlug()` es utilizado por `NewsController::show()` para resolver
 *    la noticia por su slug (URL amigable).
 * - `searchNews()` construye un QueryBuilder reutilizable con filtros: texto,
 *    categoría, etiqueta y rango de fechas. Retorna el QueryBuilder para permitir
 *    paginación externa.
 */
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

    /**
     * Búsqueda avanzada de noticias con filtros
     */
    public function searchNews(
        ?string $query = null,
        ?Category $category = null,
        ?Tag $tag = null,
        ?\DateTime $dateFrom = null,
        ?\DateTime $dateTo = null
    ) {
        $qb = $this->createQueryBuilder('n')
            ->where('n.status = :status')
            ->setParameter('status', 'published');

        // Búsqueda por texto (en título, subtítulo y cuerpo)
        if ($query && trim($query) !== '') {
            $searchTerm = '%' . trim($query) . '%';
            $qb->andWhere('n.title LIKE :query OR n.subtitle LIKE :query OR n.body LIKE :query')
               ->setParameter('query', $searchTerm);
        }

        // Filtro por categoría
        if ($category !== null) {
            $qb->innerJoin('n.categories', 'c')
               ->andWhere('c.id = :categoryId')
               ->setParameter('categoryId', $category->getId());
        }

        // Filtro por etiqueta
        if ($tag !== null) {
            $qb->innerJoin('n.tags', 't')
               ->andWhere('t.id = :tagId')
               ->setParameter('tagId', $tag->getId());
        }

        // Filtro por rango de fechas
        if ($dateFrom !== null) {
            $dateFrom->setTime(0, 0, 0);
            $qb->andWhere('n.publishedAt >= :dateFrom')
               ->setParameter('dateFrom', $dateFrom);
        }

        if ($dateTo !== null) {
            $dateTo->setTime(23, 59, 59);
            $qb->andWhere('n.publishedAt <= :dateTo')
               ->setParameter('dateTo', $dateTo);
        }

        // Asegurar que no haya duplicados cuando se usan joins
        $qb->distinct();

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