<?php

namespace App\Controller;

use App\Repository\TicketRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class VentesController extends AbstractController
{
    #[Route('/ventes', name: 'app_ventes')]
    public function index(TicketRepository $ticketRepository): Response
    {
        $tickets = $ticketRepository->findAll();
        $now = new \DateTimeImmutable();

        // Tickets vendus aujourd'hui
        $startOfDay = $now->setTime(0, 0, 0);
        $endOfDay = $now->setTime(23, 59, 59);
        $ticketsToday = $ticketRepository->createQueryBuilder('t')
            ->where('t.issuedAt >= :startOfDay')
            ->andWhere('t.issuedAt <= :endOfDay')
            ->setParameter('startOfDay', $startOfDay)
            ->setParameter('endOfDay', $endOfDay)
            ->select('COUNT(t.id)')
            ->getQuery()
            ->getSingleScalarResult();

        // Revenus totaux
        $totalRevenue = $ticketRepository->createQueryBuilder('t')
            ->select('COALESCE(SUM(t.price * COALESCE(t.quantity, 1)), 0)')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        // Revenus aujourd'hui
        $revenueToday = $ticketRepository->createQueryBuilder('t')
            ->where('t.issuedAt >= :startOfDay')
            ->andWhere('t.issuedAt <= :endOfDay')
            ->setParameter('startOfDay', $startOfDay)
            ->setParameter('endOfDay', $endOfDay)
            ->select('COALESCE(SUM(t.price * COALESCE(t.quantity, 1)), 0)')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        // Derniers tickets
        $recentTickets = $ticketRepository->findBy([], ['issuedAt' => 'DESC'], 10);

        return $this->render('ventes/index.html.twig', [
            'tickets_count' => count($tickets),
            'tickets_today' => $ticketsToday,
            'total_revenue' => $totalRevenue,
            'revenue_today' => $revenueToday,
            'recent_tickets' => $recentTickets,
        ]);
    }
}


