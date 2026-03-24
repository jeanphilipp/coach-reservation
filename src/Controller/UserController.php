<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class UserController extends AbstractController
{
    #[Route('/profil/supprimer', name: 'app_user_delete', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function delete(Request $request, EntityManagerInterface $entityManager, SessionInterface $session): Response
    {
        $user = $this->getUser();

        if ($this->isCsrfTokenValid('delete_account' . $user->getUserIdentifier(), $request->request->get('_token'))) {
            // Supprimer les réservations de l'utilisateur d'abord si nécessaire
            // Dans ce projet, on peut s'appuyer sur l'EntityManager pour tout supprimer

            $entityManager->remove($user);
            $entityManager->flush();

            // Invalider la session
            $session->invalidate();
            $this->container->get('security.token_storage')->setToken(null);

            $this->addFlash('success', 'Votre compte et toutes vos données ont été supprimés avec succès.');
        }

        return $this->redirectToRoute('app_home');
    }
}
