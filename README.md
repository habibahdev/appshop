# AppShop

Plateforme e-commerce développée avec **Symfony 7** et **Tailwind CSS** - catalogue produits avec variantes, panier, paiement Stripe, gestion de stock tracée, avis clients et back-office d'administration.

![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?logo=php&logoColor=white)
![Symfony](https://img.shields.io/badge/Symfony-7.4-000000?logo=symfony&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.4-4479A1?logo=mysql&logoColor=white)
![Docker](https://img.shields.io/badge/Docker-ready-2496ED?logo=docker&logoColor=white)
![License](https://img.shields.io/badge/license-MIT-blue)

---

## Sommaire

- [Fonctionnalités](#fonctionnalités)
- [Stack technique](#stack-technique)
- [Architecture](#architecture)
- [Démarrage](#démarrage)
- [Coder en développement](#coder-en-développement)
- [Tests](#tests)
- [Qualité de code](#qualité-de-code)
- [CI/CD](#cicd)
- [Structure du projet](#structure-du-projet)

---

## Fonctionnalités

**Catalogue**
- Produits avec déclinaisons (couleur, taille) via un modèle d'attributs extensible
- Catégories hiérarchiques avec sous-catégories
- Recherche, tri, filtrage par catégorie, pagination
- Diaporama photo sur la fiche produit
- Cache applicatif taggé avec invalidation automatique

**Panier & Commande**
- Panier persistant en session, quantités plafonnées au stock disponible
- Codes promo
- Sélection d'une adresse de livraison enregistrée ou saisie libre
- Paiement sécurisé via Stripe
- Suivi de commande piloté par une machine à état (Symfony Workflow)
- Génération automatique de factures PDF

**Stock**
- Gestion de stock séparée au catalogue, avec seuil d'alert
- Historique complet des mouvements (entrées, sorties, ventes, retours) pour une traçabilité totale

**Compte client**
- Inscription avec vérification d'email
- Connexion avec option "rester connecté"
- Réinitialisation de mot de passe
- Profil : coordonnées, téléphone, mot de passe, carnet d'adresses
- Historique des commandes

**Avis produits**
- Notation et commentaires, modération avant publication
- Badge "achat vérifié"

**Administration**
- Gestion du catalogue, des commandes, des coupons, des avis, des clients
- Notifications de nouvelles commandes dans le menu

---

## Stack technique

| Domaine | Technologies |
|---|---|
| Backend | PHP 8.3, Symfony 7.4 |
| Composants Symfony | Doctrine, Workflow, Messenger, Security Voters, Mailer |
| Frontend | Twig, Tailwind CSS 4 |
| Base de données | MySQL 8.4 |
| Paiement | API Stripe |
| PDF | Dompdf |
| Administration | EasyAdmin 4 |
| Tests | PHPUnit |
| Sualité | PHPStan, PHP_CodeSniffer |
| Infrastructure | Docker, Github Actions |

---

## Architecture

- **Modèle produit/variantes séparée** : `Product` porte le contenu éditorial, `ProductVariant` porte le prix et est l'unité réellement vendue - permet des déclinaisons couleur/taille sans duplication de fichie produit.
- **Attributs en EAV** (`ProductAttribute`/`ProductAttributeValue`) : ajout de nouveaux types d'attributs sans migration de schéma.
- **Stock tracé** : toute variation de quatnité passe par `StockService`, qui journalise systématiquement un `StockMovement` - jamais de modification directe de la quantité en base.
- **Commandes pilotées par une machine à états** : le statut d'une `Purchase` (Symfony Workflow) empêche les transitions invalides (ex. passer directement de "en attente" à "livrée").
- **Paiement découplé** : la confirmation Stripe passe par un webhook traité de façon asynchrone (Symfony Messenger), qui déclenche à son trour la génération de facture - le paiement synchrone ne bloque jamais l'utilisateur.
- **Contrôle d'accès par Voters** : chaque commande et chaque adresse vérifie explicitement son propriétaire via un Voter Sumfony dédié.

---

## Démarrage

### Pré-requis

* Docker et Docker compose

### Installation

```bash
git clone 
cd appshop
docker compose -f docker-compose.dev.yaml up
```

Au premier démarrage, le conteneur installe automatiquement les dépendances, exécute les migrations et charge les fixtures.

L'application est accessible sur **http://localhost:8000**.

Comptes de test (fixtures):

| Rôle | Email | Mot de passe |
|---|---|---|
| Administrateur | `admin@appshop.test` | `password` |
| Client | `client@appshop.test` | `password` |

L'administration est accessible sur **http://localhost:8000/admin***.

### Arrêter l'environnement

```bash
# Conserver les données
docker compose -f docker-compose.dev.yaml down

# Repartir de zéro
docker compose -f docker-compose.dev.yaml down -v
```

---

## Coder en développement

Le code source est monté en volume - toute modification est prise en compte immédiatement, sans rebuild.

```bash
# Commandes Symfony
docker compose -f docker-compose.dev.yaml exec app php bin/console <commande>

# Installer un packaqge
docker compose -f docker-compose.dev.yaml exec app composer require <package>

# Shell dans le conteneur
docker compose -f docker-compose.dev.yaml exec app sh

# Log
docker compose -f docker-compose.dev.yaml logs -f app
```

### Accès à la base de données

```bash
docker compose -f docker-compose.dev.yaml exec database mysql -uappshop -pappshop appshop
```

---

## Tests

```bash
composer compose -f docker-compose.dev.yaml exec php vendor/bin/phpunit
```

Les tests unitaires couvrent la logique métier isolable (calculs de panier, validation de coupons, mouvements de stock) via des mocks. Les tests fonctionnels vérifient les parcours HTTP de bout en bout sur une base de données dédiée.

---

## Qualité de code

```bash
# Analyse statique
docker compose -f docker-compose.dev.yaml exec app vendor/bin/phpstan

# Style de code
docker compose -f docker-compose.dev.yaml exec app vendor/bin/phpcs

# Lint des templates et de la configuration
docker compose -f docker-compose.dev.yaml exec app phpbin/console lint:twig ./templates
docker compose -f docker-compose.dev.yaml exec app phpbin/console lint:yaml ./config --parse-tags
docker compose -f docker-compose.dev.yaml exec app phpbin/console lint:container
```

---

## CI/CD

La pipeline Github Actions (`.github/workflows/ci.yaml`) s'exécute à chaque push et pull request vers `develop` : 

1. **Quality** - lint (Twig, YAML, container), style de code, analyse statique
2. **Test** - suite PHPUnit
3. **Build** *(push sur `develop` uniquement)* - construction de l'image Docker et publication sur Docker Hub, taguée `latest` et par SHA de commit

---

## Structure du projet

```
src/
├── Entity/
├── Enum/
├── Repository/
├── Service/
├── Controller/
├── Form/
├── Security/Voter/
├── EventListener/
├── EventSubscriber/
├── Message/
├── MessageHandler/
├── Twig/
└── Util/

templates/
tests/
docker/
```

---

## Licence

MIT