<?php
// =====================================================
// ReportController.php — Rapports et statistiques
// Fournit les données pour le Burndown chart
// et d'autres rapports de sprint
// =====================================================

namespace App\Controller;

use App\Entity\Sprint;
use App\Entity\SprintHistory;
use App\Entity\Task;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/reports')]
class ReportController extends AbstractController
{
    // =====================
    // GET — Burndown chart d'un sprint
    // Retourne l'historique journalier + la ligne idéale
    // =====================
    #[Route('/sprint/{sprintId}/burndown', methods: ['GET'])]
    public function burndown(int $sprintId, EntityManagerInterface $em): JsonResponse
    {
        $sprint = $em->getRepository(Sprint::class)->find($sprintId);

        if (!$sprint) {
            return $this->json(['error' => 'Sprint non trouvé'], 404);
        }

        // Récupère l'historique trié par date
        $historique = $em->getRepository(SprintHistory::class)->findBy(
            ['sprintId' => $sprintId],
            ['date' => 'ASC']
        );

        // Nombre total de tâches du sprint
        $taches = $em->getRepository(Task::class)->findBy(['sprintId' => $sprintId]);
        $totalTaches = count($taches);
        $tachesTerminees = count(array_filter($taches, fn($t) => $t->isDone()));

        // Construit les données du graphique
        $donneesReelles = array_map(function($h) {
            return [
                'date' => $h->getDate()->format('Y-m-d'),
                'tasksRemaining' => $h->getTasksRemaining(),
                'tasksDone' => $h->getTasksDone(),
                'tasksTotal' => $h->getTasksTotal(),
            ];
        }, $historique);

        // Calcule la ligne idéale (du total au 0 sur la durée du sprint)
        $ligneIdeale = [];
        if ($sprint->getStartDate() && $sprint->getEndDate()) {
            $debut = new \DateTime($sprint->getStartDate());
            $fin = new \DateTime($sprint->getEndDate());
            $duree = $debut->diff($fin)->days + 1;

            for ($i = 0; $i <= $duree; $i++) {
                $date = clone $debut;
                $date->modify('+' . $i . ' days');
                $ligneIdeale[] = [
                    'date' => $date->format('Y-m-d'),
                    // Décroissance linéaire du total vers 0
                    'tasksRemaining' => round($totalTaches * (1 - $i / $duree)),
                ];
            }
        }

        return $this->json([
            'sprint' => [
                'id' => $sprint->getId(),
                'name' => $sprint->getName(),
                'status' => $sprint->getStatus(),
                'startDate' => $sprint->getStartDate(),
                'endDate' => $sprint->getEndDate(),
                'tasksTotal' => $totalTaches,
                'tasksDone' => $tachesTerminees,
                'progression' => $totalTaches > 0 ? round(($tachesTerminees / $totalTaches) * 100) : 0,
            ],
            'donneesReelles' => $donneesReelles,
            'ligneIdeale' => $ligneIdeale,
        ]);
    }

    // =====================
    // GET — Statistiques globales d'un projet
    // =====================
    #[Route('/project/{projectId}/stats', methods: ['GET'])]
    public function projectStats(int $projectId, EntityManagerInterface $em): JsonResponse
    {
        // Récupère tous les sprints du projet
        $sprints = $em->getRepository(Sprint::class)->findBy(['projectId' => $projectId]);

        // Récupère toutes les tâches du projet
        $taches = $em->getRepository(Task::class)->findBy(['projectId' => $projectId]);

        // Répartition par statut
        $aFaire = count(array_filter($taches, fn($t) => !$t->isDone() && !$t->isInProgress()));
        $enCours = count(array_filter($taches, fn($t) => $t->isInProgress() && !$t->isDone()));
        $terminees = count(array_filter($taches, fn($t) => $t->isDone()));

        // Répartition par type de ticket
        $parType = [];
        foreach ($taches as $tache) {
            $type = $tache->getTicketType() ?? 'task';
            $parType[$type] = ($parType[$type] ?? 0) + 1;
        }

        // Répartition par priorité
        $parPriorite = [];
        foreach ($taches as $tache) {
            $priorite = $tache->getPriority() ?? 'normale';
            $parPriorite[$priorite] = ($parPriorite[$priorite] ?? 0) + 1;
        }

        // Stats des sprints
        $sprintsData = array_map(function($sprint) use ($em) {
            $tachesSprint = $em->getRepository(Task::class)->findBy(['sprintId' => $sprint->getId()]);
            $total = count($tachesSprint);
            $done = count(array_filter($tachesSprint, fn($t) => $t->isDone()));
            return [
                'id' => $sprint->getId(),
                'name' => $sprint->getName(),
                'status' => $sprint->getStatus(),
                'tasksTotal' => $total,
                'tasksDone' => $done,
                'progression' => $total > 0 ? round(($done / $total) * 100) : 0,
            ];
        }, $sprints);

        return $this->json([
            'totalTaches' => count($taches),
            'parStatut' => [
                'aFaire' => $aFaire,
                'enCours' => $enCours,
                'terminees' => $terminees,
            ],
            'parType' => $parType,
            'parPriorite' => $parPriorite,
            'sprints' => $sprintsData,
        ]);
    }
}
