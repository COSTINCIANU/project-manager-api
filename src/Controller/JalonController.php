<?php
// =====================================================
// JalonController.php — API des jalons
// Routes :
// GET    /api/projets/{id}/jalons      — liste des jalons
// POST   /api/jalons                   — créer un jalon
// PUT    /api/jalons/{id}              — modifier un jalon
// DELETE /api/jalons/{id}              — supprimer un jalon
// =====================================================

namespace App\Controller;

use App\Entity\Jalon;
use App\Repository\JalonRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class JalonController extends AbstractController
{
    // =====================
    // GET — Liste des jalons d'un projet
    // =====================
    #[Route('/projets/{id}/jalons', methods: ['GET'])]
    public function liste(int $id, JalonRepository $jalonRepo): JsonResponse
    {
        $jalons = $jalonRepo->findByProjetOrdonnes($id);

        return $this->json(array_map(
            fn (Jalon $j) => $j->versTableau(),
            $jalons
        ));
    }

    // =====================
    // POST — Créer un jalon
    // =====================
    #[Route('/jalons', methods: ['POST'])]
    public function creer(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $donnees = json_decode($request->getContent(), true);

        if (empty($donnees['nom']) || empty($donnees['date']) || empty($donnees['projetId'])) {
            return $this->json(['error' => 'Champs requis : nom, date, projetId'], 400);
        }

        $jalon = new Jalon();
        $jalon->setNom($donnees['nom']);
        $jalon->setDescription($donnees['description'] ?? null);
        $jalon->setDate($donnees['date']);
        $jalon->setProjetId((int) $donnees['projetId']);
        $jalon->setAtteint($donnees['atteint'] ?? false);
        $jalon->setCouleur($donnees['couleur'] ?? '#378ADD');

        $em->persist($jalon);
        $em->flush();

        return $this->json($jalon->versTableau(), 201);
    }

    // =====================
    // PUT — Modifier un jalon
    // =====================
    #[Route('/jalons/{id}', methods: ['PUT'])]
    public function modifier(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $jalon = $em->getRepository(Jalon::class)->find($id);

        if (!$jalon) {
            return $this->json(['error' => 'Jalon non trouvé'], 404);
        }

        $donnees = json_decode($request->getContent(), true);

        if (isset($donnees['nom']))         $jalon->setNom($donnees['nom']);
        if (isset($donnees['description'])) $jalon->setDescription($donnees['description']);
        if (isset($donnees['date']))        $jalon->setDate($donnees['date']);
        if (isset($donnees['atteint']))     $jalon->setAtteint($donnees['atteint']);
        if (isset($donnees['couleur']))     $jalon->setCouleur($donnees['couleur']);

        $em->flush();

        return $this->json($jalon->versTableau());
    }

    // =====================
    // DELETE — Supprimer un jalon
    // =====================
    #[Route('/jalons/{id}', methods: ['DELETE'])]
    public function supprimer(int $id, EntityManagerInterface $em): JsonResponse
    {
        $jalon = $em->getRepository(Jalon::class)->find($id);

        if (!$jalon) {
            return $this->json(['error' => 'Jalon non trouvé'], 404);
        }

        $em->remove($jalon);
        $em->flush();

        return $this->json(['message' => 'Jalon supprimé avec succès']);
    }
}
