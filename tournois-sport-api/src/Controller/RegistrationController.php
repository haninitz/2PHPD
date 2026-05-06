<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Registration;
use App\Entity\Tournament;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class RegistrationController extends AbstractController
{
    /**
     * @Route("/api/tournaments/{id}/registrations", name="api_registration_index", methods={"GET"})
     */
    public function index(?Tournament $tournament, EntityManagerInterface $entityManager): JsonResponse
    {
        if ($tournament === null) {
            return $this->json(['error' => 'Tournoi introuvable.'], 404);
        }

        $registrations = $entityManager
            ->getRepository(Registration::class)
            ->findBy(['tournament' => $tournament], ['id' => 'ASC']);

        $data = [];
        foreach ($registrations as $registration) {
            $data[] = $this->normalizeRegistration($registration);
        }

        return $this->json($data);
    }

    /**
     * @Route("/api/tournaments/{id}/registrations", name="api_registration_create", methods={"POST"})
     */
    public function create(
        ?Tournament $tournament,
        Request $request,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator
    ): JsonResponse {
        if ($tournament === null) {
            return $this->json(['error' => 'Tournoi introuvable.'], 404);
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

        $player = $actor;

        if ($actor->isAdmin() && !empty($payload['playerId'])) {
            $player = $entityManager->getRepository(User::class)->find((int) $payload['playerId']);

            if (!$player instanceof User) {
                return $this->json(['error' => 'Joueur introuvable.'], 404);
            }
        }

        if ($player->getStatus() !== User::STATUS_ACTIF) {
            return $this->json(['error' => 'Ce joueur ne peut pas s\'inscrire.'], 400);
        }

        $existing = $entityManager->getRepository(Registration::class)->findOneBy([
            'player' => $player,
            'tournament' => $tournament,
        ]);

        if ($existing !== null) {
            return $this->json(['error' => 'Inscription deja existante pour ce joueur.'], 409);
        }

        $currentRegistrationsCount = $entityManager->getRepository(Registration::class)->count([
            'tournament' => $tournament,
        ]);

        if ($currentRegistrationsCount >= $tournament->getMaxParticipants()) {
            return $this->json(['error' => 'Le tournoi a atteint sa capacite maximale.'], 409);
        }

        $status = Registration::STATUS_EN_ATTENTE;

        if (
            $actor->isAdmin()
            && isset($payload['status'])
            && $payload['status'] === Registration::STATUS_CONFIRMEE
        ) {
            $status = Registration::STATUS_CONFIRMEE;
        }

        $registration = (new Registration())
            ->setPlayer($player)
            ->setTournament($tournament)
            ->setStatus($status);

        $violations = $validator->validate($registration);
        if (count($violations) > 0) {
            return $this->json(['errors' => $this->formatViolations($violations)], 422);
        }

        $entityManager->persist($registration);
        $entityManager->flush();

        return $this->json($this->normalizeRegistration($registration), 201);
    }

    /**
     * @Route("/api/tournaments/{idTournament}/registrations/{idRegistration}", name="api_registration_delete", methods={"DELETE"})
     */
    public function delete(int $idTournament, int $idRegistration, EntityManagerInterface $entityManager): JsonResponse
    {
        $tournament = $entityManager->getRepository(Tournament::class)->find($idTournament);

        if (!$tournament instanceof Tournament) {
            return $this->json(['error' => 'Tournoi introuvable.'], 404);
        }

        $registration = $entityManager->getRepository(Registration::class)->find($idRegistration);

        if (
            !$registration instanceof Registration
            || !$registration->getTournament()
            || $registration->getTournament()->getId() !== $tournament->getId()
        ) {
            return $this->json(['error' => 'Inscription introuvable pour ce tournoi.'], 404);
        }

        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $actor = $this->getUser();
        if (!$actor instanceof User) {
            return $this->json(['error' => 'Non authentifie.'], 401);
        }

        if (
            !$actor->isAdmin()
            || (
                $registration->getPlayer()
                && $registration->getPlayer()->getId() !== $actor->getId()
            )
        ) {
            if (!$actor->isAdmin() && (!$registration->getPlayer() || $registration->getPlayer()->getId() !== $actor->getId())) {
                return $this->json(['error' => 'Acces refuse.'], 403);
            }
        }

        $entityManager->remove($registration);
        $entityManager->flush();

        return $this->json(['message' => 'Inscription annulee.']);
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

    private function normalizeRegistration(Registration $registration): array
    {
        $player = $registration->getPlayer();
        $tournament = $registration->getTournament();

        return [
            'id' => $registration->getId(),
            'registrationDate' => $registration->getRegistrationDate()
                ? $registration->getRegistrationDate()->format('Y-m-d')
                : null,
            'status' => $registration->getStatus(),
            'player' => [
                'id' => $player ? $player->getId() : null,
                'username' => $player ? $player->getUsername() : null,
            ],
            'tournament' => [
                'id' => $tournament ? $tournament->getId() : null,
                'tournamentName' => $tournament ? $tournament->getTournamentName() : null,
            ],
        ];
    }
}