# Architecture Decision Record (ADR)

## ADR-009: Migrate from MongoDB + Redis to PocketBase

**Status:** Accepted  
**Date:** 2026-06-08  

## 1. Context

The Sidegigs Management System currently uses:

- **MongoDB 7** as the primary database (via Doctrine MongoDB ODM 5.x)
- **Redis 7** as a caching/queue layer (provisioned but never actually used)

While MongoDB was a reasonable choice for a document-oriented schema, it introduces operational complexity for a single-developer side project:

- Requires a separate `mongodb` PHP extension (`ext-mongodb`) installed via PECL
- Requires the `doctrine/mongodb-odm-bundle` and its dependency chain
- MongoDB's `init.js` script and replica-set considerations add boilerplate
- Redis is provisioned but unused — it's dead weight in `docker-compose.yml`

### PocketBase Alternative

[PocketBase](https://pocketbase.io/) is a single Go binary that provides:

- Embedded SQLite database (zero-config, file-based)
- Built-in Admin UI at `/_/`
- REST API with filtering, sorting, and pagination
- File storage
- Real-time subscriptions (SSE)
- No runtime dependencies (no JVM, no interpreter)

For a side project, this means:

- One less Docker container to manage (SQLite eliminates the database server)
- No PHP extensions to install
- Simpler `docker-compose.yml`
- Faster startup times
- Admin UI for ad-hoc data inspection

## 2. Decision

Replace MongoDB as the primary data store with PocketBase, and remove the unused Redis service.

### 2.1 Collections

PocketBase collections map 1:1 from the existing MongoDB documents:

| MongoDB Collection | PocketBase Collection | Notes |
|---|---|---|
| `users` | `users` | No PocketBase auth; custom password hashing kept |
| `clients` | `clients` | Embedded `contacts` → JSON field |
| `workflows` | `workflows` | Embedded `stages` + `transitions` → JSON fields |
| `tasks` | `tasks` | Embedded `comments` + `worklogs` → JSON fields |
| `task_events` | `task_events` | Audit log |

Embedded arrays (contacts, stages, transitions, comments, worklogs) are stored in PocketBase **JSON fields**, preserving the document-like schema without normalizing into separate collections.

### 2.2 Authentication Strategy

PocketBase has built-in auth (email/password, OAuth2, JWT tokens), but the application already has a working Symfony Security layer with form login, password hashing via `UserPasswordHasherInterface`, and role-based access control (`ROLE_ADMIN` / `ROLE_USER`).

To minimize changes, **PocketBase auth will NOT be used**. Users are stored in a regular PocketBase collection with the password already hashed by Symfony's password hasher. The existing `UserIdentityProvider` and `UserIdentity` classes remain unchanged — only the `UserRepository` is swapped.

### 2.3 Architecture Change

```
Before:                          After:
Controller → Handler            Controller → Handler
    → RepositoryInterface           → RepositoryInterface
        → Doctrine MongoDB ODM          → PocketBase HTTP API
            → MongoDB                       → PocketBase (SQLite)
```

The change is confined entirely to the **Infrastructure layer**. Domain models, value objects, domain events, CQRS handlers, controllers, and templates are untouched.

### 2.4 HTTP Client

Communication with PocketBase uses `symfony/http-client` (native Symfony HTTP client) instead of a third-party library like Guzzle.

## 3. PocketBase Service Setup

PocketBase runs as a dedicated Docker container, separate from the PHP application:

- **Port:** 8090 (no Nginx reverse proxy)
- **Data directory:** `/pb_data` (persistent Docker volume)
- **Admin UI:** Available at `http://localhost:8090/_/`
- **API:** Available at `http://pocketbase:8090/api/` (internal Docker network)

### 3.1 Collection Initialization

Collections are created at container startup via a shell script that calls the PocketBase Admin API after the superuser is created:

```bash
PB_ADMIN_TOKEN=$(curl -s -X POST http://localhost:8090/api/admins/auth-with-password \
  -H "Content-Type: application/json" \
  -d '{"identity":"admin@sidegigs.local","password":"admin123"}' \
  | jq -r '.token')

curl -X POST http://localhost:8090/api/collections \
  -H "Authorization: $PB_ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{... collection schema ...}'
```

## 4. Files Changed

### 4.1 New Files

| File | Purpose |
|---|---|
| `docker/pocketbase/Dockerfile` | PocketBase binary + init script |
| `docker/pocketbase/init.sh` | Create collections via Admin API |
| `src/Core/Infrastructure/PocketBase/PocketBaseClient.php` | HTTP client wrapper for PocketBase |
| `src/IdentityAccess/Infrastructure/PocketBase/UserRepository.php` | User repository using PocketBase |
| `src/ClientManagement/Infrastructure/PocketBase/ClientRepository.php` | Client repository using PocketBase |
| `src/BoardManagement/Infrastructure/PocketBase/WorkflowRepository.php` | Workflow repository using PocketBase |
| `src/TaskManagement/Infrastructure/PocketBase/TaskRepository.php` | Task repository using PocketBase |
| `src/TaskManagement/Infrastructure/PocketBase/TaskEventStore.php` | Event store using PocketBase |

### 4.2 Modified Files

| File | Change |
|---|---|
| `docker-compose.yml` | Add `pocketbase` service, remove `mongodb` + `redis` + volumes |
| `docker/php/Dockerfile` | Remove `pecl install mongodb redis` |
| `app/composer.json` | Remove `doctrine/mongodb-odm-bundle`, add `symfony/http-client` |
| `app/config/bundles.php` | Remove Doctrine MongoDB + Doctrine ORM + Doctrine Fixtures |
| `app/config/services.yaml` | Change repository bindings, add PocketBaseClient, remove redis_client |
| `app/config/packages/messenger.yaml` | Remove unused `async` transport |
| `app/.env.dev` | Add `POCKETBASE_URL` |
| `app/.env.test` | Add `POCKETBASE_URL` |
| `Makefile` | Update `migrate` and `fixtures` targets |

### 4.3 Deleted Files

| File | Reason |
|---|---|
| `docker/mongodb/init.js` | MongoDB-specific init |
| `app/config/packages/doctrine_mongodb.yaml` | MongoDB ODM config |
| `app/config/packages/doctrine.yaml` | Unused ORM config |
| `app/config/packages/cache.yaml` | Redis references only |
| `src/IdentityAccess/Infrastructure/Doctrine/UserDocument.php` | Replaced by PocketBase |
| `src/ClientManagement/Infrastructure/Doctrine/ClientDocument.php` | Replaced by PocketBase |
| `src/BoardManagement/Infrastructure/Doctrine/WorkflowDocument.php` | Replaced by PocketBase |
| `src/TaskManagement/Infrastructure/Doctrine/TaskDocument.php` | Replaced by PocketBase |
| `src/TaskManagement/Infrastructure/Doctrine/TaskEventDocument.php` | Replaced by PocketBase |
| `src/IdentityAccess/Infrastructure/Doctrine/UserRepository.php` | Replaced by PocketBase |
| `src/ClientManagement/Infrastructure/Doctrine/ClientRepository.php` | Replaced by PocketBase |
| `src/BoardManagement/Infrastructure/Doctrine/WorkflowRepository.php` | Replaced by PocketBase |
| `src/TaskManagement/Infrastructure/Doctrine/TaskRepository.php` | Replaced by PocketBase |
| `src/TaskManagement/Infrastructure/Doctrine/TaskEventStore.php` | Replaced by PocketBase |

## 5. Consequences

### Positive

- **Simpler infrastructure:** One less database server, no PHP extension compilation
- **Faster container builds:** No PECL step for mongodb/redis
- **Lower memory usage:** SQLite runs in-process in PocketBase
- **Built-in Admin UI:** Inspect and edit data without a separate database client
- **Easier backups:** Single PocketBase data directory to snapshot
- **Unused code removed:** Redis, Doctrine ORM, Doctrine MongoDB all cleaned out

### Risks

- **SQLite concurrency:** SQLite is less suited for high-write workloads, but this is a single-user side project — the write volume is negligible
- **PocketBase API rate limits:** Not applicable for local Docker usage
- **Breaking change:** All existing data in MongoDB volumes will be orphaned (intentional — new branch, no migration)
- **Learning curve:** PocketBase API differs from Doctrine's query builder

## 6. Testing Strategy

### 6.1 Existing Tests (Unaffected)

All 17 existing unit tests use **InMemoryRepository** implementations and test domain logic only. They continue to pass without modification:

```
tests/Unit/IdentityAccess/Domain/Model/UserTest.php
tests/Unit/BoardManagement/Domain/Model/WorkflowTest.php
tests/Unit/ClientManagement/Domain/Model/ClientTest.php
tests/Unit/TaskManagement/Domain/Model/TaskTest.php
... (13 more)
```

### 6.2 New Tests

New integration tests verify that the PocketBase repository implementations correctly serialize/deserialize domain objects:

- `PocketBaseUserRepositoryTest` — save/findById/findByEmail/findByUsername/searchByTerm
- `PocketBaseClientRepositoryTest` — save/findById/findByNip/searchByTerm
- `PocketBaseWorkflowRepositoryTest` — save/findById/findDefault
- `PocketBaseTaskRepositoryTest` — save/findById/findByStage/findByClient/findByAssignee
- `PocketBaseTaskEventStoreTest` — append/findByTask

These tests require a running PocketBase instance and are marked as `@group integration`.
