<?php

namespace App\Controller\Admin;

use App\Entity\News;
use App\Form\NewsFormType;
use App\Repository\NewsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin/noticias')]
#[IsGranted('ROLE_EDITOR')]
class NewsManagementController extends AbstractController
{
    #[Route('/', name: 'app_admin_news_index')]
    public function index(
        Request $request,
        NewsRepository $newsRepository,
        PaginatorInterface $paginator
    ): Response {
        $queryBuilder = $newsRepository->createQueryBuilder('n')
            ->orderBy('n.createdAt', 'DESC');

        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            20
        );

        return $this->render('admin/news/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/nueva', name: 'app_admin_news_new')]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
    ): Response {
        $news = new News();
        $form = $this->createForm(NewsFormType::class, $news);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Generar slug
            $slug = $slugger->slug($news->getTitle())->lower();
            $news->setSlug($slug . '-' . uniqid());

            // Subir imagen
            $featuredImageFile = $form->get('featuredImageFile')->getData();
            if ($featuredImageFile) {
                $originalFilename = pathinfo($featuredImageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $featuredImageFile->guessExtension();

                try {
                    $featuredImageFile->move(
                        $this->getParameter('news_images_directory'),
                        $newFilename
                    );
                    $news->setFeaturedImage($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Error al subir la imagen');
                }
            }

            $news->setAuthor($this->getUser());

            if ($news->getStatus() === 'published') {
                $news->setPublishedAt(new \DateTime());
            }

            $entityManager->persist($news);
            $entityManager->flush();

            $this->addFlash('success', 'Noticia creada correctamente');

            return $this->redirectToRoute('app_admin_news_index');
        }

        return $this->render('admin/news/new.html.twig', [
            'newsForm' => $form->createView(),
        ]);
    }

    #[Route('/{id}/editar', name: 'app_admin_news_edit')]
    public function edit(
        News $news,
        Request $request,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
    ): Response {
        $form = $this->createForm(NewsFormType::class, $news);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $featuredImageFile = $form->get('featuredImageFile')->getData();
            if ($featuredImageFile) {
                $originalFilename = pathinfo($featuredImageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $featuredImageFile->guessExtension();

                try {
                    $featuredImageFile->move(
                        $this->getParameter('news_images_directory'),
                        $newFilename
                    );
                    $news->setFeaturedImage($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Error al subir la imagen');
                }
            }

            $news->setUpdatedAt(new \DateTime());

            if ($news->getStatus() === 'published' && !$news->getPublishedAt()) {
                $news->setPublishedAt(new \DateTime());
            }

            $entityManager->flush();

            $this->addFlash('success', 'Noticia actualizada correctamente');

            return $this->redirectToRoute('app_admin_news_index');
        }

        return $this->render('admin/news/edit.html.twig', [
            'news' => $news,
            'newsForm' => $form->createView(),
        ]);
    }

    #[Route('/{id}/eliminar', name: 'app_admin_news_delete', methods: ['POST'])]
    public function delete(
        News $news,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->isCsrfTokenValid('delete' . $news->getId(), $request->request->get('_token'))) {
            $entityManager->remove($news);
            $entityManager->flush();

            $this->addFlash('success', 'Noticia eliminada correctamente');
        }

        return $this->redirectToRoute('app_admin_news_index');
    }
}