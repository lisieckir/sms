# Sidegigs Management System — Status

## Session: 2026-06-07 (continued on 2026-06-08)

### Previously Done
- ADR-001, ADR-002, ADR-003 — architecture decisions
- Docker infrastructure: `docker-compose.yml`, `docker/php/Dockerfile`, `docker/nginx/default.conf`
- `Makefile` with automation targets
- Symfony 7.4 project under `./app/`
- Dependencies: mongodb-odm-bundle, messenger, security-bundle, serializer, uid, mailer, etc.
- Full DDD directory structure for 4 bounded contexts
- IdentityAccess: full implementation (User aggregate, Auth, User CRUD, password change)
- IdentityAccess UI: login, profile, user management templates

### Done This Session (2026-06-08)

#### BoardManagement (Phase 3)
- **Domain**: `WorkflowId`, `Stage`, `Transition` value objects; `Workflow` aggregate root with `createDefault()`, `addStage()`, `removeStage()`, `addTransition()`, `removeTransition()`, `canTransition()`, `sortedStages()`, `getInitialStageId()`; domain event `WorkflowCreated`; `WorkflowRepositoryInterface`
- **Application**: Commands — `CreateWorkflow`, `AddStage`, `RemoveStage`, `AddTransition`, `RemoveTransition`; Queries — `GetBoardQuery`, `GetWorkflowQuery`; DTOs — `StageDTO`, `TransitionDTO`
- **Infrastructure**: `WorkflowDocument` (Doctrine ODM mapping to `workflows` collection), `WorkflowRepository`
- **UI**: `BoardController` with `index()` and `moveTask()` stub; `WorkflowController` with `edit()`, `init()`, `createDefault()`; `app:seed-default-workflow` console command
- **Templates**: `board/kanban.html.twig` (SortableJS drag & drop, HTMX move), `board/workflow.html.twig` (stage/transition management), `board/workflow_init.html.twig`

#### TaskManagement (Phase 5)
- **Domain**: `TaskId`, `TaskDescription`, `TaskPriority`, `Comment`, `Worklog` value objects; `Task` aggregate root with `create()`, `moveToStage()`, `assignTo()`, `addComment()`, `addWorklog()`, `archive()`; domain events — `TaskCreated`, `TaskMoved`, `TaskAssigned`, `CommentAdded`, `WorklogAdded`; `TaskRepositoryInterface`
- **Application**: Commands — `CreateTask`, `MoveTask`, `AssignTask`, `AddComment`, `AddWorklog`; Queries — `GetTaskQuery`, `ListTasksByStageQuery`; DTO — `TaskDTO`
- **Infrastructure**: `TaskDocument` (Doctrine ODM mapping to `tasks` collection with embedded comments/worklogs), `TaskRepository`
- **UI**: `TaskController` with `create()`, `show()`, `addComment()`, `addWorklog()`, `assign()`
- **Templates**: `task/form.html.twig`, `task/show.html.twig` (detail view with comments, worklogs, sub-tasks)

#### ClientManagement (Phase 4)
- **Domain**: `ClientId`, `ClientNip`, `Contact` value objects; `Client` aggregate root with `register()`, `update()`, `block()`, `unblock()`, `addContact()`; domain event `ClientRegistered`; `ClientRepositoryInterface`
- **Application**: Commands — `RegisterClient`, `UpdateClient`, `BlockClient`, `AddContact`; Queries — `ListClientsQuery`, `GetClientQuery`
- **Infrastructure**: `ClientDocument` (Doctrine ODM mapping to `clients` collection with embedded contacts), `ClientRepository`
- **UI**: `ClientController` with `index()`, `create()`, `show()`, `edit()`, `toggleBlock()`, `addContact()`
- **Templates**: `client/list.html.twig` (table with links), `client/form.html.twig` (create/edit), `client/show.html.twig` (detail with contacts)

### Fixed This Session
- **WorkflowController imports**: Added missing `use` for `RemoveStageHandler` and `RemoveTransitionHandler` (500 error on `/admin/workflow`)
- **Client form template**: Fixed `client` variable not defined when rendering create form (500 error on `/admin/clients/create`)

### Services Registered (app/config/services.yaml)
- All 4 repository interfaces bound to their Doctrine implementations
- All 15+ command/query handlers tagged for messenger buses

### Verified Working
- All 23 routes registered (was 10)
- `GET /login` → 200
- `POST /login` → 302 → `/` (200)
- `GET /` → 200 (Kanban board with 4 stages: To Do, In Progress, Review, Done)
- `GET /admin/workflow` → 200 (stage/transition management)
- `GET /admin/clients` → 200 (client list)
- `GET /admin/clients/create` → 200 (create form)
- `POST /admin/clients/create` → redirect → client visible in list
- `GET /task/create` → 200 (task creation form)
- `POST /task/create` → redirect → board shows new task
- `GET /admin/users` → 200 (user list)
- `cache:clear` succeeds
- `doctrine:mongodb:schema:update` succeeds (indexes + validation)
- `app:seed-default-workflow` seeds 4 stages + 6 transitions

### Currently Registered Routes
```
app_board                     ANY      /
app_board_move                PATCH    /board/task/{taskId}/move
app_workflow_edit             ANY      /admin/workflow
app_workflow_init             ANY      /admin/workflow/init
app_workflow_create_default   ANY      /admin/workflow/create-default
app_client_list               ANY      /admin/clients
app_client_create             ANY      /admin/clients/create
app_client_show               ANY      /admin/clients/{id}
app_client_edit               ANY      /admin/clients/{id}/edit
app_client_toggle_block       POST     /admin/clients/{id}/toggle-block
app_client_add_contact        POST     /admin/clients/{id}/contact
app_login                     ANY      /login
app_logout                    ANY      /logout
app_profile                   ANY      /profile
app_user_list                 ANY      /admin/users
app_user_create               ANY      /admin/users/create
app_task_create               ANY      /task/create
app_task_show                 ANY      /task/{id}
app_task_comment              POST     /task/{id}/comment
app_task_worklog              POST     /task/{id}/worklog
app_task_assign               POST     /task/{id}/assign
```

### Fixed This Session
- **GetBoardHandler** wired to fetch real tasks per stage via `TaskRepositoryInterface`
- **MoveTaskHandler** now validates transitions against `Workflow.canTransition()` before moving
- **BoardController::moveTask** dispatches `MoveTaskCommand` via Messenger, returns 422 on invalid transitions
- **Client filter** dropdown on Kanban board (HTMX `hx-trigger="change"`)
- All 3 known issues from previous session resolved

### Verified Working (New)
- `GET /` → board shows real tasks in correct stage columns sorted by position
- `PATCH /board/task/{id}/move` → 200 with valid transition, 422 if not allowed
- Task detail page (`/task/{id}`) with comments section + add comment
- Task worklog logging with total time accumulator
- Client filter dropdown changes board content via HTMX

### Known Issues
- CSRF on login form disabled (`enable_csrf: false`)
- No Redis session handler configured (`handler_id: null` — file-based sessions)
- MongoDB runs without auth in dev
- `/app/var` needs `chmod 777` after container rebuild (Doctrine proxy/hydrator directory)
- No form validation errors displayed inline (server-side validation exists via exceptions)
- No pagination on client/task lists
- No HTMX fragments for inline task operations on board (full page redirect to `/task/create`)
- SortableJS drag & drop sends PATCH but doesn't re-render column via HTMX (full page reload needed)

### Remaining Work (by ADR-003 Phase)

#### Phase 6: Kanban Board UI Polish
- [ ] Stage transition visual hints (green = allowed, red = blocked) on board
- [ ] Stage WIP limits (optional, configurable per stage)
- [ ] Quick task creation (HTMX modal directly from board, not full page)
- [ ] Responsive: horizontal scroll on mobile, column layout on desktop

#### Phase 7: Audit & History
- [ ] Create `task_events` collection document mapping
- [ ] Implement event projector: serialize domain events → store in task_events
- [ ] Wire domain events from all handlers to event bus
- [ ] UI: `/task/{id}/history` — reverse chronological timeline

#### Phase 8: Testing
- [ ] Domain unit tests (PHPUnit): value objects, aggregate invariants, specs
- [ ] Application tests (PHPUnit): command/query handlers with mocked repos
- [ ] Infrastructure tests (PHPUnit): repository tests with MongoDB fixture
- [ ] Functional tests (Symfony Test): controller tests (login, CRUD, board moves)
- [ ] Doctrine Fixtures: Alice bundle or custom fixtures with sample data
- [ ] phpunit.xml config + bootstrap

#### Other Improvements
- [ ] SortableJS drop → HTMX re-render dropped column (instead of full page reload)
- [ ] Inline form validation errors (currently only server exceptions)
- [ ] Add assignee name resolution on board cards and task detail
- [ ] Pagination on client list / task list

### Relevant Files
- `docs/ADR-001-*-Stack.md` — Architecture & stack
- `docs/ADR-002-*-Frontend.md` — Twig+HTMX frontend
- `docs/ADR-003-*-Implementation-Plan.md` — Phased plan
- `docker-compose.yml` — 5 services (php, nginx, mongodb, redis, mailpit)
- `app/config/services.yaml` — All service bindings
- `app/src/IdentityAccess/` — Full DDD implementation
- `app/src/BoardManagement/` — Workflow + Kanban board
- `app/src/TaskManagement/` — Tasks, comments, worklogs
- `app/src/ClientManagement/` — Clients, contacts, NIP
- `app/templates/` — Twig templates (board, client, task, identity_access)
