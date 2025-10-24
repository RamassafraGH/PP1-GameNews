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
#[Route('/', name: 'app_news_index')]
public function index(
    Request $request,
    NewsRepository $newsRepository,
    PaginatorInterface $paginator
): Response {
    // Crear formulario
    $searchForm = $this->createForm(SearchFormType::class);
    $searchForm->handleRequest($request);

    $hasSearch = false;
    $appliedFilters = [];

    // Inicializar query builder base
    $queryBuilder = $newsRepository->createQueryBuilder('n')
        ->where('n.status = :status')
        ->setParameter('status', 'published')
        ->orderBy('n.publishedAt', 'DESC');

    // Procesar búsqueda
    if ($searchForm->isSubmitted() && $searchForm->isValid()) {
        $data = $searchForm->getData();
        
        $query = trim($data['query'] ?? '');
        $category = $data['category'] ?? null;
        $tag = $data['tag'] ?? null;
        $dateFrom = $data['dateFrom'] ?? null;
        $dateTo = $data['dateTo'] ?? null;

        // Verificar si hay al menos un filtro
        if (!empty($query) || $category !== null || $tag !== null || $dateFrom !== null || $dateTo !== null) {
            $hasSearch = true;

            // BÚSQUEDA POR TEXTO
            if (!empty($query)) {
                $searchTerm = '%' . $query . '%';
                $queryBuilder->andWhere(
                    $queryBuilder->expr()->orX(
                        $queryBuilder->expr()->like('n.title', ':searchTerm'),
                        $queryBuilder->expr()->like('n.subtitle', ':searchTerm'),
                        $queryBuilder->expr()->like('n.body', ':searchTerm')
                    )
                )
                ->setParameter('searchTerm', $searchTerm);
                
                $appliedFilters[] = 'Texto: "' . $query . '"';
            }

            // BÚSQUEDA POR CATEGORÍA
            if ($category !== null) {
                $queryBuilder
                    ->innerJoin('n.categories', 'cat')
                    ->andWhere('cat.id = :categoryId')
                    ->setParameter('categoryId', $category->getId());
                
                $appliedFilters[] = 'Categoría: ' . $category->getName();
            }

            // BÚSQUEDA POR ETIQUETA
            if ($tag !== null) {
                $queryBuilder
                    ->innerJoin('n.tags', 'tag')
                    ->andWhere('tag.id = :tagId')
                    ->setParameter('tagId', $tag->getId());
                
                $appliedFilters[] = 'Etiqueta: ' . $tag->getName();
            }

            // BÚSQUEDA POR RANGO DE FECHAS
            if ($dateFrom !== null) {
                $dateFrom->setTime(0, 0, 0);
                $queryBuilder
                    ->andWhere('n.publishedAt >= :dateFrom')
                    ->setParameter('dateFrom', $dateFrom);
                
                $appliedFilters[] = 'Desde: ' . $dateFrom->format('d/m/Y');
            }

            if ($dateTo !== null) {
                $dateTo->setTime(23, 59, 59);
                $queryBuilder
                    ->andWhere('n.publishedAt <= :dateTo')
                    ->setParameter('dateTo', $dateTo);
                
                $appliedFilters[] = 'Hasta: ' . $dateTo->format('d/m/Y');
            }

            // Evitar duplicados cuando se usan joins
            $queryBuilder->distinct();
        }
    }

    // Paginar resultados
    $pagination = $paginator->paginate(
        $queryBuilder->getQuery(),
        $request->query->getInt('page', 1),
        12
    );

    return $this->render('news/index.html.twig', [
        'pagination' => $pagination,
        'searchForm' => $searchForm->createView(),
        'hasSearch' => $hasSearch,
        'appliedFilters' => $appliedFilters,
    ]);
}

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

    #[Route('/{slug}/votar', name: 'app_news_vote', methods: ['POST'])]
public function vote(
    string $slug,
    Request $request,
    NewsRepository $newsRepository,
    NewsRatingRepository $ratingRepository,
    EntityManagerInterface $entityManager
): JsonResponse {
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