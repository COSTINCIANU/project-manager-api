#!/bin/bash

echo "🚀 Démarrage du conteneur PHP..."

# Attendre que MySQL soit prêt
echo "⏳ Attente de MySQL..."
until php bin/console doctrine:query:sql "SELECT 1" > /dev/null 2>&1; do
    echo "MySQL pas encore prêt - attente 2s..."
    sleep 2
done

echo "✅ MySQL est prêt !"

# Vider le cache
echo "🧹 Nettoyage du cache..."
php bin/console cache:clear --env=prod --no-warmup
php bin/console cache:warmup --env=prod

# Exécuter les migrations
echo "🗄️ Exécution des migrations..."
php bin/console doctrine:migrations:migrate --no-interaction --env=prod

echo "✅ Application prête !"

# Démarrer PHP-FPM
exec php-fpm
