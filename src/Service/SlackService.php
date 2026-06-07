<?php
// =====================================================
// SlackService.php — Service d'intégration Slack
// Envoie des notifications dans un canal Slack
// via Incoming Webhook
// =====================================================

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class SlackService
{
    public function __construct(
        private HttpClientInterface $httpClient
    ) {}

    // =====================
    // ENVOYER UN MESSAGE SLACK
    // =====================
    public function send(string $message, array $attachments = []): bool
    {
        $webhookUrl = $_ENV['SLACK_WEBHOOK_URL'] ?? null;

        if (!$webhookUrl) {
            return false;
        }

        try {
            $payload = ['text' => $message];

            if (!empty($attachments)) {
                $payload['attachments'] = $attachments;
            }

            $response = $this->httpClient->request('POST', $webhookUrl, [
                'json' => $payload,
            ]);

            return $response->getStatusCode() === 200;
        } catch (\Exception $e) {
            return false;
        }
    }

    // =====================
    // NOTIFICATION CRÉATION TÂCHE
    // =====================
    public function notifyTaskCreated(string $taskName, string $projectName, string $priority, string $userEmail): bool
    {
        $emoji = match($priority) {
            'critique' => '🔴',
            'haute' => '🟠',
            'normale' => '🟡',
            'basse' => '🟢',
            default => '⚪',
        };

        return $this->send(
            "📌 Nouvelle tâche créée",
            [[
                'color' => match($priority) {
                    'critique' => '#e74c3c',
                    'haute' => '#e67e22',
                    'normale' => '#378ADD',
                    'basse' => '#639922',
                    default => '#aaa',
                },
                'fields' => [
                    ['title' => 'Tâche', 'value' => $taskName, 'short' => true],
                    ['title' => 'Projet', 'value' => $projectName, 'short' => true],
                    ['title' => 'Priorité', 'value' => $emoji . ' ' . ucfirst($priority), 'short' => true],
                    ['title' => 'Créé par', 'value' => $userEmail, 'short' => true],
                ],
                'footer' => 'Project Manager',
                'ts' => time(),
            ]]
        );
    }

    // =====================
    // NOTIFICATION MENTION
    // =====================
    public function notifyMention(string $mentionedBy, string $mentionedEmail, string $taskName): bool
    {
        return $this->send(
            "💬 *{$mentionedBy}* a mentionné *{$mentionedEmail}* dans la tâche *{$taskName}*",
        );
    }

    // =====================
    // NOTIFICATION PROJET CRÉÉ
    // =====================
    public function notifyProjectCreated(string $projectName, string $userEmail): bool
    {
        return $this->send(
            "📁 Nouveau projet créé : *{$projectName}* par *{$userEmail}*"
        );
    }
}
