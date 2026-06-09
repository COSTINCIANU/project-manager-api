<?php
// =====================================================
// AdminController.php — Dashboard Admin
// Gère les utilisateurs et abonnements
// Accessible uniquement aux admins (ROLE_ADMIN)
// =====================================================

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/admin')]
class AdminController extends AbstractController
{
    // =====================
    // Vérification admin
    // =====================
    private function checkAdmin(): ?JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$user || !in_array('ROLE_ADMIN', $user->getRoles())) {
            return $this->json(['error' => 'Accès refusé — admin requis'], 403);
        }

        return null;
    }

    // =====================
    // GET — Statistiques globales
    // =====================
    #[Route('/stats', methods: ['GET'])]
    public function stats(EntityManagerInterface $em): JsonResponse
    {
        $error = $this->checkAdmin();
        if ($error) return $error;

        $users = $em->getRepository(User::class)->findAll();

        $totalUsers = count($users);
        $activeUsers = count(array_filter($users, fn($u) => $u->getIsActive()));
        $planCounts = [
            'free' => 0,
            'pro' => 0,
            'enterprise' => 0,
        ];

        foreach ($users as $user) {
            $plan = $user->getPlan();
            if (isset($planCounts[$plan])) {
                $planCounts[$plan]++;
            }
        }

        // Revenus mensuels estimés
        $monthlyRevenue = ($planCounts['pro'] * 9) + ($planCounts['enterprise'] * 29);

        return $this->json([
            'totalUsers' => $totalUsers,
            'activeUsers' => $activeUsers,
            'planCounts' => $planCounts,
            'monthlyRevenue' => $monthlyRevenue,
        ]);
    }

    // =====================
    // GET — Liste des utilisateurs
    // =====================
    #[Route('/users', methods: ['GET'])]
    public function users(EntityManagerInterface $em): JsonResponse
    {
        $error = $this->checkAdmin();
        if ($error) return $error;

        $users = $em->getRepository(User::class)->findAll();

        $data = array_map(function($user) {
            return [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
                'plan' => $user->getPlan(),
                'isActive' => $user->getIsActive(),
            ];
        }, $users);

        return $this->json($data);
    }

    // =====================
    // PUT — Modifier le plan d'un utilisateur
    // =====================
    #[Route('/users/{id}/plan', methods: ['PUT'])]
    public function updatePlan(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $error = $this->checkAdmin();
        if ($error) return $error;

        $user = $em->getRepository(User::class)->find($id);

        if (!$user) {
            return $this->json(['error' => 'Utilisateur non trouvé'], 404);
        }

        $data = json_decode($request->getContent(), true);
        $plan = $data['plan'] ?? 'free';

        if (!in_array($plan, ['free', 'pro', 'enterprise'])) {
            return $this->json(['error' => 'Plan invalide'], 400);
        }

        $user->setPlan($plan);
        $em->flush();

        return $this->json([
            'message' => 'Plan mis à jour',
            'plan' => $plan,
        ]);
    }

    // =====================
    // PUT — Activer/Désactiver un utilisateur
    // =====================
    #[Route('/users/{id}/toggle', methods: ['PUT'])]
    public function toggleUser(int $id, EntityManagerInterface $em): JsonResponse
    {
        $error = $this->checkAdmin();
        if ($error) return $error;

        $user = $em->getRepository(User::class)->find($id);

        if (!$user) {
            return $this->json(['error' => 'Utilisateur non trouvé'], 404);
        }

        // On ne peut pas désactiver son propre compte
        /** @var \App\Entity\User $currentUser */
        $currentUser = $this->getUser();
        if ($user->getId() === $currentUser->getId()) {
            return $this->json(['error' => 'Impossible de désactiver votre propre compte'], 400);
        }

        $user->setIsActive(!$user->getIsActive());
        $em->flush();

        return $this->json([
            'message' => $user->getIsActive() ? 'Utilisateur activé' : 'Utilisateur désactivé',
            'isActive' => $user->getIsActive(),
        ]);
    }

    // =====================
    // DELETE — Supprimer un utilisateur
    // =====================
    #[Route('/users/{id}', methods: ['DELETE'])]
    public function deleteUser(int $id, EntityManagerInterface $em): JsonResponse
    {
        $error = $this->checkAdmin();
        if ($error) return $error;

        $user = $em->getRepository(User::class)->find($id);

        if (!$user) {
            return $this->json(['error' => 'Utilisateur non trouvé'], 404);
        }

        /** @var \App\Entity\User $currentUser */
        $currentUser = $this->getUser();
        if ($user->getId() === $currentUser->getId()) {
            return $this->json(['error' => 'Impossible de supprimer votre propre compte'], 400);
        }

        $em->remove($user);
        $em->flush();

        return $this->json(['message' => 'Utilisateur supprimé']);
    }
}
