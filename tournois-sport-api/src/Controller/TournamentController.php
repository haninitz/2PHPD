<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Tournament;
use App\Entity\User;
use App\Service\TournamentStatusService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @Route("/api/tournaments")
 */
final class TournamentController extends AbstractController
{
    /**
     * @Route("", name="api_tournament_index", methods={"GET"})
     */
    public function index(EntityManagerInterface $entityManager, TournamentStatusService $statusService): JsonResponse
    {
        $tournaments = $entityManager->getRepository(Tournament::class)->findBy([], ['startDate' => 'DESC']);

        $data = [];
        foreach ($tournaments as $tournament) {
            $data[] = $this->normalizeTournament($tournament, $statusService);
        }

        return $this->json($data);
    }

    /**
     * @Route("", name="api_tournament_create", methods={"POST"})
     */
    public function create(
        Request $request,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        TournamentStatusService $statusService
    ): JsonResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $organizer = $this->getUser();
        if (!$organizer instanceof User) {
            return $this->json(['error' => 'Non authentifie.'], 401);
        }

        $payload = $this->payload($request);
        if (isset($payload['error'])) {
            return $this->json($payload, 400);
        }

        foreach (['tournamentName', 'startDate', 'endDate', 'description', 'sport'] as $required) {
            if (empty($payload[$required])) {
                return $this->json(['error' => sprintf('Le champ "%s" est requis.', $required)], 400);
            }
        }

        try {
            $tournament = (new Tournament())
                ->setTournamentName((string) $payload['tournamentName'])
                ->setStartDate(new DateTimeImmutable((string) $payload['startDate']))
                ->setEndDate(new DateTimeImmutable((string) $payload['endDate']))
                ->setLocation(isset($payload['location']) ? (string) $payload['location'] : null)
                ->setDescription((string) $payload['description'])
                ->setMaxParticipants((int) ($payload['maxParticipants'] ?? 2))
                ->setSport((string) $payload['sport'])
                ->setOrganizer($organizer);
        } catch (\Throwable $e) {
            return $this->json(['error' => 'Dates invalides. Utilisez le format YYYY-MM-DD.'], 400);
        }

        $violations = $validator->validate($tournament);
        if (count($violations) > 0) {
            return $this->json(['errors' => $this->formatViolations($violations)], 422);
        }

        $entityManager->persist($tournament);
        $entityManager->flush();

        return $this->json($this->normalizeTournament($tournament, $statusService), 201);
    }

    /**
     * @Route("/{id}", name="api_tournament_show", methods={"GET"})
     */
    public function show(?Tournament $tournament, TournamentStatusService $statusService): JsonResponse
    {
        if ($tournament === null) {
            return $this->json(['error' => 'Tournoi introuvable.'], 404);
        }

        return $this->json($this->normalizeTournament($tournament, $statusService));
    }

    /**
     * @Route("/{id}", name="api_tournament_update", methods={"PUT"})
     */
    public function update(
        ?Tournament $tournament,
        Request $request,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        TournamentStatusService $statusService
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
            return $this->json(['error' => 'Acces refuse.'], 403);
        }

        $payload = $this->payload($request);
        if (isset($payload['error'])) {
            return $this->json($payload, 400);
        }

        try {
            if (array_key_exists('tournamentName', $payload)) {
                $tournament->setTournamentName((string) $payload['tournamentName']);
            }

            if (array_key_exists('startDate', $payload)) {
                $tournament->setStartDate(new DateTimeImmutable((string) $payload['startDate']));
            }

            if (array_key_exists('endDate', $payload)) {
                $tournament->setEndDate(new DateTimeImmutable((string) $payload['endDate']));
            }
        } catch (\Throwable $e) {
            return $this->json(['error' => 'Dates invalides. Utilisez le format YYYY-MM-DD.'], 400);
        }

        if (array_key_exists('location', $payload)) {
            $tournament->setLocation($payload['location'] !== null ? (string) $payload['location'] : null);
        }

        if (array_key_exists('description', $payload)) {
            $tournament->setDescription((string) $payload['description']);
        }

        if (array_key_exists('maxParticipants', $payload)) {
            $tournament->setMaxParticipants((int) $payload['maxParticipants']);
        }

        if (array_key_exists('sport', $payload)) {
            $tournament->setSport((string) $payload['sport']);
        }

        $violations = $validator->validate($tournament);
        if (count($violations) > 0) {
            return $this->json(['errors' => $this->formatViolations($violations)], 422);
        }

        $entityManager->flush();

        return $this->json($this->normalizeTournament($tournament, $statusService));
    }

    /**
     * @Route("/{id}", name="api_tournament_delete", methods={"DELETE"})
     */
    public function delete(?Tournament $tournament, EntityManagerInterface $entityManager): JsonResponse
    {
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
            return $this->json(['error' => 'Acces refuse.'], 403);
        }

        $entityManager->remove($tournament);
        $entityManager->flush();

        return $this->json(['message' => 'Tournoi supprime.']);
    }

    private function payload(Request $request): array
    {
        try {
            return $request->toArray();
        } catch (\Throwable $e) {
            return ['error' => 'JSON invalide.'];
        }
    }

    private function formatViolations(iterable $violations): array
    {
        $errors = [];

        foreach ($violations as $violation) {
            $property = $violation->getPropertyPath();

            if ($property === '') {
                $property = 'global';
            }

            $errors[$property] = $violation->getMessage();
        }

        return $errors;
    }

    private function normalizeUser(?User $user): ?array
    {
        if ($user === null) {
            return null;
        }

        return [
            'id' => $user->getId(),
            'username' => $user->getUsername(),
        ];
    }

    private function normalizeTournament(Tournament $tournament, TournamentStatusService $statusService): array
    {
        $startDate = $tournament->getStartDate();
        $endDate = $tournament->getEndDate();

        return [
            'id' => $tournament->getId(),
            'tournamentName' => $tournament->getTournamentName(),
            'startDate' => $startDate ? $startDate->format('Y-m-d') : null,
            'endDate' => $endDate ? $endDate->format('Y-m-d') : null,
            'location' => $tournament->getLocation(),
            'description' => $tournament->getDescription(),
            'maxParticipants' => $tournament->getMaxParticipants(),
            'sport' => $tournament->getSport(),
            'status' => $statusService->compute($tournament),
            'organizer' => $this->normalizeUser($tournament->getOrganizer()),
            'winner' => $this->normalizeUser($tournament->getWinner()),
        ];
    }
}