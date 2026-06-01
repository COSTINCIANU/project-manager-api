<?php

namespace App\Controller;

use App\Entity\Task;
use App\Entity\SubTask;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

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
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
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

        return $this->json($this->taskToArray($task), 201);
    }

    // =====================
    // PUT — Modifier une tâche
    // =====================
    #[Route('/{id}', methods: ['PUT'])]
    public function update(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
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

        return $this->json($this->taskToArray($task));
    }

    // =====================
    // DELETE — Supprimer une tâche
    // =====================
    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id, EntityManagerInterface $em): JsonResponse
    {
        $task = $em->getRepository(Task::class)->find($id);
        if (!$task) {
            return $this->json(['error' => 'Tâche non trouvée'], 404);
        }

        $em->remove($task);
        $em->flush();

        return $this->json(['message' => 'Tâche supprimée avec succès']);
    }

    // =====================
    // POST — Ajouter une sous-tâche
    // =====================
    #[Route('/{id}/subtasks', methods: ['POST'])]
    public function addSubTask(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
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
    public function updateSubTask(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
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
    public function deleteSubTask(int $id, EntityManagerInterface $em): JsonResponse
    {
        $subTask = $em->getRepository(SubTask::class)->find($id);
        if (!$subTask) {
            return $this->json(['error' => 'Sous-tâche non trouvée'], 404);
        }

        $em->remove($subTask);
        $em->flush();

        return $this->json(['message' => 'Sous-tâche supprimée avec succès']);
    }
}
