<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/players')]
final class PlayerController extends AbstractController
{
    #[Route('', name: 'api_player_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): JsonResponse
    {
        $players = $entityManager->getRepository(User::class)->findBy([], ['id' => 'ASC']);

        $data = [];
        foreach ($players as $user) {
            $data[] = $this->normalizeUser($user);
        }

        return $this->json($data);
    }

    #[Route('/{id}', name: 'api_player_show', methods: ['GET'])]
    public function show(?User $user): JsonResponse
    {
        if ($user === null) {
            return $this->json(['error' => 'Joueur introuvable.'], 404);
        }

        return $this->json($this->normalizeUser($user));
    }

    #[Route('/{id}', name: 'api_player_update', methods: ['PUT'])]
    public function update(
        ?User $user,
        Request $request,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {
        if ($user === null) {
            return $this->json(['error' => 'Joueur introuvable.'], 404);
        }

        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $actor = $this->getUser();
        if (!$actor instanceof User) {
            return $this->json(['error' => 'Non authentifie.'], 401);
        }

        if (!$actor->isAdmin() && $actor->getId() !== $user->getId()) {
            return $this->json(['error' => 'Acces refuse.'], 403);
        }

        $payload = $this->payload($request);
        if (isset($payload['error'])) {
            return $this->json($payload, 400);
        }

        if (array_key_exists('lastName', $payload)) {
            $user->setLastName((string) $payload['lastName']);
        }

        if (array_key_exists('firstName', $payload)) {
            $user->setFirstName((string) $payload['firstName']);
        }

        if (array_key_exists('username', $payload)) {
            $user->setUsername((string) $payload['username']);
        }

        if (array_key_exists('emailAddress', $payload)) {
            $user->setEmailAddress((string) $payload['emailAddress']);
        }

        if (array_key_exists('status', $payload) && $actor->isAdmin()) {
            $user->setStatus((string) $payload['status']);
        }

        if (array_key_exists('roles', $payload) && $actor->isAdmin() && is_array($payload['roles'])) {
            $user->setRoles($payload['roles']);
        }

        if (!empty($payload['password'])) {
            $user->setPassword($passwordHasher->hashPassword($user, (string) $payload['password']));
        }

        $violations = $validator->validate($user);
        if (count($violations) > 0) {
            return $this->json(['errors' => $this->formatViolations($violations)], 422);
        }

        $entityManager->flush();

        return $this->json($this->normalizeUser($user));
    }

    #[Route('/{id}', name: 'api_player_delete', methods: ['DELETE'])]
    public function delete(?User $user, EntityManagerInterface $entityManager): JsonResponse
    {
        if ($user === null) {
            return $this->json(['error' => 'Joueur introuvable.'], 404);
        }

        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $entityManager->remove($user);
        $entityManager->flush();

        return $this->json(['message' => 'Joueur supprime.']);
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

    private function normalizeUser(User $user): array
    {
        return [
            'id' => $user->getId(),
            'lastName' => $user->getLastName(),
            'firstName' => $user->getFirstName(),
            'username' => $user->getUsername(),
            'emailAddress' => $user->getEmailAddress(),
            'status' => $user->getStatus(),
            'roles' => $user->getRoles(),
        ];
    }
}