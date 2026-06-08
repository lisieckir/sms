# Architecture Decision Record (ADR)

## ADR-001: Architecture and Technology Stack for Sidegigs Management System

**Status:** Proposed  
**Date:** 2026-06-07  
**Author:** Grok (based on user requirements)  
**Context:** Mini-Jira-like system for managing after-hours side projects.

### 1. Context

We need a lightweight, maintainable system similar to Jira for personal side gigs with the following core features:
- User authentication with roles (Admin / User)
- Configurable Kanban board with workflow stages and transitions
- Task management with worklogs, comments, sub-tasks, and client assignment
- Client CRM (basic)
- Audit/history of actions
- Single shared board (filterable by client)

Non-functional requirements:
- PHP + Symfony ecosystem
- MongoDB
- Docker Compose
- English interface
- Focus on Kanban MVP, extensibility for reports, notifications, etc.

### 2. Decision

#### Backend
- **PHP 8.3+** with **Symfony 7.x**
- **Architecture**: Domain-Driven Design (DDD) with multiple Bounded Contexts
- **CQRS** using **Symfony Messenger**
- **Domain Events** for audit and future async processing
- **Testing**: PHPUnit + integration/functional tests
- **Security**: Symfony Security component (login, password reset, role-based access)

**Bounded Contexts**:
- `IdentityAccess` – users, roles, authentication
- `ClientManagement` – clients and contacts
- `BoardManagement` – workflows, stages, transitions
- `TaskManagement` – tasks, sub-tasks, comments, worklogs
- `Reporting` – (future) time reports

#### Database
- **MongoDB** (via Doctrine MongoDB ODM or direct)
- Collections / Documents:
  - `users`
  - `clients` (with embedded contacts)
  - `workflows` (stages + allowed transitions)
  - `tasks` (with embedded comments, worklogs, sub-tasks references)
  - `task_events` (audit log)

#### Frontend
- **Twig templates** + **HTMX** + **Alpine.js**
- Tailwind CSS for styling
- Mobile-first responsive design (where feasible without major effort)
- Drag & drop for Kanban using HTMX + Sortable.js or native + Alpine

#### Infrastructure
- **Docker Compose** with services:
  - `php-fpm` + Nginx
  - `mongodb`
  - `redis` (session cache + Messenger transport)
  - `mailpit` (for dev emails)

### 3. Detailed Design Decisions

#### Authentication & Users
- Standard Symfony login (username + password)
- Roles: `ROLE_ADMIN`, `ROLE_USER`
- Admin can:
  - Create users (generate temporary password shown in plaintext)
  - Deactivate (soft delete / status flag) users
  - Manage all data
- Users can change their own password
- Every user has: firstName, lastName, email, username

#### Board & Workflow
- Single global board
- Admin configures **Stages** (e.g., To Do, In Progress, Review, Done)
- Admin defines **allowed Transitions** between stages (graph of possible moves)
- Tasks can be reordered by priority (manual ordering or priority field)
- Drag & drop between stages respecting transition rules

#### Tasks
- Fields:
  - Title, Description
  - Creator, Assignee
  - Client (reference)
  - Stage (current)
  - Priority / Order
  - CreatedAt, UpdatedAt
  - Sub-tasks (child tasks)
- Comments (with @mention support planned)
- Worklogs (time spent in minutes/hours) – sum propagated to parent
- History / Activity log (who did what, when)

#### Clients
- Fields: NIP, name, address, country, email, contact channels, description
- Multiple contacts per client
- Block / unblock flag
- Full CRUD by Admin only

#### Reporting (Phase 2)
- Time spent per client, task, user, month

### 4. Considered Alternatives

- **Full React SPA**: Rejected for MVP due to user's frontend experience preference.
- **Event Sourcing full**: Deferred – use Domain Events + simple audit collection first.
- **Doctrine ORM + MySQL**: Rejected in favor of MongoDB for document flexibility.

### 5. Consequences

**Positive:**
- Fast development with Symfony + HTMX
- Good separation of concerns thanks to DDD/CQRS
- Flexible data model with MongoDB
- Easy to extend (reports, notifications later)

**Risks & Mitigations:**
- HTMX complexity for complex Kanban → fallback to full page reloads if needed
- Learning curve of DDD/CQRS → start with clear Command / Query / Aggregate patterns
- Performance of complex queries → use proper indexes + read models/projections

### 6. Next Steps
1. Initialize Symfony project in Docker
2. Implement IdentityAccess context first
3. Implement Board & Workflow
4. Implement TaskManagement with sub-tasks
5. Polish Kanban UI
