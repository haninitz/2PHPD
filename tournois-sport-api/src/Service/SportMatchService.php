<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Registration;
use App\Entity\SportMatch;
use App\Entity\Tournament;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class SportMatchService
{
    private EntityManagerInterface $entityManager;
    private ValidatorInterface $validator;
    private NotificationService $notificationService;

    public function __construct(
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        NotificationService $notificationService
    ) {
        $this->entityManager = $entityManager;
        $this->validator = $validator;
        $this->notificationService = $notificationService;
    }

    public function createMatch(
        Tournament $tournament,
        User $player1,
        User $player2,
        \DateTimeImmutable $matchDate
    ): SportMatch {
        if ($player1->getId() === $player2->getId()) {
            throw new RuntimeException('Un joueur ne peut pas jouer contre lui-meme.');
        }

        $registrationRepository = $this->entityManager->getRepository(Registration::class);

        $registration1 = $registrationRepository->findOneBy([
            'player' => $player1,
            'tournament' => $tournament,
            'status' => Registration::STATUS_CONFIRMEE,
        ]);

        $registration2 = $registrationRepository->findOneBy([
            'player' => $player2,
            'tournament' => $tournament,
            'status' => Registration::STATUS_CONFIRMEE,
        ]);

        if ($registration1 === null || $registration2 === null) {
            throw new RuntimeException('Les deux joueurs doivent avoir une inscription confirmee dans ce tournoi.');
        }

        $match = (new SportMatch())
            ->setTournament($tournament)
            ->setPlayer1($player1)
            ->setPlayer2($player2)
            ->setMatchDate($matchDate)
            ->setStatus(SportMatch::STATUS_EN_ATTENTE);

        $violations = $this->validator->validate($match);
        if (count($violations) > 0) {
            throw new RuntimeException((string) $violations);
        }

        $this->entityManager->persist($match);

        return $match;
    }

    public function updateScores(
        SportMatch $match,
        User $actor,
        ?int $scorePlayer1 = null,
        ?int $scorePlayer2 = null
    ): SportMatch {
        $isAdmin = $actor->isAdmin();

        $player1 = $match->getPlayer1();
        $player2 = $match->getPlayer2();

        $isPlayer1 = $player1 && $player1->getId() === $actor->getId();
        $isPlayer2 = $player2 && $player2->getId() === $actor->getId();

        if (!$isAdmin && !$isPlayer1 && !$isPlayer2) {
            throw new RuntimeException('Seuls les joueurs de la partie ou un admin peuvent modifier les scores.');
        }

        $previousScorePlayer1 = $match->getScorePlayer1();
        $previousScorePlayer2 = $match->getScorePlayer2();

        if ($isAdmin) {
            if ($scorePlayer1 !== null) {
                $match->setScorePlayer1($scorePlayer1);
            }

            if ($scorePlayer2 !== null) {
                $match->setScorePlayer2($scorePlayer2);
            }
        } elseif ($isPlayer1) {
            if ($scorePlayer2 !== null) {
                throw new RuntimeException('Le joueur 1 ne peut modifier que son propre score.');
            }

            if ($scorePlayer1 === null) {
                throw new RuntimeException('Le score du joueur 1 est requis.');
            }

            $match->setScorePlayer1($scorePlayer1);
        } elseif ($isPlayer2) {
            if ($scorePlayer1 !== null) {
                throw new RuntimeException('Le joueur 2 ne peut modifier que son propre score.');
            }

            if ($scorePlayer2 === null) {
                throw new RuntimeException('Le score du joueur 2 est requis.');
            }

            $match->setScorePlayer2($scorePlayer2);
        }

        if ($match->getScorePlayer1() !== null && $match->getScorePlayer2() !== null) {
            $match->setStatus(SportMatch::STATUS_TERMINEE);
        } elseif ($match->getScorePlayer1() !== null || $match->getScorePlayer2() !== null) {
            $match->setStatus(SportMatch::STATUS_EN_COURS);
        } else {
            $match->setStatus(SportMatch::STATUS_EN_ATTENTE);
        }

        if (!$isAdmin) {
            $player1JustSubmitted = $isPlayer1
                && $previousScorePlayer1 === null
                && $match->getScorePlayer1() !== null;

            $player2JustSubmitted = $isPlayer2
                && $previousScorePlayer2 === null
                && $match->getScorePlayer2() !== null;

            if (
                ($player1JustSubmitted && $match->getScorePlayer2() === null)
                || ($player2JustSubmitted && $match->getScorePlayer1() === null)
            ) {
                $this->notificationService->notifyScoreSubmitted($match, $actor);
            }
        }

        $violations = $this->validator->validate($match);
        if (count($violations) > 0) {
            throw new RuntimeException((string) $violations);
        }

        return $match;
    }
}