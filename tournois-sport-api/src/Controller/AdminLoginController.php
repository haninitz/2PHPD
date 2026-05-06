<?php

declare(strict_types=1);

namespace App\Controller;

use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

final class AdminLoginController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function home(): Response
    {
    return $this->redirectToRoute('login_admin');
    }
    #[Route('/login-admin', name: 'login_admin', methods: ['GET', 'POST'])]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('admin_ui_dashboard');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        $errorHtml = '';
        if ($error !== null) {
            $errorHtml = '<div class="error">Identifiants invalides.</div>';
        }

        return new Response('
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion admin</title>
    <style>
        body { margin:0; font-family:Arial; background:#f3f4f6; display:flex; align-items:center; justify-content:center; min-height:100vh; }
        .card { width:380px; background:white; padding:30px; border-radius:14px; box-shadow:0 10px 30px rgba(0,0,0,.08); }
        h1 { text-align:center; margin-top:0; }
        label { display:block; margin-top:15px; font-weight:bold; }
        input { width:100%; padding:10px; margin-top:6px; border:1px solid #d1d5db; border-radius:8px; box-sizing:border-box; }
        button { width:100%; margin-top:20px; padding:12px; background:#2563eb; color:white; border:0; border-radius:8px; font-weight:bold; cursor:pointer; }
        .error { background:#fee2e2; color:#991b1b; padding:10px; border-radius:8px; margin-bottom:15px; }
        .hint { margin-top:15px; color:#6b7280; font-size:13px; text-align:center; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Administration</h1>
        '.$errorHtml.'
        <form method="post" action="/login-admin">
            <label>Email</label>
            <input type="email" name="emailAddress" value="'.htmlspecialchars($lastUsername, ENT_QUOTES).'" required>

            <label>Mot de passe</label>
            <input type="password" name="password" required>

            <button type="submit">Se connecter</button>
        </form>
        <div class="hint">admin@example.com / adminpass</div>
    </div>
</body>
</html>');
    }

    #[Route('/logout-admin', name: 'logout_admin', methods: ['GET', 'POST'])]
    public function logout(): void
    {
        throw new LogicException('Intercepté par Symfony Security.');
    }
}