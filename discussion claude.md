Je vais vous aider à élaborer ce projet de site de gestion de stock. Je vois que vous avez besoin de respecter les exigences du projet B2 en développement logiciel et bases de données d'Ynov Campus, tout en intégrant des fonctionnalités spécifiques pour votre application de gestion de stock.
Analyse du projet de site de stock
Votre projet semble correspondre à un projet personnel dans le cadre académique décrit. Vérifions d'abord si les exigences minimales du projet personnel sont satisfaites:

Communication entre deux logiciels (6 points) ✓

Vous prévoyez une machine PC (client) et une machine serveur
Serveur web Apache pour la communication


Lien avec une base de données (min. 3 tables) (3 points) ✓

Base de données SQL prévue


Algorithme avancé (3 points) ✓

Utilisation de Faker pour la génération de données peut être considérée comme un algorithme de génération


Interaction utilisateur (2 points) ✓

Interface web avec formulaires et boutons


CRUD sur au moins une donnée (4 points) ✓

Vous mentionnez explicitement des règles CRUD pour admin et client



Architecture proposée
Pour répondre à vos besoins, je vous propose l'architecture suivante:
Backend

Langage: PHP ou Python (selon votre préférence)
Framework:

PHP: Laravel ou Symfony (recommandé pour faciliter la gestion des authentifications)
Python: Django ou Flask


Base de données: MySQL ou PostgreSQL
Serveur: Apache sur une VM Linux

Frontend

HTML/CSS/JavaScript
Framework: Bootstrap ou Tailwind CSS pour une interface responsive
Bibliothèque JavaScript: jQuery ou React selon la complexité souhaitée

Communication

API REST entre le frontend et le backend
Protocole HTTPS pour la sécurité

Modèle de données (minimum 3 tables)

Users:

id (PK)
username
email
password (hashed)
role (admin/client)
otp_secret (pour l'authentification à deux facteurs)
created_at
updated_at


Products:

id (PK)
name
description
price
quantity
category_id (FK)
image_url
created_at
updated_at


Categories:

id (PK)
name
description
created_at
updated_at


Orders:

id (PK)
user_id (FK)
total_amount
status
created_at
updated_at


OrderItems:

id (PK)
order_id (FK)
product_id (FK)
quantity
price_at_order
created_at
updated_at



Fonctionnalités principales
Authentification

Système de login sécurisé
Double authentification (OTP) pour les administrateurs
Gestion des sessions

Administration (CRUD complet)

Gestion des produits (ajout, modification, suppression)
Gestion des catégories
Gestion des utilisateurs
Visualisation des commandes
Tableau de bord avec statistiques

Client (CRUD partiel)

Consultation du catalogue de produits
Recherche et filtrage des produits
Gestion du panier
Passage de commandes
Consultation de l'historique des commandes

Génération de données

Utilisation de Faker pour générer:

Des produits fictifs
Des utilisateurs de test
Des catégories
Des commandes historiques pour démonstration



Étapes de mise en place

Configuration de l'environnement:

Mise en place de la machine serveur (VM Linux)
Installation d'Apache, PHP/Python et MySQL/PostgreSQL
Configuration des droits d'accès


Développement de la base de données:

Création du schéma
Mise en place des migrations
Configuration des relations et contraintes


Développement du backend:

Création des modèles
Implémentation des contrôleurs
Configuration de l'authentification et OTP
Mise en place des API


Développement du frontend:

Création des templates/vues
Intégration avec le backend
Mise en place des formulaires et validations


Tests et déploiement:

Tests unitaires et fonctionnels
Déploiement sur le serveur



Aspect sécurité

Hashage des mots de passe
Protection contre les injections SQL
Validation des entrées utilisateur
Authentification à deux facteurs (OTP) pour les administrateurs
Sessions sécurisées
Protection CSRF

Pour atteindre ou dépasser les 28 points minimums requis, ce projet répond aux exigences de base (18 points) et y ajoute:

Système d'authentification avancé avec OTP (+3 points)
Système de commandes et panier (+3 points)
Recherche et filtrage avancés (+2 points)
Tableau de bord avec statistiques (+2 points)
