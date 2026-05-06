<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Registration;
use App\Entity\SportMatch;
use App\Entity\Tournament;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $admin = (new User())
            ->setLastName('Admin')
            ->setFirstName('Super')
            ->setUsername('admin')
            ->setEmailAddress('admin@example.com')
            ->setRoles(['ROLE_ADMIN'])
            ->setStatus(User::STATUS_ACTIF);

        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'adminpass'));
        $manager->persist($admin);

        $players = [];

        $playersData = [
            ['Martin', 'Alice', 'alice', 'alice@example.com'],
            ['Bernard', 'Bob', 'bob', 'bob@example.com'],
            ['Dupont', 'Chloe', 'chloe', 'chloe@example.com'],
            ['Durand', 'David', 'david', 'david@example.com'],
        ];

        foreach ($playersData as $playerData) {
            $lastName = $playerData[0];
            $firstName = $playerData[1];
            $username = $playerData[2];
            $email = $playerData[3];

            $player = (new User())
                ->setLastName($lastName)
                ->setFirstName($firstName)
                ->setUsername($username)
                ->setEmailAddress($email)
                ->setStatus(User::STATUS_ACTIF);

            $player->setPassword($this->passwordHasher->hashPassword($player, 'password'));

            $manager->persist($player);
            $players[] = $player;
        }

        $currentTournament = (new Tournament())
            ->setTournamentName('Tournoi de Tennis Printemps')
            ->setStartDate(new \DateTimeImmutable('today -1 day'))
            ->setEndDate(new \DateTimeImmutable('today +5 days'))
            ->setLocation('Paris')
            ->setDescription('Tournoi principal de demonstration.')
            ->setMaxParticipants(8)
            ->setSport('Tennis')
            ->setOrganizer($admin);

        $manager->persist($currentTournament);

        $futureTournament = (new Tournament())
            ->setTournamentName('Tournoi de Foot Etudiant')
            ->setStartDate(new \DateTimeImmutable('today +10 days'))
            ->setEndDate(new \DateTimeImmutable('today +12 days'))
            ->setLocation('Lyon')
            ->setDescription('Tournoi futur avec inscriptions en attente.')
            ->setMaxParticipants(16)
            ->setSport('Football')
            ->setOrganizer($admin);

        $manager->persist($futureTournament);

        foreach ($players as $player) {
            $registration = (new Registration())
                ->setPlayer($player)
                ->setTournament($currentTournament)
                ->setStatus(Registration::STATUS_CONFIRMEE);

            $manager->persist($registration);
        }

        foreach ([$players[0], $players[1]] as $player) {
            $registration = (new Registration())
                ->setPlayer($player)
                ->setTournament($futureTournament)
                ->setStatus(Registration::STATUS_EN_ATTENTE);

            $manager->persist($registration);
        }

        $match1 = (new SportMatch())
            ->setTournament($currentTournament)
            ->setPlayer1($players[0])
            ->setPlayer2($players[1])
            ->setMatchDate(new \DateTimeImmutable('today'))
            ->setScorePlayer1(6)
            ->setScorePlayer2(4)
            ->setStatus(SportMatch::STATUS_TERMINEE);

        $manager->persist($match1);

        $match2 = (new SportMatch())
            ->setTournament($currentTournament)
            ->setPlayer1($players[2])
            ->setPlayer2($players[3])
            ->setMatchDate(new \DateTimeImmutable('today +1 day'))
            ->setStatus(SportMatch::STATUS_EN_ATTENTE);

        $manager->persist($match2);

        $manager->flush();
    }
}