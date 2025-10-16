<?php

namespace App\Controller\Admin;

use App\Entity\Category;
use App\Form\CategoryFormType;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin/categorias')]
#[IsGranted('ROLE_EDITOR')]
class CategoryController extends AbstractController
{
    #[Route('/', name: 'app_admin_category_index')]
    public function index(CategoryRepository $categoryRepository): Response
    {
        $categories = $categoryRepository->findAllOrdered();

        return $this->render('admin/category/index.html.twig', [
            'categories' => $categories,
        ]);
    }

    #[Route('/nueva', name: 'app_admin_category_new')]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
    ): Response {
        $category = new Category();
        $form = $this->createForm(CategoryFormType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $slug = $slugger->slug($category->getName())->lower();
            $category->setSlug($slug);

            $entityManager->persist($category);
            $entityManager->flush();

            $this->addFlash('success', 'Categoría creada correctamente');

            return $this->redirectToRoute('app_admin_category_index');
        }

        return $this->render('admin/category/new.html.twig', [
            'categoryForm' => $form->createView(),
        ]);
    }

    #[Route('/{id}/editar', name: 'app_admin_category_edit')]
    public function edit(
        Category $category,
        Request $request,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
    ): Response {
        $form = $this->createForm(CategoryFormType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $slug = $slugger->slug($category->getName())->lower();
            $category->setSlug($slug);

            $entityManager->flush();

            $this->addFlash('success', 'Categoría actualizada correctamente');

            return $this->redirectToRoute('app_admin_category_index');
        }

        return $this->render('admin/category/edit.html.twig', [
            'category' => $category,
            'categoryForm' => $form->createView(),
        ]);
    }

    #[Route('/{id}/eliminar', name: 'app_admin_category_delete', methods: ['POST'])]
    public function delete(
        Category $category,
        Request $request,
        CategoryRepository $categoryRepository,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->isCsrfTokenValid('delete' . $category->getId(), $request->request->get('_token'))) {
            // Verificar si hay noticias asociadas
            $newsCount = $categoryRepository->countNewsInCategory($category);
            
            if ($newsCount > 0) {
                $this->addFlash('error', 'No se puede eliminar. Hay noticias asociadas a esta categoría.');
                return $this->redirectToRoute('app_admin_category_index');
            }

            $entityManager->remove($category);
            $entityManager->flush();

            $this->addFlash('success', 'Categoría eliminada correctamente');
        }

        return $this->redirectToRoute('app_admin_category_index');
    }
}