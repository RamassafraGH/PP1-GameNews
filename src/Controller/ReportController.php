<?php

namespace App\Controller;

use App\Entity\Report;
use App\Form\ReportFormType;
use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/denunciar')]
#[IsGranted('ROLE_USER')]
class ReportController extends AbstractController
{
    /**
     * ReportController
     *
     * Gestiona la creación de denuncias sobre comentarios. Protegida con
     * `IsGranted('ROLE_USER')`, recibe un formulario (`ReportFormType`) y
     * persiste una entidad `Report` con estado 'pending'.
     *
     * Ideal para explicar cómo se integran formularios, entidades y la
     * navegación de vuelta a la noticia (`redirectToRoute`).
     */
    #[Route('/comentario/{id}', name: 'app_report_comment')]
    public function reportComment(
        int $id,
        Request $request,
        CommentRepository $commentRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $comment = $commentRepository->find($id);

        if (!$comment) {
            throw $this->createNotFoundException('Comentario no encontrado');
        }

        $report = new Report();
        $form = $this->createForm(ReportFormType::class, $report);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $report->setReporter($this->getUser());
            $report->setComment($comment);
            $report->setStatus('pending');

            $entityManager->persist($report);
            $entityManager->flush();

            $this->addFlash('success', 'Denuncia enviada correctamente. Será revisada por nuestro equipo.');

            // Redirigir a la noticia del comentario
            return $this->redirectToRoute('app_news_show', [
                'slug' => $comment->getNews()->getSlug()
            ]);
        }

        return $this->render('report/comment.html.twig', [
            'comment' => $comment,
            'reportForm' => $form->createView(),
        ]);
    }
}