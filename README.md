# 🔧 Project Manager API

API REST développée avec Symfony 7 et MySQL pour le projet **Project Manager**.

## 🚀 Aperçu

Cette API sert de backend pour l'application React **Project Manager**. Elle gère la persistance des données des projets et des tâches dans une base de données MySQL via Doctrine ORM.

## 🛠️ Technologies utilisées

- **Symfony 7.4** — Framework PHP
- **MySQL** — Base de données relationnelle
- **Doctrine ORM** — Gestion de la base de données
- **API REST** — Architecture de l'API
- **Nelmio CORS Bundle** — Gestion des CORS pour React
- **PHP 8.2** — Langage de programmation

## 📁 Structure du projet

```
src/
├── Controller/
│   ├── ProjectController.php  # Routes API projets
│   └── TaskController.php     # Routes API tâches
├── Entity/
│   ├── Project.php            # Entité projet
│   └── Task.php               # Entité tâche
migrations/
│   └── ...                    # Migrations Doctrine
config/
│   └── packages/
│       └── nelmio_cors.yaml   # Configuration CORS

```

## 📡 Routes API

### Projets

| Méthode | Route                | Description                |
| ------- | -------------------- | -------------------------- |
| GET     | `/api/projects`      | Récupérer tous les projets |
| POST    | `/api/projects`      | Créer un nouveau projet    |
| PUT     | `/api/projects/{id}` | Modifier un projet         |
| DELETE  | `/api/projects/{id}` | Supprimer un projet        |

### Tâches

| Méthode | Route             | Description                 |
| ------- | ----------------- | --------------------------- |
| GET     | `/api/tasks`      | Récupérer toutes les tâches |
| POST    | `/api/tasks`      | Créer une nouvelle tâche    |
| PUT     | `/api/tasks/{id}` | Modifier une tâche          |
| DELETE  | `/api/tasks/{id}` | Supprimer une tâche         |

## ⚙️ Installation et lancement

### Prérequis

- PHP 8.2+
- Composer
- Symfony CLI
- MySQL (XAMPP, WAMP ou Laragon)

### Étapes

**1.** Cloner le repository

```bash
git clone https://github.com/COSTINCIANU/project-manager-api.git
cd project-manager-api
```

**2.** Installer les dépendances

```bash
composer install
```

**3.** Configurer la base de données dans `.env`

### Base de données

DATABASE_URL="mysql://app:!ChangeMe!@127.0.0.1:3306/app?serverVersion=10.11.2-MariaDB&charset=utf8mb4"

**4.** Créer la base de données

```bash
php bin/console doctrine:database:create
```

**5.** Exécuter les migrations

```bash
php bin/console doctrine:migrations:migrate
```

**6.** Lancer le serveur

```bash
symfony server:start
```

**7.** Tester l'API

https://127.0.0.1:8000/api/projects
https://127.0.0.1:8000/api/tasks

## 🔗 Projet Frontend

Ce projet est le backend de l'application React :
👉 [project-manager](https://github.com/COSTINCIANU/project-manager)

## 🎯 Compétences démontrées

- Création d'une API REST avec Symfony
- Gestion de base de données avec Doctrine ORM
- Configuration des CORS pour communication avec React
- Architecture MVC avec Symfony
- Endpoints CRUD complets
- Communication Frontend / Backend

## 👨‍💻 Auteur

**Gheorghina COSTINCIANU**

- GitHub : [@COSTINCIANU](https://github.com/COSTINCIANU)

## 📄 Licence

Ce projet est réalisé dans le cadre d'un dossier professionnel.
