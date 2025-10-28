<?php

namespace App\Repository;

use App\Entity\CommentVote;
use App\Entity\Comment;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CommentVoteRepository extends ServiceEntityRepository
{
    /**
     * CommentVoteRepository
     *
     * Repositorio para gestionar los votos de comentarios. Contiene métodos
     * como `findUserVoteForComment(User, Comment)` que permiten verificar si
     * un usuario ya votó un comentario (lógica usada por `CommentController`).
     */
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