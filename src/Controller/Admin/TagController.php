<?php

namespace App\Controller\Admin;

use App\Entity\Tag;
use App\Form\TagFormType;
use App\Repository\TagRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin/etiquetas')]
#[IsGranted('ROLE_EDITOR')]
/**
 * Controlador para la gestión de etiquetas (tags)
 *
 * Este controlador maneja todas las operaciones CRUD relacionadas
 * con las etiquetas de las noticias:
 * - Listado ordenado de etiquetas
 * - Creación de nuevas etiquetas
 * - Edición de etiquetas existentes
 * - Eliminación de etiquetas
 *
 * Características:
 * - Generación automática de slugs
 * - Validación de nombres únicos
 * - Gestión de relaciones con noticias
 * - Control de acceso por roles
 *
 * Seguridad:
 * - Requiere ROLE_EDITOR
 * - Validación de formularios
 * - Protección CSRF
 * - Verificación de relaciones antes de eliminar
 */
class TagController extends AbstractController
{
    #[Route('/', name: 'app_admin_tag_index')]
    public function index(TagRepository $tagRepository): Response
    {
        $tags = $tagRepository->findAllOrdered();

        return $this->render('admin/tag/index.html.twig', [
            'tags' => $tags,
        ]);
    }

    /**
     * Crea una nueva etiqueta
     *
     * Este método implementa el proceso de creación de etiquetas:
     * 1. Genera el formulario para datos de la etiqueta
     * 2. Procesa la submisión y valida datos
     * 3. Genera un slug único para la URL
     * 4. Persiste la nueva etiqueta
     *
     * Validaciones:
     * - Nombre único de etiqueta
     * - Slug único generado
     * - Campos requeridos completos
     *
     * @param Request $request Para procesar el formulario
     * @param EntityManagerInterface $entityManager Para persistir la etiqueta
     * @param SluggerInterface $slugger Para generar URLs amigables
     */
    #[Route('/nueva', name: 'app_admin_tag_new')]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
    ): Response {
        $tag = new Tag();
        $form = $this->createForm(TagFormType::class, $tag);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $slug = $slugger->slug($tag->getName())->lower();
            $tag->setSlug($slug);

            $entityManager->persist($tag);
            $entityManager->flush();

            $this->addFlash('success', 'Etiqueta creada correctamente');

            return $this->redirectToRoute('app_admin_tag_index');
        }

        return $this->render('admin/tag/new.html.twig', [
            'tagForm' => $form->createView(),
        ]);
    }

    #[Route('/{id}/editar', name: 'app_admin_tag_edit')]
    public function edit(
        Tag $tag,
        Request $request,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
    ): Response {
        $form = $this->createForm(TagFormType::class, $tag);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $slug = $slugger->slug($tag->getName())->lower();
            $tag->setSlug($slug);

            $entityManager->flush();

            $this->addFlash('success', 'Etiqueta actualizada correctamente');

            return $this->redirectToRoute('app_admin_tag_index');
        }

        return $this->render('admin/tag/edit.html.twig', [
            'tag' => $tag,
            'tagForm' => $form->createView(),
        ]);
    }

    #[Route('/{id}/eliminar', name: 'app_admin_tag_delete', methods: ['POST'])]
    public function delete(
        Tag $tag,
        Request $request,
        TagRepository $tagRepository,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->isCsrfTokenValid('delete' . $tag->getId(), $request->request->get('_token'))) {
            $newsCount = $tagRepository->countNewsInTag($tag);
            
            if ($newsCount > 0) {
                $this->addFlash('error', 'No se puede eliminar. Hay noticias asociadas a esta etiqueta.');
                return $this->redirectToRoute('app_admin_tag_index');
            }

            $entityManager->remove($tag);
            $entityManager->flush();

            $this->addFlash('success', 'Etiqueta eliminada correctamente');
        }

        return $this->redirectToRoute('app_admin_tag_index');
    }
}