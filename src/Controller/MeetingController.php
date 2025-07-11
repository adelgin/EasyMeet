<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use \DateTimeImmutable;
use App\Repository\MeetingRepository;
use App\Entity\MeetingParticipant;
use App\Repository\UserRepository;
use App\Service\MeetingService;
use App\Service\AvailabilitySlotsService;
use App\Form\MeetingTypeForm;
use App\Entity\Meeting;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class MeetingController extends AbstractController
{
    #[Route('/meetings', name: 'meeting_list')]
    public function list(MeetingRepository $repository): Response
    {
        $user = $this->getUser();
        if (!is_null($user)) {
            $meetings = $repository->findUserMeetings($user);

            return $this->render('meeting/list.html.twig', [
                'meetings' => $meetings,
            ]);
        }

        return $this->redirectToRoute('login');
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/meeting/new', name: 'meeting_new')]
    public function new(
        Request $request,
        UserRepository $userRepository,
        AvailabilitySlotsService $availabilityService,
        EntityManagerInterface $em
    ): Response {
        $allUsers = $userRepository->findAllBasicUsers();
        $meetingRooms = $userRepository->findAllMeetingRooms();

        $commonSlot = null;
        $calcError = null;

        if ($request->isMethod('POST')) {
            $anyMeetingRoomAdded = 0;
            $postData = $request->request->all();
            $userIds = $postData['users'] ?? [];
            $meeting_rooms = $postData['meeting_rooms'] ?? null;

            if (!is_null($meeting_rooms)) {
                $userIds[] = $meeting_rooms;
                $anyMeetingRoomAdded = 1;
            }

            $title = $postData['title'] ?? 'Новая встреча';
            $manualStart = $postData['manual_start'] ?? null;
            $manualEnd = $postData['manual_end'] ?? null;
            $action = $postData['action'] ?? null;

            $formData = [
                'selectedUserIds' => $userIds,
                'manualStart' => $manualStart,
                'manualEnd' => $manualEnd,
                'title' => $title
            ];

            if (empty($title)) {
                $this->addFlash('error', 'Введите название встречи.');
                return $this->render('meeting/new.html.twig', array_merge(['users' => $allUsers, 'rooms' => $meetingRooms], $formData));
            }

            if (count($userIds) - $anyMeetingRoomAdded < 1) {
                $this->addFlash('error', 'Выберите хотя бы одного участника.');
                return $this->render('meeting/new.html.twig', array_merge(['users' => $allUsers, 'rooms' => $meetingRooms], $formData));
            }

            $users = $userRepository->findBy(['id' => $userIds]);
            if (count($users) !== count($userIds)) {
                $this->addFlash('error', 'Некоторые пользователи не найдены.');
                return $this->render('meeting/new.html.twig', array_merge(['users' => $allUsers, 'rooms' => $meetingRooms], $formData));
            }

            if ($action === 'calc') {
                try {
                    $commonSlots = $availabilityService->findCommonAvailability($users);

                    if (empty($commonSlots)) {
                        $calcError = 'Общий доступный слот не найден.';
                    } else {
                        $commonSlot = $commonSlots[0];
                    }
                } catch (\Exception $e) {
                    $calcError = 'Ошибка при расчёте: ' . $e->getMessage();
                }

                return $this->render('meeting/new.html.twig', array_merge(
                    ['users' => $allUsers, 'rooms' => $meetingRooms, 'commonSlot' => $commonSlot, 'calcError' => $calcError],
                    $formData
                ));
            }

            if ($action === 'create') {
                if ($manualStart && $manualEnd) {
                    $startAt = \DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $manualStart);
                    $endAt = \DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $manualEnd);

                    if (!$startAt || !$endAt || $startAt >= $endAt || $startAt < new \DateTimeImmutable() || $endAt < new \DateTimeImmutable()) {
                        $this->addFlash('error', 'Неверный формат или логика времени вручную введённого слота.');
                        return $this->render('meeting/new.html.twig', array_merge(['users' => $allUsers, 'rooms' => $meetingRooms], $formData));
                    }
                } else {
                    $commonSlots = $availabilityService->findCommonAvailability($users);

                    if (empty($commonSlots)) {
                        $this->addFlash('error', 'Общий доступный слот не найден.');
                        return $this->render('meeting/new.html.twig', array_merge(['users' => $allUsers, 'rooms' => $meetingRooms], $formData));
                    }

                    $slot = $commonSlots[0];
                    $startAt = $slot['start'];
                    $endAt = $slot['end'];
                }

                $meeting = new Meeting();
                $meeting->setCreator($this->getUser());
                $meeting->setStartAt($startAt);
                $meeting->setEndAt($endAt);
                if (is_null($meeting_rooms)) {
                    $meeting->setStatus('scheduled');
                } else {
                    $meeting->setStatus('needs_approval');
                }

                $meeting->setTitle($title);

                $em->persist($meeting);

                foreach ($users as $user) {
                    $participant = new MeetingParticipant();
                    $participant->setMeeting($meeting);
                    $participant->setUser($user);
                    $participant->setStatus('invited');

                    $em->persist($participant);
                    $meeting->addParticipant($participant);
                }

                $em->flush();

                $this->addFlash('success', 'Встреча "' . $title . '" создана!');
                return $this->redirectToRoute('meeting_list');
            }
        }

        return $this->render('meeting/new.html.twig', [
            'users' => $allUsers,
            'rooms' => $meetingRooms,
        ]);
    }

}
