<?php

// =====================================================
// RegleAutomatisationController.php — API des règles
// Routes :
//   GET    /api/projects/{id}/regles        → liste des règles du projet
//   POST   /api/projects/{id}/regles        → créer une règle
//   PUT    /api/regles/{id}                 → modifier une règle
//   PATCH  /api/regles/{id}/toggle          → activer ou désactiver
//   DELETE /api/regles/{id}                 → supprimer une règle
// =====================================================

namespace App\Controller;

use App\Entity\RegleAutomatisation;
use App\Repository\ProjectRepository;
use App\Repository\RegleAutomatisationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class RegleAutomatisationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private RegleAutomatisationRepository $regleRepository,
        private ProjectRepository $projetRepository,
    ) {
    }

    // =====================
    // LISTE DES RÈGLES D'UN PROJET
    // =====================
    #[Route('/api/projects/{id}/regles', name: 'regles_liste', methods: ['GET'])]
    public function liste(int $id): JsonResponse
    {
        $projet = $this->projetRepository->find($id);
        if (!$projet) {
            return $this->json(['erreur' => 'Projet introuvable'], 404);
        }

        $regles = $this->regleRepository->findBy(
            ['projet' => $projet],
            ['creeLe' => 'DESC']
        );

        return $this->json(array_map(
            fn (RegleAutomatisation $r) => $r->versTableau(),
            $regles
        ));
    }

    // =====================
    // CRÉER UNE RÈGLE
    // =====================
    #[Route('/api/projects/{id}/regles', name: 'regle_creer', methods: ['POST'])]
    public function creer(int $id, Request $request): JsonResponse
    {
        $projet = $this->projetRepository->find($id);
        if (!$projet) {
            return $this->json(['erreur' => 'Projet introuvable'], 404);
        }

        $donnees = json_decode($request->getContent(), true);

        // Vérifie les champs obligatoires
        if (empty($donnees['nom']) || empty($donnees['declencheur']) || empty($donnees['action'])) {
            return $this->json(['erreur' => 'Les champs nom, declencheur et action sont obligatoires'], 400);
        }

        // Crée la nouvelle règle
        $regle = new RegleAutomatisation();
        $regle->setProjet($projet);
        $regle->setNom($donnees['nom']);
        $regle->setDeclencheur($donnees['declencheur']);
        $regle->setValeurDeclencheur($donnees['valeurDeclencheur'] ?? null);
        $regle->setAction($donnees['action']);
        $regle->setValeurAction($donnees['valeurAction'] ?? null);
        $regle->setActive($donnees['active'] ?? true);

        $this->em->persist($regle);
        $this->em->flush();

        return $this->json($regle->versTableau(), 201);
    }

    // =====================
    // MODIFIER UNE RÈGLE
    // =====================
    #[Route('/api/regles/{id}', name: 'regle_modifier', methods: ['PUT'])]
    public function modifier(int $id, Request $request): JsonResponse
    {
        $regle = $this->regleRepository->find($id);
        if (!$regle) {
            return $this->json(['erreur' => 'Règle introuvable'], 404);
        }

        $donnees = json_decode($request->getContent(), true);

        // Met à jour uniquement les champs fournis
        if (isset($donnees['nom'])) {
            $regle->setNom($donnees['nom']);
        }
        if (isset($donnees['declencheur'])) {
            $regle->setDeclencheur($donnees['declencheur']);
        }
        if (isset($donnees['valeurDeclencheur'])) {
            $regle->setValeurDeclencheur($donnees['valeurDeclencheur']);
        }
        if (isset($donnees['action'])) {
            $regle->setAction($donnees['action']);
        }
        if (isset($donnees['valeurAction'])) {
            $regle->setValeurAction($donnees['valeurAction']);
        }
        if (isset($donnees['active'])) {
            $regle->setActive($donnees['active']);
        }

        $this->em->flush();

        return $this->json($regle->versTableau());
    }

    // =====================
    // ACTIVER OU DÉSACTIVER UNE RÈGLE
    // =====================
    #[Route('/api/regles/{id}/toggle', name: 'regle_toggle', methods: ['PATCH'])]
    public function toggle(int $id): JsonResponse
    {
        $regle = $this->regleRepository->find($id);
        if (!$regle) {
            return $this->json(['erreur' => 'Règle introuvable'], 404);
        }

        // Inverse l'état — active devient inactive et vice versa
        $regle->setActive(!$regle->isActive());
        $this->em->flush();

        return $this->json($regle->versTableau());
    }

    // =====================
    // SUPPRIMER UNE RÈGLE
    // =====================
    #[Route('/api/regles/{id}', name: 'regle_supprimer', methods: ['DELETE'])]
    public function supprimer(int $id): JsonResponse
    {
        $regle = $this->regleRepository->find($id);
        if (!$regle) {
            return $this->json(['erreur' => 'Règle introuvable'], 404);
        }

        $this->em->remove($regle);
        $this->em->flush();

        return $this->json(['message' => 'Règle supprimée avec succès']);
    }
}
