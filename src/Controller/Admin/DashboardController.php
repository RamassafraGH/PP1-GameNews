<?php

namespace App\Controller\Admin;

use App\Repository\CommentRepository;
use App\Repository\NewsRepository;
use App\Repository\ReportRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_EDITOR')]
/**
 * Controlador del Panel de Control Administrativo
 *
 * Este controlador implementa el panel principal del área administrativa,
 * proporcionando una visión general del estado del sistema con:
 * 1. Estadísticas generales del sitio
 * 2. Métricas específicas por rol
 * 3. Análisis temporal de actividad
 *
 * Funcionalidades por rol:
 * - Editor:
 *   - Noticias publicadas
 *   - Comentarios totales
 *   - Suscriptores activos
 *   - Noticias recientes
 * 
 * - Administrador (adicional):
 *   - Total de usuarios
 *   - Reportes pendientes
 *   - Estadísticas por período
 *   - Métricas de crecimiento
 *
 * Períodos de análisis disponibles:
 * - Semanal: Estadísticas desde el lunes actual
 * - Mensual: Datos del mes en curso
 * - Anual: Métricas del año actual
 */
class DashboardController extends AbstractController
{
    /**
     * Genera y muestra el panel de control
     *
     * Este método recopila y presenta todas las estadísticas y métricas
     * relevantes del sistema, adaptando la información según el rol del usuario:
     *
     * Métricas básicas (Editores):
     * - Cantidad de noticias publicadas
     * - Total de comentarios
     * - Noticias más recientes (últimas 10)
     * - Cantidad de suscriptores activos
     *
     * Métricas avanzadas (Administradores):
     * - Total de usuarios registrados
     * - Reportes pendientes de moderación
     * - Estadísticas por período seleccionado
     * - Métricas de crecimiento temporal
     *
     * @param Request $request Para obtener filtros de período
     * @param NewsRepository $newsRepository Consultas de noticias
     * @param UserRepository $userRepository Consultas de usuarios
     * @param CommentRepository $commentRepository Consultas de comentarios
     * @param ReportRepository $reportRepository Consultas de reportes
     */
    #[Route('/', name: 'app_admin_dashboard')]
    public function index(
        Request $request,
        NewsRepository $newsRepository,
        UserRepository $userRepository,
        CommentRepository $commentRepository,
        ReportRepository $reportRepository
    ): Response {
        $user = $this->getUser();
        
        // Estadísticas básicas para todos
        $publishedNews = count($newsRepository->findBy(['status' => 'published']));
        $totalComments = count($commentRepository->findAll());
        $recentNews = $newsRepository->findBy([], ['createdAt' => 'DESC'], 10);
        
        // Contador de suscriptores (visible para editores y admins)
        $subscribersCount = count($userRepository->findActiveSubscribers());

        // Datos específicos para ADMIN
        $isAdmin = $this->isGranted('ROLE_ADMIN');
        $totalUsers = $isAdmin ? count($userRepository->findAll()) : null;
        $pendingReports = $isAdmin ? count($reportRepository->findPendingReports()) : null;

        // Estadísticas con filtro de período
        $monthlyStats = null;
        $period = $request->query->get('period', 'month'); // week, month, year
        
        if ($isAdmin) {
            $monthlyStats = $this->getStatsByPeriod($period, $newsRepository, $commentRepository, $userRepository);
        }

        return $this->render('admin/dashboard/index.html.twig', [
            'publishedNews' => $publishedNews,
            'totalUsers' => $totalUsers,
            'totalComments' => $totalComments,
            'pendingReports' => $pendingReports,
            'recentNews' => $recentNews,
            'isAdmin' => $isAdmin,
            'subscribersCount' => $subscribersCount,
            'monthlyStats' => $monthlyStats,
            'currentPeriod' => $period,
        ]);
    }

    /**
     * Genera estadísticas para un período específico
     *
     * Recopila métricas de actividad del sitio para el período seleccionado:
     * - Noticias nuevas en el período
     * - Comentarios realizados
     * - Nuevos usuarios registrados
     *
     * @param string $period Período de análisis ('week', 'month', 'year')
     * @param NewsRepository $newsRepo Para estadísticas de noticias
     * @param CommentRepository $commentRepo Para estadísticas de comentarios
     * @param UserRepository $userRepo Para estadísticas de usuarios
     * @return array Conjunto de estadísticas del período
     */
    private function getStatsByPeriod(string $period, NewsRepository $newsRepo, CommentRepository $commentRepo, UserRepository $userRepo): array
    {
        $startDate = $this->getStartDateByPeriod($period);
        
        return [
            'newsCount' => $this->getNewsCountSince($newsRepo, $startDate),
            'commentsCount' => $this->getCommentsCountSince($commentRepo, $startDate),
            'newUsersCount' => $this->getUsersCountSince($userRepo, $startDate),
            'periodLabel' => $this->getPeriodLabel($period),
        ];
    }

    /**
     * Calcula la fecha de inicio para un período dado
     *
     * Determina la fecha desde la cual se deben calcular las estadísticas:
     * - week: Primer día (lunes) de la semana actual
     * - month: Primer día del mes actual
     * - year: Primer día del año actual
     *
     * @param string $period Período deseado ('week', 'month', 'year')
     * @return \DateTime Fecha de inicio del período
     */
    private function getStartDateByPeriod(string $period): \DateTime
    {
        $date = new \DateTime();
        
        switch ($period) {
            case 'week':
                $date->modify('monday this week')->setTime(0, 0, 0);
                break;
            case 'year':
                $date->modify('first day of January')->setTime(0, 0, 0);
                break;
            case 'month':
            default:
                $date->modify('first day of this month')->setTime(0, 0, 0);
                break;
        }
        
        return $date;
    }

    /**
     * Obtiene la etiqueta en español para un período
     *
     * Traduce los identificadores de período a texto amigable:
     * - week -> 'esta semana'
     * - month -> 'este mes'
     * - year -> 'este año'
     *
     * @param string $period Identificador del período
     * @return string Etiqueta en español
     */
    private function getPeriodLabel(string $period): string
    {
        return match($period) {
            'week' => 'esta semana',
            'year' => 'este año',
            default => 'este mes',
        };
    }

    /**
     * Cuenta las noticias creadas desde una fecha específica
     *
     * Utiliza QueryBuilder para obtener el número de noticias
     * creadas a partir de una fecha determinada.
     *
     * @param NewsRepository $repository Repositorio de noticias
     * @param \DateTime $startDate Fecha desde la cual contar
     * @return int Cantidad de noticias en el período
     */
    private function getNewsCountSince(NewsRepository $repository, \DateTime $startDate): int
    {
        return count($repository->createQueryBuilder('n')
            ->where('n.createdAt >= :start')
            ->setParameter('start', $startDate)
            ->getQuery()
            ->getResult());
    }

    /**
     * Cuenta los comentarios realizados desde una fecha específica
     *
     * Utiliza QueryBuilder para obtener el número de comentarios
     * creados a partir de una fecha determinada.
     *
     * @param CommentRepository $repository Repositorio de comentarios
     * @param \DateTime $startDate Fecha desde la cual contar
     * @return int Cantidad de comentarios en el período
     */
    private function getCommentsCountSince(CommentRepository $repository, \DateTime $startDate): int
    {
        return count($repository->createQueryBuilder('c')
            ->where('c.createdAt >= :start')
            ->setParameter('start', $startDate)
            ->getQuery()
            ->getResult());
    }

    /**
     * Cuenta los usuarios registrados desde una fecha específica
     *
     * Utiliza QueryBuilder para obtener el número de usuarios
     * que se registraron a partir de una fecha determinada.
     *
     * @param UserRepository $repository Repositorio de usuarios
     * @param \DateTime $startDate Fecha desde la cual contar
     * @return int Cantidad de nuevos usuarios en el período
     */
    private function getUsersCountSince(UserRepository $repository, \DateTime $startDate): int
    {
        return count($repository->createQueryBuilder('u')
            ->where('u.createdAt >= :start')
            ->setParameter('start', $startDate)
            ->getQuery()
            ->getResult());
    }
}