<?php
// =====================================================
// ReportController.php — Rapports et statistiques
// Routes disponibles :
//   GET /api/reports/sprint/{id}/burndown          → Burndown chart
//   GET /api/reports/project/{id}/stats            → Stats globales
//   GET /api/reports/project/{id}/velocity         → Vélocité par sprint
//   GET /api/reports/project/{id}/time-spent       → Temps passé par membre
//   GET /api/reports/project/{id}/multi-sprint     → Comparatif multi-sprints
//   GET /api/reports/project/{id}/export-csv       → Export CSV des tâches
// =====================================================

namespace App\Controller;

use App\Entity\Sprint;
use App\Entity\SprintHistory;
use App\Entity\Task;
use App\Entity\Project;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
//use Symfony\Component\HttpFoundation\Request;
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
        $taches          = $em->getRepository(Task::class)->findBy(['sprintId' => $sprintId]);
        $totalTaches     = count($taches);
        $tachesTerminees = count(array_filter($taches, fn($t) => $t->isDone()));

        // Construit les données du graphique
        $donneesReelles = array_map(function($h) {
            return [
                'date'           => $h->getDate()->format('Y-m-d'),
                'tasksRemaining' => $h->getTasksRemaining(),
                'tasksDone'      => $h->getTasksDone(),
                'tasksTotal'     => $h->getTasksTotal(),
            ];
        }, $historique);

        // Calcule la ligne idéale (du total au 0 sur la durée du sprint)
        $ligneIdeale = [];
        if ($sprint->getStartDate() && $sprint->getEndDate()) {
            $debut  = new \DateTime($sprint->getStartDate());
            $fin    = new \DateTime($sprint->getEndDate());
            $duree  = $debut->diff($fin)->days + 1;

            for ($i = 0; $i <= $duree; $i++) {
                $date = clone $debut;
                $date->modify('+' . $i . ' days');
                $ligneIdeale[] = [
                    'date'           => $date->format('Y-m-d'),
                    'tasksRemaining' => round($totalTaches * (1 - $i / $duree)),
                ];
            }
        }

        return $this->json([
            'sprint' => [
                'id'          => $sprint->getId(),
                'name'        => $sprint->getName(),
                'status'      => $sprint->getStatus(),
                'startDate'   => $sprint->getStartDate(),
                'endDate'     => $sprint->getEndDate(),
                'tasksTotal'  => $totalTaches,
                'tasksDone'   => $tachesTerminees,
                'progression' => $totalTaches > 0 ? round(($tachesTerminees / $totalTaches) * 100) : 0,
            ],
            'donneesReelles' => $donneesReelles,
            'ligneIdeale'    => $ligneIdeale,
        ]);
    }

    // =====================
    // GET — Statistiques globales d'un projet
    // =====================
    #[Route('/project/{projectId}/stats', methods: ['GET'])]
    public function projectStats(int $projectId, EntityManagerInterface $em): JsonResponse
    {
        $sprints = $em->getRepository(Sprint::class)->findBy(['projectId' => $projectId]);
        $taches  = $em->getRepository(Task::class)->findBy(['projectId' => $projectId]);

        // Répartition par statut
        $aFaire   = count(array_filter($taches, fn($t) => !$t->isDone() && !$t->isInProgress()));
        $enCours  = count(array_filter($taches, fn($t) => $t->isInProgress() && !$t->isDone()));
        $terminees = count(array_filter($taches, fn($t) => $t->isDone()));

        // Répartition par type de ticket
        $parType = [];
        foreach ($taches as $tache) {
            $type          = $tache->getTicketType() ?? 'task';
            $parType[$type] = ($parType[$type] ?? 0) + 1;
        }

        // Répartition par priorité
        $parPriorite = [];
        foreach ($taches as $tache) {
            $priorite              = $tache->getPriority() ?? 'normale';
            $parPriorite[$priorite] = ($parPriorite[$priorite] ?? 0) + 1;
        }

        // Stats des sprints
        $sprintsData = array_map(function($sprint) use ($em) {
            $tachesSprint = $em->getRepository(Task::class)->findBy(['sprintId' => $sprint->getId()]);
            $total        = count($tachesSprint);
            $done         = count(array_filter($tachesSprint, fn($t) => $t->isDone()));
            return [
                'id'          => $sprint->getId(),
                'name'        => $sprint->getName(),
                'status'      => $sprint->getStatus(),
                'tasksTotal'  => $total,
                'tasksDone'   => $done,
                'progression' => $total > 0 ? round(($done / $total) * 100) : 0,
            ];
        }, $sprints);

        return $this->json([
            'totalTaches' => count($taches),
            'parStatut'   => [
                'aFaire'    => $aFaire,
                'enCours'   => $enCours,
                'terminees' => $terminees,
            ],
            'parType'     => $parType,
            'parPriorite' => $parPriorite,
            'sprints'     => $sprintsData,
        ]);
    }

    // =====================
    // GET — Vélocité de l'équipe par sprint
    // Retourne le nombre de tâches terminées par sprint
    // pour visualiser la capacité de l'équipe dans le temps
    // =====================
    #[Route('/project/{projectId}/velocity', methods: ['GET'])]
    public function velocite(int $projectId, EntityManagerInterface $em): JsonResponse
    {
        // Vérifie que le projet existe
        $projet = $em->getRepository(Project::class)->find($projectId);
        if (!$projet) {
            return $this->json(['erreur' => 'Projet introuvable'], 404);
        }

        // Récupère tous les sprints du projet triés par date de début
        $sprints = $em->getRepository(Sprint::class)->findBy(
            ['projectId' => $projectId],
            ['id' => 'ASC']
        );

        $donneesVelocite = [];

        foreach ($sprints as $sprint) {
            // Tâches assignées à ce sprint
            $tachesSprint    = $em->getRepository(Task::class)->findBy(['sprintId' => $sprint->getId()]);
            $totalTaches     = count($tachesSprint);
            $tachesTerminees = count(array_filter($tachesSprint, fn($t) => $t->isDone()));

            // Répartition par priorité dans ce sprint
            $parPriorite = [];
            foreach ($tachesSprint as $tache) {
                $priorite              = $tache->getPriority() ?? 'normale';
                $parPriorite[$priorite] = ($parPriorite[$priorite] ?? 0) + 1;
            }

            $donneesVelocite[] = [
                'sprintId'         => $sprint->getId(),
                'sprintNom'        => $sprint->getName(),
                'statut'           => $sprint->getStatus(),
                'dateDebut'        => $sprint->getStartDate(),
                'dateFin'          => $sprint->getEndDate(),
                'totalTaches'      => $totalTaches,
                'tachesTerminees'  => $tachesTerminees,
                // Vélocité = nombre de tâches terminées (mesure de capacité de l'équipe)
                'velocite'         => $tachesTerminees,
                // Taux de complétion en pourcentage
                'tauxCompletion'   => $totalTaches > 0
                    ? round(($tachesTerminees / $totalTaches) * 100)
                    : 0,
                'parPriorite'      => $parPriorite,
            ];
        }

        // Calcule la vélocité moyenne sur tous les sprints terminés
        $sprintsTermines  = array_filter($donneesVelocite, fn($s) => $s['statut'] === 'completed');
        $velociteMoyenne  = count($sprintsTermines) > 0
            ? round(array_sum(array_column($sprintsTermines, 'velocite')) / count($sprintsTermines), 1)
            : 0;

        return $this->json([
            'projetId'        => $projectId,
            'projetNom'       => $projet->getName(),
            'sprints'         => $donneesVelocite,
            'velociteMoyenne' => $velociteMoyenne,
            'totalSprints'    => count($sprints),
        ]);
    }

    // =====================
    // GET — Temps passé par tâche et par membre
    // Basé sur le champ estimatedTime des tâches
    // et sur les assignations
    // =====================
    #[Route('/project/{projectId}/time-spent', methods: ['GET'])]
    public function tempsDepense(int $projectId, EntityManagerInterface $em): JsonResponse
    {
        $projet = $em->getRepository(Project::class)->find($projectId);
        if (!$projet) {
            return $this->json(['erreur' => 'Projet introuvable'], 404);
        }

        $taches = $em->getRepository(Task::class)->findBy(['projectId' => $projectId]);

        // Calcule le temps total estimé par membre assigné
        $tempParMembre = [];
        $tachesParMembre = [];

        foreach ($taches as $tache) {
            $assigneA = $tache->getAssignedTo();
            if (!$assigneA) continue;

            $cle = $assigneA;
            if (!isset($tempParMembre[$cle])) {
                $tempParMembre[$cle]    = 0;
                $tachesParMembre[$cle] = ['total' => 0, 'terminees' => 0];
            }

            // Cumule le temps estimé en heures
            $tempParMembre[$cle]              += $tache->getEstimatedTime() ?? 0;
            $tachesParMembre[$cle]['total']   += 1;
            if ($tache->isDone()) {
                $tachesParMembre[$cle]['terminees'] += 1;
            }
        }

        // Formate les données par membre
        $donneesMembres = [];
        foreach ($tempParMembre as $membreId => $heures) {
            $donneesMembres[] = [
                'membreId'        => $membreId,
                'heuresEstimees'  => $heures,
                'totalTaches'     => $tachesParMembre[$membreId]['total'],
                'tachesTerminees' => $tachesParMembre[$membreId]['terminees'],
                'tauxCompletion'  => $tachesParMembre[$membreId]['total'] > 0
                    ? round(($tachesParMembre[$membreId]['terminees'] / $tachesParMembre[$membreId]['total']) * 100)
                    : 0,
            ];
        }

        // Trie par heures estimées décroissantes
        usort($donneesMembres, fn($a, $b) => $b['heuresEstimees'] <=> $a['heuresEstimees']);

        // Calcule aussi le temps par tâche (les plus longues en premier)
        $donneesParTache = array_map(fn(Task $tache) => [
            'tacheId'        => $tache->getId(),
            'tacheNom'       => $tache->getName(),
            'heuresEstimees' => $tache->getEstimatedTime() ?? 0,
            'priorite'       => $tache->getPriority(),
            'statut'         => $tache->isDone() ? 'terminée' : ($tache->isInProgress() ? 'en cours' : 'à faire'),
            'assigneA'       => $tache->getAssignedTo(),
            'sprintId'       => $tache->getSprintId(),
        ], $taches);

        // Trie par heures estimées décroissantes
        usort($donneesParTache, fn($a, $b) => $b['heuresEstimees'] <=> $a['heuresEstimees']);

        // Calcule le total global
        $totalHeures = array_sum(array_column($donneesParTache, 'heuresEstimees'));

        return $this->json([
            'projetId'      => $projectId,
            'projetNom'     => $projet->getName(),
            'totalHeures'   => $totalHeures,
            'parMembre'     => $donneesMembres,
            'parTache'      => $donneesParTache,
        ]);
    }

    // =====================
    // GET — Comparatif multi-sprints
    // Compare les performances entre plusieurs sprints
    // d'un même projet sur un même graphe
    // =====================
    #[Route('/project/{projectId}/multi-sprint', methods: ['GET'])]
    public function multiSprint(int $projectId, EntityManagerInterface $em): JsonResponse
    {
        $projet = $em->getRepository(Project::class)->find($projectId);
        if (!$projet) {
            return $this->json(['erreur' => 'Projet introuvable'], 404);
        }

        $sprints = $em->getRepository(Sprint::class)->findBy(
            ['projectId' => $projectId],
            ['id' => 'ASC']
        );

        $comparatif = [];

        foreach ($sprints as $sprint) {
            $tachesSprint    = $em->getRepository(Task::class)->findBy(['sprintId' => $sprint->getId()]);
            $totalTaches     = count($tachesSprint);
            $tachesTerminees = count(array_filter($tachesSprint, fn($t) => $t->isDone()));
            $tachesEnCours   = count(array_filter($tachesSprint, fn($t) => $t->isInProgress() && !$t->isDone()));
            $tachesAFaire    = count(array_filter($tachesSprint, fn($t) => !$t->isDone() && !$t->isInProgress()));

            // Répartition par type de ticket
            $parType = [];
            foreach ($tachesSprint as $tache) {
                $type          = $tache->getTicketType() ?? 'task';
                $parType[$type] = ($parType[$type] ?? 0) + 1;
            }

            // Temps total estimé pour ce sprint
            $tempsTotal = array_sum(array_map(
                fn($t) => $t->getEstimatedTime() ?? 0,
                $tachesSprint
            ));

            $comparatif[] = [
                'sprintId'        => $sprint->getId(),
                'sprintNom'       => $sprint->getName(),
                'statut'          => $sprint->getStatus(),
                'dateDebut'       => $sprint->getStartDate(),
                'dateFin'         => $sprint->getEndDate(),
                'totalTaches'     => $totalTaches,
                'tachesTerminees' => $tachesTerminees,
                'tachesEnCours'   => $tachesEnCours,
                'tachesAFaire'    => $tachesAFaire,
                'tauxCompletion'  => $totalTaches > 0
                    ? round(($tachesTerminees / $totalTaches) * 100)
                    : 0,
                'parType'         => $parType,
                'tempsEstime'     => $tempsTotal,
            ];
        }

        return $this->json([
            'projetId'   => $projectId,
            'projetNom'  => $projet->getName(),
            'sprints'    => $comparatif,
            'totalSprints' => count($sprints),
        ]);
    }

    // =====================
    // GET — Export CSV des tâches d'un projet
    // Génère un fichier CSV téléchargeable directement
    // =====================
    #[Route('/project/{projectId}/export-csv', methods: ['GET'])]
    public function exportCsv(int $projectId, EntityManagerInterface $em): Response
    {
        $projet = $em->getRepository(Project::class)->find($projectId);
        if (!$projet) {
            return new Response('Projet introuvable', 404);
        }

        $taches = $em->getRepository(Task::class)->findBy(['projectId' => $projectId]);

        // Construit le contenu CSV
        $lignes = [];

        // En-tête du CSV
        $lignes[] = implode(',', [
            'ID',
            'Nom',
            'Priorité',
            'Type',
            'Statut',
            'Assigné à',
            'Sprint',
            'Date échéance',
            'Temps estimé (h)',
        ]);

        // Une ligne par tâche
        foreach ($taches as $tache) {
            // Détermine le statut lisible
            $statut = $tache->isDone()
                ? 'Terminée'
                : ($tache->isInProgress() ? 'En cours' : 'À faire');

            $lignes[] = implode(',', [
                $tache->getId(),
                // Entoure de guillemets pour gérer les virgules dans le nom
                '"' . str_replace('"', '""', $tache->getName()) . '"',
                $tache->getPriority() ?? 'normale',
                $tache->getTicketType() ?? 'task',
                $statut,
                $tache->getAssignedTo() ?? '',
                $tache->getSprintId() ?? '',
                $tache->getDueDate() ?? '',
                $tache->getEstimatedTime() ?? 0,
            ]);
        }

        $contenuCsv = implode("\n", $lignes);

        // Retourne le fichier CSV avec les bons headers HTTP
        return new Response($contenuCsv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="rapport_projet_' . $projectId . '_' . date('Y-m-d') . '.csv"',
        ]);
    }
}
