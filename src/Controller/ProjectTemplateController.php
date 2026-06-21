<?php
// =====================================================
// ProjectTemplateController.php — Gestion des templates
// Permet de créer des projets à partir de modèles prédéfinis
// Permissions :
// - GET : tous les rôles connectés
// - POST (créer template) : admin uniquement
// - POST (créer projet depuis template) : manager et admin
// - DELETE : admin uniquement
// =====================================================

namespace App\Controller;

use App\Entity\Project;
use App\Entity\ProjectTemplate;
use App\Entity\ProjectTemplateTask;
use App\Entity\Task;
use App\Service\PermissionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/templates')]
class ProjectTemplateController extends AbstractController
{
    // =====================
    // GET — Récupérer tous les templates disponibles
    // =====================
    #[Route('', methods: ['GET'])]
    public function index(EntityManagerInterface $em): JsonResponse
    {
        $templates = $em->getRepository(ProjectTemplate::class)->findAll();

        $data = array_map(function($template) {
            // On retourne aussi les tâches préremplies de chaque template
            $taches = array_map(function($tache) {
                return [
                    'id' => $tache->getId(),
                    'name' => $tache->getName(),
                    'priority' => $tache->getPriority(),
                    'position' => $tache->getPosition(),
                ];
            }, $template->getTasks()->toArray());

            return [
                'id' => $template->getId(),
                'name' => $template->getName(),
                'description' => $template->getDescription(),
                'color' => $template->getColor(),
                'icon' => $template->getIcon(),
                'tasksCount' => count($taches),
                'tasks' => $taches,
            ];
        }, $templates);

        return $this->json($data);
    }

    // =====================
    // GET — Récupérer un template par son ID
    // =====================
    #[Route('/{id}', methods: ['GET'])]
    public function show(int $id, EntityManagerInterface $em): JsonResponse
    {
        $template = $em->getRepository(ProjectTemplate::class)->find($id);

        if (!$template) {
            return $this->json(['error' => 'Template non trouvé'], 404);
        }

        $taches = array_map(function($tache) {
            return [
                'id' => $tache->getId(),
                'name' => $tache->getName(),
                'priority' => $tache->getPriority(),
                'position' => $tache->getPosition(),
            ];
        }, $template->getTasks()->toArray());

        return $this->json([
            'id' => $template->getId(),
            'name' => $template->getName(),
            'description' => $template->getDescription(),
            'color' => $template->getColor(),
            'icon' => $template->getIcon(),
            'tasks' => $taches,
        ]);
    }

    // =====================
    // POST — Créer un nouveau template (admin uniquement)
    // =====================
    #[Route('', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em, PermissionService $permissions): JsonResponse
    {
        // Seul l'admin peut créer des templates
        if (!$permissions->isAdmin()) {
            return $this->json(['error' => 'Accès refusé — rôle admin requis'], 403);
        }

        $data = json_decode($request->getContent(), true);

        if (empty($data['name'])) {
            return $this->json(['error' => 'Le nom du template est requis'], 400);
        }

        $template = new ProjectTemplate();
        $template->setName($data['name']);
        $template->setDescription($data['description'] ?? null);
        $template->setColor($data['color'] ?? '#9B7FD4');
        $template->setIcon($data['icon'] ?? 'folder');

        // On ajoute les tâches préremplies si fournies
        if (!empty($data['tasks'])) {
            foreach ($data['tasks'] as $position => $tacheData) {
                $tache = new ProjectTemplateTask();
                $tache->setName($tacheData['name']);
                $tache->setPriority($tacheData['priority'] ?? 'normale');
                $tache->setPosition($tacheData['position'] ?? $position + 1);
                $template->addTask($tache);
                $em->persist($tache);
            }
        }

        $em->persist($template);
        $em->flush();

        return $this->json([
            'id' => $template->getId(),
            'name' => $template->getName(),
            'description' => $template->getDescription(),
            'color' => $template->getColor(),
            'icon' => $template->getIcon(),
            'tasksCount' => $template->getTasks()->count(),
        ], 201);
    }

    // =====================
    // POST — Créer un projet depuis un template
    // Crée le projet + toutes les tâches préremplies
    // =====================
    #[Route('/{id}/create-project', methods: ['POST'])]
    public function createProjectFromTemplate(int $id, Request $request, EntityManagerInterface $em, PermissionService $permissions): JsonResponse
    {
        // Manager et admin peuvent créer un projet depuis un template
        if (!$permissions->canCreateProject()) {
            return $this->json(['error' => 'Accès refusé — rôle manager ou admin requis'], 403);
        }

        $template = $em->getRepository(ProjectTemplate::class)->find($id);

        if (!$template) {
            return $this->json(['error' => 'Template non trouvé'], 404);
        }

        $data = json_decode($request->getContent(), true);

        // On crée le projet avec les infos du template
        $project = new Project();
        $project->setName($data['name'] ?? $template->getName());
        $project->setStatus('a_faire');
        $project->setColor($template->getColor() ?? '#9B7FD4');
        $project->setProgress(0);

        $em->persist($project);
        $em->flush();

        // On crée toutes les tâches préremplies du template
        foreach ($template->getTasks() as $tacheTemplate) {
            $tache = new Task();
            $tache->setName($tacheTemplate->getName());
            $tache->setPriority($tacheTemplate->getPriority() ?? 'normale');
            $tache->setDone(false);
            $tache->setInProgress(false);
            $tache->setProjectId($project->getId());
            $em->persist($tache);
        }

        $em->flush();

        return $this->json([
            'message' => 'Projet créé depuis le template "' . $template->getName() . '"',
            'projectId' => $project->getId(),
            'projectName' => $project->getName(),
            'tasksCreated' => $template->getTasks()->count(),
        ], 201);
    }

    // =====================
    // DELETE — Supprimer un template (admin uniquement)
    // =====================
    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id, EntityManagerInterface $em, PermissionService $permissions): JsonResponse
    {
        if (!$permissions->isAdmin()) {
            return $this->json(['error' => 'Accès refusé — rôle admin requis'], 403);
        }

        $template = $em->getRepository(ProjectTemplate::class)->find($id);

        if (!$template) {
            return $this->json(['error' => 'Template non trouvé'], 404);
        }

        $em->remove($template);
        $em->flush();

        return $this->json(['message' => 'Template supprimé avec succès']);
    }
}
