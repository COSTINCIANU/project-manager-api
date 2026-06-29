<?php

// =====================================================
// PublicApiController.php — API Publique
// Endpoints accessibles via clé API (X-API-Key)
// pour les développeurs externes
//
// Authentification : Header X-API-Key: votre_cle
//
// Endpoints disponibles :
// GET /api/public/projects     — Liste des projets
// GET /api/public/tasks        — Liste des tâches
// GET /api/public/tasks/{id}   — Détail d'une tâche
// GET /api/public/stats        — Statistiques globales
// =====================================================

namespace App\Controller;

use App\Entity\ApiKey;
use App\Entity\Project;
use App\Entity\Task;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/public')]
class PublicApiController extends AbstractController
{
    // =====================
    // MIDDLEWARE — Vérification de la clé API
    // =====================
    private function checkApiKey(Request $request, EntityManagerInterface $em): ?JsonResponse
    {
        $apiKey = $request->headers->get('X-API-Key');

        if (!$apiKey) {
            return new JsonResponse([
                'error' => 'Clé API manquante',
                'message' => 'Ajoutez le header X-API-Key: votre_cle',
            ], 401);
        }

        $key = $em->getRepository(ApiKey::class)->findActiveKey($apiKey);

        if (!$key) {
            return new JsonResponse([
                'error' => 'Clé API invalide ou désactivée',
            ], 401);
        }

        // On met à jour la date de dernière utilisation
        $key->setLastUsedAt(new \DateTime());
        $em->flush();

        return null;
    }

    // =====================
    // GET — Liste des projets
    // =====================
    #[Route('/projects', methods: ['GET'])]
    public function projects(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $error = $this->checkApiKey($request, $em);
        if ($error) {
            return $error;
        }

        $projects = $em->getRepository(Project::class)->findAll();

        $data = array_map(function ($project) {
            return [
                'id' => $project->getId(),
                'name' => $project->getName(),
                'status' => $project->getStatus(),
                'progress' => $project->getProgress(),
                'color' => $project->getColor(),
            ];
        }, $projects);

        return $this->json([
            'data' => $data,
            'total' => count($data),
            'timestamp' => date('Y-m-d H:i:s'),
        ]);
    }

    // =====================
    // GET — Liste des tâches
    // =====================
    #[Route('/tasks', methods: ['GET'])]
    public function tasks(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $error = $this->checkApiKey($request, $em);
        if ($error) {
            return $error;
        }

        // Filtres optionnels
        $projectId = $request->query->get('project_id');
        $priority = $request->query->get('priority');
        $done = $request->query->get('done');

        $criteria = [];
        if ($projectId) {
            $criteria['projectId'] = (int) $projectId;
        }
        if ($priority) {
            $criteria['priority'] = $priority;
        }
        if (null !== $done) {
            $criteria['done'] = 'true' === $done;
        }

        $tasks = $em->getRepository(Task::class)->findBy($criteria);

        $data = array_map(function ($task) {
            return [
                'id' => $task->getId(),
                'name' => $task->getName(),
                'description' => $task->getDescription(),
                'priority' => $task->getPriority(),
                'done' => $task->isDone(),
                'inProgress' => $task->isInProgress(),
                'dueDate' => $task->getDueDate(),
                'projectId' => $task->getProjectId(),
                'tags' => $task->getTags() ?? [],
            ];
        }, $tasks);

        return $this->json([
            'data' => $data,
            'total' => count($data),
            'timestamp' => date('Y-m-d H:i:s'),
        ]);
    }

    // =====================
    // GET — Détail d'une tâche
    // =====================
    #[Route('/tasks/{id}', methods: ['GET'])]
    public function task(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $error = $this->checkApiKey($request, $em);
        if ($error) {
            return $error;
        }

        $task = $em->getRepository(Task::class)->find($id);

        if (!$task) {
            return $this->json(['error' => 'Tâche non trouvée'], 404);
        }

        return $this->json([
            'data' => [
                'id' => $task->getId(),
                'name' => $task->getName(),
                'description' => $task->getDescription(),
                'priority' => $task->getPriority(),
                'done' => $task->isDone(),
                'inProgress' => $task->isInProgress(),
                'dueDate' => $task->getDueDate(),
                'projectId' => $task->getProjectId(),
                'tags' => $task->getTags() ?? [],
                'estimatedTime' => $task->getEstimatedTime(),
                'elapsedTime' => $task->getElapsedTime(),
            ],
            'timestamp' => date('Y-m-d H:i:s'),
        ]);
    }

    // =====================
    // GET — Statistiques globales
    // =====================
    #[Route('/stats', methods: ['GET'])]
    public function stats(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $error = $this->checkApiKey($request, $em);
        if ($error) {
            return $error;
        }

        $tasks = $em->getRepository(Task::class)->findAll();
        $projects = $em->getRepository(Project::class)->findAll();

        $totalTasks = count($tasks);
        $doneTasks = count(array_filter($tasks, fn ($t) => $t->isDone()));
        $inProgressTasks = count(array_filter($tasks, fn ($t) => $t->isInProgress() && !$t->isDone()));

        return $this->json([
            'data' => [
                'projects' => [
                    'total' => count($projects),
                    'active' => count(array_filter($projects, fn ($p) => 'En cours' === $p->getStatus())),
                ],
                'tasks' => [
                    'total' => $totalTasks,
                    'done' => $doneTasks,
                    'inProgress' => $inProgressTasks,
                    'todo' => $totalTasks - $doneTasks - $inProgressTasks,
                    'completionRate' => $totalTasks > 0
                        ? round(($doneTasks / $totalTasks) * 100, 1)
                        : 0,
                ],
                'priorities' => [
                    'critique' => count(array_filter($tasks, fn ($t) => 'critique' === $t->getPriority())),
                    'haute' => count(array_filter($tasks, fn ($t) => 'haute' === $t->getPriority())),
                    'normale' => count(array_filter($tasks, fn ($t) => 'normale' === $t->getPriority())),
                    'basse' => count(array_filter($tasks, fn ($t) => 'basse' === $t->getPriority())),
                ],
            ],
            'timestamp' => date('Y-m-d H:i:s'),
        ]);
    }
}
