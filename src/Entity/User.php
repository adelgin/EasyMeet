<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_USERNAME', fields: ['username'])]
#[UniqueEntity(fields: ['username'], message: 'There is already an account with this username')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $username = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    /**
     * @var Collection<int, AvailabilitySlots>
     */
    #[ORM\OneToMany(targetEntity: AvailabilitySlots::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $availabilitySlots;

    /**
     * @var Collection<int, Meeting>
     */
    #[ORM\OneToMany(targetEntity: Meeting::class, mappedBy: 'creator')]
    private Collection $createdMeetings;

    /**
     * @var Collection<int, MeetingParticipant>
     */
    #[ORM\OneToMany(targetEntity: MeetingParticipant::class, mappedBy: 'user')]
    private Collection $meetings;

    #[ORM\Column(nullable: true)]
    private ?bool $isMeetingRoom = null;

    public function __construct()
    {
        $this->availabilitySlots = new ArrayCollection();
        $this->createdMeetings = new ArrayCollection();
        $this->meetings = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->username;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    /**
     * @return Collection<int, AvailabilitySlots>
     */
    public function getAvailabilitySlots(): Collection
    {
        return $this->availabilitySlots;
    }

    public function addAvailabilitySlot(AvailabilitySlots $availabilitySlot): static
    {
        if (!$this->availabilitySlots->contains($availabilitySlot)) {
            $this->availabilitySlots->add($availabilitySlot);
            $availabilitySlot->setUser($this);
        }

        return $this;
    }

    public function removeAvailabilitySlot(AvailabilitySlots $availabilitySlot): static
    {
        if ($this->availabilitySlots->removeElement($availabilitySlot)) {
            // set the owning side to null (unless already changed)
            if ($availabilitySlot->getUser() === $this) {
                $availabilitySlot->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Meeting>
     */
    public function getCreatedMeetings(): Collection
    {
        return $this->createdMeetings;
    }

    public function addCreatedMeeting(Meeting $createdMeeting): static
    {
        if (!$this->createdMeetings->contains($createdMeeting)) {
            $this->createdMeetings->add($createdMeeting);
            $createdMeeting->setCreator($this);
        }

        return $this;
    }

    public function removeCreatedMeeting(Meeting $createdMeeting): static
    {
        if ($this->createdMeetings->removeElement($createdMeeting)) {
            // set the owning side to null (unless already changed)
            if ($createdMeeting->getCreator() === $this) {
                $createdMeeting->setCreator(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, MeetingParticipant>
     */
    public function getMeetings(): Collection
    {
        return $this->meetings;
    }

    public function addMeeting(MeetingParticipant $meeting): static
    {
        if (!$this->meetings->contains($meeting)) {
            $this->meetings->add($meeting);
            $meeting->setUser($this);
        }

        return $this;
    }

    public function removeMeeting(MeetingParticipant $meeting): static
    {
        if ($this->meetings->removeElement($meeting)) {
            // set the owning side to null (unless already changed)
            if ($meeting->getUser() === $this) {
                $meeting->setUser(null);
            }
        }

        return $this;
    }

    public function isMeetingRoom(): ?bool
    {
        return $this->isMeetingRoom ?? false;
    }

    public function setIsMeetingRoom(bool $isMeetingRoom): static
    {
        $this->isMeetingRoom = $isMeetingRoom;

        return $this;
    }
}
