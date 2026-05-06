<?php

namespace App\Command;

use App\Entity\SportMatch;
use App\Entity\User;
use App\Entity\Tournament;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UserStatsCommand extends Command
{
    protected static $defaultName = 'app:user-stats';
    protected static $defaultDescription = 'Affiche le nombre total de victoires et défaites d’un utilisateur, avec filtre tournoi optionnel.';

    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();

        $this->entityManager = $entityManager;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('userId', InputArgument::REQUIRED, 'ID de l’utilisateur')
            ->addArgument('tournamentId', InputArgument::OPTIONAL, 'ID du tournoi');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $userId = (int) $input->getArgument('userId');
        $tournamentId = $input->getArgument('tournamentId');

        $user = $this->entityManager->getRepository(User::class)->find($userId);

        if (!$user) {
            $output->writeln('<error>Utilisateur introuvable.</error>');
            return Command::FAILURE;
        }

        $criteria = [
            'status' => 'terminé',
        ];

        if ($tournamentId !== null) {
            $tournament = $this->entityManager->getRepository(Tournament::class)->find((int) $tournamentId);

            if (!$tournament) {
                $output->writeln('<error>Tournoi introuvable.</error>');
                return Command::FAILURE;
            }

            $criteria['tournament'] = $tournament;
        }

        $matches = $this->entityManager->getRepository(SportMatch::class)->findBy($criteria);

        $wins = 0;
        $losses = 0;

        foreach ($matches as $match) {
            if ($match->getScorePlayer1() === null || $match->getScorePlayer2() === null) {
                continue;
            }

            $isPlayer1 = $match->getPlayer1() && $match->getPlayer1()->getId() === $user->getId();
            $isPlayer2 = $match->getPlayer2() && $match->getPlayer2()->getId() === $user->getId();

            if (!$isPlayer1 && !$isPlayer2) {
                continue;
            }

            if ($isPlayer1) {
                if ($match->getScorePlayer1() > $match->getScorePlayer2()) {
                    $wins++;
                } elseif ($match->getScorePlayer1() < $match->getScorePlayer2()) {
                    $losses++;
                }
            }

            if ($isPlayer2) {
                if ($match->getScorePlayer2() > $match->getScorePlayer1()) {
                    $wins++;
                } elseif ($match->getScorePlayer2() < $match->getScorePlayer1()) {
                    $losses++;
                }
            }
        }

        $output->writeln('Utilisateur : '.$user->getEmailAddress());

        if ($tournamentId !== null) {
            $output->writeln('Tournoi ID : '.$tournamentId);
        }

        $output->writeln('Victoires : '.$wins);
        $output->writeln('Défaites : '.$losses);

        return Command::SUCCESS;
    }
}