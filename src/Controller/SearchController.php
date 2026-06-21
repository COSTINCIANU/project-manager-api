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
    // =====================
    // GET — Recherche globale
    // Paramètres URL :
    // ?q=mot          — terme de recherche
    // ?type=bug       — filtre par type de ticket
    // ?priority=haute — filtre par priorité
    // ?status=done    — filtre par statut (todo, in_progress, done)
    // =====================
    #[Route('', methods: ['GET'])]
    public function search(Request $request, EntityManagerInterface $em): JsonResponse
    {
        // Récupère les paramètres de recherche
        $terme = $request->query->get('q', '');
        $type = $request->query->get('type', '');
        $priorite = $request->query->get('priority', '');
        $statut = $request->query->get('status', '');

        // Si aucun terme et aucun filtre — retourne vide
        if (empty($terme) && empty($type) && empty($priorite) && empty($statut)) {
            return $this->json(['projects' => [], 'tasks' => [], 'total' => 0]);
        }

        // =====================
        // Recherche dans les PROJETS
        // =====================
        $qbProjects = $em->createQueryBuilder()
            ->select('p')
            ->from(Project::class, 'p');

        if (!empty($terme)) {
            $qbProjects->andWhere(
                $qbProjects->expr()->orX(
                    $qbProjects->expr()->like('p.name', ':terme'),
                    $qbProjects->expr()->like('p.description', ':terme')
                )
            )->setParameter('terme', '%' . $terme . '%');
        }

        $projets = $qbProjects->getQuery()->getResult();

        $projetsData = array_map(function($project) {
            return [
                'id' => $project->getId(),
                'name' => $project->getName(),
                'description' => $project->getDescription(),
                'status' => $project->getStatus(),
                'color' => $project->getColor(),
                'progress' => $project->getProgress(),
                'type' => 'project', // Pour distinguer dans les résultats
            ];
        }, $projets);

        // =====================
        // Recherche dans les TÂCHES
        // =====================
        $qbTasks = $em->createQueryBuilder()
            ->select('t')
            ->from(Task::class, 't');

        // Filtre par terme de recherche
        if (!empty($terme)) {
            $qbTasks->andWhere(
                $qbTasks->expr()->orX(
                    $qbTasks->expr()->like('t.name', ':terme'),
                    $qbTasks->expr()->like('t.description', ':terme')
                )
            )->setParameter('terme', '%' . $terme . '%');
        }

        // Filtre par type de ticket
        if (!empty($type)) {
            $qbTasks->andWhere('t.ticketType = :type')
                ->setParameter('type', $type);
        }

        // Filtre par priorité
        if (!empty($priorite)) {
            $qbTasks->andWhere('t.priority = :priorite')
                ->setParameter('priorite', $priorite);
        }

        // Filtre par statut
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
                'type' => 'task', // Pour distinguer dans les résultats
            ];
        }, $taches);

        return $this->json([
            'projects' => $projetsData,
            'tasks' => $tachesData,
            'total' => count($projetsData) + count($tachesData),
        ]);
    }
}
