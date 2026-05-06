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
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin-ui')]
final class AdminUiController extends AbstractController
{
    #[Route('', name: 'admin_ui_dashboard', methods: ['GET'])]
    public function dashboard(EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $users = $em->getRepository(User::class)->findBy([], ['id' => 'ASC']);
        $tournaments = $em->getRepository(Tournament::class)->findBy([], ['id' => 'ASC']);
        $registrations = $em->getRepository(Registration::class)->findBy([], ['id' => 'ASC']);
        $matches = $em->getRepository(SportMatch::class)->findBy([], ['id' => 'ASC']);

        return new Response($this->layout(
            'Dashboard admin',
            $this->renderStats($users, $tournaments, $registrations, $matches)
            .$this->renderTournaments($tournaments, $users)
            .$this->renderRegistrations($registrations)
            .$this->renderMatches($matches)
            .$this->renderUsers($users)
        ));
    }

    #[Route('/registrations/{id}/confirm', name: 'admin_ui_registration_confirm', methods: ['POST'])]
    public function confirmRegistration(Registration $registration, EntityManagerInterface $em): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $registration->setStatus(Registration::STATUS_CONFIRMEE);
        $em->flush();

        return $this->redirectToRoute('admin_ui_dashboard');
    }

    #[Route('/matches/{id}/scores', name: 'admin_ui_match_scores', methods: ['POST'])]
    public function updateScores(
        SportMatch $match,
        Request $request,
        EntityManagerInterface $em,
        SportMatchService $sportMatchService
    ): RedirectResponse {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $admin = $this->getUser();

        if (!$admin instanceof User) {
            return $this->redirectToRoute('login_admin');
        }

        $scorePlayer1 = $request->request->get('scorePlayer1');
        $scorePlayer2 = $request->request->get('scorePlayer2');

        try {
            $sportMatchService->updateScores(
                $match,
                $admin,
                $scorePlayer1 !== null && $scorePlayer1 !== '' ? (int) $scorePlayer1 : null,
                $scorePlayer2 !== null && $scorePlayer2 !== '' ? (int) $scorePlayer2 : null
            );

            $em->flush();
        } catch (RuntimeException) {
            // On revient simplement au dashboard si la mise à jour échoue.
        }

        return $this->redirectToRoute('admin_ui_dashboard');
    }

    #[Route('/tournaments/{id}/winner', name: 'admin_ui_tournament_winner', methods: ['POST'])]
    public function setWinner(
        Tournament $tournament,
        Request $request,
        EntityManagerInterface $em,
        NotificationService $notificationService
    ): RedirectResponse {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $winnerId = (int) $request->request->get('winnerId');
        $winner = $em->getRepository(User::class)->find($winnerId);

        if ($winner instanceof User) {
            $tournament->setWinner($winner);
            $notificationService->notifyTournamentWon($tournament);
            $em->flush();
        }

        return $this->redirectToRoute('admin_ui_dashboard');
    }

    private function renderStats(array $users, array $tournaments, array $registrations, array $matches): string
    {
        return '
        <div class="grid">
            <div class="stat"><strong>'.count($users).'</strong><span>Utilisateurs</span></div>
            <div class="stat"><strong>'.count($tournaments).'</strong><span>Tournois</span></div>
            <div class="stat"><strong>'.count($registrations).'</strong><span>Inscriptions</span></div>
            <div class="stat"><strong>'.count($matches).'</strong><span>Parties</span></div>
        </div>';
    }

    private function renderTournaments(array $tournaments, array $users): string
    {
        $html = '<section><h2>Tournois</h2><table><tr><th>ID</th><th>Nom</th><th>Sport</th><th>Dates</th><th>Organisateur</th><th>Vainqueur</th><th>Action</th></tr>';

        foreach ($tournaments as $tournament) {
            $winnerOptions = '<option value="">Choisir</option>';

            foreach ($users as $user) {
                $selected = $tournament->getWinner() && $tournament->getWinner()->getId() === $user->getId() ? 'selected' : '';
                $winnerOptions .= '<option value="'.$user->getId().'" '.$selected.'>'.$this->e($user->getUsername()).'</option>';
            }

            $html .= '<tr>
                <td>'.$tournament->getId().'</td>
                <td>'.$this->e($tournament->getTournamentName()).'</td>
                <td>'.$this->e($tournament->getSport()).'</td>
                <td>'.$tournament->getStartDate()->format('Y-m-d').' → '.$tournament->getEndDate()->format('Y-m-d').'</td>
                <td>'.$this->e($tournament->getOrganizer() ? $tournament->getOrganizer()->getUsername() : '-').'</td>
                <td>'.$this->e($tournament->getWinner() ? $tournament->getWinner()->getUsername() : '-').'</td>
                <td>
                    <form method="post" action="/admin-ui/tournaments/'.$tournament->getId().'/winner">
                        <select name="winnerId">'.$winnerOptions.'</select>
                        <button>Valider</button>
                    </form>
                </td>
            </tr>';
        }

        return $html.'</table></section>';
    }

    private function renderRegistrations(array $registrations): string
    {
        $html = '<section><h2>Inscriptions</h2><table><tr><th>ID</th><th>Joueur</th><th>Tournoi</th><th>Status</th><th>Action</th></tr>';

        foreach ($registrations as $registration) {
            $action = '-';

            if ($registration->getStatus() !== Registration::STATUS_CONFIRMEE) {
                $action = '<form method="post" action="/admin-ui/registrations/'.$registration->getId().'/confirm">
                    <button>Confirmer</button>
                </form>';
            }

            $html .= '<tr>
                <td>'.$registration->getId().'</td>
                <td>'.$this->e($registration->getPlayer() ? $registration->getPlayer()->getUsername() : '-').'</td>
                <td>'.$this->e($registration->getTournament() ? $registration->getTournament()->getTournamentName() : '-').'</td>
                <td><span class="badge">'.$this->e($registration->getStatus()).'</span></td>
                <td>'.$action.'</td>
            </tr>';
        }

        return $html.'</table></section>';
    }

    private function renderMatches(array $matches): string
    {
        $html = '<section><h2>Parties</h2><table><tr><th>ID</th><th>Tournoi</th><th>Joueur 1</th><th>Score 1</th><th>Joueur 2</th><th>Score 2</th><th>Status</th><th>Action</th></tr>';

        foreach ($matches as $match) {
            $html .= '<tr>
                <td>'.$match->getId().'</td>
                <td>'.$this->e($match->getTournament() ? $match->getTournament()->getTournamentName() : '-').'</td>
                <td>'.$this->e($match->getPlayer1() ? $match->getPlayer1()->getUsername() : '-').'</td>
                <td>'.$this->e((string) $match->getScorePlayer1()).'</td>
                <td>'.$this->e($match->getPlayer2() ? $match->getPlayer2()->getUsername() : '-').'</td>
                <td>'.$this->e((string) $match->getScorePlayer2()).'</td>
                <td><span class="badge">'.$this->e($match->getStatus()).'</span></td>
                <td>
                    <form class="inline" method="post" action="/admin-ui/matches/'.$match->getId().'/scores">
                        <input type="number" name="scorePlayer1" placeholder="S1" value="'.$this->e((string) $match->getScorePlayer1()).'">
                        <input type="number" name="scorePlayer2" placeholder="S2" value="'.$this->e((string) $match->getScorePlayer2()).'">
                        <button>Maj</button>
                    </form>
                </td>
            </tr>';
        }

        return $html.'</table></section>';
    }

    private function renderUsers(array $users): string
    {
        $html = '<section><h2>Utilisateurs</h2><table><tr><th>ID</th><th>Nom</th><th>Email</th><th>Roles</th><th>Status</th></tr>';
       

        foreach ($users as $user) {
            $status = $user->getStatus();
            $badgeClass = match($status){
                'actif' => 'bg-green-500',
                'suspendu' => 'bg-orange-500',
                'banni' =>'bg-red-500'
                };
            $html .= '<tr>
                <td>'.$user->getId().'</td>
                <td>'.$this->e($user->getFirstName().' '.$user->getLastName()).'</td>
                <td>'.$this->e($user->getEmailAddress()).'</td>
                <td>'.$this->e(implode(', ', $user->getRoles())).'</td>
                <td><span class="badge '.$badgeClass.'">'.$this->e($status).'</span></td>
            </tr>';
        }

        return $html.'</table></section>';
    }

    private function layout(string $title, string $content): string
    {
        return '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>'.$this->e($title).'</title>
    <style>
        body { margin:0; font-family:Arial, sans-serif; background:#f3f4f6; color:#111827; }
        header { background:#111827; color:white; padding:18px 30px; display:flex; justify-content:space-between; align-items:center; }
        header a { color:white; text-decoration:none; background:#374151; padding:8px 12px; border-radius:8px; }
        main { padding:30px; }
        h1 { margin:0; }
        h2 { margin-top:35px; }
        .grid { display:grid; grid-template-columns:repeat(4, 1fr); gap:18px; }
        .stat { background:white; padding:22px; border-radius:14px; box-shadow:0 8px 20px rgba(0,0,0,.06); }
        .stat strong { display:block; font-size:30px; }
        .stat span { color:#6b7280; }
        section { background:white; padding:22px; margin-top:22px; border-radius:14px; box-shadow:0 8px 20px rgba(0,0,0,.06); overflow:auto; }
        table { width:100%; border-collapse:collapse; }
        th, td { padding:10px; border-bottom:1px solid #e5e7eb; text-align:left; vertical-align:middle; }
        th { background:#f9fafb; }
        button { background:#2563eb; color:white; border:0; padding:7px 10px; border-radius:7px; cursor:pointer; }
        input, select { padding:7px; border:1px solid #d1d5db; border-radius:7px; }
        input[type=number] { width:60px; }
        .badge { background:#e5e7eb; padding:4px 8px; border-radius:999px; font-size:12px; }
        .bg-green-500 { background:#10b981; color:white; }
        .bg-orange-500 { background:#f59e0b; color:black; }
        .bg-red-500 { background:#ef4444; color:white; }
        .inline { display:flex; gap:6px; align-items:center; }
    </style>
</head>
<body>
    <header>
        <h1>'.$this->e($title).'</h1>
        <a href="/logout-admin">Déconnexion</a>
    </header>
    <main>'.$content.'</main>
</body>
</html>';
    }

    private function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}