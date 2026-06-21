<?php
// =====================================================
// SearchController.php — Recherche globale
// Cherche dans les projets et les tâches simultanément
// Filtres disponibles : type, priorité, statut
// =====================================================

namespace App\Controller;

use App\Entity\Project;
use App\Entity\Task;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/search')]
class SearchController extends AbstractController
{
    #[Route('', methods: ['GET'])]
    public function search(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $terme = $request->query->get('q', '');
        $type = $request->query->get('type', '');
        $priorite = $request->query->get('priority', '');
        $statut = $request->query->get('status', '');

        if (empty($terme) && empty($type) && empty($priorite) && empty($statut)) {
            return $this->json(['projects' => [], 'tasks' => [], 'total' => 0]);
        }

        // Recherche dans les PROJETS — uniquement par nom
        $qbProjects = $em->createQueryBuilder()
            ->select('p')
            ->from(Project::class, 'p');

        if (!empty($terme)) {
            $qbProjects->andWhere(
                $qbProjects->expr()->like('p.name', ':terme')
            )->setParameter('terme', '%' . $terme . '%');
        }

        $projets = $qbProjects->getQuery()->getResult();

        $projetsData = array_map(function($project) {
            return [
                'id' => $project->getId(),
                'name' => $project->getName(),
                'description' => null,
                'status' => $project->getStatus(),
                'color' => $project->getColor(),
                'progress' => $project->getProgress(),
                'type' => 'project',
            ];
        }, $projets);

        // Recherche dans les TÂCHES
        $qbTasks = $em->createQueryBuilder()
            ->select('t')
            ->from(Task::class, 't');

        if (!empty($terme)) {
            $qbTasks->andWhere(
                $qbTasks->expr()->orX(
                    $qbTasks->expr()->like('t.name', ':terme'),
                    $qbTasks->expr()->like('t.description', ':terme')
                )
            )->setParameter('terme', '%' . $terme . '%');
        }

        if (!empty($type)) {
            $qbTasks->andWhere('t.ticketType = :type')
                ->setParameter('type', $type);
        }

        if (!empty($priorite)) {
            $qbTasks->andWhere('t.priority = :priorite')
                ->setParameter('priorite', $priorite);
        }

        if (!empty($statut)) {
            if ($statut === 'done') {
                $qbTasks->andWhere('t.done = :done')->setParameter('done', true);
            } elseif ($statut === 'in_progress') {
                $qbTasks->andWhere('t.inProgress = :inProgress AND t.done = :done')
                    ->setParameter('inProgress', true)
                    ->setParameter('done', false);
            } elseif ($statut === 'todo') {
                $qbTasks->andWhere('t.inProgress = :inProgress AND t.done = :done')
                    ->setParameter('inProgress', false)
                    ->setParameter('done', false);
            }
        }

        $taches = $qbTasks->getQuery()->getResult();

        $tachesData = array_map(function($task) {
            return [
                'id' => $task->getId(),
                'name' => $task->getName(),
                'description' => $task->getDescription(),
                'priority' => $task->getPriority(),
                'ticketType' => $task->getTicketType() ?? 'task',
                'done' => $task->isDone(),
                'inProgress' => $task->isInProgress(),
                'projectId' => $task->getProjectId(),
                'dueDate' => $task->getDueDate(),
                'type' => 'task',
            ];
        }, $taches);

        return $this->json([
            'projects' => $projetsData,
            'tasks' => $tachesData,
            'total' => count($projetsData) + count($tachesData),
        ]);
    }
}
