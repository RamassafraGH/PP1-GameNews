<?php

namespace App\Controller;

use App\Repository\NewsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    /**
     * HomeController
     *
     * Controlador para la página principal (home). Muestra noticias destacadas
     * utilizando `NewsRepository::findFeaturedNews()` y renderiza la plantilla
     * `home/index.html.twig`.
     *
     * En la demo úsalo como punto de entrada para mostrar la UI y navegar al
     * listado de noticias.
     */
    #[Route('/', name: 'app_home')]
    public function index(NewsRepository $newsRepository): Response
    {
        $featuredNews = $newsRepository->findFeaturedNews(6);
        
        return $this->render('home/index.html.twig', [
            'featuredNews' => $featuredNews,
        ]);
    }
}