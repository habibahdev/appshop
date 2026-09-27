# AppShop

Plateforme e-commerce développée avec **Symfony 7** et **Tailwind CSS** - catalogue produits avec variantes, panier, paiement Stripe, gestion de stock tracée, avis clients et back-office d'administration.

![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?logo=php&logoColor=white)
![Symfony](https://img.shields.io/badge/Symfony-7.4-000000?logo=symfony&logoColor=white)
![Tailwind CSS](https://img.shields.io/badge/Tailwind%20CSS-v4-06B6D4?logo=tailwindcss&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.4-4479A1?logo=mysql&logoColor=white)
![License](https://img.shields.io/badge/license-MIT-blue)

---

## Sommaire

- [Objectif](#objectif)
- [Fonctionnalités principales](#fonctionnalités-principales)
- [Documentation](#documentation)

---

## Objectif

AppShop a été conçu pour couvrir, l'ensemble du cycle de vie d'une boutique en ligne : de la présentation du
cataloque jusqu'au paiement et au suivi de commandes, en passant par la gestion du stock et l'administration.

L'objectif était double :
- construire une base e-commerce réaliste et complète
- appliqer des pratiques de production sur l'ensemble de la chaîne

## Fonctionnalités principales

**Catalogue**
- Produits avec déclinaisons (couleur, taille) via un modèle d'attributs extensible
- Catégories avec sous-catégories
- Recherche, tri, filtrage par catégorie, pagination

**Panier & Commande**
- Panier persistant en session
- Codes promo
- Sélection d'une adresse de livraison enregistrée ou saisie libre
- Paiement via Stripe
- Suivi de commande
- Génération automatique de factures PDF

**Stock**
- Gestion de stock séparée au catalogue
- Seuil d'alert
- Historique complet des mouvements (entrées, sorties, ventes, retours)

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

## Documentation

La documentation d'installation et d'utilisation est disponible [ici](docs/index.md).

---

## Licence

Projet conçu et développé par [Habibah](https://github.com/habibahdev) - MIT