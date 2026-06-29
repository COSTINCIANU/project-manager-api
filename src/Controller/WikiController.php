<?php

// =====================================================
// WikiController.php — Wiki par projet
// CRUD complet pour les pages Wiki en Markdown
// Permissions : lecture pour tous, écriture pour dev+
// =====================================================

namespace App\Controller;

use App\Entity\WikiPage;
use App\Service\PermissionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/wiki')]
class WikiController extends AbstractController
{
    // =====================
    // GET — Récupérer toutes les pages d'un projet
    // =====================
    #[Route('/project/{projectId}', methods: ['GET'])]
    public function index(int $projectId, EntityManagerInterface $em): JsonResponse
    {
        $pages = $em->getRepository(WikiPage::class)->findBy(
            ['projectId' => $projectId],
            ['createdAt' => 'DESC']
        );

        $data = array_map(function ($page) {
            return [
                'id' => $page->getId(),
                'title' => $page->getTitle(),
                'content' => $page->getContent(),
                'projectId' => $page->getProjectId(),
                'authorEmail' => $page->getAuthorEmail(),
                'createdAt' => $page->getCreatedAt()->format('Y-m-d H:i:s'),
                'updatedAt' => $page->getUpdatedAt()?->format('Y-m-d H:i:s'),
            ];
        }, $pages);

        return $this->json($data);
    }

    // =====================
    // GET — Récupérer une page par son id
    // =====================
    #[Route('/{id}', methods: ['GET'])]
    public function show(int $id, EntityManagerInterface $em): JsonResponse
    {
        $page = $em->getRepository(WikiPage::class)->find($id);

        if (!$page) {
            return $this->json(['error' => 'Page non trouvée'], 404);
        }

        return $this->json([
            'id' => $page->getId(),
            'title' => $page->getTitle(),
            'content' => $page->getContent(),
            'projectId' => $page->getProjectId(),
            'authorEmail' => $page->getAuthorEmail(),
            'createdAt' => $page->getCreatedAt()->format('Y-m-d H:i:s'),
            'updatedAt' => $page->getUpdatedAt()?->format('Y-m-d H:i:s'),
        ]);
    }

    // =====================
    // POST — Créer une nouvelle page Wiki
    // =====================
    #[Route('', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em, PermissionService $permissions): JsonResponse
    {
        // Dev, manager et admin peuvent créer une page Wiki
        if (!$permissions->canCreateTask()) {
            return $this->json(['error' => 'Accès refusé'], 403);
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $data = json_decode($request->getContent(), true);

        if (empty($data['title']) || empty($data['projectId'])) {
            return $this->json(['error' => 'Titre et projet requis'], 400);
        }

        $page = new WikiPage();
        $page->setTitle($data['title']);
        $page->setContent($data['content'] ?? '');
        $page->setProjectId($data['projectId']);
        $page->setAuthorEmail($user->getEmail());

        $em->persist($page);
        $em->flush();

        return $this->json([
            'id' => $page->getId(),
            'title' => $page->getTitle(),
            'content' => $page->getContent(),
            'projectId' => $page->getProjectId(),
            'authorEmail' => $page->getAuthorEmail(),
            'createdAt' => $page->getCreatedAt()->format('Y-m-d H:i:s'),
            'updatedAt' => null,
        ], 201);
    }

    // =====================
    // PUT — Modifier une page Wiki
    // =====================
    #[Route('/{id}', methods: ['PUT'])]
    public function update(int $id, Request $request, EntityManagerInterface $em, PermissionService $permissions): JsonResponse
    {
        // Dev, manager et admin peuvent modifier une page Wiki
        if (!$permissions->canEditTask()) {
            return $this->json(['error' => 'Accès refusé'], 403);
        }

        $page = $em->getRepository(WikiPage::class)->find($id);

        if (!$page) {
            return $this->json(['error' => 'Page non trouvée'], 404);
        }

        $data = json_decode($request->getContent(), true);
        $page->setTitle($data['title'] ?? $page->getTitle());
        $page->setContent($data['content'] ?? $page->getContent());
        $page->setUpdatedAt(new \DateTime());

        $em->flush();

        return $this->json([
            'id' => $page->getId(),
            'title' => $page->getTitle(),
            'content' => $page->getContent(),
            'projectId' => $page->getProjectId(),
            'authorEmail' => $page->getAuthorEmail(),
            'createdAt' => $page->getCreatedAt()->format('Y-m-d H:i:s'),
            'updatedAt' => $page->getUpdatedAt()->format('Y-m-d H:i:s'),
        ]);
    }

    // =====================
    // DELETE — Supprimer une page Wiki
    // =====================
    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id, EntityManagerInterface $em, PermissionService $permissions): JsonResponse
    {
        // Manager et admin peuvent supprimer une page Wiki
        if (!$permissions->canDeleteTask()) {
            return $this->json(['error' => 'Accès refusé'], 403);
        }

        $page = $em->getRepository(WikiPage::class)->find($id);

        if (!$page) {
            return $this->json(['error' => 'Page non trouvée'], 404);
        }

        $em->remove($page);
        $em->flush();

        return $this->json(['message' => 'Page supprimée']);
    }
}
