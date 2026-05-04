<?php

namespace App\Controller;

use App\Entity\Task;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/tasks')]
class TaskController extends AbstractController
{
    // =====================
    // GET — Récupérer toutes les tâches
    // =====================
    #[Route('', methods: ['GET'])]
    public function index(EntityManagerInterface $em): JsonResponse
    {
        // On récupère toutes les tâches depuis la base de données
        $tasks = $em->getRepository(Task::class)->findAll();

        // On convertit les tâches en tableau pour les envoyer en JSON
        $data = array_map(function($task) {
            return [
                'id' => $task->getId(),
                'name' => $task->getName(),
                'priority' => $task->getPriority(),
                'done' => $task->isDone(),
                'inProgress' => $task->isInProgress(),
                'dueDate' => $task->getDueDate(),
                'projectId' => $task->getProjectId(),
            ];
        }, $tasks);

        return $this->json($data);
    }

    // =====================
    // POST — Créer une nouvelle tâche
    // =====================
    #[Route('', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        // On récupère les données envoyées par React
        $data = json_decode($request->getContent(), true);

        // On crée une nouvelle tâche
        $task = new Task();
        $task->setName($data['name']);
        $task->setPriority($data['priority']);
        $task->setDone($data['done'] ?? false);
        $task->setInProgress($data['inProgress'] ?? false);
        $task->setDueDate($data['dueDate'] ?? null);
        $task->setProjectId($data['projectId']);

        // On sauvegarde dans la base de données
        $em->persist($task);
        $em->flush();

        return $this->json([
            'id' => $task->getId(),
            'name' => $task->getName(),
            'priority' => $task->getPriority(),
            'done' => $task->isDone(),
            'inProgress' => $task->isInProgress(),
            'dueDate' => $task->getDueDate(),
            'projectId' => $task->getProjectId(),
        ], 201);
    }

    // =====================
    // PUT — Modifier une tâche
    // =====================
    #[Route('/{id}', methods: ['PUT'])]
    public function update(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        // On cherche la tâche par son id
        $task = $em->getRepository(Task::class)->find($id);

        // Si la tâche n'existe pas on retourne une erreur
        if (!$task) {
            return $this->json(['error' => 'Tâche non trouvée'], 404);
        }

        // On met à jour les données
        $data = json_decode($request->getContent(), true);
        $task->setName($data['name'] ?? $task->getName());
        $task->setPriority($data['priority'] ?? $task->getPriority());
        $task->setDone($data['done'] ?? $task->isDone());
        $task->setInProgress($data['inProgress'] ?? $task->isInProgress());
        $task->setDueDate($data['dueDate'] ?? $task->getDueDate());
        $task->setProjectId($data['projectId'] ?? $task->getProjectId());

        // On sauvegarde les modifications
        $em->flush();

        return $this->json([
            'id' => $task->getId(),
            'name' => $task->getName(),
            'priority' => $task->getPriority(),
            'done' => $task->isDone(),
            'inProgress' => $task->isInProgress(),
            'dueDate' => $task->getDueDate(),
            'projectId' => $task->getProjectId(),
        ]);
    }

    // =====================
    // DELETE — Supprimer une tâche
    // =====================
    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id, EntityManagerInterface $em): JsonResponse
    {
        // On cherche la tâche par son id
        $task = $em->getRepository(Task::class)->find($id);

        // Si la tâche n'existe pas on retourne une erreur
        if (!$task) {
            return $this->json(['error' => 'Tâche non trouvée'], 404);
        }

        // On supprime la tâche
        $em->remove($task);
        $em->flush();

        return $this->json(['message' => 'Tâche supprimée avec succès']);
    }
}
