<?php

namespace App\Controller;

use App\Entity\Project;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/projects')]
class ProjectController extends AbstractController
{
    // =====================
    // GET — Récupérer tous les projets
    // =====================
    #[Route('', methods: ['GET'])]
    public function index(EntityManagerInterface $em): JsonResponse
    {
        // On récupère tous les projets depuis la base de données
        $projects = $em->getRepository(Project::class)->findAll();

        // On convertit les projets en tableau pour les envoyer en JSON
        $data = array_map(function($project) {
            return [
                'id' => $project->getId(),
                'name' => $project->getName(),
                'status' => $project->getStatus(),
                'color' => $project->getColor(),
                'progress' => $project->getProgress(),
            ];
        }, $projects);

        return $this->json($data);
    }

    // =====================
    // POST — Créer un nouveau projet
    // =====================
    #[Route('', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        // On récupère les données envoyées par React
        $data = json_decode($request->getContent(), true);

        // On crée un nouveau projet
        $project = new Project();
        $project->setName($data['name']);
        $project->setStatus($data['status']);
        $project->setColor($data['color']);
        $project->setProgress($data['progress'] ?? 0);

        // On sauvegarde dans la base de données
        $em->persist($project);
        $em->flush();

        return $this->json([
            'id' => $project->getId(),
            'name' => $project->getName(),
            'status' => $project->getStatus(),
            'color' => $project->getColor(),
            'progress' => $project->getProgress(),
        ], 201);
    }

    // =====================
    // PUT — Modifier un projet
    // =====================
    #[Route('/{id}', methods: ['PUT'])]
    public function update(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        // On cherche le projet par son id
        $project = $em->getRepository(Project::class)->find($id);

        // Si le projet n'existe pas on retourne une erreur
        if (!$project) {
            return $this->json(['error' => 'Projet non trouvé'], 404);
        }

        // On met à jour les données
        $data = json_decode($request->getContent(), true);
        $project->setName($data['name'] ?? $project->getName());
        $project->setStatus($data['status'] ?? $project->getStatus());
        $project->setColor($data['color'] ?? $project->getColor());
        $project->setProgress($data['progress'] ?? $project->getProgress());

        // On sauvegarde les modifications
        $em->flush();

        return $this->json([
            'id' => $project->getId(),
            'name' => $project->getName(),
            'status' => $project->getStatus(),
            'color' => $project->getColor(),
            'progress' => $project->getProgress(),
        ]);
    }

    // =====================
    // DELETE — Supprimer un projet
    // =====================
    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id, EntityManagerInterface $em): JsonResponse
    {
        // On cherche le projet par son id
        $project = $em->getRepository(Project::class)->find($id);

        // Si le projet n'existe pas on retourne une erreur
        if (!$project) {
            return $this->json(['error' => 'Projet non trouvé'], 404);
        }

        // On supprime le projet
        $em->remove($project);
        $em->flush();

        return $this->json(['message' => 'Projet supprimé avec succès']);
    }
}
