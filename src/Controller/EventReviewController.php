<?php

namespace App\Controller;

use App\Entity\EventReview;
use App\Form\EventReviewType;
use App\Repository\EventReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/event/review')]
final class EventReviewController extends AbstractController
{
    #[Route(name: 'app_event_review_index', methods: ['GET'])]
    public function index(EventReviewRepository $eventReviewRepository): Response
    {
        return $this->render('event_review/index.html.twig', [
            'event_reviews' => $eventReviewRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_event_review_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $eventReview = new EventReview();
        $form = $this->createForm(EventReviewType::class, $eventReview);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($eventReview);
            $entityManager->flush();

            $this->addFlash('success', 'L\'avis a été créé avec succès.');
            return $this->redirectToRoute('app_event_review_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('event_review/new.html.twig', [
            'event_review' => $eventReview,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_event_review_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(EventReview $eventReview): Response
    {
        return $this->render('event_review/show.html.twig', [
            'event_review' => $eventReview,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_event_review_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, EventReview $eventReview, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(EventReviewType::class, $eventReview);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'L\'avis a été modifié avec succès.');
            return $this->redirectToRoute('app_event_review_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('event_review/edit.html.twig', [
            'event_review' => $eventReview,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_event_review_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, EventReview $eventReview, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$eventReview->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($eventReview);
            $entityManager->flush();
            $this->addFlash('success', 'L\'avis a été supprimé avec succès.');
        }

        return $this->redirectToRoute('app_event_review_index', [], Response::HTTP_SEE_OTHER);
    }
}


