#!/bin/sh

set -e

echo "Installation des dépendances Composer..."
composer install --no-interaction

echo "Attente de MySQL..."

until php -r '
try {
    $pdo = new PDO(
        "mysql:host=database;port=3306;dbname=appshop",
        "appshop",
        "appshop"
    );
    $pdo->query("SELECT 1");
    exit(0);
} catch (Throwable $e) {
    exit(1);
}
'
do
    sleep 2
done

echo "MySQL est disponible."

echo "Exécution des migrations..."

php bin/console doctrine:migrations:migrate --no-interaction

if [ ! -f var/.fixtures_loaded ]; then
    echo "Chargement des fixtures..."

    php bin/console doctrine:fixtures:load --no-interaction

    touch var/.fixtures_loaded

    echo "Fixtures chargées."
else
    echo "Fixtures déjà chargées."
fi

echo "Compilation des assets..."

php bin/console asset-map:compile

echo "Démarrage de Symfony..."

exec php -S 0.0.0.0:8000 -t public