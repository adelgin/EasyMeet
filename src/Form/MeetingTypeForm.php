<?php

namespace App\Form;

use App\Entity\Meeting;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\{ChoiceType, TextType};
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MeetingTypeForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Meeting Title'
            ])
            ->add('participants', EntityType::class, [
                'class' => User::class,
                'choices' => $options['regular_users'],
                'choice_label' => 'username',
                'multiple' => true,
                'required' => false,
                'label' => 'Invite Users'
            ])
            ->add('meetingRooms', EntityType::class, [
                'class' => User::class,
                'choices' => $options['meeting_rooms'],
                'choice_label' => 'username',
                'multiple' => true,
                'required' => false,
                'label' => 'Select Meeting Rooms'
            ])
            ->add('duration', ChoiceType::class, [
                'choices' => [
                    '30 minutes' => 30,
                    '1 hour' => 60,
                    '1.5 hours' => 90,
                    '2 hours' => 120,
                ],
                'label' => 'Duration'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Meeting::class,
            'regular_users' => [],
            'meeting_rooms' => [],
        ]);
    }
}