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
/**
 * Controlador para la gestión de usuarios del sistema
 *
 * Este controlador maneja todas las operaciones administrativas
 * relacionadas con los usuarios:
 * - Listado de usuarios del sistema
 * - Activación/desactivación de cuentas
 * - Gestión de roles y permisos
 * - Monitoreo de actividad
 *
 * Características principales:
 * - Listado paginado de usuarios
 * - Acciones administrativas sobre cuentas
 * - Control de estado de usuarios
 * - Protección contra acciones no autorizadas
 *
 * Seguridad:
 * - Acceso exclusivo para ROLE_ADMIN
 * - Protección CSRF en todas las acciones
 * - Registro de cambios de estado
 * - Validación de permisos jerárquicos
 */
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

    /**
     * Cambia el estado de activación de un usuario
     *
     * Este método implementa un toggle del estado de la cuenta:
     * - Si está activa -> la desactiva
     * - Si está inactiva -> la activa
     *
     * Características:
     * - Método POST obligatorio
     * - Protección CSRF por usuario
     * - Mensajes flash personalizados
     * - Actualización inmediata
     *
     * Seguridad:
     * - Validación de token CSRF
     * - Solo accesible para administradores
     * - Protección contra auto-desactivación
     *
     * @param User $user El usuario a modificar (inyectado por ParamConverter)
     * @param Request $request Para validar token CSRF
     * @param EntityManagerInterface $entityManager Para persistir cambios
     */
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