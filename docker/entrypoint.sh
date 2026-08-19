#!/bin/sh

set -e

echo "======================================"
echo " AppShop - Initialisation"
echo "======================================"

echo "Installation des dépendances..."
composer install --no-interaction

echo "Attente de MySQL..."

until php bin/console dbal:run-sql "SELECT 1" >/dev/null 2>&1
do
    sleep 2
done

echo "MySQL est disponible."

echo "Exécution des migrations..."
php bin/console doctrine:migrations:migrate --no-interaction

echo "Vérification des fixtures..."

if [ ! -f var/.fixtures_loaded ]; then
    echo "Chargement des fixtures..."
    php bin/console doctrine:fixtures:load --no-interaction
    touch var/.fixtures_loaded
    echo "Fixtures chargées."
else
    echo "Fixtures déjà chargées."
fi

echo "Démarrage de Symfony..."

exec php -S 0.0.0.0:8000 -t public