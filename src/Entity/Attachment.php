<?php
namespace App\Entity;

use App\Repository\AttachmentRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AttachmentRepository::class)]
class Attachment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $filename = null;

    #[ORM\Column(length: 255)]
    private ?string $path = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $mimeType = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $uploadedAt = null;

    #[ORM\ManyToOne(targetEntity: Task::class, inversedBy: 'attachments')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Task $task = null;

    public function __construct()
    {
        $this->uploadedAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }

    public function getFilename(): ?string { return $this->filename; }
    public function setFilename(string $filename): static { $this->filename = $filename; return $this; }

    public function getPath(): ?string { return $this->path; }
    public function setPath(string $path): static { $this->path = $path; return $this; }

    public function getMimeType(): ?string { return $this->mimeType; }
    public function setMimeType(?string $mimeType): static { $this->mimeType = $mimeType; return $this; }

    public function getUploadedAt(): ?\DateTimeInterface { return $this->uploadedAt; }

    public function getTask(): ?Task { return $this->task; }
    public function setTask(?Task $task): static { $this->task = $task; return $this; }
}
