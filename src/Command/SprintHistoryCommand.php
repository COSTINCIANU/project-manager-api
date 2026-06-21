<?php
// =====================================================
// SprintHistoryCommand.php — Commande cron
// Enregistre chaque jour l'état de tous les sprints actifs
// À exécuter chaque nuit à minuit via cron o2switch
// Commande : php bin/console app:sprint-history
// =====================================================

namespace App\Command;

use App\Entity\Sprint;
use App\Entity\SprintHistory;
use App\Entity\Task;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:sprint-history',
    description: 'Enregistre chaque jour l\'état des sprints pour le Burndown chart',
)]
class SprintHistoryCommand extends Command
{
    public function __construct(private EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $today = new \DateTime('today');
        $output->writeln('📊 Enregistrement de l\'historique des sprints — ' . $today->format('Y-m-d'));

        // On récupère tous les sprints actifs
        $sprints = $this->em->getRepository(Sprint::class)->findBy(['status' => 'actif']);

        if (empty($sprints)) {
            $output->writeln('ℹ️  Aucun sprint actif trouvé.');
            return Command::SUCCESS;
        }

        $nombreEnregistres = 0;

        foreach ($sprints as $sprint) {
            // On vérifie qu'on n'a pas déjà enregistré aujourd'hui pour ce sprint
            $existant = $this->em->getRepository(SprintHistory::class)->findOneBy([
                'sprintId' => $sprint->getId(),
                'date' => $today,
            ]);

            if ($existant) {
                $output->writeln('⏭️  Sprint #' . $sprint->getId() . ' déjà enregistré aujourd\'hui');
                continue;
            }

            // On récupère toutes les tâches du sprint
            $taches = $this->em->getRepository(Task::class)->findBy([
                'sprintId' => $sprint->getId(),
            ]);

            $total = count($taches);
            $terminees = count(array_filter($taches, fn($t) => $t->isDone()));
            $restantes = $total - $terminees;

            // On enregistre l'état du sprint pour aujourd'hui
            $history = new SprintHistory();
            $history->setSprintId($sprint->getId());
            $history->setDate($today);
            $history->setTasksTotal($total);
            $history->setTasksDone($terminees);
            $history->setTasksRemaining($restantes);

            $this->em->persist($history);
            $nombreEnregistres++;

            $output->writeln(
                '✅ Sprint "' . $sprint->getName() . '" — ' .
                $total . ' tâches, ' . $terminees . ' terminées, ' . $restantes . ' restantes'
            );
        }

        $this->em->flush();
        $output->writeln('🎉 ' . $nombreEnregistres . ' sprint(s) enregistré(s) avec succès !');

        return Command::SUCCESS;
    }
}
