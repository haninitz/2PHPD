<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\SportMatch;
use App\Entity\Tournament;
use App\Entity\User;
use App\Service\SportMatchService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

final class SportMatchController extends AbstractController
{
    /**
     * @Route("/api/tournaments/{id}/sport-matchs", name="api_sport_match_index", methods={"GET"})
     */
    public function index(?Tournament $tournament, EntityManagerInterface $entityManager): JsonResponse
    {
        if ($tournament === null) {
            return $this->json(['error' => 'Tournoi introuvable.'], 404);
        }

        $matches = $entityManager->getRepository(SportMatch::class)->findBy(
            ['tournament' => $tournament],
            ['matchDate' => 'ASC', 'id' => 'ASC']
        );

        $data = [];
        foreach ($matches as $match) {
            $data[] = $this->normalizeMatch($match);
        }

        return $this->json($data);
    }

    /**
     * @Route("/api/tournaments/{id}/sport-matchs", name="api_sport_match_create", methods={"POST"})
     */
    public function create(
        ?Tournament $tournament,
        Request $request,
        EntityManagerInterface $entityManager,
        SportMatchService $sportMatchService
    ): JsonResponse {
        if ($tournament === null) {
            return $this->json(['error' => 'Tournoi introuvable.'], 404);
        }

        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $actor = $this->getUser();
        if (!$actor instanceof User) {
            return $this->json(['error' => 'Non authentifie.'], 401);
        }

        $organizer = $tournament->getOrganizer();

        if (!$actor->isAdmin() && (!$organizer || $organizer->getId() !== $actor->getId())) {
            return $this->json(['error' => 'Seul l\'organisateur ou un admin peut creer une partie.'], 403);
        }

        $payload = $this->payload($request);
        if (isset($payload['error'])) {
            return $this->json($payload, 400);
        }

        foreach (['player1Id', 'player2Id', 'matchDate'] as $required) {
            if (empty($payload[$required])) {
                return $this->json(['error' => sprintf('Le champ "%s" est requis.', $required)], 400);
            }
        }

        $player1 = $entityManager->getRepository(User::class)->find((int) $payload['player1Id']);
        $player2 = $entityManager->getRepository(User::class)->find((int) $payload['player2Id']);

        if (!$player1 instanceof User || !$player2 instanceof User) {
            return $this->json(['error' => 'Joueur 1 ou joueur 2 introuvable.'], 404);
        }

        try {
            $matchDate = new DateTimeImmutable((string) $payload['matchDate']);

            $match = $sportMatchService->createMatch(
                $tournament,
                $player1,
                $player2,
                $matchDate
            );

            $entityManager->flush();
        } catch (RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            return $this->json(['error' => 'Date de match invalide. Utilisez le format YYYY-MM-DD.'], 400);
        }

        return $this->json($this->normalizeMatch($match), 201);
    }

    /**
     * @Route("/api/tournaments/{idTournament}/sport-matchs/{idSportMatchs}", name="api_sport_match_show", methods={"GET"})
     */
    public function show(int $idTournament, int $idSportMatchs, EntityManagerInterface $entityManager): JsonResponse
    {
        $match = $entityManager->getRepository(SportMatch::class)->find($idSportMatchs);

        if (
            !$match instanceof SportMatch
            || !$match->getTournament()
            || $match->getTournament()->getId() !== $idTournament
        ) {
            return $this->json(['error' => 'Partie introuvable pour ce tournoi.'], 404);
        }

        return $this->json($this->normalizeMatch($match));
    }

    /**
     * @Route("/api/tournaments/{idTournament}/sport-matchs/{idSportMatchs}", name="api_sport_match_update", methods={"PUT"})
     */
    public function update(
        int $idTournament,
        int $idSportMatchs,
        Request $request,
        EntityManagerInterface $entityManager,
        SportMatchService $sportMatchService
    ): JsonResponse {
        $match = $entityManager->getRepository(SportMatch::class)->find($idSportMatchs);

        if (
            !$match instanceof SportMatch
            || !$match->getTournament()
            || $match->getTournament()->getId() !== $idTournament
        ) {
            return $this->json(['error' => 'Partie introuvable pour ce tournoi.'], 404);
        }

        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $actor = $this->getUser();
        if (!$actor instanceof User) {
            return $this->json(['error' => 'Non authentifie.'], 401);
        }

        $payload = $this->payload($request);
        if (isset($payload['error'])) {
            return $this->json($payload, 400);
        }

        try {
            $updated = $sportMatchService->updateScores(
                $match,
                $actor,
                array_key_exists('scorePlayer1', $payload) ? (int) $payload['scorePlayer1'] : null,
                array_key_exists('scorePlayer2', $payload) ? (int) $payload['scorePlayer2'] : null
            );

            $entityManager->flush();
        } catch (RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], 422);
        }

        return $this->json($this->normalizeMatch($updated));
    }

    /**
     * @Route("/api/tournaments/{idTournament}/sport-matchs/{idSportMatchs}", name="api_sport_match_delete", methods={"DELETE"})
     */
    public function delete(int $idTournament, int $idSportMatchs, EntityManagerInterface $entityManager): JsonResponse
    {
        $match = $entityManager->getRepository(SportMatch::class)->find($idSportMatchs);

        if (
            !$match instanceof SportMatch
            || !$match->getTournament()
            || $match->getTournament()->getId() !== $idTournament
        ) {
            return $this->json(['error' => 'Partie introuvable pour ce tournoi.'], 404);
        }

        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $entityManager->remove($match);
        $entityManager->flush();

        return $this->json(['message' => 'Partie supprimee.']);
    }

    private function payload(Request $request): array
    {
        try {
            return $request->toArray();
        } catch (\Throwable $e) {
            return ['error' => 'JSON invalide.'];
        }
    }

    private function normalizeMatch(SportMatch $match): array
    {
        $tournament = $match->getTournament();
        $player1 = $match->getPlayer1();
        $player2 = $match->getPlayer2();
        $matchDate = $match->getMatchDate();

        return [
            'id' => $match->getId(),
            'matchDate' => $matchDate ? $matchDate->format('Y-m-d') : null,
            'status' => $match->getStatus(),
            'scorePlayer1' => $match->getScorePlayer1(),
            'scorePlayer2' => $match->getScorePlayer2(),
            'tournament' => [
                'id' => $tournament ? $tournament->getId() : null,
                'tournamentName' => $tournament ? $tournament->getTournamentName() : null,
            ],
            'player1' => [
                'id' => $player1 ? $player1->getId() : null,
                'username' => $player1 ? $player1->getUsername() : null,
            ],
            'player2' => [
                'id' => $player2 ? $player2->getId() : null,
                'username' => $player2 ? $player2->getUsername() : null,
            ],
        ];
    }
}