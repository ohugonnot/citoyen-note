# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

CitoyenNote — plateforme citoyenne d'évaluation des services publics locaux. Monorepo avec backend Symfony 6.4 (API REST) et frontend Vue.js 3 dans `cityavis-front/`.

## Tech Stack

- **Backend**: PHP 8.1+, Symfony 6.4 LTS, Doctrine ORM 3, PostgreSQL 16 + PostGIS
- **Auth**: JWT (lexik/jwt-authentication-bundle) + refresh tokens (gesdinet)
- **Frontend**: Vue.js 3, PrimeVue 4, Pinia, Vite, Leaflet (maps), Bootstrap 5
- **Dev infra**: Docker Compose (PostgreSQL + Mailpit), Symfony CLI

## Commands

### Backend (root directory)
```bash
# Install deps
composer install

# Dev server
symfony server:start

# Database
php bin/console doctrine:migrations:migrate
php bin/console doctrine:migrations:diff    # generate migration from entity changes

# Tests
php bin/console --env=test doctrine:database:create  # first time
./vendor/bin/phpunit                                  # all tests
./vendor/bin/phpunit tests/path/ToTest.php            # single file
./vendor/bin/phpunit --filter testMethodName           # single test

# Cache clear
php bin/console cache:clear

# Data import commands
php bin/console app:import-services          # from CSV
php bin/console app:import-services-datagov  # from data.gouv.fr
php bin/console app:update-coordinates       # geocoding
```

### Frontend (`cityavis-front/`)
```bash
npm install
npm run dev      # Vite dev server
npm run build    # production build
npm run lint     # ESLint --fix
npm run format   # Prettier
```

### Docker
```bash
docker compose up -d   # PostgreSQL + Mailpit
```

## Architecture

### Backend (`src/`)

Pattern: **Controller → Service (Manager) → Repository**, avec DTOs pour validation entrée/sortie et Helpers pour sérialisation JSON.

- `Controller/Admin/` — endpoints admin (CRUD complet: User, ServicePublic, Evaluation, CategorieService)
- `Controller/Public/` — endpoints publics (lecture seule: services, catégories)
- `Controller/AuthController.php` — login/register/refresh
- `Service/*Manager.php` — logique métier (UserManager, ServicePublicManager, EvaluationManager, CategorieServiceManager)
- `Service/GeolocationService.php` — géocodage adresses via API externe
- `Service/SearchNormalizer.php` — normalisation recherche texte
- `Entity/` — 5 entités Doctrine: User, ServicePublic, Evaluation, CategorieService, RefreshToken
- `Entity/Traits/TimestampableTrait.php` — createdAt/updatedAt automatiques
- `Enum/` — StatutUser, StatutService, StatutEvaluation
- `Dto/` — Create/Update/Filter DTOs par entité
- `Helper/*JsonHelper.php` — transformation entité → JSON response
- `Repository/` — requêtes Doctrine custom
- `Command/` — import données CSV et data.gouv, géocodage batch

### Frontend (`cityavis-front/src/`)

Vue.js 3 SPA avec vue-router, Pinia stores, composants PrimeVue, cartes Leaflet.

### Config notable
- `config/packages/security.yaml` — firewalls JWT, routes publiques
- `config/packages/nelmio_cors.yaml` — CORS pour le frontend SPA
- `config/packages/lexik_jwt_authentication.yaml` — config JWT
- `migrations/` — Doctrine migrations (PostgreSQL)
