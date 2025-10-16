<?php

namespace App\Controller;

use App\Entity\CommentVote;
use App\Repository\CommentRepository;
use App\Repository\CommentVoteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/comentarios')]
class CommentController extends AbstractController
{
    #[Route('/{id}/votar', name: 'app_comment_vote', methods: ['POST'])]
    public function vote(
        int $id,
        Request $request,
        CommentRepository $commentRepository,
        CommentVoteRepository $voteRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        if (!$this->getUser()) {
            return new JsonResponse(['error' => 'Debes iniciar sesión'], 401);
        }

        $comment = $commentRepository->find($id);
        if (!$comment) {
            return new JsonResponse(['error' => 'Comentario no encontrado'], 404);
        }

        $voteType = $request->request->get('type'); // 'like' o 'dislike'
        
        if (!in_array($voteType, ['like', 'dislike'])) {
            return new JsonResponse(['error' => 'Tipo de voto inválido'], 400);
        }

        $existingVote = $voteRepository->findUserVoteForComment($this->getUser(), $comment);

        if ($existingVote) {
            // Si es el mismo tipo de voto, lo eliminamos
            if ($existingVote->getVoteType() === $voteType) {
                // Decrementar contador
                if ($voteType === 'like') {
                    $comment->setLikesCount($comment->getLikesCount() - 1);
                } else {
                    $comment->setDislikesCount($comment->getDislikesCount() - 1);
                }
                
                $entityManager->remove($existingVote);
                $entityManager->flush();

                return new JsonResponse([
                    'success' => true,
                    'action' => 'removed',
                    'likesCount' => $comment->getLikesCount(),
                    'dislikesCount' => $comment->getDislikesCount(),
                ]);
            }

            // Si es diferente, cambiamos el voto
            // Decrementar el contador anterior
            if ($existingVote->getVoteType() === 'like') {
                $comment->setLikesCount($comment->getLikesCount() - 1);
            } else {
                $comment->setDislikesCount($comment->getDislikesCount() - 1);
            }

            // Incrementar el nuevo
            if ($voteType === 'like') {
                $comment->setLikesCount($comment->getLikesCount() + 1);
            } else {
                $comment->setDislikesCount($comment->getDislikesCount() + 1);
            }

            $existingVote->setVoteType($voteType);
            $entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'action' => 'changed',
                'likesCount' => $comment->getLikesCount(),
                'dislikesCount' => $comment->getDislikesCount(),
            ]);
        }

        // Crear nuevo voto
        $vote = new CommentVote();
        $vote->setUser($this->getUser());
        $vote->setComment($comment);
        $vote->setVoteType($voteType);

        if ($voteType === 'like') {
            $comment->setLikesCount($comment->getLikesCount() + 1);
        } else {
            $comment->setDislikesCount($comment->getDislikesCount() + 1);
        }

        $entityManager->persist($vote);
        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'action' => 'added',
            'likesCount' => $comment->getLikesCount(),
            'dislikesCount' => $comment->getDislikesCount(),
        ]);
    }
}