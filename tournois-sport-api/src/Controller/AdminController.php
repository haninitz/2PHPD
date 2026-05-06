<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Registration;
use App\Entity\SportMatch;
use App\Entity\Tournament;
use App\Entity\User;
use App\Service\NotificationService;
use App\Service\SportMatchService;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin")
 */
final class AdminController extends AbstractController
{
    /**
     * @Route("/dashboard", name="admin_dashboard", methods={"GET"})
     */
    public function dashboard(EntityManagerInterface $entityManager): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->json([
            'users' => $entityManager->getRepository(User::class)->count([]),
            'tournaments' => $entityManager->getRepository(Tournament::class)->count([]),
            'registrationsPending' => $entityManager->getRepository(Registration::class)->count([
                'status' => Registration::STATUS_EN_ATTENTE,
            ]),
            'matchesInProgress' => $entityManager->getRepository(SportMatch::class)->count([
                'status' => SportMatch::STATUS_EN_COURS,
            ]),
        ]);
    }

    /**
     * @Route("/registrations/{id}/confirm", name="admin_registration_confirm", methods={"PUT"})
     */
    public function confirmRegistration(?Registration $registration, EntityManagerInterface $entityManager): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($registration === null) {
            return $this->json(['error' => 'Inscription introuvable.'], 404);
        }

        $registration->setStatus(Registration::STATUS_CONFIRMEE);
        $entityManager->flush();

        return $this->json([
            'message' => 'Inscription confirmee.',
            'registration' => [
                'id' => $registration->getId(),
                'status' => $registration->getStatus(),
                'player' => $registration->getPlayer() ? $registration->getPlayer()->getUsername() : null,
                'tournament' => $registration->getTournament() ? $registration->getTournament()->getTournamentName() : null,
            ],
        ]);
    }

    /**
     * @Route("/tournaments/{id}/winner", name="admin_tournament_winner", methods={"PUT"})
     */
    public function setWinner(
        ?Tournament $tournament,
        Request $request,
        EntityManagerInterface $entityManager,
        NotificationService $notificationService
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($tournament === null) {
            return $this->json(['error' => 'Tournoi introuvable.'], 404);
        }

        $payload = $this->payload($request);
        if (isset($payload['error'])) {
            return $this->json($payload, 400);
        }

        $winner = $entityManager->getRepository(User::class)->find((int) ($payload['winnerId'] ?? 0));
        if (!$winner instanceof User) {
            return $this->json(['error' => 'Vainqueur introuvable.'], 404);
        }

        $confirmedRegistration = $entityManager->getRepository(Registration::class)->findOneBy([
            'player' => $winner,
            'tournament' => $tournament,
            'status' => Registration::STATUS_CONFIRMEE,
        ]);

        if ($confirmedRegistration === null) {
            return $this->json(['error' => 'Le vainqueur doit avoir une inscription confirmee dans ce tournoi.'], 422);
        }

        $tournament->setWinner($winner);
        $notificationService->notifyTournamentWon($tournament);
        $entityManager->flush();

        return $this->json([
            'message' => 'Vainqueur enregistre et notifications creees.',
            'tournament' => [
                'id' => $tournament->getId(),
                'winner' => [
                    'id' => $winner->getId(),
                    'username' => $winner->getUsername(),
                ],
            ],
        ]);
    }

    /**
     * @Route("/sport-matchs/{id}/scores", name="admin_match_scores", methods={"PUT"})
     */
    public function adminUpdateScores(
        ?SportMatch $match,
        Request $request,
        EntityManagerInterface $entityManager,
        SportMatchService $sportMatchService
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($match === null) {
            return $this->json(['error' => 'Partie introuvable.'], 404);
        }

        $admin = $this->getUser();
        if (!$admin instanceof User) {
            return $this->json(['error' => 'Non authentifie.'], 401);
        }

        $payload = $this->payload($request);
        if (isset($payload['error'])) {
            return $this->json($payload, 400);
        }

        try {
            $sportMatchService->updateScores(
                $match,
                $admin,
                array_key_exists('scorePlayer1', $payload) ? (int) $payload['scorePlayer1'] : null,
                array_key_exists('scorePlayer2', $payload) ? (int) $payload['scorePlayer2'] : null
            );

            $entityManager->flush();
        } catch (RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], 422);
        }

        return $this->json([
            'message' => 'Scores admin mis a jour sans notification.',
            'match' => [
                'id' => $match->getId(),
                'status' => $match->getStatus(),
                'scorePlayer1' => $match->getScorePlayer1(),
                'scorePlayer2' => $match->getScorePlayer2(),
            ],
        ]);
    }

    private function payload(Request $request): array
    {
        try {
            return $request->toArray();
        } catch (\Throwable $e) {
            return ['error' => 'JSON invalide.'];
        }
    }
}