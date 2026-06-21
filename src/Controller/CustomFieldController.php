<?php
// =====================================================
// CustomFieldController.php — Gestion des champs personnalisés
// Permet d'ajouter des champs sur mesure aux projets et tâches
// Permissions :
// - GET : tous les rôles connectés
// - POST : manager et admin
// - PUT : manager et admin
// - DELETE : manager et admin
// =====================================================

namespace App\Controller;

use App\Entity\CustomField;
use App\Service\PermissionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/custom-fields')]
class CustomFieldController extends AbstractController
{
    // =====================
    // GET — Récupérer les champs d'un projet
    // =====================
    #[Route('/project/{projectId}', methods: ['GET'])]
    public function getByProject(int $projectId, EntityManagerInterface $em): JsonResponse
    {
        $champs = $em->getRepository(CustomField::class)->findBy([
            'projectId' => $projectId,
            'taskId' => null, // Uniquement les champs du projet, pas des tâches
        ]);

        return $this->json(array_map(fn($c) => $this->champVersTableau($c), $champs));
    }

    // =====================
    // GET — Récupérer les champs d'une tâche
    // =====================
    #[Route('/task/{taskId}', methods: ['GET'])]
    public function getByTask(int $taskId, EntityManagerInterface $em): JsonResponse
    {
        $champs = $em->getRepository(CustomField::class)->findBy([
            'taskId' => $taskId,
        ]);

        return $this->json(array_map(fn($c) => $this->champVersTableau($c), $champs));
    }

    // =====================
    // POST — Créer un champ personnalisé
    // =====================
    #[Route('', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em, PermissionService $permissions): JsonResponse
    {
        // Seuls manager et admin peuvent créer des champs
        if (!$permissions->isManagerOrAbove()) {
            return $this->json(['error' => 'Accès refusé — rôle manager ou admin requis'], 403);
        }

        $data = json_decode($request->getContent(), true);

        // Vérification des champs obligatoires
        if (empty($data['name']) || empty($data['label']) || empty($data['type']) || empty($data['projectId'])) {
            return $this->json(['error' => 'name, label, type et projectId sont requis'], 400);
        }

        // Vérification que le type est valide
        $typesValides = ['text', 'number', 'date', 'boolean'];
        if (!in_array($data['type'], $typesValides)) {
            return $this->json(['error' => 'Type invalide. Utilisez : text, number, date, boolean'], 400);
        }

        $champ = new CustomField();
        $champ->setName($data['name']);
        $champ->setLabel($data['label']);
        $champ->setType($data['type']);
        $champ->setValue($data['value'] ?? null);
        $champ->setProjectId($data['projectId']);
        $champ->setTaskId($data['taskId'] ?? null);

        $em->persist($champ);
        $em->flush();

        return $this->json($this->champVersTableau($champ), 201);
    }

    // =====================
    // PUT — Modifier la valeur d'un champ
    // =====================
    #[Route('/{id}', methods: ['PUT'])]
    public function update(int $id, Request $request, EntityManagerInterface $em, PermissionService $permissions): JsonResponse
    {
        if (!$permissions->isManagerOrAbove()) {
            return $this->json(['error' => 'Accès refusé — rôle manager ou admin requis'], 403);
        }

        $champ = $em->getRepository(CustomField::class)->find($id);

        if (!$champ) {
            return $this->json(['error' => 'Champ non trouvé'], 404);
        }

        $data = json_decode($request->getContent(), true);

        // On peut modifier le label et la valeur
        if (isset($data['label'])) {
            $champ->setLabel($data['label']);
        }
        if (array_key_exists('value', $data)) {
            $champ->setValue($data['value']);
        }

        $em->flush();

        return $this->json($this->champVersTableau($champ));
    }

    // =====================
    // DELETE — Supprimer un champ
    // =====================
    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(int $id, EntityManagerInterface $em, PermissionService $permissions): JsonResponse
    {
        if (!$permissions->isManagerOrAbove()) {
            return $this->json(['error' => 'Accès refusé — rôle manager ou admin requis'], 403);
        }

        $champ = $em->getRepository(CustomField::class)->find($id);

        if (!$champ) {
            return $this->json(['error' => 'Champ non trouvé'], 404);
        }

        $em->remove($champ);
        $em->flush();

        return $this->json(['message' => 'Champ supprimé avec succès']);
    }

    // =====================
    // Convertit un champ en tableau pour la réponse JSON
    // =====================
    private function champVersTableau(CustomField $champ): array
    {
        return [
            'id' => $champ->getId(),
            'name' => $champ->getName(),
            'label' => $champ->getLabel(),
            'type' => $champ->getType(),
            'value' => $champ->getValue(),
            'projectId' => $champ->getProjectId(),
            'taskId' => $champ->getTaskId(),
        ];
    }
}
