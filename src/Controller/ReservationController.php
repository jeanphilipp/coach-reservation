<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Repository\DisciplineRepository;
use App\Repository\ReservationRepository;
use DateTimeImmutable;
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
        ReservationRepository $reservationRepository
    ): Response {
        $date = $request->query->get('date');        // YYYY-mm-dd
        $hour = (int) $request->query->get('hour');  // 17/18/19/20

        $disciplines = $disciplineRepository->findAll();

        if ($request->isMethod('POST')) {
            $disciplineId = (int) $request->request->get('discipline_id');
            $discipline = $disciplineRepository->find($disciplineId);

            if (!$discipline) {
                throw $this->createNotFoundException('Discipline not found.');
            }

            $reservation = new Reservation();
            $reservation->setUser($this->getUser());
            $reservation->setDiscipline($discipline);
            $reservation->setSessionDate(new \DateTime($date));
            $reservation->setStartHour($hour);
            $reservation->setStatus('PENDING');
            $reservation->setCreatedAt(new DateTimeImmutable());

            $reservationRepository->save($reservation, true);

            return $this->redirectToRoute('app_planning');
        }

        return $this->render('reservation/new.html.twig', [
            'date' => $date,
            'hour' => $hour,
            'disciplines' => $disciplines,
        ]);
    }
}
