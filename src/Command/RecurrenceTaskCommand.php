<?php
// =====================================================
// RecurrenceTaskCommand.php — Cron de récurrence
// Duplique les tâches récurrentes selon leur fréquence
// Commande : php bin/console app:recurrence-task
// À planifier : cron quotidien à minuit
// =====================================================

namespace App\Command;

use App\Entity\Task;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:recurrence-task',
    description: 'Duplique les tâches récurrentes selon leur fréquence (daily/weekly/monthly)',
)]
class RecurrenceTaskCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $today = new \DateTime();
        $todayStr = $today->format('Y-m-d');

        $output->writeln("=== Traitement des tâches récurrentes — {$todayStr} ===");

        // Récupère toutes les tâches avec une récurrence définie
        $taches = $this->em->getRepository(Task::class)->createQueryBuilder('t')
            ->where('t.recurrence IS NOT NULL')
            ->getQuery()
            ->getResult();

        $compteur = 0;

        foreach ($taches as $tache) {
            // Vérifie si la date de fin de récurrence est dépassée
            if ($tache->getRecurrenceEndDate()) {
                $dateFin = new \DateTime($tache->getRecurrenceEndDate());
                if ($today > $dateFin) {
                    $output->writeln("  ⏭️  Récurrence terminée : {$tache->getName()}");
                    continue;
                }
            }

            // Vérifie si la tâche doit être dupliquée aujourd'hui
            $dueDate = $tache->getDueDate();
            if (!$dueDate) {
                continue;
            }

            $dateTache = new \DateTime($dueDate);
            $doitDupliquer = false;

            switch ($tache->getRecurrence()) {
                case 'daily':
                    // Duplique chaque jour
                    $doitDupliquer = true;
                    break;

                case 'weekly':
                    // Duplique si même jour de la semaine
                    $doitDupliquer = $dateTache->format('N') === $today->format('N');
                    break;

                case 'monthly':
                    // Duplique si même jour du mois
                    $doitDupliquer = $dateTache->format('j') === $today->format('j');
                    break;
            }

            if (!$doitDupliquer) {
                continue;
            }

            // Vérifie qu'une tâche identique n'existe pas déjà pour aujourd'hui
            $tacheExistante = $this->em->getRepository(Task::class)->findOneBy([
                'name' => $tache->getName() . ' (récurrence)',
                'projectId' => $tache->getProjectId(),
                'dueDate' => $todayStr,
            ]);

            if ($tacheExistante) {
                $output->writeln("  ⚠️  Déjà dupliquée : {$tache->getName()}");
                continue;
            }

            // Calcule la nouvelle date d'échéance selon la récurrence
            $nouvelleDateEcheance = clone $today;
            switch ($tache->getRecurrence()) {
                case 'daily':
                    $nouvelleDateEcheance->modify('+1 day');
                    break;
                case 'weekly':
                    $nouvelleDateEcheance->modify('+1 week');
                    break;
                case 'monthly':
                    $nouvelleDateEcheance->modify('+1 month');
                    break;
            }

            // Crée la nouvelle tâche dupliquée
            $nouvelleTache = new Task();
            $nouvelleTache->setName($tache->getName() . ' (récurrence)');
            $nouvelleTache->setDescription($tache->getDescription());
            $nouvelleTache->setPriority($tache->getPriority() ?? 'normale');
            $nouvelleTache->setDone(false);
            $nouvelleTache->setInProgress(false);
            $nouvelleTache->setDueDate($nouvelleDateEcheance->format('Y-m-d'));
            $nouvelleTache->setProjectId($tache->getProjectId());
            $nouvelleTache->setEstimatedTime($tache->getEstimatedTime());
            $nouvelleTache->setTags($tache->getTags() ?? []);
            $nouvelleTache->setAssignedTo($tache->getAssignedTo());
            $nouvelleTache->setTicketType($tache->getTicketType() ?? 'task');
            // Conserve la récurrence sur la nouvelle tâche
            $nouvelleTache->setRecurrence($tache->getRecurrence());
            $nouvelleTache->setRecurrenceEndDate($tache->getRecurrenceEndDate());

            $this->em->persist($nouvelleTache);
            ++$compteur;

            $output->writeln("  ✅ Dupliquée : {$tache->getName()} → {$nouvelleDateEcheance->format('Y-m-d')}");
        }

        $this->em->flush();

        $output->writeln("=== {$compteur} tâche(s) récurrente(s) créée(s) ===");

        return Command::SUCCESS;
    }
}
