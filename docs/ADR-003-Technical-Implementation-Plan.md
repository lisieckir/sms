# Architecture Decision Record (ADR)

## ADR-003: Technical Implementation Plan

**Status:** Proposed
**Date:** 2026-06-07

## 1. Context

The technology stack and architecture are finalized (ADR-001, ADR-002). The project repo is empty. This ADR defines a concrete, step-by-step implementation plan with file structure, command examples, and dependency details.

## 2. Project Bootstrap

### 2.1 Directory Structure

```
SidegigsManagementSystem/
├── docker/
│   ├── php/
│   │   └── Dockerfile
│   ├── nginx/
│   │   └── default.conf
│   └── mongodb/
│       └── init.js
├── docker-compose.yml
├── Makefile
├── .env
└── app/
    ├── composer.json
    ├── symfony.lock
    ├── bin/console
    ├── config/
    │   ├── packages/
    │   ├── routes/
    │   └── services.yaml
    ├── src/
    │   ├── Kernel.php
    │   ├── IdentityAccess/
    │   │   ├── Domain/
    │   │   ├── Application/
    │   │   │   ├── Command/
    │   │   │   ├── Query/
    │   │   │   └── UseCase/
    │   │   ├── Infrastructure/
    │   │   │   ├── Doctrine/
    │   │   │   ├── Security/
    │   │   │   └── Controller/
    │   │   └── UI/
    │   │       ├── Controller/
    │   │       └── Form/
    │   ├── BoardManagement/
    │   │   └── (same sub-structure)
    │   ├── TaskManagement/
    │   │   └── (same sub-structure)
    │   └── ClientManagement/
    │       └── (same sub-structure)
    ├── templates/
    ├── public/
    │   └── index.php
    └── tests/
```

### 2.2 Docker Compose Services

| Service    | Image                                      | Port(s)       |
|------------|--------------------------------------------|---------------|
| php        | custom (php:8.3-fpm + extensions)          | 9000          |
| nginx      | nginx:alpine                               | 80 -> 8080    |
| mongodb    | mongo:7                                    | 27017         |
| redis      | redis:7-alpine                             | 6379          |
| mailpit    | axllent/mailpit                            | 1025, 8025    |

### 2.3 MongoDB Collections & Indexes

| Collection      | Key Indexes                                          | Embedding Strategy                |
|-----------------|------------------------------------------------------|-----------------------------------|
| users           | `email` (unique), `username` (unique), `status`      | Flat document                     |
| clients         | `name`, `nip` (unique), `status`                     | `contacts[]` embedded             |
| workflows       | `name` (unique)                                      | `stages[].transitions[]` embedded |
| tasks           | `stage`, `assignee`, `client`, `createdAt`, `parentTask` | `comments[]`, `worklogs[]` embedded |
| task_events     | `taskId`, `userId`, `createdAt`                      | Flat document                     |

## 3. Implementation Phases

### Phase 1: Foundation (Days 1-2)

**Step 1.1 — Docker environment**
- Create `docker-compose.yml` with all 5 services
- Build custom PHP Dockerfile with:
  - PHP 8.3, extensions: `mongodb`, `redis`, `intl`, `opcache`
  - Composer installed
- Nginx config: document root `/app/public`, pass `.php` to `php:9000`
- MongoDB init script creates database `sidegigs` and user
- Persist data with named volumes (`mongodb_data`, `redis_data`)

**Step 1.2 — Symfony skeleton**
- `composer create-project symfony/skeleton app` inside the container
- Install bundles via `composer require`:
  ```
  doctrine/mongodb-odm-bundle
  symfony/messenger
  symfony/security-bundle
  symfony/serializer-pack
  symfony/uid
  symfony/twig-pack
  symfony/asset
  symfony/mailer
  symfony/notifier
  symfony/validator
  symfony/monolog-bundle
  ```
- Frontend deps (via `npm` or CDN):
  - Tailwind CSS v3 (compiled via `tailwindcss` CLI or CDN in dev)
  - HTMX (CDN + `htmx.org`)
  - Alpine.js (CDN + `alpinejs`)
  - SortableJS (for drag & drop)

**Step 1.3 — Base configuration**
- `config/packages/doctrine_mongodb.yaml` — connection + default database
- `config/packages/security.yaml` — form login, argon2i password hashing, roles hierarchy
- `config/packages/messenger.yaml` — default bus + event bus (with `DoctrineTransport` or sync)
- `config/packages/twig.yaml` — Twig config with Tailwind + HTMX globals
- `config/routes.yaml` — import routes per bundle
- `.env` — `MONGO_URL`, `APP_SECRET`, `MAILER_DSN`

**Step 1.4 — Makefile targets**
```makefile
up        — docker compose up -d
down      — docker compose down
bash      — exec into php container
console   — bin/console
test      — phpunit
migrate   — sync MongoDB indexes
```

### Phase 2: IdentityAccess Context (Days 3-5)

#### 2.1 Domain Layer

```
src/IdentityAccess/Domain/
├── Model/
│   ├── User.php                       — Aggregate root
│   ├── UserId.php                     — Value object (UUID)
│   ├── UserEmail.php                  — VO
│   ├── UserPassword.php               — VO (hashed)
│   ├── UserName.php                   — VO (first + last)
│   ├── UserRepositoryInterface.php
│   ├── Event/
│   │   ├── UserRegistered.php
│   │   └── UserPasswordChanged.php
│   └── Spec/
│       └── UniqueEmailSpec.php
├── Service/
│   └── PasswordHasherInterface.php
└── Exception/
    ├── UserNotFound.php
    └── EmailAlreadyInUse.php
```

**User aggregate** — state:
- `UserId id`, `UserName name`, `UserEmail email`, `Username username`, `UserPassword password`, `array roles`, `DateTimeImmutable createdAt`, `DateTimeImmutable updatedAt`, `string status` (active/inactive)

Methods:
- `register(UserId, UserName, UserEmail, Username, UserPassword, PasswordHasherInterface)` — factory
- `changePassword(old, new, hasher)`
- `deactivate()`, `activate()`
- `recordEvent(DomainEvent)` — internal event collection

#### 2.2 Application Layer

```
src/IdentityAccess/Application/
├── Command/
│   ├── RegisterUser/
│   │   ├── RegisterUserCommand.php
│   │   └── RegisterUserHandler.php
│   ├── ChangePassword/
│   │   ├── ChangePasswordCommand.php
│   │   └── ChangePasswordHandler.php
│   └── DeactivateUser/
│       └── DeactivateUserHandler.php
├── Query/
│   ├── ListUsersQuery.php
│   ├── GetUserByIdQuery.php
│   └── ListUsersHandler.php
└── UseCase/
    └── RegisterUserUseCase.php
```

**Command Handlers** — receive Command object, coordinate domain logic + persistence:
1. `RegisterUserHandler` — validate uniqueness, create User, persist, dispatch events
2. `ChangePasswordHandler` — load user, verify old password, hash new, persist
3. `DeactivateUserHandler` — load user, deactivate, persist

**Event Dispatcher**: On `UserRegistered` → send welcome email (async via Messenger).

#### 2.3 Infrastructure Layer

```
src/IdentityAccess/Infrastructure/
├── Doctrine/
│   ├── UserDocument.php               — MongoDB ODM mapping
│   └── UserRepository.php             — implements UserRepositoryInterface
├── Security/
│   ├── SymfonyPasswordHasher.php      — implements PasswordHasherInterface
│   ├── UserIdentityProvider.php       — loadUserByIdentifier
│   └── UserVoter.php                  — role/permission checks
└── Controller/
    └── AuthController.php             — login, logout, profile
```

**Doctrine ODM mapping** — use Attributes or YAML mapping. The `UserDocument` class maps to `users` collection.

**Security configuration**:
- `form_login` — login route `/login`
- `logout` — `/logout`
- `access_control`:
  - `^/login` — no auth
  - `^/admin` — `ROLE_ADMIN`
  - `^/` — `ROLE_USER`

#### 2.4 UI Layer

```
src/IdentityAccess/UI/
├── Controller/
│   ├── RegisterController.php         — GET/POST (admin only)
│   ├── UserController.php             — CRUD for users (admin)
│   ├── ProfileController.php          — password change
│   └── AdminController.php            — dashboard
└── Form/
    ├── RegisterUserFormType.php
    ├── ChangePasswordFormType.php
    └── EditUserFormType.php
```

**Templates** (in `templates/identity_access/`):
- `login.html.twig`
- `register.html.twig`
- `user_list.html.twig`
- `user_edit.html.twig`
- `profile.html.twig`

**Base template** (`templates/base.html.twig`):
- Tailwind CSS (CDN in dev, compiled in prod)
- HTMX + Alpine.js loaded
- Nav bar with user name, role badge, logout
- Flash messages via Alpine (auto-dismiss)
- Sidebar on desktop (admin menu)

### Phase 3: BoardManagement Context (Days 6-8)

#### 3.1 Domain

```
src/BoardManagement/Domain/
├── Model/
│   ├── Workflow.php                   — Aggregate root
│   ├── WorkflowId.php
│   ├── Stage.php                      — VO (value object, part of Workflow)
│   ├── Transition.php                 — VO (fromStage → toStage)
│   └── WorkflowRepositoryInterface.php
├── Spec/
│   └── NoCycleInTransitionsSpec.php
└── Event/
    └── StageTransitionAllowedChanged.php
```

**Workflow aggregate** — one (default singleton) or multiple named workflows. Contains:
- `WorkflowId id`, `string name`, `Stage[] stages`, `bool isDefault`
- `addStage(name, position)` — adds stage
- `removeStage(stageId)` — removes if no tasks in stage
- `addTransition(from, to)` — adds allowed transition
- `removeTransition(from, to)`
- `canTransition(fromStage, toStage): bool`

#### 3.2 Application

```
src/BoardManagement/Application/
├── Command/
│   ├── CreateWorkflow/
│   ├── AddStage/
│   ├── RemoveStage/
│   ├── AddTransition/
│   └── RemoveTransition/
├── Query/
│   ├── GetBoardQuery.php              — returns stages + tasks for Kanban
│   └── GetWorkflowQuery.php
└── DTO/
    ├── StageDTO.php
    └── TransitionDTO.php
```

**GetBoardQuery handler** — fetches current workflow (default) with all stages, and for each stage all the tasks. Returns structured data for the Kanban view.

#### 3.3 Infrastructure

```
src/BoardManagement/Infrastructure/
├── Doctrine/
│   ├── WorkflowDocument.php
│   └── WorkflowRepository.php
└── Controller/
    └── BoardController.php            — main Kanban view
```

#### 3.4 UI

- `BoardController::index` — renders the Kanban board (Twig + HTMX)
- `BoardController::moveTask` — HTMX endpoint (PATCH) to move a task between stages

**Templates**:
- `board/kanban.html.twig`
- `board/_column.html.twig` — single stage column (replaced on HTMX re-render)
- `board/_card.html.twig` — single task card (draggable)

**Drag-and-drop UX**:
- SortableJS on column containers
- On drop → HTMX `PATCH /board/task/{id}/move?stage={newStage}&position={pos}`
- Server validates transition, updates task, returns updated column HTML

### Phase 4: ClientManagement Context (Days 8-9)

#### 4.1 Domain

```
src/ClientManagement/Domain/
├── Model/
│   ├── Client.php                     — Aggregate root
│   ├── ClientId.php
│   ├── ClientNip.php                  — VO (NIP format validation)
│   ├── Contact.php                    — VO (embedded)
│   ├── ClientRepositoryInterface.php
│   └── Event/
│       └── ClientRegistered.php
└── Service/
    └── NipValidatorService.php
```

**Client aggregate**: `ClientId id`, `ClientNip nip`, `string name`, `Address address`, `string country`, `string email`, `string description`, `Contact[] contacts`, `string status` (active/blocked), `DateTimeImmutable createdAt`

#### 4.2 Application

```
src/ClientManagement/Application/
├── Command/
│   ├── RegisterClient/
│   ├── UpdateClient/
│   ├── BlockClient/
│   └── AddContact/
└── Query/
    ├── ListClientsQuery.php
    └── GetClientQuery.php
```

#### 4.3 Infrastructure + UI

```
src/ClientManagement/Infrastructure/
├── Doctrine/
│   ├── ClientDocument.php
│   └── ClientRepository.php
└── Controller/
    └── ClientCrudController.php
```

**Templates**: `client/list.html.twig`, `client/form.html.twig`, `client/show.html.twig`

### Phase 5: TaskManagement Context (Days 10-14)

#### 5.1 Domain

```
src/TaskManagement/Domain/
├── Model/
│   ├── Task.php                       — Aggregate root
│   ├── TaskId.php
│   ├── TaskDescription.php
│   ├── TaskPriority.php               — VO (low/medium/high/critical)
│   ├── Stage.php                      — reference to BoardManagement stage
│   ├── Comment.php                    — VO (embedded)
│   ├── Worklog.php                    — VO (embedded)
│   ├── TaskRepositoryInterface.php
│   └── Event/
│       ├── TaskCreated.php
│       ├── TaskMoved.php
│       ├── TaskAssigned.php
│       ├── CommentAdded.php
│       └── WorklogAdded.php
└── Service/
    └── TaskStageValidatorService.php  — delegates to Workflow.canTransition()
```

**Task aggregate**:
- `TaskId id`, `string title`, `TaskDescription description`
- `UserId creatorId`, `?UserId assigneeId`
- `?ClientId clientId` (nullable — tasks can be generic)
- `string stageId` (current stage; at creation = first stage of default workflow)
- `int position` (ordering within stage)
- `Comment[] comments` (embedded, capped at ~50; beyond that, archive)
- `Worklog[] worklogs` (embedded)
- `?TaskId parentTaskId` (for sub-tasks)
- `int totalTimeSpent` (computed from worklogs + sub-tasks)
- `DateTimeImmutable createdAt`, `DateTimeImmutable updatedAt`

**Domain invariants**:
- Task must be created in the initial stage of the selected workflow
- Task can only move via allowed transitions
- Assignee must be an active user
- Sub-tasks cannot have sub-tasks (single level)

#### 5.2 Application

```
src/TaskManagement/Application/
├── Command/
│   ├── CreateTask/
│   ├── MoveTask/
│   ├── AssignTask/
│   ├── AddComment/
│   ├── AddWorklog/
│   └── ReorderTasks/
├── Query/
│   ├── GetTaskQuery.php
│   ├── ListTasksByStageQuery.php
│   └── ListTasksByClientQuery.php
├── DTO/
│   ├── TaskDTO.php
│   ├── CommentDTO.php
│   └── WorklogDTO.php
└── Projection/
    └── TaskEventProjector.php         — handles events → task_events collection
```

#### 5.3 Infrastructure

```
src/TaskManagement/Infrastructure/
├── Doctrine/
│   ├── TaskDocument.php
│   └── TaskRepository.php
├── Controller/
│   ├── TaskController.php             — full CRUD
│   ├── CommentController.php          — HTMX inline add/delete
│   └── WorklogController.php          — HTMX add worklog
├── Normalizer/
│   └── TaskEventNormalizer.php        — normalizes events for audit storage
└── Projection/
    └── TaskEventStore.php             — persists to task_events collection
```

#### 5.4 UI

**Templates**:
- `task/show.html.twig` — full task detail with comments, worklogs, sub-tasks
- `task/form.html.twig` — create/edit form (HTMX modal or full page)
- `task/_comments.html.twig` — HTMX fragment for comments section
- `task/_worklogs.html.twig` — HTMX fragment for worklogs
- `task/_subtasks.html.twig` — sub-task list

**HTMX interactions**:
- `POST /task/{id}/comment` — adds comment, returns updated comment list
- `POST /task/{id}/worklog` — adds worklog, updates total time
- `POST /task/{id}/assign` — async assign
- `GET /task/{id}/edit` — load edit form in modal
- `DELETE /task/{id}` — soft delete

### Phase 6: Kanban Board UI Polish (Days 15-16)

**Features**:
- Real-time filter by client (HTMX `hx-trigger="change"` on a select dropdown)
- Sortable columns with position persistence
- Stage transition visual hints (green arrow = allowed, red = blocked)
- Stage WIP limits (optional, configurable per stage)
- Quick task creation (HTMX modal directly from board)
- Responsive: horizontal scroll on mobile, column layout on desktop

### Phase 7: Audit & History (Day 17)

**Event store** (`task_events` collection):
- Every domain event is serialized and stored as a document
- Fields: `eventId`, `taskId`, `userId`, `type` (e.g. "TaskMoved"), `data` (JSON), `occurredAt`
- UI: `/task/{id}/history` — reverse chronological timeline

No Event Sourcing — this is an append-only log, not the source of truth. Tasks remain the source of truth.

### Phase 8: Testing (Days 18-20)

| Layer           | Tool         | Scope                                        |
|-----------------|--------------|----------------------------------------------|
| Domain unit     | PHPUnit      | Value objects, aggregate invariants, specs   |
| Application     | PHPUnit      | Command/Query handlers (with mocked repos)   |
| Infrastructure  | PHPUnit      | Repository tests with MongoDB fixture         |
| Functional      | Symfony Test | Controller tests (login, CRUD, board moves)  |
| E2E (optional)  | Playwright   | Drag-and-drop, modal interactions             |

**Fixtures**: Alice bundle or custom Doctrine Fixtures with sample data for development.

## 4. Key Technical Decisions

### 4.1 Embedded vs Referenced Documents

| Scenario             | Strategy    | Reasoning                                                     |
|----------------------|-------------|---------------------------------------------------------------|
| Comments on task     | Embedded    | Always viewed together, bounded cardinality (~50)             |
| Worklogs on task     | Embedded    | Always viewed together, bounded cardinality                   |
| Contacts on client   | Embedded    | Always viewed together, few contacts                          |
| Sub-tasks            | Reference   | They are full tasks themselves, need independent querying     |
| Stages in workflow   | Embedded    | Fixed set, always loaded with workflow                        |

### 4.2 CQRS Implementation

- **Commands** go through Symfony Messenger (`command.bus`)
- **Queries** are simple DTOs handled by query classes (can use Messenger or manual dispatch)
- **Domain Events** go through `event.bus` (async via Redis transport for welcome emails)
- Projections: simple event listeners that update read models or audit log

### 4.3 HTMX + Alpine.js Patterns

```
Common pattern for CRUD:
  Button → hx-get="/resource/{id}/edit" → hx-target="#modal"
  → Server returns form HTML
  → Alpine.js shows modal
  → Form submit: hx-put="/resource/{id}" → hx-target="body"
  → Server returns updated list fragment or full page

Common pattern for Kanban:
  SortableJS on columns
  onEnd → htmx.ajax('PATCH', '/board/move', {values: {taskId, stageId, position}})
  → Server validates, returns updated column
```

## 5. Risks & Mitigations

| Risk                                       | Mitigation                                                       |
|--------------------------------------------|------------------------------------------------------------------|
| MongoDB ODM learning curve                 | Use plain Doctrine ODM docs (not full ORM-style); simple maps     |
| HTMX complexity for real-time Kanban       | Fallback: full page reload on drop; SortableJS + Alpine for UI   |
| Drag-and-drop across columns in SortableJS | Use `group` option; validate transition server-side              |
| No built-in migrations in MongoDB          | Use `doctrine:mongodb:schema:update` + custom index migration    |
| Monorepo growth                            | Each bounded context is a PHP namespace; split to bundles later  |

## 6. Open Questions

1. Should we use a single Workflow (singleton) or allow multiple named Workflows?
   — Recommendation: single default workflow for MVP; store as singleton document.
2. Sub-task depth limit?
   — Recommendation: single level (no sub-tasks of sub-tasks).
3. NIP format validation — Polish-only or configurable?
   — Recommendation: Polish-only for MVP, pluggable validator later.

## 7. Dependencies Summary

| PHP Package              | Purpose                             |
|--------------------------|-------------------------------------|
| `doctrine/mongodb-odm-bundle` | MongoDB ODM integration        |
| `symfony/messenger`      | CQRS command/event buses            |
| `symfony/security-bundle` | Authentication & authorization      |
| `symfony/serializer`     | Domain event normalization          |
| `symfony/uid`            | UUID generation                     |
| `symfony/mailer`         | Welcome/password emails             |
| `symfony/validator`      | Input validation                    |
| `doctrine/doctrine-fixtures-bundle` | Dev fixtures           |
| `twbs/bootstrap` or Tailwind | CSS framework                   |

| Frontend Library | Purpose                        |
|------------------|--------------------------------|
| Tailwind CSS     | Styling                        |
| HTMX             | AJAX, dynamic fragments        |
| Alpine.js        | Client-side interactivity      |
| SortableJS       | Drag & drop                    |
