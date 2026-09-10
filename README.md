# AppShop

## Pré-requis

* Docker et Docker compose

## Démarrage

```bash
docker compose -f docker-compose.dev.yaml up
```

Premier démarrage : le conteneur `app` va automatiquement, via `docker/entrypoint.sh`:

1. Installer les dépendances composer
2. Attendre que MySQL soit prêt à accepter des connexions
3. Exécuter les migrations doctrine
4. Charger les fixtures (une seule fois avec `var/.fixtures_loaded` qui évite de les recharger aux démarrages suivants)
5. Démarre le serveur PHP intégré sur le port 8000

L'application est ensuite accessible sur **http://localhost:8000**

Pour lancer en arrière-plan :
```bash
docker compose -f docker-compose.dev.yaml -d
```

## Arrêt de l'environnement
```bash
# Arrête les conteneurs, garde les données (volumes conservés)
docker compose -f dokcer-compose.dev.yaml down

# Arrête et supprime aussi les données MySQL (repart à zéro au prochain up)
docker compose -f docker-compose.dev.yaml down -v
```

## Commande Symfony
Toutes les commandes `php bin/console` doivent s'exécuter **à l'intérieur** du conteneur `app` :
```bash
docker compose -f docker-compose.dev.yaml exec app php bin/console <commande>
```

## Installer un package
```bash
docker compose -f docker-compose.dev.yaml exec app composer require <package>
```

## Ouvrir un shell dans le conteneur
```bash
docker compose -f docker-compose.dev.yaml exec app php sh
```

## Accéder à la base de données
```bash
docker compose -f docker-compose.dev.yaml exec database mysql -uappshop -pappshop appshop
```

## Voir les logs
```bash
# Logs du conteneur app en direct
docker compose -f docker-compose.dev.yaml logs -f app

# Logs de Mysql
docker compose -f docker-compose.dev.yaml logs -f database
```

## Rebuild l'image après modification du Dockerfile
Si modification de `Dockerfile.dev` :
```bash
docker compose -f docker-compose.dev.yaml up -d --build
```

## Réinitialisation complète de l'environnement
En cas de problème :
```bash
docker compose -f docker-compose.dev.yaml down -v
rm -f var/.fixtures_loaded
docker compose -d docker-compose.dev.yaml up -d --build
```
