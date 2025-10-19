<?php

namespace App\Controller\Admin;

use App\Entity\Comment;
use App\Entity\Report;
use App\Repository\CommentRepository;
use App\Repository\ReportRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/moderacion')]
#[IsGranted('ROLE_ADMIN')]
class ModerationController extends AbstractController
{
    #[Route('/', name: 'app_admin_moderation_index')]
    public function index(
        Request $request,
        ReportRepository $reportRepository,
        PaginatorInterface $paginator
    ): Response {
        $queryBuilder = $reportRepository->createQueryBuilder('r')
            ->orderBy('r.createdAt', 'DESC');

        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            20
        );

        return $this->render('admin/moderation/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/{id}/revisar', name: 'app_admin_moderation_review')]
    public function review(
        Report $report,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        if ($request->isMethod('POST')) {
            $action = $request->request->get('action');

            if ($action === 'delete_comment') {
                // Eliminar comentario
                $comment = $report->getComment();
                if ($comment) {
                    $entityManager->remove($comment);
                }
                $report->setStatus('resolved');
                $report->setResolvedAt(new \DateTime());
                
                $this->addFlash('success', 'Comentario eliminado correctamente');
            } elseif ($action === 'dismiss') {
                // Descartar denuncia
                $report->setStatus('dismissed');
                $report->setResolvedAt(new \DateTime());
                
                $this->addFlash('info', 'Denuncia descartada');
            }

            $entityManager->flush();

            return $this->redirectToRoute('app_admin_moderation_index');
        }

        return $this->render('admin/moderation/review.html.twig', [
            'report' => $report,
        ]);
    }
}