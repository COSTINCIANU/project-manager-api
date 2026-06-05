<?php
// =====================================================
// PermissionService.php — Service de permissions
// Vérifie les droits selon le rôle métier de l'utilisateur
// admin > manager > dev > client
// =====================================================

namespace App\Service;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;

class PermissionService
{
    public function __construct(private Security $security) {}

    // Récupère l'utilisateur connecté
    public function getUser(): ?User
    {
        return $this->security->getUser();
    }

    // Récupère le rôle métier
    public function getRole(): string
    {
        $user = $this->getUser();
        return $user ? ($user->getRole() ?? 'client') : 'client';
    }

    // Admin uniquement
    public function isAdmin(): bool
    {
        return $this->getRole() === 'admin';
    }

    // Manager ou admin
    public function isManagerOrAbove(): bool
    {
        return in_array($this->getRole(), ['admin', 'manager']);
    }

    // Dev, manager ou admin
    public function isDevOrAbove(): bool
    {
        return in_array($this->getRole(), ['admin', 'manager', 'dev']);
    }

    // Peut créer un projet
    public function canCreateProject(): bool
    {
        return $this->isManagerOrAbove();
    }

    // Peut modifier un projet
    public function canEditProject(): bool
    {
        return $this->isManagerOrAbove();
    }

    // Peut supprimer un projet
    public function canDeleteProject(): bool
    {
        return $this->isAdmin();
    }

    // Peut créer une tâche
    public function canCreateTask(): bool
    {
        return $this->isDevOrAbove();
    }

    // Peut modifier une tâche
    public function canEditTask(): bool
    {
        return $this->isDevOrAbove();
    }

    // Peut supprimer une tâche
    public function canDeleteTask(): bool
    {
        return $this->isManagerOrAbove();
    }

    // Peut inviter des membres
    public function canInvite(): bool
    {
        return $this->isManagerOrAbove();
    }

    // Peut voir tout — tout le monde
    public function canView(): bool
    {
        return true;
    }
}
