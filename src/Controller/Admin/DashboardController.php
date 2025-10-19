<?php

namespace App\Controller\Admin;

use App\Repository\CommentRepository;
use App\Repository\NewsRepository;
use App\Repository\ReportRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_EDITOR')]
class DashboardController extends AbstractController
{
    #[Route('/', name: 'app_admin_dashboard')]
    public function index(
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

        // Datos específicos para ADMIN
        $isAdmin = $this->isGranted('ROLE_ADMIN');
        $totalUsers = $isAdmin ? count($userRepository->findAll()) : null;
        $pendingReports = $isAdmin ? count($reportRepository->findPendingReports()) : null;
        $subscribersCount = $isAdmin ? count($userRepository->findActiveSubscribers()) : null;

        // Estadísticas avanzadas para admin
        $monthlyStats = null;
        if ($isAdmin) {
            $monthlyStats = [
                'newsThisMonth' => $this->getNewsCountThisMonth($newsRepository),
                'commentsThisMonth' => $this->getCommentsCountThisMonth($commentRepository),
                'newUsersThisMonth' => $this->getUsersCountThisMonth($userRepository),
            ];
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
        ]);
    }

    private function getNewsCountThisMonth(NewsRepository $repository): int
    {
        $startOfMonth = new \DateTime('first day of this month 00:00:00');
        return count($repository->createQueryBuilder('n')
            ->where('n.createdAt >= :start')
            ->setParameter('start', $startOfMonth)
            ->getQuery()
            ->getResult());
    }

    private function getCommentsCountThisMonth(CommentRepository $repository): int
    {
        $startOfMonth = new \DateTime('first day of this month 00:00:00');
        return count($repository->createQueryBuilder('c')
            ->where('c.createdAt >= :start')
            ->setParameter('start', $startOfMonth)
            ->getQuery()
            ->getResult());
    }

    private function getUsersCountThisMonth(UserRepository $repository): int
    {
        $startOfMonth = new \DateTime('first day of this month 00:00:00');
        return count($repository->createQueryBuilder('u')
            ->where('u.createdAt >= :start')
            ->setParameter('start', $startOfMonth)
            ->getQuery()
            ->getResult());
    }
}