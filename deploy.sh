#!/bin/bash

set -euo pipefail

APP_DIR="/var/www/cityavis-backend"
FRONT_DIR="/var/www/cityavis-backend/cityavis-front"
BRANCH="main"
APACHE_SERVICE="apache2"
BACKUP_DIR="/var/www/backups"

echo "=== Déploiement CitoyenNote ==="
echo "Date: $(date)"

# Créer le dossier de backup si nécessaire
mkdir -p "$BACKUP_DIR"

echo "[1/8] Pull dernières modifications"
git fetch origin
git reset --hard "origin/$BRANCH"

echo "[2/8] Install Composer (prod)"
composer install --no-dev --optimize-autoloader --no-interaction

echo "[3/8] Clear cache prod"
php bin/console cache:clear --env=prod --no-interaction
php bin/console cache:warmup --env=prod --no-interaction

echo "[4/8] Backup base de données"
BACKUP_FILE="$BACKUP_DIR/db_backup_$(date +%Y%m%d_%H%M%S).sql"
php bin/console doctrine:database:dump --env=prod > "$BACKUP_FILE" 2>/dev/null || {
    echo "⚠ Backup via doctrine échoué, tentative pg_dump..."
    pg_dump --no-password -f "$BACKUP_FILE" 2>/dev/null || echo "⚠ Backup échoué — migration sans backup"
}

echo "[5/8] Migrations Doctrine"
php bin/console doctrine:migrations:migrate --no-interaction --env=prod

echo "[6/8] Build frontend"
cd cityavis-front
npm ci
npm run build
cd ..

echo "[7/8] Fix permissions"
sudo chown -R folken:www-data "$APP_DIR"
sudo chown -R folken:www-data "$FRONT_DIR"

echo "[8/8] Redémarrage Apache"
sudo systemctl reload "$APACHE_SERVICE"

echo "=== Déploiement terminé avec succès ==="
