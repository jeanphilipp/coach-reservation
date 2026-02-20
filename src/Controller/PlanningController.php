<?php

namespace App\Controller;

use App\Repository\ReservationRepository;
use DateInterval;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PlanningController extends AbstractController
{
    #[Route('/planning', name: 'app_planning')]
    public function index(ReservationRepository $reservationRepository): Response
    {
        $today = new DateTimeImmutable('today');

        // Lundi de la semaine courante (ISO-8601: lundi = 1)
        $monday = $today->modify('monday this week');
        $days = [];
        for ($i = 0; $i < 5; $i++) {
            $days[] = $monday->add(new DateInterval('P' . $i . 'D'));
        }

        $hours = [17, 18, 19, 20];

        // Récupère les réservations de la semaine (lundi 00:00 -> samedi 00:00)
        $weekStart = $monday;
        $weekEnd = $monday->add(new DateInterval('P5D'));

        $reservations = $reservationRepository->createQueryBuilder('r')
            ->andWhere('r.sessionDate >= :start')
            ->andWhere('r.sessionDate < :end')
            ->setParameter('start', $weekStart->format('Y-m-d'))
            ->setParameter('end', $weekEnd->format('Y-m-d'))
            ->getQuery()
            ->getResult();

        // Index "réservé" : [YYYY-mm-dd][hour] = true
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
        ]);
    }
}
