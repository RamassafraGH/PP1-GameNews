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
class DashboardController extends AbstractController
{
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

    private function getPeriodLabel(string $period): string
    {
        return match($period) {
            'week' => 'esta semana',
            'year' => 'este año',
            default => 'este mes',
        };
    }

    private function getNewsCountSince(NewsRepository $repository, \DateTime $startDate): int
    {
        return count($repository->createQueryBuilder('n')
            ->where('n.createdAt >= :start')
            ->setParameter('start', $startDate)
            ->getQuery()
            ->getResult());
    }

    private function getCommentsCountSince(CommentRepository $repository, \DateTime $startDate): int
    {
        return count($repository->createQueryBuilder('c')
            ->where('c.createdAt >= :start')
            ->setParameter('start', $startDate)
            ->getQuery()
            ->getResult());
    }

    private function getUsersCountSince(UserRepository $repository, \DateTime $startDate): int
    {
        return count($repository->createQueryBuilder('u')
            ->where('u.createdAt >= :start')
            ->setParameter('start', $startDate)
            ->getQuery()
            ->getResult());
    }
}