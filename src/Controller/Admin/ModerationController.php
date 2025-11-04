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
 * - Acciones de moderación (eliminar comentario/eliminar reporte)
 *
 * Seguridad:
 * - Requiere ROLE_ADMIN para todas las acciones
 * - Acceso restringido a área administrativa
 * - Manejo seguro de eliminación de contenido
 * 
 * Gestión de integridad referencial:
 * - Utiliza cascade remove en la entidad Comment para eliminar automáticamente
 *   votos (CommentVote) y reportes (Report) asociados
 * - Los reportes descartados se eliminan completamente de la base de datos
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
     * - Ordenamiento por fecha más reciente (los más nuevos primero)
     * - Vista rápida del estado de cada reporte
     * - Acceso directo a la acción de revisión
     *
     * @param Request $request Para manejar la paginación mediante query params
     * @param ReportRepository $reportRepository Acceso al repositorio de reportes
     * @param PaginatorInterface $paginator Servicio de paginación de KnpPaginator
     * 
     * @return Response Vista con el listado paginado de reportes
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
     * 
     * 1. **Eliminar el comentario reportado** (delete_comment):
     *    - Elimina el comentario de la base de datos
     *    - Gracias a cascade remove, también elimina automáticamente:
     *      · Todos los votos (CommentVote) asociados al comentario
     *      · Todos los reportes (Report) asociados al comentario
     *    - Evita violaciones de restricciones de clave foránea
     *    - Mantiene la integridad referencial de la base de datos
     * 
     * 2. **Descartar el reporte** (dismiss):
     *    - Elimina el reporte completamente de la base de datos
     *    - No modifica el comentario reportado
     *    - Útil cuando el reporte es infundado o incorrecto
     *    - Mantiene la base de datos limpia sin reportes descartados
     *
     * Flujo de trabajo:
     * 1. GET: Muestra el formulario con detalles del reporte y el comentario
     * 2. POST: Procesa la acción seleccionada por el administrador
     * 3. Ejecuta la eliminación correspondiente
     * 4. Muestra mensaje de confirmación mediante flash
     * 5. Redirige al listado de reportes
     *
     * Ventajas del enfoque cascade:
     * - Código más limpio y mantenible
     * - Sin necesidad de eliminar manualmente entidades relacionadas
     * - La lógica de eliminación está en la entidad, no en el controlador
     *
     * @param Report $report El reporte a revisar (inyectado automáticamente por Symfony)
     * @param Request $request Para procesar el método HTTP y la acción seleccionada
     * @param EntityManagerInterface $entityManager Para persistir los cambios en la BD
     * 
     * @return Response Vista de revisión (GET) o redirección al índice (POST)
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
                // Eliminar comentario (cascade eliminará votos y reportes automáticamente)
                $comment = $report->getComment();
                if ($comment) {
                    $entityManager->remove($comment);
                    $this->addFlash('success', 'Comentario eliminado correctamente');
                }
                
            } elseif ($action === 'dismiss') {
                // Eliminar el reporte de la base de datos
                $entityManager->remove($report);
                $this->addFlash('info', 'Denuncia eliminada correctamente');
            }

            $entityManager->flush();

            return $this->redirectToRoute('app_admin_moderation_index');
        }

        return $this->render('admin/moderation/review.html.twig', [
            'report' => $report,
        ]);
    }
}