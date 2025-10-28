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
/**
 * Controlador para la gestión de moderación de contenido
 *
 * Este controlador maneja todas las operaciones relacionadas con la
 * moderación de comentarios y reportes de usuarios:
 * - Listado de reportes pendientes
 * - Revisión de reportes individuales
 * - Acciones de moderación (eliminar/descartar)
 *
 * Seguridad:
 * - Requiere ROLE_ADMIN para todas las acciones
 * - Acceso restringido a área administrativa
 * - Manejo seguro de eliminación de contenido
 */
class ModerationController extends AbstractController
{
    /**
     * Lista todos los reportes pendientes de moderación
     *
     * Muestra un listado paginado de reportes ordenados por fecha
     * de creación, permitiendo a los administradores revisar y
     * gestionar el contenido reportado por los usuarios.
     *
     * Características:
     * - Paginación de resultados (20 por página)
     * - Ordenamiento por fecha más reciente
     * - Vista rápida del estado de cada reporte
     *
     * @param Request $request Para manejar la paginación
     * @param ReportRepository $reportRepository Acceso a reportes
     * @param PaginatorInterface $paginator Para paginar resultados
     */
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

    /**
     * Procesa un reporte específico y toma acciones de moderación
     *
     * Este método permite a los administradores revisar reportes
     * individuales y tomar dos posibles acciones:
     * 1. Eliminar el comentario reportado
     * 2. Descartar el reporte
     *
     * Flujo de trabajo:
     * 1. Muestra detalles del reporte y contenido reportado
     * 2. Procesa la acción seleccionada por el administrador
     * 3. Actualiza el estado del reporte
     * 4. Registra la fecha de resolución
     *
     * Acciones disponibles:
     * - delete_comment: Elimina el comentario y marca reporte como resuelto
     * - dismiss: Marca el reporte como descartado
     *
     * @param Report $report El reporte a revisar (inyectado por ParamConverter)
     * @param Request $request Para procesar la acción seleccionada
     * @param EntityManagerInterface $entityManager Para persistir cambios
     */
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