# 📚 EbookShare

Plateforme sociale de partage d'ebooks construite avec **Symfony 7.4**. Les utilisateurs peuvent publier des livres au format PDF, interagir via des likes et des commentaires, et gérer leur bibliothèque personnelle.

---

## Table des matières

1. [Aperçu des fonctionnalités](#aperçu-des-fonctionnalités)
2. [Stack technique](#stack-technique)
3. [Prérequis](#prérequis)
4. [Installation](#installation)
5. [Configuration de l'environnement](#configuration-de-lenvironnement)
6. [Base de données](#base-de-données)
7. [Assets (CSS / JS)](#assets-css--js)
8. [Fixtures de développement](#fixtures-de-développement)
9. [Structure du projet](#structure-du-projet)
10. [Routes de l'application](#routes-de-lapplication)
11. [Entités & relations](#entités--relations)
12. [Comptes de test](#comptes-de-test)

---

## Aperçu des fonctionnalités

| Fonctionnalité | Détail |
|---|---|
| **Inscription / Connexion** | Formulaire complet avec vérification de pseudo en temps réel (AJAX) |
| **Fil d'actualité** | Liste paginée de tous les livres publiés, colonnes fixes + centre scrollable |
| **Publication d'un livre** | Titre, description, upload PDF (max 10 Mo) |
| **Like / Unlike** | Toggle AJAX instantané sans rechargement de page |
| **Commentaires** | Affichage de tous les commentaires + envoi AJAX par carte |
| **Mes livres** | Vue personnelle de ses publications avec statistiques |
| **Suppression** | Suppression d'un livre avec confirmation modale + suppression du fichier PDF |
| **Design responsive** | Interface Tailwind CSS v4, thème indigo / violet |

---

## Stack technique

### Backend
| Technologie | Version |
|---|---|
| PHP | ≥ 8.2 |
| Symfony | 7.4 |
| Doctrine ORM | ^3.6 |
| Doctrine Migrations | ^3.7 |
| PostgreSQL | — |
| SymfonyCasts Verify Email | ^1.18 |

### Frontend
| Technologie | Version |
|---|---|
| Tailwind CSS | 4.2.2 |
| Symfony AssetMapper | 7.4 (JS) |
| Symfony Webpack Encore | ^6.0 (CSS uniquement) |
| PostCSS | ^8.5 |

### Dev
| Outil | Version |
|---|---|
| Doctrine Fixtures Bundle | ^4.3 |
| FakerPHP | ^1.24 |
| Symfony Maker Bundle | ^1.67 |
| PHPUnit | ^11.5 |

---

## Prérequis

- PHP **8.2+** avec les extensions `pdo_pgsql`, `ctype`, `iconv`
- **Composer** 2+
- **Node.js** 18+ et **npm**
- **PostgreSQL** en cours d'exécution
- *(Optionnel)* [Symfony CLI](https://symfony.com/download)

---

## Installation

```bash
# 1. Cloner le dépôt
git clone <url-du-repo> ebookshare
cd ebookshare

# 2. Installer les dépendances PHP
composer install

# 3. Installer les dépendances Node
npm install
```

---

## Configuration de l'environnement

Créez un fichier `.env.local` à la racine du projet en copiant `.env` :

```bash
cp .env .env.local
```

Puis éditez les variables suivantes dans `.env.local` :

```dotenv
# Connexion PostgreSQL
DATABASE_URL="postgresql://USER:PASSWORD@127.0.0.1:5432/ebookshare?serverVersion=16&charset=utf8"

# Clé secrète de l'application (générer avec : php bin/console secret:generate-keys)
APP_SECRET=votre_cle_secrete_ici

# Mailer (pour la vérification d'email, optionnel en dev)
MAILER_DSN=null://null
```

> En environnement de développement, `APP_ENV=dev` est déjà défini dans `.env`.

---

## Base de données

```bash
# Créer la base de données
php bin/console doctrine:database:create

# Exécuter toutes les migrations
php bin/console doctrine:migrations:migrate

# Créer le dossier de stockage des PDFs
mkdir -p public/books
```

---

## Assets (CSS / JS)

Le projet utilise une architecture **hybride** :
- **Webpack Encore** compile uniquement le CSS (Tailwind CSS v4 via PostCSS)
- **AssetMapper** gère le JavaScript (Stimulus, Turbo)

```bash
# Développement avec watch (recompile à chaque changement)
npm run watch

# Build de production
npm run build
```

Les fichiers compilés sont générés dans `public/build/`.

---

## Fixtures de développement

Pour peupler la base avec des données de test réalistes :

```bash
php bin/console doctrine:fixtures:load
```

Cela crée :
- **1 administrateur** et **20 utilisateurs** aléatoires
- **30 livres** répartis entre les utilisateurs
- **~130 commentaires** et **~200 likes**

> ⚠️ Cette commande **vide** la base avant d'insérer les données. Ne pas utiliser en production.

---

## Lancer le serveur de développement

```bash
# Avec Symfony CLI (recommandé)
symfony server:start

# Ou avec le serveur PHP intégré
php -S localhost:8000 -t public/
```

Accédez ensuite à [http://localhost:8000](http://localhost:8000).

---

## Structure du projet

```
ebookshare/
├── assets/
│   ├── app.js                  # Point d'entrée JS (AssetMapper)
│   ├── app.webpack.js          # Point d'entrée Webpack (CSS uniquement)
│   ├── styles/
│   │   └── app.css             # @import "tailwindcss"
│   └── controllers/            # Contrôleurs Stimulus
│
├── config/
│   ├── packages/
│   │   ├── security.yaml       # Firewall, rôles, routes protégées
│   │   └── doctrine.yaml       # Configuration ORM
│   └── services.yaml           # books_directory: public/books/
│
├── migrations/                 # Migrations Doctrine
│
├── public/
│   ├── books/                  # PDFs uploadés par les utilisateurs
│   └── build/                  # CSS/JS compilés par Webpack
│
├── src/
│   ├── Controller/
│   │   ├── HomeController.php          # Page d'accueil
│   │   ├── SecurityController.php      # Login / Logout
│   │   ├── RegistrationController.php  # Inscription + check pseudo AJAX
│   │   ├── FeedController.php          # Fil, publication, like, commentaire
│   │   └── BookController.php          # Mes livres, suppression
│   │
│   ├── Entity/
│   │   ├── User.php            # Utilisateur (email unique, pseudo unique)
│   │   ├── Book.php            # Livre (titre, description, filepath PDF)
│   │   ├── Comment.php         # Commentaire (contenu, auteur, livre)
│   │   └── Like.php            # Like (propriétaire, livre)
│   │
│   ├── Form/
│   │   ├── RegistrationFormType.php    # Formulaire d'inscription
│   │   └── BookType.php                # Formulaire de publication
│   │
│   ├── Repository/
│   │   ├── UserRepository.php          # isPseudoTaken, findTakenPseudosLike
│   │   ├── BookRepository.php          # findFeed, countAll, findByOwner
│   │   └── LikeRepository.php          # findOneByOwnerAndBook
│   │
│   └── DataFixtures/
│       └── AppFixtures.php             # Données de test (Faker)
│
├── templates/
│   ├── base.html.twig
│   ├── home/         index.html.twig   # Landing page
│   ├── security/     login.html.twig   # Page de connexion
│   ├── registration/ register.html.twig
│   ├── feed/         index.html.twig   # Fil d'actualité
│   └── book/         my_books.html.twig
│
├── postcss.config.js
├── webpack.config.js
├── compose.yaml                # Docker Compose (PostgreSQL)
└── .env
```

---

## Routes de l'application

| Route | Méthode | URL | Accès |
|---|---|---|---|
| `app_home` | GET | `/` | Public |
| `app_login` | GET/POST | `/login` | Public |
| `app_logout` | ANY | `/logout` | Authentifié |
| `app_register` | GET/POST | `/register` | Public |
| `app_register_check_pseudo` | GET | `/register/check-pseudo` | Public |
| `app_feed` | GET | `/feed` | `ROLE_USER` |
| `app_feed_publish` | POST | `/feed/publish` | `ROLE_USER` |
| `app_feed_like` | POST | `/feed/like/{id}` | `ROLE_USER` |
| `app_feed_comment` | POST | `/feed/comment/{id}` | `ROLE_USER` |
| `app_my_books` | GET | `/my-books` | `ROLE_USER` |
| `app_book_delete` | POST | `/my-books/{id}/delete` | `ROLE_USER` (propriétaire) |

---

## Entités & relations

```
User
 ├── id, firstname, lastname, pseudo (unique), email (unique)
 ├── password, roles[], isVerified
 ├── OneToMany → Book   (books)
 ├── OneToMany → Comment (via author)
 └── OneToMany → Like   (via owner)

Book
 ├── id, title, description, filepath, uploadetat
 ├── ManyToOne → User   (owner)
 ├── OneToMany → Comment (comments, orphanRemoval)
 └── OneToMany → Like   (likes, orphanRemoval)

Comment
 ├── id, content (varchar 255), createdat
 ├── ManyToOne → User   (author)
 └── ManyToOne → Book   (book)

Like  [table: `like`]
 ├── id
 ├── ManyToOne → User   (owner)
 └── ManyToOne → Book   (book)
```

---

## Comptes de test

Après le chargement des fixtures :

| Rôle | Email | Mot de passe |
|---|---|---|
| Administrateur | `admin@ebookshare.com` | `admin1234` |
| Utilisateur lambda | *(généré aléatoirement, visible en base)* | `password` |

---

## Docker (optionnel)

Un fichier `compose.yaml` est fourni pour lancer PostgreSQL rapidement :

```bash
docker compose up -d
```

Vérifiez les identifiants dans `compose.yaml` et adaptez `DATABASE_URL` dans `.env.local` en conséquence.

---

## Licence

Projet personnel — tous droits réservés.
