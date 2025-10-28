<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\NewsRating;
use App\Form\CommentFormType;
use App\Form\SearchFormType;
use App\Repository\CategoryRepository;
use App\Repository\CommentRepository;
use App\Repository\CommentVoteRepository;
use App\Repository\NewsRatingRepository;
use App\Repository\NewsRepository;
use App\Repository\TagRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/noticias')]
class NewsController extends AbstractController
{
    /**
     * NewsController
     *
     * Controlador encargado de las vistas públicas y operaciones relacionadas con
     * las noticias (listado, detalle, votación). Cada método está anotado con
     * la ruta correspondiente y delega en repositorios/entidades para consultar
     * y persistir datos. Ideal para explicar CU05 (explorar), CU20 (ver) y CU10 (valorar).
     *
     * Buen lugar para mostrar el flujo: request -> repositorio -> entidad -> template.
     */
    /**
     * Lista y filtra las noticias publicadas
     *
     * Este método implementa el listado principal de noticias con búsqueda avanzada:
     * 1. Construye una consulta base que obtiene solo noticias publicadas
     * 2. Aplica filtros dinámicamente según los parámetros de búsqueda:
     *    - Búsqueda por texto en título, subtítulo y cuerpo
     *    - Filtrado por categoría
     *    - Filtrado por etiqueta
     *    - Rango de fechas
     * 3. Pagina los resultados usando KnpPaginator
     * 
     * Parámetros de búsqueda (query string):
     * @param string query     Texto a buscar en título/subtítulo/cuerpo
     * @param int    category  ID de la categoría para filtrar
     * @param int    tag       ID de la etiqueta para filtrar
     * @param date   dateFrom  Fecha inicial del rango
     * @param date   dateTo    Fecha final del rango
     */
    #[Route('/', name: 'app_news_index')]
    public function index(
        Request $request,
        NewsRepository $newsRepository,
        CategoryRepository $categoryRepository,
        TagRepository $tagRepository,
        PaginatorInterface $paginator
    ): Response {
        // Inicialización de flags y colección de filtros aplicados
        $hasSearch = false;
        $appliedFilters = [];

        // Consulta base: solo noticias publicadas, ordenadas por fecha
        $queryBuilder = $newsRepository->createQueryBuilder('n')
            ->where('n.status = :status')
            ->setParameter('status', 'published')
            ->orderBy('n.publishedAt', 'DESC');

        // Obtener parámetros de búsqueda directamente de la query string
        $searchQuery = $request->query->get('query', '');
        $categoryId = $request->query->get('category', '');
        $tagId = $request->query->get('tag', '');
        $dateFrom = $request->query->get('dateFrom', '');
        $dateTo = $request->query->get('dateTo', '');

        // Verificar si hay búsqueda activa
        $hasSearch = !empty($searchQuery) || !empty($categoryId) || !empty($tagId) || !empty($dateFrom) || !empty($dateTo);

        if ($hasSearch) {
            // FILTRO POR TEXTO
            // Busca el término en título, subtítulo y cuerpo usando OR
            // Usa LIKE con comodines para búsqueda parcial
            if (!empty($searchQuery)) {
                $queryBuilder->andWhere(
                    $queryBuilder->expr()->orX(
                        'n.title LIKE :search',     // Busca en título
                        'n.subtitle LIKE :search',  // Busca en subtítulo
                        'n.body LIKE :search'       // Busca en contenido
                    )
                )
                ->setParameter('search', '%' . $searchQuery . '%');
                
                $appliedFilters[] = 'Texto: "' . $searchQuery . '"';
            }

            // FILTRO POR CATEGORÍA
            if (!empty($categoryId)) {
                $category = $categoryRepository->find($categoryId);
                if ($category) {
                    $queryBuilder
                        ->innerJoin('n.categories', 'c')
                        ->andWhere('c.id = :categoryId')
                        ->setParameter('categoryId', $categoryId);
                    
                    $appliedFilters[] = 'Categoría: ' . $category->getName();
                }
            }

            // FILTRO POR ETIQUETA
            if (!empty($tagId)) {
                $tag = $tagRepository->find($tagId);
                if ($tag) {
                    $queryBuilder
                        ->innerJoin('n.tags', 't')
                        ->andWhere('t.id = :tagId')
                        ->setParameter('tagId', $tagId);
                    
                    $appliedFilters[] = 'Etiqueta: ' . $tag->getName();
                }
            }

            // FILTRO POR FECHA DESDE
            if (!empty($dateFrom)) {
                try {
                    $dateFromObj = new \DateTime($dateFrom);
                    $dateFromObj->setTime(0, 0, 0);
                    $queryBuilder
                        ->andWhere('n.publishedAt >= :dateFrom')
                        ->setParameter('dateFrom', $dateFromObj);
                    
                    $appliedFilters[] = 'Desde: ' . $dateFromObj->format('d/m/Y');
                } catch (\Exception $e) {
                    // Fecha inválida, ignorar
                }
            }

            // FILTRO POR FECHA HASTA
            if (!empty($dateTo)) {
                try {
                    $dateToObj = new \DateTime($dateTo);
                    $dateToObj->setTime(23, 59, 59);
                    $queryBuilder
                        ->andWhere('n.publishedAt <= :dateTo')
                        ->setParameter('dateTo', $dateToObj);
                    
                    $appliedFilters[] = 'Hasta: ' . $dateToObj->format('d/m/Y');
                } catch (\Exception $e) {
                    // Fecha inválida, ignorar
                }
            }

            // Evitar duplicados
            $queryBuilder->distinct();
        }

        // Paginar
        $pagination = $paginator->paginate(
            $queryBuilder->getQuery(),
            $request->query->getInt('page', 1),
            12
        );

        // Crear formulario solo para mostrar
        $searchForm = $this->createForm(SearchFormType::class, null, [
            'action' => $this->generateUrl('app_news_index'),
            'method' => 'GET',
        ]);

        return $this->render('news/index.html.twig', [
            'pagination' => $pagination,
            'searchForm' => $searchForm->createView(),
            'hasSearch' => $hasSearch,
            'appliedFilters' => $appliedFilters,
            'currentSearch' => [
                'query' => $searchQuery,
                'category' => $categoryId,
                'tag' => $tagId,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
            ],
        ]);
    }

    /**
     * Muestra el detalle de una noticia individual
     *
     * Este método maneja:
     * 1. Visualización de la noticia completa
     * 2. Sistema de comentarios (listado y creación)
     * 3. Sistema de valoraciones
     * 
     * Flujo de ejecución:
     * 1. Busca la noticia por su slug
     * 2. Verifica que exista y esté publicada
     * 3. Obtiene comentarios y valoraciones asociadas
     * 4. Si el usuario está autenticado:
     *    - Prepara formulario de comentarios
     *    - Obtiene la valoración del usuario
     * 
     * @param string $slug Identificador único amigable de la noticia
     * @throws NotFoundHttpException si la noticia no existe o no está publicada
     */
    #[Route('/{slug}', name: 'app_news_show')]
    public function show(
        string $slug,
        Request $request,
        NewsRepository $newsRepository,
        CommentRepository $commentRepository,
        NewsRatingRepository $ratingRepository,
        CommentVoteRepository $voteRepository,
        EntityManagerInterface $entityManager
    ): Response {
        // Buscar noticia por slug
        $news = $newsRepository->findBySlug($slug);

        if (!$news || !$news->isPublished()) {
            throw $this->createNotFoundException('Noticia no encontrada');
        }

        // Incrementar contador de vistas
        $news->incrementViewCount();
        $entityManager->flush();

        // Obtener comentarios aprobados
        $comments = $commentRepository->createQueryBuilder('c')
        ->where('c.news = :news')
        ->setParameter('news', $news)
        ->orderBy('c.createdAt', 'DESC')
        ->getQuery()
        ->getResult();

        // Formulario de comentarios
        $comment = new Comment();
        $commentForm = $this->createForm(CommentFormType::class, $comment);
        $commentForm->handleRequest($request);

        if ($commentForm->isSubmitted() && $commentForm->isValid()) {
            if (!$this->getUser()) {
            $this->addFlash('error', 'Debes iniciar sesión para comentar');
            return $this->redirectToRoute('app_login');
        }

        $comment->setAuthor($this->getUser());
        $comment->setNews($news);
    // Ya no se necesita setIsApproved(false), el constructor lo pone en true

        $entityManager->persist($comment);
        $entityManager->flush();

        $this->addFlash('success', 'Comentario publicado correctamente.');

            return $this->redirectToRoute('app_news_show', ['slug' => $slug]);
        }

        // Obtener votos del usuario actual
        $userVotes = [];
        if ($this->getUser()) {
            foreach ($comments as $c) {
                $vote = $voteRepository->findUserVoteForComment($this->getUser(), $c);
                if ($vote) {
                    $userVotes[$c->getId()] = $vote->getVoteType();
                }
            }
        }

        // Obtener rating del usuario
        $userRating = null;
        if ($this->getUser()) {
            $rating = $ratingRepository->findUserRatingForNews($this->getUser(), $news);
            $userRating = $rating ? $rating->getRating() : null;
        }

        return $this->render('news/show.html.twig', [
            'news' => $news,
            'comments' => $comments,
            'commentForm' => $commentForm->createView(),
            'userVotes' => $userVotes,
            'userRating' => $userRating,
        ]);
    }

    /**
     * Endpoint AJAX para votar una noticia
     *
     * Este método implementa el sistema de valoración de noticias:
     * 1. Verifica autenticación y existencia de la noticia
     * 2. Valida que el usuario no haya votado previamente
     * 3. Registra la nueva valoración
     * 4. Actualiza el promedio de la noticia
     *
     * Reglas de negocio:
     * - Solo usuarios autenticados pueden votar
     * - Valoraciones válidas: 1-5 estrellas
     * - Un usuario solo puede votar una vez por noticia
     * - Las valoraciones no se pueden modificar
     *
     * @param string $slug    Identificador de la noticia
     * @param int    $rating  Valoración (1-5)
     * @return JsonResponse  Resultado de la operación
     */
    #[Route('/{slug}/votar', name: 'app_news_vote', methods: ['POST'])]
    public function vote(
        string $slug,
        Request $request,
        NewsRepository $newsRepository,
        NewsRatingRepository $ratingRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        // Verificar autenticación
        if (!$this->getUser()) {
            return new JsonResponse(['error' => 'Debes iniciar sesión'], 401);
        }

    $news = $newsRepository->findBySlug($slug);
    if (!$news) {
        return new JsonResponse(['error' => 'Noticia no encontrada'], 404);
    }

    $rating = $request->request->getInt('rating');
    if ($rating < 1 || $rating > 5) {
        return new JsonResponse(['error' => 'Puntuación inválida'], 400);
    }

    $existingRating = $ratingRepository->findUserRatingForNews($this->getUser(), $news);

    if ($existingRating) {
        return new JsonResponse(['error' => 'Ya has votado esta noticia'], 400);
    }

    $newsRating = new NewsRating();
    $newsRating->setUser($this->getUser());
    $newsRating->setNews($news);
    $newsRating->setRating($rating);

    $entityManager->persist($newsRating);
    $entityManager->flush();

    // Recalcular promedio
    $avgRating = $ratingRepository->calculateAverageRating($news);
    $news->setAverageRating(number_format($avgRating, 2));
    $news->setRatingCount($news->getRatingCount() + 1);
    $entityManager->flush();

    return new JsonResponse([
        'success' => true,
        'averageRating' => $news->getAverageRating(),
        'ratingCount' => $news->getRatingCount(),
        'userRating' => $rating,
    ]);
}
}