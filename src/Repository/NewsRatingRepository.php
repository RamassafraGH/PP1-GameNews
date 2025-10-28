<?php

namespace App\Repository;

use App\Entity\NewsRating;
use App\Entity\News;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class NewsRatingRepository extends ServiceEntityRepository
{
    /**
     * NewsRatingRepository
     *
     * Repositorio para gestionar valoraciones de noticias. Métodos útiles:
     * - `findUserRatingForNews(User, News)` devuelve la valoración de un usuario
     *    para una noticia concreta (o null).
     * - `calculateAverageRating(News)` devuelve el promedio para actualizar
     *    el campo en la entidad `News`.
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NewsRating::class);
    }

    public function findUserRatingForNews(User $user, News $news): ?NewsRating
    {
        return $this->createQueryBuilder('nr')
            ->where('nr.user = :user')
            ->andWhere('nr.news = :news')
            ->setParameter('user', $user)
            ->setParameter('news', $news)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function calculateAverageRating(News $news): ?float
    {
        $result = $this->createQueryBuilder('nr')
            ->select('AVG(nr.rating) as average')
            ->where('nr.news = :news')
            ->setParameter('news', $news)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? (float) $result : null;
    }
}