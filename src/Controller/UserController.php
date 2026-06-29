<?php

// =====================================================
// UserController.php — Gestion des utilisateurs
// Permet de récupérer la liste des utilisateurs
// pour l'assignation des tâches
// =====================================================

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/users')]
class UserController extends AbstractController
{
    // =====================
    // GET — Récupérer tous les utilisateurs
    // =====================
    #[Route('', methods: ['GET'])]
    public function index(EntityManagerInterface $em): JsonResponse
    {
        // On récupère tous les utilisateurs
        $users = $em->getRepository(User::class)->findAll();

        // On retourne uniquement les infos non sensibles
        // (pas le mot de passe !)
        $data = array_map(function ($user) {
            return [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
            ];
        }, $users);

        return $this->json($data);
    }
}
