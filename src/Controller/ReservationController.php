<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ReservationController extends AbstractController
{
    #[Route('/reservation/new', name: 'app_reservation_new')]
    #[IsGranted('ROLE_USER')]
    public function new(Request $request): Response
    {
        $date = $request->query->get('date');
        $hour = (int) $request->query->get('hour');

        return $this->render('reservation/new.html.twig', [
            'date' => $date,
            'hour' => $hour,
        ]);
    }
}
