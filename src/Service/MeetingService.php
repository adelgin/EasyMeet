<?php

namespace App\Service;

use App\Entity\{User, Meeting, MeetingParticipant};
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;

class MeetingService
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserRepository $userRepository,
        private AvailabilitySlotsService $availabilityService
    ) {}

    public function getAllBasicUsersAndMeetingRooms(): array
    {
        return [
            'users' => $this->userRepository->findAllBasicUsers(),
            'rooms' => $this->userRepository->findAllMeetingRooms()
        ];
    }

    public function handleMeetingCreation(array $postData, UserInterface $creator): array
    {
        $userIds = $postData['users'] ?? [];
        $meetingRooms = $postData['meeting_rooms'] ?? null;
        $title = $postData['title'] ?? 'Новая встреча';
        $manualStart = $postData['manual_start'] ?? null;
        $manualEnd = $postData['manual_end'] ?? null;
        $action = $postData['action'] ?? null;

        $anyMeetingRoomAdded = $meetingRooms ? 1 : 0;
        $userIds = $meetingRooms ? array_merge($userIds, [$meetingRooms]) : $userIds;

        $formData = [
            'selectedUserIds' => $userIds,
            'manualStart' => $manualStart,
            'manualEnd' => $manualEnd,
            'title' => $title
        ];

        if (empty($title)) {
            throw new \InvalidArgumentException('Введите название встречи.');
        }

        if (count($userIds) - $anyMeetingRoomAdded < 1) {
            throw new \InvalidArgumentException('Выберите хотя бы одного участника.');
        }

        $users = $this->userRepository->findBy(['id' => $userIds]);
        if (count($users) !== count($userIds)) {
            throw new \InvalidArgumentException('Некоторые пользователи не найдены.');
        }

        if ($action === 'calc') {
            try {
                $commonSlots = $this->availabilityService->findCommonAvailability($users);
                $commonSlot = empty($commonSlots) ? null : $commonSlots[0];
                $calcError = $commonSlot ? null : 'Общий доступный слот не найден.';

                return [
                    'commonSlot' => $commonSlot,
                    'calcError' => $calcError,
                    'formData' => $formData
                ];
            } catch (\Exception $e) {
                return [
                    'commonSlot' => null,
                    'calcError' => 'Ошибка при расчёте: ' . $e->getMessage(),
                    'formData' => $formData
                ];
            }
        }

        if ($action === 'create') {
            if ($manualStart && $manualEnd) {
                $startAt = \DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $manualStart);
                $endAt = \DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $manualEnd);

                if (!$startAt || !$endAt || $startAt >= $endAt || $startAt < new \DateTimeImmutable() || $endAt < new \DateTimeImmutable()) {
                    throw new \InvalidArgumentException('Неверный формат или логика времени вручную введённого слота.');
                }
            } else {
                $commonSlots = $this->availabilityService->findCommonAvailability($users);
                if (empty($commonSlots)) {
                    throw new \InvalidArgumentException('Общий доступный слот не найден.');
                }

                $slot = $commonSlots[0];
                $startAt = $slot['start'];
                $endAt = $slot['end'];
            }

            $meeting = new Meeting();
            $meeting->setCreator($creator);
            $meeting->setStartAt($startAt);
            $meeting->setEndAt($endAt);
            $meeting->setStatus($meetingRooms ? 'needs_approval' : 'scheduled');
            $meeting->setTitle($title);

            $this->em->persist($meeting);

            foreach ($users as $user) {
                $participant = new MeetingParticipant();
                $participant->setMeeting($meeting);
                $participant->setUser($user);
                $participant->setStatus('invited');

                $this->em->persist($participant);
                $meeting->addParticipant($participant);
            }

            $this->em->flush();

            return [
                'success' => true,
                'title' => $title
            ];
        }

        return ['formData' => $formData];
    }
}
