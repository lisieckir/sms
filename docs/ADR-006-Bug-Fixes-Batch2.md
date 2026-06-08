# ADR-006: Bug Fixes and UX Improvements (Batch 2)

- **Status**: Accepted
- **Date**: 2026-06-08

## Context

Three additional issues were identified after the initial batch of 8 bug fixes:

1. **TaskAssigned event history shows raw user UUID** instead of username
2. **Subticket functionality was documented in ADR-001 but never implemented** — the domain model has `parentTaskId` but no UI or worklog propagation
3. **Task assignment UI is a separate page with a plain `<select>`** — users want a Jira-like inline searchable assignment field

## Issue 1: Username in TaskAssigned History

### Observation
The task history page (`/task/{id}/history`) renders:
```
TaskAssigned 2026-06-08 07:16:25
Assigned to user 2e84ef71-cac9-4b10-90e2-eb9fe1567d0b
```

The UUID comes from `TaskEventProjector::projectTaskAssigned()` which stores `event.newAssigneeId()` (the raw UUID) in the event data array.

### Solution
Resolve usernames at read time in `TaskController::history` by injecting `UserRepositoryInterface`. Build a userId→username map for all `TaskAssigned` events before rendering.

## Issue 2: Subticket Functionality

### Observation
- `Task` aggregate already has `$parentTaskId` property and `parentTaskId()` getter
- `TaskRepositoryInterface` already has `findByParent(TaskId)` method
- `CreateTaskCommand` already accepts `?string $parentTaskId`
- `TaskDTO` already has `parentTaskId` field
- **No**: subtask validation (single-level enforcement), worklog propagation from subtasks to parent, UI for creating/viewing subtasks

### Solution
1. **Worklog propagation**: In `GetTaskHandler`, if a task has no `parentTaskId`, load subtasks via `findByParent()` and add their `totalTimeSpent` to the parent
2. **TaskDTO**: Add `parentTaskName` (resolved parent title) and `subtasks` array (for rendering subtask list)
3. **UI**: Add subtask section on `show.html.twig` with inline creation form, add parent-task link in sidebar
4. **Controller**: Add `createSubtask` action that creates a task with `parentTaskId` and redirects back

## Issue 3: Jira-like Searchable Assignment Field

### Observation
Current assignment flow requires navigating to `/task/{id}/assign`, choosing from a plain `<select>` with all users, and submitting. Users want inline editing like Jira — click the assignee field, start typing, see filtered results.

### Solution
Replace the static assignee display in `show.html.twig` with an Alpine.js component:
1. Show current assignee as clickable text
2. On click, show a search input with dropdown
3. Search queries a new JSON endpoint that returns matching users
4. On selection, POST via fetch to `app_task_assign`

Backend changes:
- Add `searchByTerm(string $term): array` to `UserRepositoryInterface`
- Implement in `UserRepository` using MongoDB `$regex` on `firstName`, `lastName`, `username`
- Add `UserSearchController` with `GET /users/search?q=` returning JSON

## Files Modified

| File | Change |
|------|--------|
| `src/IdentityAccess/Domain/Model/UserRepositoryInterface.php` | Add `searchByTerm()` |
| `src/IdentityAccess/Infrastructure/Doctrine/UserRepository.php` | Implement `searchByTerm()` |
| `src/TaskManagement/UI/Controller/UserSearchController.php` | **New** — JSON search endpoint |
| `src/TaskManagement/Application/DTO/TaskDTO.php` | Add `parentTaskName`, `subtasks` |
| `src/TaskManagement/Application/Query/GetTaskHandler.php` | Resolve parent name, sum subtask time |
| `src/TaskManagement/UI/Controller/TaskController.php` | Fix history usernames; add `createSubtask` |
| `templates/task/history.html.twig` | Render resolved username |
| `templates/task/show.html.twig` | Inline assignee search; subtasks section; parent link |
| `templates/task/assign.html.twig` | Keep for fallback (no change needed) |
