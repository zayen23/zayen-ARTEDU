<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/sponsoring')]
final class SponsoringController extends AbstractController
{
    #[Route('/', name: 'app_sponsoring_index')]
    public function index(): Response
    {
        return $this->render('sponsoring/index.html.twig');
    }
}


