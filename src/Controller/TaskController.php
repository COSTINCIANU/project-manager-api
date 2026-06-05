<?php
// =====================================================
// TaskController.php — Gestion des tâches
// Permissions selon le rôle métier :
// - GET : tous les rôles
// - POST : dev, manager et admin
// - PUT : dev (ses tâches), manager et admin (toutes)
// - DELETE : manager et admin
// =====================================================

namespace App\Controller;

use App\Entity\Task;
use App\Entity\SubTask;
use App\Service\PermissionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\ActionLogService;



#[Route('/api/tasks')]
class TaskController extends AbstractController
{
    // =====================
    // Fonction pour convertir une tâche en tableau
    // =====================
    private function taskToArray(Task $task): array
    {
        // Sous-tâches
        $subTasks = array_map(function($subTask) {
            return [
                'id' => $subTask->getId(),
                'name' => $subTask->getName(),
                'done' => $subTask->isDone(),
            ];
        }, $task->getSubTasks()->toArray());

        return [
            'id' => $task->getId(),
            'name' => $task->getName(),
            'description' => $task->getDescription(),
            'priority' => $task->getPriority(),
            'done' => $task->isDone(),
            'inProgress' => $task->isInProgress(),
            'dueDate' => $task->getDueDate(),
            'projectId' => $task->getProjectId(),
            'estimatedTime' => $task->getEstimatedTime(),
            'elapsedTime' => $task->getElapsedTime(),
            'tags' => $task->getTags() ?? [],
            'assignedTo' => $task->getAssignedTo(),
            'subTasks' => $subTasks,
        ];
    }

    // =====================
    // GET — Récupérer toutes les tâches
    // =====================
    #[Route('', methods: ['GET'])]
    public function index(EntityManagerInterface $em): JsonResponse
    {
        // Tous les rôles peuvent voir les tâches
        $tasks = $em->getRepository(Task::class)->findAll();
        $data = array_map(fn($task) => $this->taskToArray($task), $tasks);
        return $this->json($data);
    }

    // =====================
    // GET — Récupérer une tâche par son id
    // =====================
    #[Route('/{id}', methods: ['GET'])]
    public function show(int $id, EntityManagerInterface $em): JsonResponse
    {
        $task = $em->getRepository(Task::class)->find($id);
        if (!$task) {
            return $this->json(['error' => 'Tâche non trouvée'], 404);
        }
        return $this->json($this->taskToArray($task));
    }

    // =====================
    // POST — Créer une nouvelle tâche
    // =====================
    #[Route('', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em, PermissionService $permissions, ActionLogService $actionLog): JsonResponse
    {
        // Dev, manager et admin peuvent créer une tâche
        if (!$permissions->canCreateTask()) {
            return $this->json(['error' => 'Accès refusé — rôle dev ou supérieur requis'], 403);
        }

        $data = json_decode($request->getContent(), true);

        $task = new Task();
        $task->setName($data['name']);
        $task->setDescription($data['description'] ?? null);
        $task->setPriority($data['priority'] ?? 'normale');
        $task->setDone($data['done'] ?? false);
        $task->setInProgress($data['inProgress'] ?? false);
        $task->setDueDate($data['dueDate'] ?? null);
        $task->setProjectId($data['projectId']);
        $task->setEstimatedTime($data['estimatedTime'] ?? null);
        $task->setElapsedTime($data['elapsedTime'] ?? 0);
        $task->setTags($data['tags'] ?? []);
        $task->setAssignedTo($data['assignedTo'] ?? null);

        // Sous-tâches
        if (!empty($data['subTasks'])) {
            foreach ($data['subTasks'] as $subTaskData) {
                $subTask = new SubTask();
                $subTask->setName($subTaskData['name']);
                $subTask->setDone($subTaskData['done'] ?? false);
                $subTask->setTask($task);
                $em->persist($subTask);
            }
        }

        $em->persist($task);
        $em->flush();

        // =====================
        // Log de l'action — enregistre la création dans l'historique
        // =====================
        $actionLog->log('create_task', 'Tâche créée : ' . $task->getName(), 'task', $task->getId());

        return $this->json($this->taskToArray($task), 201);
    }

    // =====================
    // PUT — Modifier une tâche
    // =====================
    #[Route('/{id}', methods: ['PUT'])]
    public function update(int $id, Request $request, EntityManagerInterface $em, PermissionService $permissions, ActionLogService $actionLog): JsonResponse
    {
        // Dev, manager et admin peuvent modifier une tâche
        if (!$permissions->canEditTask()) {
            return $this->json(['error' => 'Accès refusé — rôle dev ou supérieur requis'], 403);
        }

        $task = $em->getRepository(Task::class)->find($id);
        if (!$task) {
            return $this->json(['error' => 'Tâche non trouvée'], 404);
        }

        $data = json_decode($request->getContent(), true);
        $task->setName($data['name'] ?? $task->getName());
        $task->setDescription($data['description'] ?? $task->getDescription());
        $task->setPriority($data['priority'] ?? $task->getPriority());
        $task->setDone($data['done'] ?? $task->isDone());
        $task->setInProgress($data['inProgress'] ?? $task->isInProgress());
        $task->setDueDate($data['dueDate'] ?? $task->getDueDate());
        $task->setProjectId($data['projectId'] ?? $task->getProjectId());
        $task->setEstimatedTime($data['estimatedTime'] ?? $task->getEstimatedTime());
        $task->setElapsedTime($data['elapsedTime'] ?? $task->getElapsedTime());
        $task->setTags($data['tags'] ?? $task->getTags());
        $task->setAssignedTo($data['assignedTo'] ?? $task->getAssignedTo());

        $em->flush();

        // =====================
        // Log de l'action — enregistre la modification dans l'historique
        // =====================
        $actionLog->log('update_task', 'Tâche modifiée : ' . $task->getName(), 'task', $task->getId());

        return $this->json($this->taskToArray($task));
    }

    // =====================
    // DELETE — Supprimer une tâche
    // =====================
    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id, EntityManagerInterface $em, PermissionService $permissions, ActionLogService $actionLog): JsonResponse
    {
        // Manager et admin peuvent supprimer une tâche
        if (!$permissions->canDeleteTask()) {
            return $this->json(['error' => 'Accès refusé — rôle manager ou admin requis'], 403);
        }

        $task = $em->getRepository(Task::class)->find($id);
        if (!$task) {
            return $this->json(['error' => 'Tâche non trouvée'], 404);
        }

        $em->remove($task);

        // =====================
        // Log de l'action — enregistre la suppression AVANT de supprimer
        // =====================
        $actionLog->log('delete_task', 'Tâche supprimée : ' . $task->getName(), 'task', $id);
        $em->flush();

        return $this->json(['message' => 'Tâche supprimée avec succès']);
    }

    // =====================
    // POST — Ajouter une sous-tâche
    // =====================
    #[Route('/{id}/subtasks', methods: ['POST'])]
    public function addSubTask(int $id, Request $request, EntityManagerInterface $em, PermissionService $permissions): JsonResponse
    {
        if (!$permissions->canEditTask()) {
            return $this->json(['error' => 'Accès refusé'], 403);
        }

        $task = $em->getRepository(Task::class)->find($id);
        if (!$task) {
            return $this->json(['error' => 'Tâche non trouvée'], 404);
        }

        $data = json_decode($request->getContent(), true);
        $subTask = new SubTask();
        $subTask->setName($data['name']);
        $subTask->setDone(false);
        $subTask->setTask($task);

        $em->persist($subTask);
        $em->flush();

        return $this->json([
            'id' => $subTask->getId(),
            'name' => $subTask->getName(),
            'done' => $subTask->isDone(),
        ], 201);
    }

    // =====================
    // PUT — Modifier une sous-tâche
    // =====================
    #[Route('/subtasks/{id}', methods: ['PUT'])]
    public function updateSubTask(int $id, Request $request, EntityManagerInterface $em, PermissionService $permissions): JsonResponse
    {
        if (!$permissions->canEditTask()) {
            return $this->json(['error' => 'Accès refusé'], 403);
        }

        $subTask = $em->getRepository(SubTask::class)->find($id);
        if (!$subTask) {
            return $this->json(['error' => 'Sous-tâche non trouvée'], 404);
        }

        $data = json_decode($request->getContent(), true);
        $subTask->setName($data['name'] ?? $subTask->getName());
        $subTask->setDone($data['done'] ?? $subTask->isDone());

        $em->flush();

        return $this->json([
            'id' => $subTask->getId(),
            'name' => $subTask->getName(),
            'done' => $subTask->isDone(),
        ]);
    }

    // =====================
    // DELETE — Supprimer une sous-tâche
    // =====================
    #[Route('/subtasks/{id}', methods: ['DELETE'])]
    public function deleteSubTask(int $id, EntityManagerInterface $em, PermissionService $permissions): JsonResponse
    {
        if (!$permissions->canDeleteTask()) {
            return $this->json(['error' => 'Accès refusé'], 403);
        }

        $subTask = $em->getRepository(SubTask::class)->find($id);
        if (!$subTask) {
            return $this->json(['error' => 'Sous-tâche non trouvée'], 404);
        }

        $em->remove($subTask);
        $em->flush();

        return $this->json(['message' => 'Sous-tâche supprimée avec succès']);
    }
}
