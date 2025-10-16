<?php

namespace App\Controller\Admin;

use App\Repository\CommentRepository;
use App\Repository\NewsRepository;
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
        CommentRepository $commentRepository
    ): Response {
        $publishedNews = count($newsRepository->findBy(['status' => 'published']));
        $totalUsers = count($userRepository->findAll());
        $totalComments = count($commentRepository->findAll());
        $pendingComments = count($commentRepository->findPendingModeration());

        // Actividad reciente (últimas 50 acciones)
        $recentNews = $newsRepository->findBy([], ['createdAt' => 'DESC'], 10);

        return $this->render('admin/dashboard/index.html.twig', [
            'publishedNews' => $publishedNews,
            'totalUsers' => $totalUsers,
            'totalComments' => $totalComments,
            'pendingComments' => $pendingComments,
            'recentNews' => $recentNews,
        ]);
    }
}