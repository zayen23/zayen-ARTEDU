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
    public function index(): Response
    {
        // Pour l'instant, on utilise des valeurs par défaut pour les utilisateurs
        // Ces valeurs seront remplacées quand l'entité User sera créée
        return $this->render('home/index.html.twig', [
            'clients_count' => 1,
            'vendeurs_count' => 0,
            'admins_count' => 0,
            'total_users' => 1,
        ]);
    }
}

