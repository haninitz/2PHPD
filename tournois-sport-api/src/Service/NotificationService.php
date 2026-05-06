<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Notification;
use App\Entity\Registration;
use App\Entity\SportMatch;
use App\Entity\Tournament;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

final class NotificationService
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function create(
        User $user,
        string $type,
        string $message,
        ?Tournament $tournament = null,
        ?SportMatch $sportMatch = null
    ): Notification {
        $notification = (new Notification())
            ->setUser($user)
            ->setType($type)
            ->setMessage($message)
            ->setTournament($tournament)
            ->setSportMatch($sportMatch);

        $this->entityManager->persist($notification);

        return $notification;
    }

    public function notifyTournamentWon(Tournament $tournament): void
    {
        $winner = $tournament->getWinner();

        if ($winner === null) {
            return;
        }

        foreach ($tournament->getRegistrations() as $registration) {
            $player = $registration->getPlayer();

            if ($player === null) {
                continue;
            }

            if ($registration->getStatus() !== Registration::STATUS_CONFIRMEE) {
                continue;
            }

            $this->create(
                $player,
                'tournament_won',
                sprintf(
                    'Le tournoi "%s" a ete remporte par %s.',
                    $tournament->getTournamentName(),
                    $winner->getUsername()
                ),
                $tournament
            );
        }
    }

    public function notifyScoreSubmitted(SportMatch $match, User $author): void
    {
        $opponent = null;
        $opponentScore = null;

        $player1 = $match->getPlayer1();
        $player2 = $match->getPlayer2();

        if ($player1 && $player1->getId() === $author->getId()) {
            $opponent = $player2;
            $opponentScore = $match->getScorePlayer2();
        } elseif ($player2 && $player2->getId() === $author->getId()) {
            $opponent = $player1;
            $opponentScore = $match->getScorePlayer1();
        }

        if ($opponent === null || $opponentScore !== null) {
            return;
        }

        $this->create(
            $opponent,
            'score_submitted',
            sprintf(
                'Votre adversaire a saisi son score pour le match #%d. Merci de renseigner le votre.',
                $match->getId()
            ),
            $match->getTournament(),
            $match
        );
    }
}