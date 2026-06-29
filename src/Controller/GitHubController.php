<?php

// =====================================================
// GitHubController.php — Gestion des webhooks GitHub
// Reçoit les webhooks de GitHub et enregistre
// les commits liés aux tâches
// =====================================================

namespace App\Controller;

use App\Entity\GitCommit;
use App\Repository\GitCommitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/github')]
class GitHubController extends AbstractController
{
    // =====================
    // POST — Webhook GitHub
    // GitHub envoie les données ici à chaque push
    // =====================
    #[Route('/webhook', methods: ['POST'])]
    public function webhook(Request $request, EntityManagerInterface $em): JsonResponse
    {
        // On récupère le payload GitHub
        $payload = json_decode($request->getContent(), true);

        if (!$payload || !isset($payload['commits'])) {
            return $this->json(['error' => 'Payload invalide'], 400);
        }

        // Nom du repository
        $repository = $payload['repository']['full_name'] ?? 'Inconnu';

        $savedCommits = [];

        // On traite chaque commit du push
        foreach ($payload['commits'] as $commitData) {
            // On vérifie si le commit n'existe pas déjà
            $existing = $em->getRepository(GitCommit::class)->findOneBy([
                'sha' => $commitData['id'],
            ]);

            if ($existing) {
                continue;
            }

            // On cherche si le message contient un #id de tâche
            // Convention : "#123" dans le message lie au task id 123
            $taskId = null;
            if (preg_match('/#(\d+)/', $commitData['message'], $matches)) {
                $taskId = (int) $matches[1];
            }

            // On crée le commit
            $commit = new GitCommit();
            $commit->setSha($commitData['id']);
            $commit->setMessage($commitData['message']);
            $commit->setAuthor($commitData['author']['name'] ?? 'Inconnu');
            $commit->setUrl($commitData['url']);
            $commit->setTaskId($taskId);
            $commit->setRepository($repository);
            $commit->setCommittedAt(new \DateTime($commitData['timestamp']));

            $em->persist($commit);
            $savedCommits[] = $commit->getSha();
        }

        $em->flush();

        return $this->json([
            'message' => count($savedCommits).' commit(s) enregistré(s)',
            'commits' => $savedCommits,
        ]);
    }

    // =====================
    // GET — Récupérer tous les commits
    // =====================
    #[Route('/commits', methods: ['GET'])]
    public function commits(EntityManagerInterface $em): JsonResponse
    {
        $commits = $em->getRepository(GitCommit::class)->findBy(
            [],
            ['committedAt' => 'DESC'],
            20 // Limite à 20 commits
        );

        $data = array_map(function ($commit) {
            return [
                'id' => $commit->getId(),
                'sha' => substr($commit->getSha(), 0, 7),
                'message' => $commit->getMessage(),
                'author' => $commit->getAuthor(),
                'url' => $commit->getUrl(),
                'taskId' => $commit->getTaskId(),
                'repository' => $commit->getRepository(),
                'committedAt' => $commit->getCommittedAt()->format('Y-m-d H:i:s'),
            ];
        }, $commits);

        return $this->json($data);
    }

    // =====================
    // GET — Récupérer les commits d'une tâche
    // =====================
    #[Route('/commits/task/{taskId}', methods: ['GET'])]
    public function commitsByTask(int $taskId, GitCommitRepository $repo): JsonResponse
    {
        $commits = $repo->findByTaskId($taskId);

        $data = array_map(function ($commit) {
            return [
                'id' => $commit->getId(),
                'sha' => substr($commit->getSha(), 0, 7),
                'message' => $commit->getMessage(),
                'author' => $commit->getAuthor(),
                'url' => $commit->getUrl(),
                'repository' => $commit->getRepository(),
                'committedAt' => $commit->getCommittedAt()->format('Y-m-d H:i:s'),
            ];
        }, $commits);

        return $this->json($data);
    }
}
