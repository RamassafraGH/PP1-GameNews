<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    /**
     * SecurityController
     *
     * Controla el login/logout. Observa que el método `logout()` queda vacío ya
     * que Symfony intercepta la ruta según la configuración del firewall.
     *
     * En la demo: mostrar la plantilla `security/login.html.twig` y explicar
     * cómo `AuthenticationUtils` provee el último usuario y errores.
     */
    /**
     * Maneja el proceso de inicio de sesión
     *
     * Este método implementa la página de login con las siguientes características:
     * 1. Redirección automática si ya hay sesión
     * 2. Gestión de errores de autenticación
     * 3. Recordar último nombre de usuario
     *
     * Flujo de seguridad:
     * 1. Symfony intercepta el envío del formulario
     * 2. El Guard Authenticator valida credenciales
     * 3. En caso de error, AuthenticationUtils mantiene el contexto
     * 4. En éxito, redirección según security.yaml
     *
     * @param AuthenticationUtils $authenticationUtils Utilidad de Symfony para gestionar autenticación
     * @return Response Vista de login o redirección si ya hay sesión
     */
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // Redirección si ya hay sesión activa
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}