<?php

namespace App\Repository;

use App\Entity\CommentVote;
use App\Entity\Comment;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CommentVoteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CommentVote::class);
    }

    public function findUserVoteForComment(User $user, Comment $comment): ?CommentVote
    {
        return $this->createQueryBuilder('cv')
            ->where('cv.user = :user')
            ->andWhere('cv.comment = :comment')
            ->setParameter('user', $user)
            ->setParameter('comment', $comment)
            ->getQuery()
            ->getOneOrNullResult();
    }
}