<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/usuarios')]
#[IsGranted('ROLE_ADMIN')]
class UserManagementController extends AbstractController
{
    #[Route('/', name: 'app_admin_users_index')]
    public function index(
        Request $request,
        UserRepository $userRepository,
        PaginatorInterface $paginator
    ): Response {
        $queryBuilder = $userRepository->createQueryBuilder('u')
            ->orderBy('u.createdAt', 'DESC');

        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            20
        );

        return $this->render('admin/users/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/{id}/cambiar-estado', name: 'app_admin_users_toggle_status', methods: ['POST'])]
    public function toggleStatus(
        User $user,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->isCsrfTokenValid('toggle_status' . $user->getId(), $request->request->get('_token'))) {
            $user->setIsActive(!$user->isActive());
            $entityManager->flush();

            $message = $user->isActive() ? 'Usuario activado' : 'Usuario desactivado';
            $this->addFlash('success', $message);
        }

        return $this->redirectToRoute('app_admin_users_index');
    }

    #[Route('/{id}/cambiar-rol', name: 'app_admin_users_change_role', methods: ['POST'])]
    public function changeRole(
        User $user,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->isCsrfTokenValid('change_role' . $user->getId(), $request->request->get('_token'))) {
            $newRole = $request->request->get('role');
            
            if (in_array($newRole, ['ROLE_USER', 'ROLE_EDITOR', 'ROLE_ADMIN'])) {
                $user->setRoles([$newRole]);
                $entityManager->flush();

                $this->addFlash('success', 'Rol actualizado correctamente');
            }
        }

        return $this->redirectToRoute('app_admin_users_index');
    }
}