#!/bin/bash

set -e

APP_DIR="/var/www/cityavis-backend"
FRONT_DIR="/var/www/cityavis-backend/cityavis-front"
GIT_REPO="https://github.com/ohugonnot/citoyen-note.git"
BRANCH="main"
APACHE_SERVICE="apache2"


source .env
echo "Pull dernières modifs"
git fetch origin
git reset --hard origin/$BRANCH

echo "Install Composer (prod)"
#composer install --no-dev --optimize-autoloader
composer install --optimize-autoloader

echo "Clear cache prod"
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod

echo "Migrations doctrine"
php bin/console doctrine:migrations:migrate --no-interaction

echo "Fix permissions backend"
sudo chown -R folken:www-data $APP_DIR
#find $APP_DIR/var -type d -exec sudo chmod 775 {} \;
#find $APP_DIR/var -type f -exec sudo chmod 664 {} \;

echo "Import des Services publiques"
php bin/console app:import-services ./src/Csv/services.csv

echo "Build frontend"
cd cityavis-front
npm install
npm run build

echo "Fix permissions frontend"
sudo chown -R folken:www-data $FRONT_DIR

echo "Redémarrage Apache"
sudo systemctl reload $APACHE_SERVICE
cd ..

echo "=== Déploiement terminé avec succès ==="
