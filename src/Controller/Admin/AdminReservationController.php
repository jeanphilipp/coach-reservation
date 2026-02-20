<?php

namespace App\Controller\Admin;

use App\Repository\ReservationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

use App\Entity\Reservation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminReservationController extends AbstractController
{
    #[Route('/reservations', name: 'admin_reservations', methods: ['GET'])]
    public function index(ReservationRepository $reservationRepository): Response
    {
        $pending = $reservationRepository->findPending();

        return $this->render('admin/reservations.html.twig', [
            'pending' => $pending,
        ]);
    }

    #[Route('/reservations/{id}/accept', name: 'admin_reservations_accept', methods: ['POST'])]
    public function accept(
        Reservation $reservation,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        if (!$this->isCsrfTokenValid('admin_reservation_'.$reservation->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $reservation->setStatus('ACCEPTED');
        $em->flush();

        $this->addFlash('success', 'Réservation acceptée.');
        return $this->redirectToRoute('admin_reservations');
    }

    #[Route('/reservations/{id}/refuse', name: 'admin_reservations_refuse', methods: ['POST'])]
    public function refuse(
        Reservation $reservation,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        if (!$this->isCsrfTokenValid('admin_reservation_'.$reservation->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        // Refus = suppression => le créneau est libéré
        $em->remove($reservation);
        $em->flush();

        $this->addFlash('success', 'Réservation refusée (créneau libéré).');
        return $this->redirectToRoute('admin_reservations');
    }


}
