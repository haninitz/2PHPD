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

final class AuthController extends AbstractController
{
    
    #[Route('/register', name: 'api_register', methods: ['POST'])]
    public function register(
        Request $request,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {
        $payload = $this->payload($request);
        if (isset($payload['error'])) {
            return $this->json($payload, 400);
        }

        $user = (new User())
            ->setLastName((string) ($payload['lastName'] ?? ''))
            ->setFirstName((string) ($payload['firstName'] ?? ''))
            ->setUsername((string) ($payload['username'] ?? ''))
            ->setEmailAddress((string) ($payload['emailAddress'] ?? ''))
            ->setStatus((string) ($payload['status'] ?? User::STATUS_ACTIF));

        $plainPassword = (string) ($payload['password'] ?? '');
        if ($plainPassword === '') {
            return $this->json(['errors' => ['password' => 'Le mot de passe est requis.']], 400);
        }

        $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));

        if (!empty($payload['roles']) && is_array($payload['roles']) && $this->isGranted('ROLE_ADMIN')) {
            $user->setRoles($payload['roles']);
        }

        $violations = $validator->validate($user);
        if (count($violations) > 0) {
            return $this->json(['errors' => $this->formatViolations($violations)], 422);
        }

        $entityManager->persist($user);
        $entityManager->flush();

        return $this->json([
            'message' => 'Utilisateur cree.',
            'user' => $this->normalizeUser($user),
        ], 201);
    }

    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Email ou mot de passe invalide.'], 401);
        }

        return $this->json([
            'message' => 'Connexion reussie.',
            'user' => $this->normalizeUser($user),
        ]);
    }

    /**
     * @Route("/api/me", name="api_me", methods={"GET"})
     */
    public function me(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Non authentifie.'], 401);
        }

        return $this->json($this->normalizeUser($user));
    }

    /**
     * @Route("/api/logout", name="api_logout", methods={"POST"})
     */
    public function logout(): void
    {
        throw new \LogicException('Cette methode est interceptee par le firewall logout.');
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