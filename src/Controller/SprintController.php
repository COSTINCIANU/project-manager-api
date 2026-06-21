<?php
// =====================================================
// SprintController.php — Gestion des sprints
// Un sprint est une période de travail fixe
// Permissions :
// - GET : tous les rôles connectés
// - POST : manager et admin
// - PUT : manager et admin
// - DELETE : manager et admin
// =====================================================

namespace App\Controller;

use App\Entity\Sprint;
use App\Entity\Task;
use App\Service\PermissionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/sprints')]
class SprintController extends AbstractController
{
    // =====================
    // GET — Récupérer tous les sprints d'un projet
    // =====================
    #[Route('/project/{projectId}', methods: ['GET'])]
    public function getByProject(int $projectId, EntityManagerInterface $em): JsonResponse
    {
        $sprints = $em->getRepository(Sprint::class)->findBy(
            ['projectId' => $projectId],
            ['id' => 'DESC']
        );

        return $this->json(array_map(fn($s) => $this->sprintVersTableau($s, $em), $sprints));
    }

    // =====================
    // GET — Récupérer le backlog d'un projet
    // Toutes les tâches sans sprint assigné
    // =====================
    #[Route('/project/{projectId}/backlog', methods: ['GET'])]
    public function getBacklog(int $projectId, EntityManagerInterface $em): JsonResponse
    {
        // Les tâches du backlog sont celles sans sprintId
        $taches = $em->getRepository(Task::class)->findBy([
            'projectId' => $projectId,
            'sprintId' => null,
        ]);

        $data = array_map(function($tache) {
            return [
                'id' => $tache->getId(),
                'name' => $tache->getName(),
                'description' => $tache->getDescription(),
                'priority' => $tache->getPriority(),
                'ticketType' => $tache->getTicketType() ?? 'task',
                'done' => $tache->isDone(),
                'inProgress' => $tache->isInProgress(),
                'dueDate' => $tache->getDueDate(),
                'sprintId' => $tache->getSprintId(),
            ];
        }, $taches);

        return $this->json($data);
    }

    // =====================
    // POST — Créer un sprint
    // =====================
    #[Route('', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em, PermissionService $permissions): JsonResponse
    {
        if (!$permissions->isManagerOrAbove()) {
            return $this->json(['error' => 'Accès refusé — rôle manager ou admin requis'], 403);
        }

        $data = json_decode($request->getContent(), true);

        if (empty($data['name']) || empty($data['projectId'])) {
            return $this->json(['error' => 'name et projectId sont requis'], 400);
        }

        $sprint = new Sprint();
        $sprint->setName($data['name']);
        $sprint->setGoal($data['goal'] ?? null);
        $sprint->setStatus($data['status'] ?? 'planifie');
        $sprint->setStartDate($data['startDate'] ?? null);
        $sprint->setEndDate($data['endDate'] ?? null);
        $sprint->setProjectId($data['projectId']);

        $em->persist($sprint);
        $em->flush();

        return $this->json($this->sprintVersTableau($sprint, $em), 201);
    }

    // =====================
    // PUT — Modifier un sprint
    // =====================
    #[Route('/{id}', methods: ['PUT'])]
    public function update(int $id, Request $request, EntityManagerInterface $em, PermissionService $permissions): JsonResponse
    {
        if (!$permissions->isManagerOrAbove()) {
            return $this->json(['error' => 'Accès refusé — rôle manager ou admin requis'], 403);
        }

        $sprint = $em->getRepository(Sprint::class)->find($id);

        if (!$sprint) {
            return $this->json(['error' => 'Sprint non trouvé'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['name'])) $sprint->setName($data['name']);
        if (isset($data['goal'])) $sprint->setGoal($data['goal']);
        if (isset($data['status'])) $sprint->setStatus($data['status']);
        if (isset($data['startDate'])) $sprint->setStartDate($data['startDate']);
        if (isset($data['endDate'])) $sprint->setEndDate($data['endDate']);

        $em->flush();

        return $this->json($this->sprintVersTableau($sprint, $em));
    }

    // =====================
    // POST — Assigner une tâche à un sprint
    // =====================
    #[Route('/{id}/assign-task', methods: ['POST'])]
    public function assignTask(int $id, Request $request, EntityManagerInterface $em, PermissionService $permissions): JsonResponse
    {
        if (!$permissions->isManagerOrAbove()) {
            return $this->json(['error' => 'Accès refusé — rôle manager ou admin requis'], 403);
        }

        $sprint = $em->getRepository(Sprint::class)->find($id);
        if (!$sprint) {
            return $this->json(['error' => 'Sprint non trouvé'], 404);
        }

        $data = json_decode($request->getContent(), true);
        if (empty($data['taskId'])) {
            return $this->json(['error' => 'taskId est requis'], 400);
        }

        $tache = $em->getRepository(Task::class)->find($data['taskId']);
        if (!$tache) {
            return $this->json(['error' => 'Tâche non trouvée'], 404);
        }

        // On assigne la tâche au sprint
        $tache->setSprintId($id);
        $em->flush();

        return $this->json(['message' => 'Tâche assignée au sprint avec succès']);
    }

    // =====================
    // POST — Retirer une tâche du sprint (retour au backlog)
    // =====================
    #[Route('/{id}/remove-task', methods: ['POST'])]
    public function removeTask(int $id, Request $request, EntityManagerInterface $em, PermissionService $permissions): JsonResponse
    {
        if (!$permissions->isManagerOrAbove()) {
            return $this->json(['error' => 'Accès refusé — rôle manager ou admin requis'], 403);
        }

        $data = json_decode($request->getContent(), true);
        if (empty($data['taskId'])) {
            return $this->json(['error' => 'taskId est requis'], 400);
        }

        $tache = $em->getRepository(Task::class)->find($data['taskId']);
        if (!$tache) {
            return $this->json(['error' => 'Tâche non trouvée'], 404);
        }

        // On remet la tâche dans le backlog
        $tache->setSprintId(null);
        $em->flush();

        return $this->json(['message' => 'Tâche retirée du sprint — retour au backlog']);
    }

    // =====================
    // DELETE — Supprimer un sprint
    // =====================
    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id, EntityManagerInterface $em, PermissionService $permissions): JsonResponse
    {
        if (!$permissions->isManagerOrAbove()) {
            return $this->json(['error' => 'Accès refusé — rôle manager ou admin requis'], 403);
        }

        $sprint = $em->getRepository(Sprint::class)->find($id);

        if (!$sprint) {
            return $this->json(['error' => 'Sprint non trouvé'], 404);
        }

        // On remet toutes les tâches du sprint dans le backlog
        $taches = $em->getRepository(Task::class)->findBy(['sprintId' => $id]);
        foreach ($taches as $tache) {
            $tache->setSprintId(null);
        }

        $em->remove($sprint);
        $em->flush();

        return $this->json(['message' => 'Sprint supprimé — tâches remises dans le backlog']);
    }

    // =====================
    // Convertit un sprint en tableau pour la réponse JSON
    // Inclut les tâches assignées au sprint
    // =====================
    private function sprintVersTableau(Sprint $sprint, EntityManagerInterface $em): array
    {
        // Récupère les tâches assignées à ce sprint
        $taches = $em->getRepository(Task::class)->findBy(['sprintId' => $sprint->getId()]);

        $tachesData = array_map(function($tache) {
            return [
                'id' => $tache->getId(),
                'name' => $tache->getName(),
                'priority' => $tache->getPriority(),
                'ticketType' => $tache->getTicketType() ?? 'task',
                'done' => $tache->isDone(),
                'inProgress' => $tache->isInProgress(),
                'dueDate' => $tache->getDueDate(),
            ];
        }, $taches);

        // Calcule la progression du sprint
        $total = count($tachesData);
        $terminees = count(array_filter($tachesData, fn($t) => $t['done']));
        $progression = $total > 0 ? round(($terminees / $total) * 100) : 0;

        return [
            'id' => $sprint->getId(),
            'name' => $sprint->getName(),
            'goal' => $sprint->getGoal(),
            'status' => $sprint->getStatus(),
            'startDate' => $sprint->getStartDate(),
            'endDate' => $sprint->getEndDate(),
            'projectId' => $sprint->getProjectId(),
            'tasksCount' => $total,
            'tasksDone' => $terminees,
            'progression' => $progression,
            'tasks' => $tachesData,
        ];
    }
}
