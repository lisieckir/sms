# Architecture Decision Record (ADR)

## ADR-004: Development & Test Fixtures

**Status:** Proposed
**Date:** 2026-06-08

## 1. Context

The application has four bounded contexts (IdentityAccess, BoardManagement, ClientManagement, TaskManagement) with 5 MongoDB collections (`users`, `workflows`, `clients`, `tasks`, `task_events`). Developers need reproducible sample data for:

- Manual testing during development
- Automated functional tests (Phase 8 of ADR-003)
- Consistent demo environment for stakeholders

Currently only `app:seed-default-workflow` exists, which populates a single workflow. No sample users, clients, or tasks are available out of the box.

## 2. Decision

Use `doctrine/doctrine-fixtures-bundle` — the standard Symfony fixtures mechanism, which supports MongoDB ODM via the `ObjectManager` abstraction. This is already referenced in ADR-003 section 7.

### Fixture loading order

| Order | Fixture | Dependencies | Data |
|-------|---------|-------------|------|
| 1 | `UserFixtures` | — | 1 admin + 4 regular users |
| 2 | `WorkflowFixtures` | — | 1 default workflow (5 stages, 6 transitions) |
| 3 | `ClientFixtures` | — | 4 Polish companies with contacts |
| 4 | `TaskFixtures` | Users, Workflow, Clients | 10 tasks across stages + comments/worklogs |

Dependencies are resolved via `DependentFixtureInterface` and `FixtureInterface`.

### 2.1 Sample Data

#### Users (`UserFixtures`)

| Email | Username | Role | Password |
|-------|----------|------|----------|
| admin@example.com | admin | ROLE_ADMIN | admin123 |
| alice@example.com | alice | ROLE_USER | user1234 |
| bob@example.com | bob | ROLE_USER | user1234 |
| carol@example.com | carol | ROLE_USER | user1234 |
| dave@example.com | dave | ROLE_USER | user1234 |

#### Workflow (`WorkflowFixtures`)

5 stages: Backlog → To Do → In Progress → Review → Done
Transitions: linear forward (4) + backward from Review→In Progress and Done→Review (2) = 6 transitions.

#### Clients (`ClientFixtures`)

| Name | NIP | Contacts |
|------|-----|----------|
| Acme Corp | 1234567890 | 2 contacts |
| Globex Inc | 2345678901 | 1 contact |
| Initech | 3456789012 | 2 contacts |
| Umbrella Co | 4567890123 | 1 contact |

#### Tasks (`TaskFixtures`)

10 sample tasks distributed across stages:
- 2 in Backlog, 3 in To Do, 2 in In Progress, 2 in Review, 1 in Done
- Mix of assigned/unassigned, with/without client association
- Some with comments and worklogs
- Corresponding `task_events` entries for every change

### 2.2 Loading Command

Fixtures are loaded via the standard Symfony command:
```bash
php bin/console doctrine:fixtures:load
```

For test environments, fixtures can be loaded per test case using `AbstractFixture` + `purger`.

### 2.3 File Structure

```
app/src/DataFixtures/
├── UserFixtures.php
├── WorkflowFixtures.php
├── ClientFixtures.php
└── TaskFixtures.php
```

Each file is standalone — no orchestration class needed (the bundles auto-discovery finds all classes implementing `FixtureInterface`).

## 3. Consequences

**Positive:**
- One command seeds the entire database with realistic data
- Consistent data for all developers
- Can be reused in functional tests
- Standard Symfony approach — no custom infrastructure

**Risks:**
- `doctrine:fixtures:load` drops and recreates collections (append mode available with `--append`)
- Task fixtures are tightly coupled to user/client UUIDs — order must be fixed
- Time needed to generate realistic Polish NIP numbers (10-digit checksums)

## 4. Implementation Notes

- All passwords use the `UserPasswordHasherInterface` from Security bundle
- NIP values are structurally valid but not registered in any real REGON database
- Task positions are evenly spaced (100, 200, 300...) to allow insertion
- Comments contain realistic (simulated) content in English
- Worklogs record round minute values (15–480 min range)
