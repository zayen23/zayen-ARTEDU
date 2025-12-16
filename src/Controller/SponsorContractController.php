<?php

namespace App\Controller;

use App\Entity\SponsorContract;
use App\Form\SponsorContractType;
use App\Repository\SponsorContractRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/sponsor/contract')]
final class SponsorContractController extends AbstractController
{
    #[Route('/', name: 'app_sponsor_contract_index')]
    public function index(SponsorContractRepository $sponsorContractRepository): Response
    {
        return $this->render('sponsor_contract/index.html.twig', [
            'sponsor_contracts' => $sponsorContractRepository->findAllWithSponsor(),
        ]);
    }

    #[Route('/new', name: 'app_sponsor_contract_new')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $sponsorContract = new SponsorContract();
        $form = $this->createForm(SponsorContractType::class, $sponsorContract);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($sponsorContract);
            $entityManager->flush();

            $this->addFlash('success', 'Le contrat a été créé avec succès.');
            return $this->redirectToRoute('app_sponsor_contract_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('sponsor_contract/new.html.twig', [
            'sponsor_contract' => $sponsorContract,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_sponsor_contract_show')]
    public function show(SponsorContract $sponsorContract): Response
    {
        return $this->render('sponsor_contract/show.html.twig', [
            'sponsor_contract' => $sponsorContract,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_sponsor_contract_edit')]
    public function edit(Request $request, SponsorContract $sponsorContract, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(SponsorContractType::class, $sponsorContract);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Le contrat a été modifié avec succès.');
            return $this->redirectToRoute('app_sponsor_contract_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('sponsor_contract/edit.html.twig', [
            'sponsor_contract' => $sponsorContract,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_sponsor_contract_delete')]
    public function delete(Request $request, SponsorContract $sponsorContract, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$sponsorContract->getId(), $request->request->getString('_token'))) {
            $entityManager->remove($sponsorContract);
            $entityManager->flush();
            $this->addFlash('success', 'Le contrat a été supprimé avec succès.');
        }

        return $this->redirectToRoute('app_sponsor_contract_index', [], Response::HTTP_SEE_OTHER);
    }
}
