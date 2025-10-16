<?php

namespace App\Controller;

use App\Repository\NewsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(NewsRepository $newsRepository): Response
    {
        $featuredNews = $newsRepository->findFeaturedNews(6);
        
        return $this->render('home/index.html.twig', [
            'featuredNews' => $featuredNews,
        ]);
    }
}