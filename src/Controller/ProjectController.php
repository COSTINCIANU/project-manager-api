<?php
// =====================================================
// ProjectController.php — Gestion des projets
// Permissions selon le rôle métier :
// - GET : tous les rôles
// - POST : manager et admin
// - PUT : manager et admin
// - DELETE : admin uniquement
// =====================================================

namespace App\Controller;

use App\Entity\Project;
use App\Service\PermissionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/projects')]
class ProjectController extends AbstractController
{
    // =====================
    // GET — Récupérer tous les projets
    // =====================
    #[Route('', methods: ['GET'])]
    public function index(EntityManagerInterface $em): JsonResponse
    {
        // Tous les rôles peuvent voir les projets
        $projects = $em->getRepository(Project::class)->findAll();

        $data = array_map(function($project) {
            return [
                'id' => $project->getId(),
                'name' => $project->getName(),
                'status' => $project->getStatus(),
                'color' => $project->getColor(),
                'progress' => $project->getProgress(),
            ];
        }, $projects);

        return $this->json($data);
    }

    // =====================
    // POST — Créer un nouveau projet
    // =====================
    #[Route('', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em, PermissionService $permissions): JsonResponse
    {
        // Seuls manager et admin peuvent créer un projet
        if (!$permissions->canCreateProject()) {
            return $this->json(['error' => 'Accès refusé — rôle manager ou admin requis'], 403);
        }

        $data = json_decode($request->getContent(), true);

        $project = new Project();
        $project->setName($data['name']);
        $project->setStatus($data['status']);
        $project->setColor($data['color']);
        $project->setProgress($data['progress'] ?? 0);

        $em->persist($project);
        $em->flush();

        return $this->json([
            'id' => $project->getId(),
            'name' => $project->getName(),
            'status' => $project->getStatus(),
            'color' => $project->getColor(),
            'progress' => $project->getProgress(),
        ], 201);
    }

    // =====================
    // PUT — Modifier un projet
    // =====================
    #[Route('/{id}', methods: ['PUT'])]
    public function update(int $id, Request $request, EntityManagerInterface $em, PermissionService $permissions): JsonResponse
    {
        // Seuls manager et admin peuvent modifier un projet
        if (!$permissions->canEditProject()) {
            return $this->json(['error' => 'Accès refusé — rôle manager ou admin requis'], 403);
        }

        $project = $em->getRepository(Project::class)->find($id);

        if (!$project) {
            return $this->json(['error' => 'Projet non trouvé'], 404);
        }

        $data = json_decode($request->getContent(), true);
        $project->setName($data['name'] ?? $project->getName());
        $project->setStatus($data['status'] ?? $project->getStatus());
        $project->setColor($data['color'] ?? $project->getColor());
        $project->setProgress($data['progress'] ?? $project->getProgress());

        $em->flush();

        return $this->json([
            'id' => $project->getId(),
            'name' => $project->getName(),
            'status' => $project->getStatus(),
            'color' => $project->getColor(),
            'progress' => $project->getProgress(),
        ]);
    }

    // =====================
    // DELETE — Supprimer un projet
    // =====================
    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id, EntityManagerInterface $em, PermissionService $permissions): JsonResponse
    {
        // Seul admin peut supprimer un projet
        if (!$permissions->canDeleteProject()) {
            return $this->json(['error' => 'Accès refusé — rôle admin requis'], 403);
        }

        $project = $em->getRepository(Project::class)->find($id);

        if (!$project) {
            return $this->json(['error' => 'Projet non trouvé'], 404);
        }

        $em->remove($project);
        $em->flush();

        return $this->json(['message' => 'Projet supprimé avec succès']);
    }
}
