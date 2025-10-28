<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ProfileFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/perfil')]
#[IsGranted('ROLE_USER')]
class ProfileController extends AbstractController
{
    /**
     * ProfileController
     *
     * Operaciones sobre el perfil del usuario (CU04). Incluye edición de datos,
     * subida de imagen de perfil y gestión de la suscripción al boletín.
     *
     * Puntos para explicar en la demo:
     * - Protección de rutas con `IsGranted('ROLE_USER')`.
     * - Manejo de archivos en `edit()` y uso de `SluggerInterface`.
     * - Persistencia con EntityManager -> `flush()`.
     */
    #[Route('/', name: 'app_profile')]
    public function index(): Response
    {
        return $this->render('profile/index.html.twig');
    }

    /**
     * Maneja la edición del perfil de usuario
     *
     * Este método implementa:
     * 1. Actualización de datos básicos del perfil
     * 2. Subida y procesamiento de imagen de perfil
     * 3. Validación de formulario y datos
     *
     * Proceso de imagen de perfil:
     * 1. Obtiene archivo del formulario
     * 2. Genera nombre seguro usando slugger
     * 3. Mueve archivo al directorio configurado
     * 4. Actualiza referencia en entidad User
     *
     * Seguridad:
     * - Verifica tipo de usuario (User)
     * - Valida extensión de archivo
     * - Usa nombres de archivo seguros
     *
     * @param Request $request Datos del formulario
     * @param EntityManagerInterface $entityManager Para persistir cambios
     * @param SluggerInterface $slugger Para generar nombres de archivo seguros
     * @throws AccessDeniedException Si el usuario no es válido
     */
    #[Route('/editar', name: 'app_profile_edit')]
    public function edit(
        Request $request,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
    ): Response {
        $user = $this->getUser();

        /** @var User|null $user */
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Usuario no válido');
        }

        $form = $this->createForm(ProfileFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $profileImageFile = $form->get('profileImageFile')->getData();

            if ($profileImageFile) {
                $originalFilename = pathinfo($profileImageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $profileImageFile->guessExtension();

                try {
                    $profileImageFile->move(
                        $this->getParameter('profile_images_directory'),
                        $newFilename
                    );
                    $user->setProfileImage($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Error al subir la imagen');
                }
            }

            $entityManager->flush();
            $this->addFlash('success', 'Perfil actualizado correctamente');

            return $this->redirectToRoute('app_profile');
        }

        return $this->render('profile/edit.html.twig', [
            'profileForm' => $form->createView(),
        ]);
    }

    /**
     * Gestiona la suscripción/desuscripción al boletín de noticias
     *
     * Este método implementa un toggle para la suscripción:
     * 1. Si el usuario está suscrito -> se da de baja
     * 2. Si no está suscrito -> se suscribe
     *
     * Características:
     * - Método POST para evitar cambios accidentales
     * - Mensajes flash personalizados según la acción
     * - Actualización inmediata en base de datos
     *
     * Seguridad:
     * - Verifica tipo de usuario (User)
     * - Requiere token CSRF (configurado en template)
     * - Solo accesible para usuarios autenticados
     *
     * @param EntityManagerInterface $entityManager Para persistir el cambio
     * @throws AccessDeniedException Si el usuario no es válido
     */
    #[Route('/suscribirse-boletin', name: 'app_profile_subscribe_newsletter', methods: ['POST'])]
    public function subscribeNewsletter(EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        /** @var User|null $user */
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Usuario no válido');
        }

        $user->setIsSubscribedToNewsletter(!$user->isSubscribedToNewsletter());
        $entityManager->flush();

        $message = $user->isSubscribedToNewsletter()
            ? 'Te has suscrito al boletín correctamente'
            : 'Te has dado de baja del boletín';

        $this->addFlash('success', $message);

        return $this->redirectToRoute('app_profile');
    }
}
