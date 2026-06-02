<?php
// =====================================================
// AttachmentController.php — Gestion des fichiers
// Permet d'uploader et télécharger des fichiers
// attachés aux tâches
// =====================================================

namespace App\Controller;

use App\Entity\Attachment;
use App\Entity\Task;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/tasks')]
class AttachmentController extends AbstractController
{
    // Dossier où les fichiers sont stockés
    private string $uploadDir;

    public function __construct()
    {
        // Les fichiers sont stockés dans public/uploads/
        // On utilise __DIR__ pour trouver le chemin absolu
        $this->uploadDir = __DIR__ . '/../../public/uploads/';
    }

    // =====================
    // GET — Récupérer tous les fichiers d'une tâche
    // =====================
    #[Route('/{id}/attachments', methods: ['GET'])]
    public function index(int $id, EntityManagerInterface $em): JsonResponse
    {
        $task = $em->getRepository(Task::class)->find($id);
        if (!$task) {
            return $this->json(['error' => 'Tâche non trouvée'], 404);
        }

        $attachments = $task->getAttachments()->toArray();

        $data = array_map(function($attachment) {
            return [
                'id' => $attachment->getId(),
                'filename' => $attachment->getFilename(),
                'path' => $attachment->getPath(),
                'mimeType' => $attachment->getMimeType(),
                'uploadedAt' => $attachment->getUploadedAt()->format('Y-m-d H:i:s'),
                'url' => '/uploads/' . $attachment->getPath(),
            ];
        }, $attachments);

        return $this->json($data);
    }

    // =====================
    // POST — Uploader un fichier
    // =====================
    #[Route('/{id}/attachments', methods: ['POST'])]
    public function upload(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $task = $em->getRepository(Task::class)->find($id);
        if (!$task) {
            return $this->json(['error' => 'Tâche non trouvée'], 404);
        }

        // On récupère le fichier uploadé
        $file = $request->files->get('file');
        if (!$file) {
            return $this->json(['error' => 'Aucun fichier fourni'], 400);
        }

        // Vérifie la taille du fichier (max 10MB)
        if ($file->getSize() > 10 * 1024 * 1024) {
            return $this->json(['error' => 'Fichier trop volumineux (max 10MB)'], 400);
        }

        // Génère un nom unique pour le fichier
        $newFilename = uniqid() . '.' . $file->getClientOriginalExtension();

        // Crée le dossier uploads s'il n'existe pas
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0777, true);
        }

        // Déplace le fichier dans le dossier uploads
        $file->move($this->uploadDir, $newFilename);

        // Sauvegarde en base de données
        $attachment = new Attachment();
        $attachment->setFilename($file->getClientOriginalName());
        $attachment->setPath($newFilename);
        $attachment->setMimeType($file->getClientMimeType());
        $attachment->setTask($task);

        $em->persist($attachment);
        $em->flush();

        return $this->json([
            'id' => $attachment->getId(),
            'filename' => $attachment->getFilename(),
            'path' => $attachment->getPath(),
            'mimeType' => $attachment->getMimeType(),
            'uploadedAt' => $attachment->getUploadedAt()->format('Y-m-d H:i:s'),
            'url' => '/uploads/' . $attachment->getPath(),
        ], 201);
    }

    // =====================
    // DELETE — Supprimer un fichier
    // =====================
    #[Route('/attachments/{id}', methods: ['DELETE'])]
    public function delete(int $id, EntityManagerInterface $em): JsonResponse
    {
        $attachment = $em->getRepository(Attachment::class)->find($id);
        if (!$attachment) {
            return $this->json(['error' => 'Fichier non trouvé'], 404);
        }

        // Supprime le fichier physique
        $filePath = $this->uploadDir . $attachment->getPath();
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        $em->remove($attachment);
        $em->flush();

        return $this->json(['message' => 'Fichier supprimé']);
    }
}
