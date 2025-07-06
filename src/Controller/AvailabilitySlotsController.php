<?php

namespace App\Controller;

use App\Entity\AvailabilitySlots;
use App\Form\AvailabilitySlotsTypeForm;
use App\Service\AvailabilitySlotsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AvailabilitySlotsController extends AbstractController
{
    #[Route('/newslot', name: 'new_slot')]
    public function index(Request $request, AvailabilitySlotsService $availabilitySlotsService): Response
    {
    $availabilitySlot = new AvailabilitySlots();
    $form = $this->createForm(AvailabilitySlotsTypeForm::class, $availabilitySlot);
    $form->handleRequest($request);

    if ($form->isSubmitted()) {
        if ($availabilitySlot->getStartAt() >= $availabilitySlot->getEndAt()) {
                return $this->redirectToRoute('new_slot');
            }

        if (!$form->isValid()) {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('error', $error->getMessage());
            }
        } else {
            $availabilitySlotsService->addSlot($form);
            $this->addFlash('success', 'Time slot was successfully added!');
            return $this->redirectToRoute('new_slot');
        }
    }

    return $this->render('availability_slots/index.html.twig', [
        'form' => $form->createView(),
    ]);
    }
}