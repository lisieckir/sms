# ADR-008: Unit Testing with In-Memory Repositories

- **Status**: Accepted
- **Date**: 2026-06-08

## Context

The project has no test suite. To ensure correctness of domain logic, value object validation, event recording, and command handler orchestration, a comprehensive unit test suite is needed.

### Constraints
- No mocking frameworks — use real in-memory implementations of repository interfaces
- Tests must run without Symfony container, database, or external services
- Follow DDD boundaries: each bounded context tested independently

## Strategy

### Test Organization
```
tests/
  InMemory/
    InMemoryPasswordHasher.php
    InMemoryTaskRepository.php
    InMemoryUserRepository.php
    InMemoryWorkflowRepository.php
    InMemoryClientRepository.php
  Unit/
    BoardManagement/Domain/Model/    (Workflow, Stage, Transition)
    ClientManagement/Domain/Model/   (Client, ClientNip)
    IdentityAccess/Domain/Model/     (User, UserEmail, UserName)
    TaskManagement/Domain/Model/     (Task, TaskDescription, TaskId, TaskPriority, Comment, Worklog)
    TaskManagement/Application/Command/  (handlers)
```

### In-Memory Repositories
- Each implements the corresponding domain `*RepositoryInterface`
- Stores aggregate instances in an indexed array
- `nextIdentity()` returns a fresh `TaskId`/`UserId`/etc.
- `findBy*()` methods filter the array by the given property
- `save()` upserts by ID (same behavior as the real Doctrine repos)

### In-Memory Password Hasher
- `InMemoryPasswordHasher` implements `PasswordHasherInterface`
- Hash: appends `$hashed` suffix to plain text (deterministic)
- Verify: checks that plain text + suffix matches stored hash

### What's Tested

| Layer | What | Example |
|-------|------|---------|
| Value objects | Construction (valid/invalid), equality, getters | `TaskDescription` rejects >5000 chars |
| Aggregates | State mutations, event recording, invariants | `Task::addComment` records `CommentAdded`, enforces 50 limit |
| Command handlers | Full flow: command → handler → repo interaction | `CreateTaskHandler` persists task and projects events |

### What's NOT Tested
- Controllers/routes (need Symfony kernel)
- Twig templates (need Symfony kernel)
- Symfony Messenger bus wiring
- Doctrine MongoDB ODM persistence
- Integration/end-to-end flows

## Files Created

| File | Purpose |
|------|---------|
| `phpunit.xml.dist` | PHPUnit configuration |
| `tests/InMemory/InMemoryPasswordHasher.php` | Deterministic password hasher |
| `tests/InMemory/InMemoryTaskRepository.php` | In-memory task storage |
| `tests/InMemory/InMemoryUserRepository.php` | In-memory user storage |
| `tests/InMemory/InMemoryWorkflowRepository.php` | In-memory workflow storage |
| `tests/InMemory/InMemoryClientRepository.php` | In-memory client storage |
| `tests/Unit/TaskManagement/Domain/Model/TaskTest.php` | Task aggregate tests |
| `tests/Unit/TaskManagement/Domain/Model/TaskDescriptionTest.php` | VO tests |
| `tests/Unit/TaskManagement/Domain/Model/TaskIdTest.php` | VO tests |
| `tests/Unit/TaskManagement/Domain/Model/TaskPriorityTest.php` | VO tests |
| `tests/Unit/TaskManagement/Domain/Model/CommentTest.php` | VO tests |
| `tests/Unit/TaskManagement/Domain/Model/WorklogTest.php` | VO tests |
| `tests/Unit/TaskManagement/Application/Command/CreateTaskHandlerTest.php` | Handler tests |
| `tests/Unit/TaskManagement/Application/Command/AssignTaskHandlerTest.php` | Handler tests |
| `tests/Unit/TaskManagement/Application/Command/MoveTaskHandlerTest.php` | Handler tests |
| `tests/Unit/TaskManagement/Application/Command/AddCommentHandlerTest.php` | Handler tests |
| `tests/Unit/TaskManagement/Application/Command/AddWorklogHandlerTest.php` | Handler tests |
| `tests/Unit/TaskManagement/Application/Command/EditDescriptionHandlerTest.php` | Handler tests |
| `tests/Unit/IdentityAccess/Domain/Model/UserTest.php` | User aggregate tests |
| `tests/Unit/IdentityAccess/Domain/Model/UserEmailTest.php` | VO tests |
| `tests/Unit/IdentityAccess/Domain/Model/UserNameTest.php` | VO tests |
| `tests/Unit/BoardManagement/Domain/Model/WorkflowTest.php` | Workflow aggregate tests |
| `tests/Unit/BoardManagement/Domain/Model/StageTest.php` | VO tests |
| `tests/Unit/BoardManagement/Domain/Model/TransitionTest.php` | VO tests |
| `tests/Unit/ClientManagement/Domain/Model/ClientTest.php` | Client aggregate tests |
| `tests/Unit/ClientManagement/Domain/Model/ClientNipTest.php` | VO tests |
