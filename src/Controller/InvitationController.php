<?php
// =====================================================
// InvitationController.php — Gestion des invitations
// Permet d'inviter des utilisateurs à rejoindre
// un projet par email
// =====================================================

namespace App\Controller;

use App\Entity\Invitation;
use App\Entity\Project;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/invitations')]
class InvitationController extends AbstractController
{
    // =====================
    // POST — Envoyer une invitation par email
    // =====================
    #[Route('', methods: ['POST'])]
    public function invite(
        Request $request,
        EntityManagerInterface $em,
        MailerInterface $mailer
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        // Vérifie que l'email et le projet sont fournis
        if (empty($data['email']) || empty($data['projectId'])) {
            return $this->json(['error' => 'Email et projet requis'], 400);
        }

        // Récupère le projet
        $project = $em->getRepository(Project::class)->find($data['projectId']);
        if (!$project) {
            return $this->json(['error' => 'Projet non trouvé'], 404);
        }

        // Crée l'invitation
        $invitation = new Invitation();
        $invitation->setEmail($data['email']);
        $invitation->setProjectId($data['projectId']);

        $em->persist($invitation);
        $em->flush();

        // Envoie l'email d'invitation
        $email = (new Email())
            ->from('noreply@costincianu.fr')
            ->to($data['email'])
            ->subject('Invitation à rejoindre ' . $project->getName())
            ->html(
                '<h2>Vous avez été invité à rejoindre le projet <strong>' . $project->getName() . '</strong></h2>' .
                '<p>Cliquez sur le lien ci-dessous pour accepter l\'invitation :</p>' .
                '<a href="http://project-manager.xena8933.odns.fr/invitation/' . $invitation->getToken() . '" ' .
                'style="background:#111;color:#fff;padding:12px 24px;border-radius:8px;text-decoration:none;">' .
                'Accepter l\'invitation</a>' .
                '<p style="color:#aaa;font-size:12px;margin-top:20px;">Si vous ne souhaitez pas rejoindre ce projet, ignorez cet email.</p>'
            );

        $mailer->send($email);

        return $this->json([
            'message' => 'Invitation envoyée à ' . $data['email'],
            'token' => $invitation->getToken(),
        ], 201);
    }

    // =====================
    // GET — Récupérer toutes les invitations d'un projet
    // =====================
    #[Route('/project/{projectId}', methods: ['GET'])]
    public function list(int $projectId, EntityManagerInterface $em): JsonResponse
    {
        $invitations = $em->getRepository(Invitation::class)->findBy([
            'projectId' => $projectId
        ]);

        $data = array_map(function($inv) {
            return [
                'id' => $inv->getId(),
                'email' => $inv->getEmail(),
                'status' => $inv->getStatus(),
                'createdAt' => $inv->getCreatedAt()->format('Y-m-d H:i:s'),
            ];
        }, $invitations);

        return $this->json($data);
    }
}
