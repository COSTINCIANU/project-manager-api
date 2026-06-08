<?php
// =====================================================
// deploy.php — Webhook de déploiement automatique
// Appelé par GitHub Actions pour déployer le backend
// =====================================================

// Vérification du token de sécurité
$token = $_SERVER['HTTP_X_DEPLOY_TOKEN'] ?? '';
$expectedToken = getenv('DEPLOY_TOKEN') ?: 'change_me_deploy_token';

if ($token !== $expectedToken) {
    http_response_code(403);
    die(json_encode(['error' => 'Token invalide']));
}

// Exécution du déploiement
$output = [];
exec('cd /home/xena8933/public_html/project-manager-api && git pull origin master 2>&1', $output);
exec('cd /home/xena8933/public_html/project-manager-api && php bin/console cache:clear 2>&1', $output);

echo json_encode([
    'success' => true,
    'output' => implode("\n", $output),
    'timestamp' => date('Y-m-d H:i:s'),
]);
