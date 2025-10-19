<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Repository\NewsRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/boletin')]
#[IsGranted('ROLE_EDITOR')]
class NewsletterController extends AbstractController
{
    #[Route('/suscriptores', name: 'app_admin_newsletter_subscribers')]
    public function subscribers(
        Request $request,
        UserRepository $userRepository,
        PaginatorInterface $paginator
    ): Response {
        $queryBuilder = $userRepository->createQueryBuilder('u')
            ->where('u.isSubscribedToNewsletter = :subscribed')
            ->andWhere('u.isActive = :active')
            ->setParameter('subscribed', true)
            ->setParameter('active', true)
            ->orderBy('u.createdAt', 'DESC');

        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            20
        );

        return $this->render('admin/newsletter/subscribers.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/enviar', name: 'app_admin_newsletter_send')]
    public function send(
        Request $request,
        NewsRepository $newsRepository,
        UserRepository $userRepository
    ): Response {
        // Obtener noticias publicadas de los últimos 30 días
        $recentNews = $newsRepository->createQueryBuilder('n')
            ->where('n.status = :status')
            ->andWhere('n.publishedAt >= :date')
            ->setParameter('status', 'published')
            ->setParameter('date', new \DateTime('-30 days'))
            ->orderBy('n.publishedAt', 'DESC')
            ->getQuery()
            ->getResult();

        if ($request->isMethod('POST')) {
            $selectedNewsIds = $request->request->all('news');
            
            if (empty($selectedNewsIds)) {
                $this->addFlash('error', 'Debes seleccionar al menos una noticia');
                return $this->redirectToRoute('app_admin_newsletter_send');
            }

            $subscribers = $userRepository->findActiveSubscribers();
            
            // Aquí iría la lógica de envío de emails
            // Por ahora solo mostramos un mensaje de éxito simulado
            
            $this->addFlash('success', sprintf(
                'Boletín enviado correctamente a %d suscriptores con %d noticias',
                count($subscribers),
                count($selectedNewsIds)
            ));

            return $this->redirectToRoute('app_admin_newsletter_subscribers');
        }

        return $this->render('admin/newsletter/send.html.twig', [
            'recentNews' => $recentNews,
        ]);
    }
}