# AppShop - Documentation

## Sommaire

- [Stack technique](#stack-technique)
- [Architecture](#architecture)
- [Installation](#installation)
- [Tests](#tests)
- [Qualité de code](#qualité-de-code)
- [CI/CD](#cicd)
- [Structure du projet](#structure-du-projet)
- [Dépannage](#dépannage)

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
| Qualité | PHPStan, PHP_CodeSniffer |

---

## Architecture

- **Modèle produit/variantes séparée** : `Product` porte le contenu éditorial, `ProductVariant` porte le prix et est l'unité réellement vendue - permet des déclinaisons couleur/taille sans duplication de fichie produit.
- **Attributs en EAV** (`ProductAttribute`/`ProductAttributeValue`) : ajout de nouveaux types d'attributs sans migration de schéma.
- **Stock tracé** : toute variation de quatnité passe par `StockService`, qui journalise systématiquement un `StockMovement` - jamais de modification directe de la quantité en base.
- **Commandes pilotées par une machine à états** : le statut d'une `Purchase` (Symfony Workflow) empêche les transitions invalides (ex. passer directement de "en attente" à "livrée").
- **Paiement découplé** : la confirmation Stripe passe par un webhook traité de façon asynchrone (Symfony Messenger), qui déclenche à son trour la génération de facture - le paiement synchrone ne bloque jamais l'utilisateur.
- **Contrôle d'accès par Voters** : chaque commande et chaque adresse vérifie explicitement son propriétaire via un Voter Sumfony dédié.

---

## Installation

### Pré-requis

* PHP >= 8.2
* Extensions :
    * `pdo_mysql`
    * `bcmath`
    * `opcache`
    * `ctype`
    * `mbstring`
    * `pdo`
* MySQL 8.4
* [Symfony CLI](https://symfony.com/download)
* Compte [Stripe de test](stripe)

### Étapes

```bash
git clone git@github.com:habibahdev/appshop.git
cd appshop
composer install
```

Copie `.env` en `.env.local` et adapte `DATABASE_URL` à ta base MySQL locale:

```bash
DATABASE_URL="mysql://<utilisateur>:<mot-de-passe>@127.0.0.1:3306/appshop?serverVersion=8.0.32&charset=utf8mb4"
```

Renseigne également, dans le fichier `.env.local` les clés Stripe (`STRIPE_SECRET_KEY`, `STRIPE_WEBHOOK_SECRET`) si
tu veux tester le tunnel de paiement.

Crée la base et charge les données de test :
```bash
symfony console doctrine:database:create
symfony console doctrine:migrations:migrate -n ou d:m:m -n
symfony console doctrine:fixtures:load -n
```

Lance le serveur :
```bash
# Pour ne pas voir les logs
symfony serve -d

# Pour voir les logs
symfony server:start

# ou, sans Symfony CLI
php -S 127.0.0.1:8000 -t public
```
L'application est accessible sur **http://localhost:8000**, l'administration sur **http://localhost:8000/admin**.

Comptes de test (fixtures):

| Rôle | Email | Mot de passe |
|---|---|---|
| Administrateur | `admin@appshop.test` | `password` |
| Client | `client@appshop.test` | `password` |

---

## Tests

```bash
vendor/bin/phpunit
```

Les tests unitaires couvrent la logique métier isolable (calculs de panier, validation de coupons, mouvements de stock)
via des mocks. Les tests fonctionnels vérifient le parcours HTTP de bout en bout sur une base de données dédiée (`.env.test`).

---

## Qualité de code

```bash
# Analyse statique
vendor/bin/phpstan analyse

# Style de code
vendor/bin/phpcs

# Lint des templates et de la configuration
php bin/console lint:twig ./templates
php bin/console lint:yaml ./config --parse-tags
php bin/console lint:container
```

---

## CI/CD

La pipeline Github Actions (`.github/workflows/ci.yaml`) s'exécute à chaque pull request vers `develop` : 

1. **Quality** - lint (Twig, YAML, container), style de code, analyse statique
2. **Test** - PHPUnit

---

## Structure du projet

```
src/
├── Entity/             Modèle de données
├── Enum/               États et types
├── Repository/         Accès aux données, filtres, pagination, cache
├── Service/            Logique métier
├── Controller/         Contrôleurs
├── Form/               Formulaires
├── Security/Voter/     Contrôle d'accès (commandes, adresses)
├── EventListener/      Invalidation de cache
├── EventSubscriber/    Garde-fous 
├── Message/            Messages asynchrones
├── MessageHandler/     Traitement des messages asynchrones
├── Twig/               Fonctions Twig custom
└── Util/               Utilitaires

templates/              Vues twig
tests/                  Tests unitaires et fonctionnels
.github/workflows/      Pipeline CI/CD
```

---

## Dépannage

| Symptôme | Cause probable | Solution |
|---|---|---|
| `SQLSTATE[HY000] [2002]` | MySQL non démarré ou `DATABASE_URL` incorrecte | Vérifie que MySQL tourne et que les identifiants dans `.env.local` sont corrects |
| Extension PHP manquante | Extension non installée | `php -m` pour lister les extensions actives, installe celle qui manque |
| Modifications non prises en compte | Cache Symfony pas vidé | `php bin/console cache:clear` |