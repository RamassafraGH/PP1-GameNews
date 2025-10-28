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

    /**
     * Crea una nueva noticia en el sistema
     *
     * Este método maneja el proceso completo de creación de noticias:
     * 1. Generación de slug único para URL amigable
     * 2. Procesamiento de imagen destacada
     * 3. Asignación de autor y fecha de publicación
     *
     * Procesamiento de archivos:
     * 1. Validación de imagen subida
     * 2. Generación de nombre seguro
     * 3. Movimiento al directorio configurado
     *
     * Reglas de negocio:
     * - Solo editores pueden crear noticias
     * - El slug debe ser único
     * - Fecha de publicación automática si status = published
     * - Autor asignado automáticamente
     *
     * @param Request $request Para procesar formulario
     * @param EntityManagerInterface $entityManager Para persistir la noticia
     * @param SluggerInterface $slugger Para generar URLs amigables
     * @throws FileException Si hay error al subir imagen
     */
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

    /**
     * Edita una noticia existente
     *
     * Este método gestiona la actualización de noticias con:
     * 1. Gestión de imágenes (mantener existente o subir nueva)
     * 2. Control de fechas (actualización y publicación)
     * 3. Manejo de estado de publicación
     *
     * Características principales:
     * - ParamConverter inyecta la noticia automáticamente
     * - Actualización selectiva de imagen
     * - Control automático de fechas
     * - Manejo de estado de publicación
     *
     * Reglas de negocio:
     * - Solo editores pueden modificar noticias
     * - Actualización automática de updatedAt
     * - PublishedAt se establece al publicar por primera vez
     * - Mantiene imagen anterior si no se sube nueva
     *
     * @param News $news La noticia a editar (inyectada por ParamConverter)
     * @param Request $request Para procesar el formulario
     * @param EntityManagerInterface $entityManager Para persistir cambios
     * @param SluggerInterface $slugger Para procesar nombres de archivo
     * @throws FileException Si hay error al subir imagen
     */
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