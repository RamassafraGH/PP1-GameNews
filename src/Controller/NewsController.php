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
        $searchForm = $this->createForm(SearchFormType::class);
        $searchForm->handleRequest($request);

        // Inicializar el query builder
        $queryBuilder = $newsRepository->createQueryBuilder('n')
            ->where('n.status = :status')
            ->setParameter('status', 'published')
            ->orderBy('n.publishedAt', 'DESC');

        // Aplicar filtros si el formulario fue enviado
        if ($searchForm->isSubmitted() && $searchForm->isValid()) {
            $data = $searchForm->getData();
            
            $queryBuilder = $newsRepository->searchNews(
                $data['query'] ?? null,
                $data['category'] ?? null,
                $data['tag'] ?? null,
                $data['dateFrom'] ?? null,
                $data['dateTo'] ?? null
            );
        }

        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            12
        );

        return $this->render('news/index.html.twig', [
            'pagination' => $pagination,
            'searchForm' => $searchForm->createView(),
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

        // Actualizar promedio
        $news->setRatingCount($news->getRatingCount() + 1);
        $avgRating = $ratingRepository->calculateAverageRating($news);
        $news->setAverageRating(number_format($avgRating, 2));

        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'averageRating' => $news->getAverageRating(),
            'ratingCount' => $news->getRatingCount(),
        ]);
    }
}