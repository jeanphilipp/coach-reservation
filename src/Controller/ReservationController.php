<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Repository\DisciplineRepository;
use App\Repository\ReservationRepository;
use App\Service\ActivityLogService;
use DateTimeImmutable;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ReservationController extends AbstractController
{
    #[Route('/reservation/new', name: 'app_reservation_new')]
    #[IsGranted('ROLE_USER')]
    public function new(
        Request $request,
        DisciplineRepository $disciplineRepository,
        ReservationRepository $reservationRepository,
        ActivityLogService $activityLogService
    ): Response {
        $date = $request->query->get('date');        // YYYY-mm-dd
        $hour = (int) $request->query->get('hour');  // 17/18/19/20

        if (!$date || !\DateTime::createFromFormat('Y-m-d', $date)) {
            throw $this->createNotFoundException('Date invalide.');
        }

        if (!in_array($hour, [17, 18, 19, 20], true)) {
            throw $this->createNotFoundException('Heure invalide.');
        }

        if (new \DateTime($date) < new \DateTime('today')) {
            $this->addFlash('error', 'Impossible de réserver un créneau passé.');
            return $this->redirectToRoute('app_planning');
        }

        $disciplines = $disciplineRepository->findAll();

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('reservation_new', (string) $request->request->get('_token'))) {
                throw $this->createAccessDeniedException('Invalid CSRF token.');
            }

            $disciplineId = (int) $request->request->get('discipline_id');
            $discipline = $disciplineRepository->find($disciplineId);

            if (!$discipline) {
                throw $this->createNotFoundException('Discipline not found.');
            }

            $existing = $reservationRepository->findOneBy([
                'sessionDate' => new \DateTime($date),
                'startHour' => $hour,
            ]);

            if ($existing) {
                $this->addFlash('error', 'Ce créneau est déjà réservé. Choisis un autre horaire.');
                return $this->redirectToRoute('app_planning');
            }

            $reservation = new Reservation();
            $reservation->setUser($this->getUser());
            $reservation->setDiscipline($discipline);
            $reservation->setSessionDate(new \DateTime($date));
            $reservation->setStartHour($hour);
            $reservation->setStatus('PENDING');
            $reservation->setCreatedAt(new DateTimeImmutable());

            try {
                $reservationRepository->save($reservation, true);
            } catch (UniqueConstraintViolationException) {
                $this->addFlash('error', 'Ce créneau vient d\'être réservé simultanément. Choisis un autre horaire.');
                return $this->redirectToRoute('app_planning');
            }

            // Appel au service NoSQL (Logs)
            $activityLogService->logAction($this->getUser(), 'reservation_created', $reservation);

            return $this->redirectToRoute('app_planning');
        }

        return $this->render('reservation/new.html.twig', [
            'date' => $date,
            'hour' => $hour,
            'disciplines' => $disciplines,
        ]);
    }

    #[Route('/mes-reservations', name: 'app_my_reservations', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function myReservations(ReservationRepository $reservationRepository): Response
    {
        $reservations = $reservationRepository->findByUserOrdered($this->getUser());

        return $this->render('reservation/my_reservations.html.twig', [
            'reservations' => $reservations,
        ]);
    }

    #[Route('/reservation/{id}/cancel', name: 'app_reservation_cancel', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function cancel(
        Reservation $reservation,
        Request $request,
        ReservationRepository $reservationRepository,
        ActivityLogService $activityLogService
    ): Response {
        // Sécurité : vérifier que la réservation appartient bien à l'utilisateur connecté
        if ($reservation->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($reservation->getStatus() !== 'PENDING') {
            $this->addFlash('error', 'Seules les réservations en attente peuvent être annulées.');
            return $this->redirectToRoute('app_my_reservations');
        }

        // Vérification CSRF
        if (!$this->isCsrfTokenValid('cancel_reservation_'.$reservation->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $today = new \DateTimeImmutable('today');

        if ($reservation->getSessionDate() < $today) {
            $this->addFlash('error', 'Impossible d’annuler une réservation passée.');
            return $this->redirectToRoute('app_my_reservations');
        }

        // Suppression = libère le créneau
        // Avant de supprimer en SQL, on logue l'action en NoSQL
        $activityLogService->logAction($this->getUser(), 'reservation_cancelled', $reservation);

        $reservationRepository->remove($reservation, true);

        $this->addFlash('success', 'Réservation annulée.');
        return $this->redirectToRoute('app_my_reservations');
    }
}
