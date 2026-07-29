# Sidegigs Management System

> Lightweight task & project management for side projects. Kanban board, client CRM, time tracking, configurable workflows — self-hosted with a single binary.

## Features

- **Kanban board** — drag-and-drop tasks across workflow stages (SortableJS + HTMX)
- **Task management** — comments, worklogs, descriptions (Markdown), priorities, assignments
- **Client CRM** — register, search, block/unblock, manage contacts
- **Configurable workflow** — define stages, transitions, and reorder them visually
- **User management** — roles (`ROLE_ADMIN` / `ROLE_USER`), password change, account toggle
- **Authentication** — form login with remember-me, custom security provider
- **PocketBase admin UI** — `/_/` for direct database access
- **Schema migrations** — versioned, deploy-safe PocketBase field migrations

## Tech Stack

| Layer | Choice |
|---|---|
| **Backend** | PHP 8.3, Symfony 7.4 |
| **Architecture** | DDD + CQRS (Symfony Messenger) |
| **Persistence** | PocketBase 0.27 (SQLite + REST API) |
| **Templates** | Twig 3.x |
| **Frontend** | HTMX, Alpine.js, Tailwind CSS, SortableJS |
| **Dev environment** | Docker Compose (PHP-FPM, Nginx, PocketBase, Mailpit) |
| **Production** | Alpine Linux, Nginx, PHP-FPM via Unix socket |

## Quick Start

```sh
make up            # docker compose up -d
make bash          # exec into PHP container
composer install   # install PHP deps
make fixtures      # load dev seed data
```

Then visit `http://localhost:8080` (login: `admin@sidegigs.local` / `admin123`).

PocketBase admin UI at `http://localhost:8090/_/`.

More targets in `Makefile`.

## Architecture

The app is split into four bounded contexts, each with strict DDD layering:

```
Module/           DDD layers
├── Domain/       Aggregates, Value Objects, Repository interfaces, Domain Events
├── Application/  Commands, Queries, Handlers, DTOs
├── Infrastructure/ PocketBase repositories, Security providers, Console commands
└── UI/           Controllers (Symfony), Forms, Twig templates
```

### Modules

| Module | Responsibility |
|---|---|
| **IdentityAccess** | User registration, authentication (form login), roles, profile management |
| **BoardManagement** | Kanban board view, workflow editor (stages, transitions, drag-and-drop) |
| **TaskManagement** | Task CRUD, comments, worklogs, priorities, assignment to users/clients |
| **ClientManagement** | Client CRUD, NIP, settlement type (B2B/Umowa zlecenie/UseMe), contacts |
| **Core** | Shared infrastructure: PocketBase HTTP client, migration system, Markdown rendering |

All communication between modules goes through the **Messenger command/query bus**. Domain events are dispatched and projected for cross-cutting concerns.

## Commands

```sh
# App
php bin/console app:seed-default-workflow    # Create default 4-stage workflow
php bin/console app:fixtures:load            # Load dev data (users, clients, tasks)
php bin/console app:admin:create-user        # Create an admin user

# PocketBase migrations
php bin/console app:pocketbase:migrate                  # Run pending migrations
php bin/console app:pocketbase:generate-migration        # Scaffold a new migration class
```

## Deployment

Production is deployed via SSH to an Alpine Linux server:

```sh
# One-time server setup
deploy/setup-server.sh <ip>

# Configure inventory
cp deploy/inventory.example deploy/inventory
# edit REMOTE_HOST, APP_SECRET, etc.

# Deploy
deploy/deploy.sh            # code + migrations
deploy/deploy.sh --seed     # also load seed data

# Optional: replace password auth with a revocable token
deploy/setup-pb-token.sh    # saves token to /etc/sms/.pocketbase_token
# then remove POCKETBASE_ADMIN_PASSWORD from inventory
```

The deploy script:
1. Rsyncs code (excludes `docker/`, `vendor/`, `var/`, etc.)
2. Runs `composer install --no-dev`
3. Generates `.env` from template
4. Sets up Nginx, PocketBase, logrotate, permissions
5. Warms Symfony cache
6. Runs PocketBase schema migrations

## Tests

```sh
make test                      # all tests
php bin/phpunit                # same
php bin/phpunit --testsuite=unit    # unit only
php bin/phpunit --testsuite=integration --group=integration  # integration (needs PocketBase)
```

- **57+ test files** across all 4 modules + core
- **Unit tests** use in-memory repository implementations (no mocking framework)
- **Integration tests** run against a real PocketBase instance

## Project Structure

```
├── app/                    Symfony application
│   ├── config/             YAML configuration
│   ├── src/                Domain-driven code (4 modules)
│   ├── templates/          Twig views
│   ├── tests/              Unit + integration tests
│   ├── public/             Web root (index.php)
│   └── var/                Cache, logs
├── docker/                 Docker build files
│   ├── php/                PHP 8.3-FPM Dockerfile
│   ├── nginx/              Nginx config
│   └── pocketbase/         PocketBase 0.27 Dockerfile + init.sh
├── deploy/                 Production deployment
│   ├── deploy.sh           Main deployment script
│   ├── setup-server.sh     Alpine server bootstrap
│   ├── setup-pb-token.sh   PocketBase token setup
│   └── templates/          Nginx, logrotate, .env templates
├── docker-compose.yml      Dev environment (4 services)
└── Makefile                Automation targets
```

## License

MIT
