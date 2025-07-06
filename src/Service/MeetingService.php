<?php

namespace App\Service;

use App\Entity\{User, Meeting, MeetingParticipant, AvailabilitySlots};
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\AvailabilitySlotsRepository;

class MeetingService
{
    public function __construct(
        private EntityManagerInterface $em,
        private AvailabilitySlotsRepository $availabilityRepo
    ) {}

    public function createMeeting(
        User $creator,
        string $title,
        array $participants,
        array $meetingRooms,
        int $durationMinutes
    ): ?Meeting {
        $allParticipants = array_merge([$creator], $participants, $meetingRooms);
        $commonSlot = $this->findCommonSlot($allParticipants, $durationMinutes);

        if (!$commonSlot) {
            return null;
        }

        $meeting = new Meeting();
        $meeting->setCreator($creator);
        $meeting->setTitle($title);
        $meeting->setStartAt($commonSlot['start']);
        $meeting->setEndAt($commonSlot['end']);

        $this->addParticipants($meeting, $participants, $meetingRooms);

        $this->em->persist($meeting);
        $this->em->flush();

        return $meeting;
    }

    private function addParticipants(Meeting $meeting, array $users, array $meetingRooms): void
    {
        foreach ($users as $user) {
            $participant = new MeetingParticipant();
            $participant->setUser($user);
            $participant->setMeeting($meeting);
            $participant->setStatus('confirmed');
            $meeting->addParticipant($participant);
        }

        foreach ($meetingRooms as $room) {
            $participant = new MeetingParticipant();
            $participant->setUser($room);
            $participant->setMeeting($meeting);
            $participant->setStatus('needs_approval');
            $meeting->addParticipant($participant);
        }
    }

    private function findCommonSlot(array $users, int $durationMinutes): ?array
    {
        $slotsByUser = [];
        foreach ($users as $user) {
            $slotsByUser[$user->getId()] = $this->availabilityRepo->findByUserOrdered($user);
        }

        foreach ($slotsByUser[array_key_first($slotsByUser)] as $slot) {
            $start = $slot->getStartAt();
            $end = (clone $start)->modify("+{$durationMinutes} minutes");

            $allAvailable = true;
            foreach ($users as $user) {
                if ($user->getId() === $slot->getUser()->getId()) continue;

                $userAvailable = false;
                foreach ($slotsByUser[$user->getId()] as $userSlot) {
                    if ($userSlot->getStartAt() <= $start && $userSlot->getEndAt() >= $end) {
                        $userAvailable = true;
                        break;
                    }
                }

                if (!$userAvailable) {
                    $allAvailable = false;
                    break;
                }
            }

            if ($allAvailable) {
                return ['start' => $start, 'end' => $end];
            }
        }

        return null;
    }
}