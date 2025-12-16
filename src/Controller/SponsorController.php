<?php

namespace App\Controller;

use App\Entity\Sponsor;
use App\Form\SponsorType;
use App\Form\SponsorSearchType;
use App\Repository\SponsorRepository;
use App\Service\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/sponsor')]
final class SponsorController extends AbstractController
{
    #[Route('/', name: 'app_sponsor_index')]
    public function index(Request $request, SponsorRepository $sponsorRepository): Response
    {
        // Recherche depuis la navbar (paramètre GET 'q')
        $searchQuery = $request->query->get('q', '');

        $sponsors = $searchQuery
            ? $sponsorRepository->searchByCriteria(['search' => $searchQuery])
            : $sponsorRepository->findAll();

        return $this->render('sponsor/index.html.twig', [
            'sponsors' => $sponsors,
        ]);
    }

    #[Route('/new', name: 'app_sponsor_new')]
    public function new(Request $request, EntityManagerInterface $entityManager, FileUploader $fileUploader): Response
    {
        $sponsor = new Sponsor();
        $form = $this->createForm(SponsorType::class, $sponsor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gestion de l'upload du logo
            $logoFile = $form->get('logoFile')->getData();
            if ($logoFile) {
                $logoFilename = $fileUploader->upload($logoFile, 'sponsor_logo');
                $sponsor->setLogo($logoFilename);
            }

            $entityManager->persist($sponsor);
            $entityManager->flush();

            $this->addFlash('success', 'Le sponsor a été créé avec succès.');
            return $this->redirectToRoute('app_sponsor_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('sponsor/new.html.twig', [
            'sponsor' => $sponsor,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_sponsor_show')]
    public function show(Sponsor $sponsor): Response
    {
        return $this->render('sponsor/show.html.twig', [
            'sponsor' => $sponsor,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_sponsor_edit')]
    public function edit(Request $request, Sponsor $sponsor, EntityManagerInterface $entityManager, FileUploader $fileUploader): Response
    {
        $oldLogo = $sponsor->getLogo();
        $form = $this->createForm(SponsorType::class, $sponsor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gestion de l'upload du logo
            $logoFile = $form->get('logoFile')->getData();
            if ($logoFile) {
                // Supprimer l'ancien logo si existe
                if ($oldLogo) {
                    $fileUploader->remove($oldLogo);
                }
                $logoFilename = $fileUploader->upload($logoFile, 'sponsor_logo');
                $sponsor->setLogo($logoFilename);
            }

            $entityManager->flush();

            $this->addFlash('success', 'Le sponsor a été modifié avec succès.');
            return $this->redirectToRoute('app_sponsor_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('sponsor/edit.html.twig', [
            'sponsor' => $sponsor,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_sponsor_delete')]
    public function delete(Request $request, Sponsor $sponsor, EntityManagerInterface $entityManager, FileUploader $fileUploader): Response
    {
        if ($this->isCsrfTokenValid('delete'.$sponsor->getId(), $request->request->getString('_token'))) {
            // Supprimer le logo si existe
            if ($sponsor->getLogo()) {
                $fileUploader->remove($sponsor->getLogo());
            }
            
            $entityManager->remove($sponsor);
            $entityManager->flush();
            $this->addFlash('success', 'Le sponsor a été supprimé avec succès.');
        }

        return $this->redirectToRoute('app_sponsor_index', [], Response::HTTP_SEE_OTHER);
    }
}
