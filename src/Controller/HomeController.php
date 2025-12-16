<?php

namespace App\Controller;

use App\Repository\SponsorContractRepository;
use App\Repository\SponsorRepository;
use App\Repository\SponsorshipRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(
        SponsorRepository $sponsorRepository,
        SponsorContractRepository $contractRepository,
        SponsorshipRepository $sponsorshipRepository
    ): Response {
        return $this->render('home/index.html.twig', [
            'sponsors_count' => $sponsorRepository->count([]),
            'contracts_count' => $contractRepository->count([]),
            'sponsorships_count' => $sponsorshipRepository->count([]),
            'recent_sponsors' => $sponsorRepository->findBy([], ['id' => 'DESC'], 5),
            'recent_contracts' => $contractRepository->findRecentWithSponsor(5),
            'recent_sponsorships' => $sponsorshipRepository->findRecentWithSponsor(5),
        ]);
    }
}

