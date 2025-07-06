<?php

namespace App\Controller;

use App\Repository\MeetingRepository;
use App\Repository\ParticipantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final class AdminController extends AbstractController
{
    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        return $this->render('admin/index.html.twig', [
            'controller_name' => 'AdminController',
        ]);
    }

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

    #[Route('/participant/{id}/approve', name: 'participant_approve', methods: ['POST'])]
    public function approveParticipant(
        int $id,
        Request $request,
        ParticipantRepository $participantRepository,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('Нет доступа');
        }

        $participant = $participantRepository->find($id);
        if (!$participant) {
            throw $this->createNotFoundException('Участник не найден');
        }

        $submittedToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('approve' . $participant->getId(), $submittedToken)) {
            throw $this->createAccessDeniedException('Неверный CSRF токен');
        }

        if ($participant->getStatus() === 'needs_approval') {
            $participant->setStatus('confirmed');
            $entityManager->flush();

            $this->addFlash('success', 'Участник успешно подтверждён.');
        } else {
            $this->addFlash('warning', 'Участник уже подтверждён или не требует подтверждения.');
        }

        return $this->redirectToRoute('admin_meeting_list');
    }

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
            $submittedToken = $request->request->get('_token');
            if (!$this->isCsrfTokenValid('approve_meeting' . $meeting->getId(), $submittedToken)) {
                throw $this->createAccessDeniedException('Неверный CSRF токен');
            }

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
            'csrf_token' => $this->get('security.csrf.token_manager')->getToken('approve_meeting' . $meeting->getId()),
        ]);
    }
}
