<?php

namespace App\Repository;

use App\Entity\Comment;
use App\Entity\News;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Comment::class);
    }

    public function findApprovedByNews(News $news): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.news = :news')
            ->andWhere('c.isApproved = :approved')
            ->setParameter('news', $news)
            ->setParameter('approved', true)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findPendingModeration(): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.isApproved = :approved')
            ->setParameter('approved', false)
            ->orderBy('c.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}