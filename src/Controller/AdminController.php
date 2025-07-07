<?php

namespace App\Controller;

use App\Repository\MeetingParticipantRepository;
use App\Repository\MeetingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class AdminController extends AbstractController
{
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        return $this->render('admin/index.html.twig', [
            'controller_name' => 'AdminController',
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/admin_meetings', name: 'admin_meeting_list')]
    public function list(MeetingRepository $repository): Response
    {
        $user = $this->getUser();
        if (!is_null($user)) {
            $meetings = $repository->findAllMeetings();

            return $this->render('admin/meeting_list.html.twig', [
                'meetings' => $meetings,
            ]);
        }

        return $this->redirectToRoute('login');
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/meeting/{id}/approve', name: 'meeting_approve', methods: ['POST', 'GET'])]
    public function approveMeeting(
        int $id,
        Request $request,
        MeetingRepository $meetingRepository,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('Нет доступа');
        }

        $meeting = $meetingRepository->find($id);
        if (!$meeting) {
            throw $this->createNotFoundException('Встреча не найдена');
        }

        if ($request->isMethod('POST')) {
            if ($meeting->getStatus() === 'needs_approval' || $meeting->getStatus() === 'pending') {
                $meeting->setStatus('scheduled');
                $entityManager->flush();

                $this->addFlash('success', 'Встреча успешно подтверждена и запланирована.');
            } else {
                $this->addFlash('warning', 'Встреча уже подтверждена или не требует подтверждения.');
            }

            return $this->redirectToRoute('admin_meeting_list');
        }

        return $this->render('admin/meeting_approve.html.twig', [
            'meeting' => $meeting,
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/meeting/{id}/decline', name: 'meeting_decline', methods: ['POST', 'GET'])]
    public function declineMeeting(
        int $id,
        Request $request,
        MeetingRepository $meetingRepository,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('Нет доступа');
        }

        $meeting = $meetingRepository->find($id);
        if (!$meeting) {
            throw $this->createNotFoundException('Встреча не найдена');
        }

        if ($request->isMethod('POST')) {
            if ($meeting->getStatus() === 'needs_approval' || $meeting->getStatus() === 'pending') {
                $meeting->setStatus('decline');
                $entityManager->flush();

                $this->addFlash('success', 'Встреча успешно отменена.');
            } else {
                $this->addFlash('warning', 'Встреча уже была отменена.');
            }

            return $this->redirectToRoute('admin_meeting_list');
        }

        return $this->render('admin/meeting_decline.html.twig', [
            'meeting' => $meeting,
        ]);
    }
}
