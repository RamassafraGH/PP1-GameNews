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
        CategoryRepository $categoryRepository,
        TagRepository $tagRepository,
        PaginatorInterface $paginator
    ): Response {
        $hasSearch = false;
        $appliedFilters = [];

        // Query base
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
            if (!empty($searchQuery)) {
                $queryBuilder->andWhere(
                    $queryBuilder->expr()->orX(
                        'n.title LIKE :search',
                        'n.subtitle LIKE :search',
                        'n.body LIKE :search'
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