<?php
// =====================================================
// AIController.php — Assistant IA
// Fait l'appel à l'API Claude d'Anthropic
// La clé API reste cachée côté serveur
// =====================================================

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/api/ai')]
class AIController extends AbstractController
{
    // private string $anthropicApiKey;

    // public function __construct(
    //     private HttpClientInterface $httpClient,
    //     string $anthropicApiKey = ''
    // ) {
    //     $this->anthropicApiKey = $anthropicApiKey;
    // }

    private string $anthropicApiKey;

    public function __construct(
        private HttpClientInterface $httpClient
    ) {
        // Lit la clé API depuis les variables d'environnement
        // Si pas de clé → mode simulation automatiquement
        $this->anthropicApiKey = $_ENV['ANTHROPIC_API_KEY'] ?? '';
    }

    // =====================
    // POST — Chat avec l'IA
    // =====================
    #[Route('/chat', methods: ['POST'])]
    public function chat(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['messages'])) {
            return $this->json(['error' => 'Messages requis'], 400);
        }

        // Si pas de clé API — on retourne une réponse simulée
        if (empty($this->anthropicApiKey)) {
            return $this->simulatedResponse($data['messages']);
        }

        try {
            // Appel à l'API Claude
            $response = $this->httpClient->request('POST', 'https://api.anthropic.com/v1/messages', [
                'headers' => [
                    'x-api-key' => $this->anthropicApiKey,
                    'anthropic-version' => '2023-06-01',
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'claude-opus-4-5',
                    'max_tokens' => 1024,
                    'system' => $data['system'] ?? 'Tu es un assistant de gestion de projet. Réponds en français.',
                    'messages' => $data['messages'],
                ],
            ]);

            $result = $response->toArray();
            return $this->json([
                'content' => $result['content'][0]['text'],
                'simulated' => false,
            ]);

        } catch (\Exception $e) {
            // En cas d'erreur on retourne une réponse simulée
            return $this->simulatedResponse($data['messages']);
        }
    }

    // =====================
    // POST — Générer des tâches automatiquement
    // L'IA génère des tâches depuis une description
    // =====================

    #[Route('/generate-tasks', methods: ['POST'])]
    public function generateTasks(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['description'])) {
            return $this->json(['error' => 'Description requise'], 400);
        }

        $description = $data['description'];

        // Si pas de clé API — on retourne des tâches simulées
        if (empty($this->anthropicApiKey)) {
            return $this->simulatedTasks($description);
        }

        try {
            // Appel à l'API Claude pour générer les tâches
            $response = $this->httpClient->request('POST', 'https://api.anthropic.com/v1/messages', [
                'headers' => [
                    'x-api-key' => $this->anthropicApiKey,
                    'anthropic-version' => '2023-06-01',
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'claude-opus-4-5',
                    'max_tokens' => 1024,
                    'system' => 'Tu es un expert en gestion de projet. Génère des tâches en JSON uniquement. Format : {"tasks": [{"name": "...", "priority": "haute|normale|basse", "description": "..."}]}. Pas de texte en dehors du JSON.',
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => "Génère 5-8 tâches pour ce projet : $description"
                        ]
                    ],
                ],
            ]);

            $result = $response->toArray();
            $content = $result['content'][0]['text'];

            // Parse le JSON retourné par l'IA
            $tasksData = json_decode($content, true);

            return $this->json([
                'tasks' => $tasksData['tasks'] ?? [],
                'simulated' => false,
            ]);

        } catch (\Exception $e) {
            return $this->simulatedTasks($description);
        }
    }

    // =====================
    // Génération simulée de tâches
    // =====================
    private function simulatedTasks(string $description): JsonResponse
    {
        // On génère des tâches selon les mots clés de la description
        $desc = strtolower($description);

        if (str_contains($desc, 'e-commerce') || str_contains($desc, 'boutique') || str_contains($desc, 'shop')) {
            $tasks = [
                ['name' => 'Créer la maquette UI/UX', 'priority' => 'haute', 'description' => 'Design des pages principales'],
                ['name' => 'Configurer la base de données', 'priority' => 'haute', 'description' => 'Tables produits, commandes, utilisateurs'],
                ['name' => 'Développer le catalogue produits', 'priority' => 'haute', 'description' => 'Liste et détail des produits'],
                ['name' => 'Intégrer le panier', 'priority' => 'haute', 'description' => 'Ajout/suppression de produits'],
                ['name' => 'Configurer le paiement Stripe', 'priority' => 'haute', 'description' => 'Paiement sécurisé en ligne'],
                ['name' => 'Système de gestion des commandes', 'priority' => 'normale', 'description' => 'Suivi des commandes'],
                ['name' => 'Emails automatiques', 'priority' => 'normale', 'description' => 'Confirmation de commande'],
                ['name' => 'Déployer en production', 'priority' => 'normale', 'description' => 'Mise en ligne du site'],
            ];
        } elseif (str_contains($desc, 'blog') || str_contains($desc, 'article') || str_contains($desc, 'cms')) {
            $tasks = [
                ['name' => 'Créer la structure du blog', 'priority' => 'haute', 'description' => 'Architecture des pages'],
                ['name' => 'Système de catégories', 'priority' => 'haute', 'description' => 'Organisation des articles'],
                ['name' => 'Éditeur de contenu', 'priority' => 'haute', 'description' => 'Interface de rédaction'],
                ['name' => 'Gestion des commentaires', 'priority' => 'normale', 'description' => 'Modération des commentaires'],
                ['name' => 'Optimisation SEO', 'priority' => 'normale', 'description' => 'Balises meta, sitemap'],
                ['name' => 'Système de recherche', 'priority' => 'normale', 'description' => 'Recherche dans les articles'],
                ['name' => 'Newsletter', 'priority' => 'basse', 'description' => 'Inscription et envoi'],
            ];
        } elseif (str_contains($desc, 'mobile') || str_contains($desc, 'app') || str_contains($desc, 'application')) {
            $tasks = [
                ['name' => 'Définir les user stories', 'priority' => 'haute', 'description' => 'Fonctionnalités principales'],
                ['name' => 'Créer les maquettes', 'priority' => 'haute', 'description' => 'Design de l\'interface'],
                ['name' => 'Configurer le projet React Native', 'priority' => 'haute', 'description' => 'Setup initial'],
                ['name' => 'Développer l\'authentification', 'priority' => 'haute', 'description' => 'Login / Register'],
                ['name' => 'Développer les fonctionnalités core', 'priority' => 'haute', 'description' => 'Features principales'],
                ['name' => 'Tests sur iOS et Android', 'priority' => 'normale', 'description' => 'Tests multi-plateformes'],
                ['name' => 'Publier sur les stores', 'priority' => 'normale', 'description' => 'App Store et Google Play'],
            ];
        } else {
            // Tâches génériques
            $tasks = [
                ['name' => 'Analyser les besoins', 'priority' => 'haute', 'description' => 'Recueillir les exigences'],
                ['name' => 'Créer la maquette', 'priority' => 'haute', 'description' => 'Design de l\'interface'],
                ['name' => 'Développer le backend', 'priority' => 'haute', 'description' => 'API et base de données'],
                ['name' => 'Développer le frontend', 'priority' => 'haute', 'description' => 'Interface utilisateur'],
                ['name' => 'Écrire les tests', 'priority' => 'normale', 'description' => 'Tests unitaires et fonctionnels'],
                ['name' => 'Déployer en staging', 'priority' => 'normale', 'description' => 'Environnement de test'],
                ['name' => 'Déployer en production', 'priority' => 'normale', 'description' => 'Mise en ligne'],
            ];
        }

        return $this->json([
            'tasks' => $tasks,
            'simulated' => true,
        ]);
    }
    // =====================
    // Réponses simulées intelligentes
    // Basées sur le message de l'utilisateur
    // =====================
    private function simulatedResponse(array $messages): JsonResponse
    {
        // On récupère le dernier message utilisateur
        $lastMessage = end($messages);
        $userMessage = strtolower($lastMessage['content'] ?? '');

        // On génère une réponse selon le message
        if (str_contains($userMessage, 'résume') || str_contains($userMessage, 'état')) {
            $response = "📊 **Résumé de vos projets**\n\nD'après vos données actuelles, vous avez plusieurs projets en cours avec des tâches à différents stades d'avancement.\n\n**Recommandations :**\n• Concentrez-vous sur les tâches en retard en priorité\n• Les tâches critique doivent être traitées immédiatement\n• Planifiez une révision hebdomadaire de vos projets";
        } elseif (str_contains($userMessage, 'retard')) {
            $response = "⚠️ **Tâches en retard**\n\nVoici mes recommandations pour rattraper le retard :\n\n1. Identifiez les blocages sur chaque tâche\n2. Réassignez si nécessaire\n3. Mettez à jour les dates d'échéance si elles ne sont plus réalistes\n4. Communiquez avec votre équipe sur les priorités";
        } elseif (str_contains($userMessage, 'génère') || str_contains($userMessage, 'tâches')) {
            $response = "✨ **Tâches générées automatiquement**\n\nVoici 5 tâches suggérées :\n\n1. Définir les objectifs du sprint\n2. Mettre à jour la documentation\n3. Réviser les tests unitaires\n4. Planifier la réunion d'équipe\n5. Faire le point sur les métriques\n\n💡 *Avec la vraie IA, les tâches seront générées selon votre contexte exact !*";
        } elseif (str_contains($userMessage, 'priorité') || str_contains($userMessage, 'prioriser')) {
            $response = "🎯 **Priorisation recommandée**\n\nBasé sur vos projets :\n\n1. **Critique** : Traitez d'abord les tâches en retard\n2. **Haute** : Tâches avec échéance dans les 3 prochains jours\n3. **Normale** : Tâches en cours sans retard\n4. **Basse** : Tâches sans date d'échéance\n\n💡 *Avec la vraie IA, l'analyse sera basée sur vos données réelles !*";
        } else {
            $response = "👋 Je suis votre assistant IA en mode **simulation**.\n\nJe peux vous aider avec :\n• Résumé de vos projets\n• Analyse des tâches en retard\n• Génération de tâches\n• Priorisation\n\n💡 *Pour activer la vraie IA avec Claude, ajoutez votre clé API Anthropic dans la configuration.*";
        }

        return $this->json([
            'content' => $response,
            'simulated' => true,
        ]);
    }
}
