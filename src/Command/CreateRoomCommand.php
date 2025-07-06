<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-room',
    description: 'Add a short description for your command',
)]
class CreateRoomCommand extends Command
{
    private EntityManagerInterface $em;
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher)
    {
        parent::__construct();

        $this->em = $em;
        $this->passwordHasher = $passwordHasher;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Создает пользователя-переговорку')
            ->addArgument('username', InputArgument::REQUIRED, 'Имя пользователя (название комнаты)')
            ->addArgument('password', InputArgument::OPTIONAL, 'Пароль (если не указан, будет сгенерирован автоматически)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $username = $input->getArgument('username');
        $password = $input->getArgument('password');

        $userRepo = $this->em->getRepository(User::class);
        $existingUser = $userRepo->findOneBy(['username' => $username]);
        
        if ($existingUser) {
            $io->error('Пользователь с таким именем уже существует.');
            return Command::FAILURE;
        }

        $user = new User();
        $user->setUsername($username);
        $user->setIsMeetingRoom(true);
        $user->setRoles(['ROLE_USER']);

        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        $this->em->persist($user);
        $this->em->flush();
        $io->success([
            'Пользователь-переговорка успешно создан!',
            sprintf('Имя: %s', $username),
            sprintf('Пароль: %s', $password)
        ]);

        return Command::SUCCESS;
    }
}
