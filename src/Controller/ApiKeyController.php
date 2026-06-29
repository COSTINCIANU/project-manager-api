<?php

// =====================================================
// ApiKeyController.php — Gestion des clés API
// Permet aux utilisateurs de créer, lister
// et révoquer leurs clés API publiques
// =====================================================

namespace App\Controller;

use App\Entity\ApiKey;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/api-keys')]
class ApiKeyController extends AbstractController
{
    // =====================
    // GET — Lister les clés API de l'utilisateur
    // =====================
    #[Route('', methods: ['GET'])]
    public function index(EntityManagerInterface $em): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        $keys = $em->getRepository(ApiKey::class)->findBy(
            ['userEmail' => $user->getEmail()],
            ['createdAt' => 'DESC']
        );

        $data = array_map(function ($key) {
            return [
                'id' => $key->getId(),
                'name' => $key->getName(),
                // On masque la clé — affiche seulement les 8 premiers caractères
                'apiKey' => substr($key->getApiKey(), 0, 8).'...',
                'fullKey' => $key->getApiKey(),
                'isActive' => $key->isActive(),
                'createdAt' => $key->getCreatedAt()->format('Y-m-d H:i:s'),
                'lastUsedAt' => $key->getLastUsedAt()?->format('Y-m-d H:i:s'),
            ];
        }, $keys);

        return $this->json($data);
    }

    // =====================
    // POST — Créer une nouvelle clé API
    // =====================
    #[Route('', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        $data = json_decode($request->getContent(), true);

        if (empty($data['name'])) {
            return $this->json(['error' => 'Nom de la clé requis'], 400);
        }

        // On crée la nouvelle clé API
        $apiKey = new ApiKey();
        $apiKey->setUserEmail($user->getEmail());
        $apiKey->setName($data['name']);

        $em->persist($apiKey);
        $em->flush();

        return $this->json([
            'id' => $apiKey->getId(),
            'name' => $apiKey->getName(),
            'apiKey' => $apiKey->getApiKey(),
            'message' => 'Clé API créée — copiez-la maintenant, elle ne sera plus affichée en entier !',
        ], 201);
    }

    // =====================
    // DELETE — Révoquer une clé API
    // =====================
    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id, EntityManagerInterface $em): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $apiKey = $em->getRepository(ApiKey::class)->find($id);

        if (!$apiKey || $apiKey->getUserEmail() !== $user->getEmail()) {
            return $this->json(['error' => 'Clé non trouvée'], 404);
        }

        // On désactive la clé au lieu de la supprimer
        $apiKey->setIsActive(false);
        $em->flush();

        return $this->json(['message' => 'Clé API révoquée']);
    }
}
