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
    #[Route('/', name: 'app_profile')]
    public function index(): Response
    {
        return $this->render('profile/index.html.twig');
    }

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
