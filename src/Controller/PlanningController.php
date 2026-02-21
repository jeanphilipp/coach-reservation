<?php

namespace App\Controller;

use App\Repository\ReservationRepository;
use DateInterval;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PlanningController extends AbstractController
{
    #[Route('/planning', name: 'app_planning')]
    public function index(Request $request, ReservationRepository $reservationRepository): Response
    {
        $weekOffset = (int) $request->query->get('week', 0); // -1 / 0 / 1 / ...

        $today = new DateTimeImmutable('today');

        // Lundi de la semaine courante, puis décalage de X semaines
        $monday = $today->modify('monday this week')->modify(sprintf('%+d week', $weekOffset));

        $days = [];
        for ($i = 0; $i < 5; $i++) {
            $days[] = $monday->add(new DateInterval('P' . $i . 'D'));
        }

        $hours = [17, 18, 19, 20];

        $weekStart = $monday; // lundi 00:00
        $weekEnd = $monday->add(new DateInterval('P5D')); // samedi 00:00

        $reservations = $reservationRepository->createQueryBuilder('r')
            ->andWhere('r.sessionDate >= :start')
            ->andWhere('r.sessionDate < :end')
            ->setParameter('start', $weekStart->format('Y-m-d'))
            ->setParameter('end', $weekEnd->format('Y-m-d'))
            ->getQuery()
            ->getResult();

        $booked = [];
        foreach ($reservations as $r) {
            $dateKey = $r->getSessionDate()->format('Y-m-d');
            $hourKey = $r->getStartHour();
            $booked[$dateKey][$hourKey] = true;
        }

        return $this->render('planning/index.html.twig', [
            'days' => $days,
            'hours' => $hours,
            'booked' => $booked,
            'weekOffset' => $weekOffset,
        ]);
    }
}
