<?php

// =====================================================
// SearchController.php — Recherche avancée
// Cherche dans les projets et les tâches simultanément
// Filtres disponibles :
//   q          → terme de recherche (nom, description)
//   type       → type de ticket (task, bug, story, epic)
//   priority   → priorité (basse, normale, haute, critique)
//   status     → statut (todo, in_progress, done)
//   project_id → filtrer par projet
//   assigned_to → filtrer par utilisateur assigné
//   sprint_id  → filtrer par sprint
//   date_from  → date d'échéance à partir de (YYYY-MM-DD)
//   date_to    → date d'échéance jusqu'à (YYYY-MM-DD)
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
    public function rechercher(Request $request, EntityManagerInterface $em): JsonResponse
    {
        // =====================
        // RÉCUPÉRATION DES FILTRES
        // =====================

        // Terme de recherche libre
        $terme = trim($request->query->get('q', ''));

        // Filtre par type de ticket
        $type = $request->query->get('type', '');

        // Filtre par priorité
        $priorite = $request->query->get('priority', '');

        // Filtre par statut
        $statut = $request->query->get('status', '');

        // Filtre par projet
        $projetId = $request->query->get('project_id', '');

        // Filtre par utilisateur assigné
        $assigneA = $request->query->get('assigned_to', '');

        // Filtre par sprint
        $sprintId = $request->query->get('sprint_id', '');

        // Filtre par date d'échéance — début de plage
        $dateDebut = $request->query->get('date_from', '');

        // Filtre par date d'échéance — fin de plage
        $dateFin = $request->query->get('date_to', '');

        // Si aucun filtre fourni — retourne vide
        $aucunFiltre = empty($terme) && empty($type) && empty($priorite)
            && empty($statut) && empty($projetId) && empty($assigneA)
            && empty($sprintId) && empty($dateDebut) && empty($dateFin);

        if ($aucunFiltre) {
            return $this->json(['projects' => [], 'tasks' => [], 'total' => 0]);
        }

        // =====================
        // RECHERCHE DANS LES PROJETS
        // Filtrés uniquement par terme de recherche
        // =====================
        $qbProjets = $em->createQueryBuilder()
            ->select('p')
            ->from(Project::class, 'p');

        if (!empty($terme)) {
            $qbProjets->andWhere(
                $qbProjets->expr()->orX(
                    $qbProjets->expr()->like('p.name', ':terme'),
                    // $qbProjets->expr()->like('p.description', ':terme')
                )
            )->setParameter('terme', '%'.$terme.'%');
        }

        // Filtre par projet si fourni
        if (!empty($projetId)) {
            $qbProjets->andWhere('p.id = :projetId')
                ->setParameter('projetId', (int) $projetId);
        }

        $projets = $qbProjets->getQuery()->getResult();
        $projetsData = array_map(fn (Project $projet) => [
            'id' => $projet->getId(),
            'name' => $projet->getName(),
            'description' => null,
            'status' => $projet->getStatus(),
            'color' => $projet->getColor(),
            'progress' => $projet->getProgress(),
            'type' => 'project',
        ], $projets);

        // =====================
        // RECHERCHE DANS LES TÂCHES
        // Tous les filtres combinés
        // =====================
        $qbTaches = $em->createQueryBuilder()
            ->select('t')
            ->from(Task::class, 't');

        // Filtre par terme — cherche dans nom et description
        if (!empty($terme)) {
            $qbTaches->andWhere(
                $qbTaches->expr()->orX(
                    $qbTaches->expr()->like('t.name', ':terme'),
                    $qbTaches->expr()->like('t.description', ':terme')
                )
            )->setParameter('terme', '%'.$terme.'%');
        }

        // Filtre par type de ticket
        if (!empty($type)) {
            $qbTaches->andWhere('t.ticketType = :type')
                ->setParameter('type', $type);
        }

        // Filtre par priorité
        if (!empty($priorite)) {
            $qbTaches->andWhere('t.priority = :priorite')
                ->setParameter('priorite', $priorite);
        }

        // Filtre par statut — 3 états possibles
        if (!empty($statut)) {
            match ($statut) {
                'done' => $qbTaches->andWhere('t.done = true'),
                'in_progress' => $qbTaches->andWhere('t.inProgress = true AND t.done = false'),
                'todo' => $qbTaches->andWhere('t.inProgress = false AND t.done = false'),
                default => null,
            };
        }

        // Filtre par projet
        if (!empty($projetId)) {
            $qbTaches->andWhere('t.projectId = :projetId')
                ->setParameter('projetId', (int) $projetId);
        }

        // Filtre par utilisateur assigné
        if (!empty($assigneA)) {
            $qbTaches->andWhere('t.assignedTo = :assigneA')
                ->setParameter('assigneA', (int) $assigneA);
        }

        // Filtre par sprint
        if (!empty($sprintId)) {
            $qbTaches->andWhere('t.sprintId = :sprintId')
                ->setParameter('sprintId', (int) $sprintId);
        }

        // Filtre par date d'échéance — début de plage
        if (!empty($dateDebut)) {
            $qbTaches->andWhere('t.dueDate >= :dateDebut')
                ->setParameter('dateDebut', $dateDebut);
        }

        // Filtre par date d'échéance — fin de plage
        if (!empty($dateFin)) {
            $qbTaches->andWhere('t.dueDate <= :dateFin')
                ->setParameter('dateFin', $dateFin);
        }

        // Tri par date de création décroissante
        $qbTaches->orderBy('t.id', 'DESC');

        $taches = $qbTaches->getQuery()->getResult();
        $tachesData = array_map(fn (Task $tache) => [
            'id' => $tache->getId(),
            'name' => $tache->getName(),
            'description' => $tache->getDescription(),
            'priority' => $tache->getPriority(),
            'ticketType' => $tache->getTicketType() ?? 'task',
            'done' => $tache->isDone(),
            'inProgress' => $tache->isInProgress(),
            'projectId' => $tache->getProjectId(),
            'assignedTo' => $tache->getAssignedTo(),
            'sprintId' => $tache->getSprintId(),
            'dueDate' => $tache->getDueDate(),
            'type' => 'task',
        ], $taches);

        return $this->json([
            'projects' => $projetsData,
            'tasks' => $tachesData,
            'total' => count($projetsData) + count($tachesData),
        ]);
    }

    // =====================
    // ROUTE SÉPARÉE — Recherche avancée tâches uniquement
    // Utilisée par le panneau de filtres avancés
    // GET /api/search/tasks?priority=haute&status=todo&project_id=1
    // =====================
    #[Route('/tasks', methods: ['GET'])]
    public function rechercherTaches(Request $request, EntityManagerInterface $em): JsonResponse
    {
        // Récupère tous les filtres
        $terme = trim($request->query->get('q', ''));
        $type = $request->query->get('type', '');
        $priorite = $request->query->get('priority', '');
        $statut = $request->query->get('status', '');
        $projetId = $request->query->get('project_id', '');
        $assigneA = $request->query->get('assigned_to', '');
        $sprintId = $request->query->get('sprint_id', '');
        $dateDebut = $request->query->get('date_from', '');
        $dateFin = $request->query->get('date_to', '');

        $qb = $em->createQueryBuilder()
            ->select('t')
            ->from(Task::class, 't');

        // Filtre par terme
        if (!empty($terme)) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('t.name', ':terme'),
                    $qb->expr()->like('t.description', ':terme')
                )
            )->setParameter('terme', '%'.$terme.'%');
        }

        // Filtre par type de ticket
        if (!empty($type)) {
            $qb->andWhere('t.ticketType = :type')
                ->setParameter('type', $type);
        }

        // Filtre par priorité
        if (!empty($priorite)) {
            $qb->andWhere('t.priority = :priorite')
                ->setParameter('priorite', $priorite);
        }

        // Filtre par statut
        if (!empty($statut)) {
            match ($statut) {
                'done' => $qb->andWhere('t.done = true'),
                'in_progress' => $qb->andWhere('t.inProgress = true AND t.done = false'),
                'todo' => $qb->andWhere('t.inProgress = false AND t.done = false'),
                default => null,
            };
        }

        // Filtre par projet
        if (!empty($projetId)) {
            $qb->andWhere('t.projectId = :projetId')
                ->setParameter('projetId', (int) $projetId);
        }

        // Filtre par utilisateur assigné
        if (!empty($assigneA)) {
            $qb->andWhere('t.assignedTo = :assigneA')
                ->setParameter('assigneA', (int) $assigneA);
        }

        // Filtre par sprint
        if (!empty($sprintId)) {
            $qb->andWhere('t.sprintId = :sprintId')
                ->setParameter('sprintId', (int) $sprintId);
        }

        // Filtre par date début
        if (!empty($dateDebut)) {
            $qb->andWhere('t.dueDate >= :dateDebut')
                ->setParameter('dateDebut', $dateDebut);
        }

        // Filtre par date fin
        if (!empty($dateFin)) {
            $qb->andWhere('t.dueDate <= :dateFin')
                ->setParameter('dateFin', $dateFin);
        }

        $qb->orderBy('t.id', 'DESC');

        $taches = $qb->getQuery()->getResult();

        $tachesData = array_map(fn (Task $tache) => [
            'id' => $tache->getId(),
            'name' => $tache->getName(),
            'description' => $tache->getDescription(),
            'priority' => $tache->getPriority(),
            'ticketType' => $tache->getTicketType() ?? 'task',
            'done' => $tache->isDone(),
            'inProgress' => $tache->isInProgress(),
            'projectId' => $tache->getProjectId(),
            'assignedTo' => $tache->getAssignedTo(),
            'sprintId' => $tache->getSprintId(),
            'dueDate' => $tache->getDueDate(),
            'type' => 'task',
        ], $taches);

        return $this->json([
            'tasks' => $tachesData,
            'total' => count($tachesData),
        ]);
    }
}
