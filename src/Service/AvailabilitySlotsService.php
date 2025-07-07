<?php

namespace App\Service;

use App\Entity\AvailabilitySlots;
use App\Entity\User;
use DateInterval;
use Doctrine\ORM\EntityManagerInterface;
use \DateTimeImmutable;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormInterface;
use App\Entity\MeetingParticipant;

class AvailabilitySlotsService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security
    ) {}

    public function addSlot(FormInterface $form): void
    {
        $availabilitySlot = $form->getData();

        $user = $this->security->getUser();

        if ($user) {
            $availabilitySlot->setUser($user);

            $this->entityManager->persist($availabilitySlot);
            $this->entityManager->flush();
        }
    }


    /**
     * Находит общий доступный слот для всех пользователей.
     *
     * @param User[] $users Массив пользователей-участников
     * @return array Массив общих доступных слотов в формате [['start' => DateTimeImmutable, 'end' => DateTimeImmutable], ...]
     */
    public function findCommonAvailability(array $users): array
    {
        if (empty($users)) {
            return [];
        }

        $allUserSlots = [];
        foreach ($users as $user) {
            if (!$user->isMeetingRoom()) {
                $slots = $user->getAvailabilitySlots()->toArray();
                $userSlots = array_map(function (AvailabilitySlots $slot) {
                    return [
                        'start' => $slot->getStartAt(),
                        'end' => $slot->getEndAt(),
                    ];
                }, $slots);
                $allUserSlots[] = $userSlots;
            }
        }

        return $this->intersectSlots($allUserSlots);
    }

    /**
     * Находит пересечение нескольких списков слотов.
     *
     * @param array $allUserSlots Массив массивов слотов каждого пользователя
     * @return array Общие слоты
     */
    private function intersectSlots(array $allUserSlots): array
    {
        $common = $allUserSlots[0];

        for ($i = 1; $i < count($allUserSlots); $i++) {
            $common = $this->intersectTwoSlots($common, $allUserSlots[$i]);
            if (empty($common)) {
                break;
            }
        }

        return $common;
    }

    /**
     * Находит пересечение двух списков слотов.
     *
     * @param array $slots1
     * @param array $slots2
     * @return array
     */
    private function intersectTwoSlots(array $slots1, array $slots2): array
    {
        $result = [];

        foreach ($slots1 as $slot1) {
            foreach ($slots2 as $slot2) {
                $start = max($slot1['start'], $slot2['start']);
                $end = min($slot1['end'], $slot2['end']);

                if ($start < $end) {
                    $result[] = ['start' => $start, 'end' => $end];
                }
            }
        }

        return $result;
    }

    private function isSlotDurationSufficient(array $slot, DateInterval $meetingDuration): bool
    {
        $slotDuration = $slot['end']->getTimestamp() - $slot['start']->getTimestamp();
        $requiredDuration = $meetingDuration->s + $meetingDuration->i * 60 + $meetingDuration->h * 3600;

        return $slotDuration >= $requiredDuration;
    }
}
