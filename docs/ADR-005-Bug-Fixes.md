# Architecture Decision Record (ADR)

## ADR-005: Bug Fixes & UI Improvements

**Status:** Proposed
**Date:** 2026-06-08

## 1. Context

During testing of the Kanban board and admin panels, several bugs and missing features were identified:

| # | Issue | Type | Component |
|---|-------|------|-----------|
| 1 | Client edit/view returns type error (domain vs DTO) | Bug | ClientManagement |
| 2 | Task detail shows stage UUID instead of human-readable name | UX | TaskManagement |
| 3 | Comments/worklogs show user UUID instead of username | UX | TaskManagement |
| 4 | Task drag-and-drop makes column disappear (empty HTMX response) | Bug | BoardManagement |
| 5 | No UI to assign user to task | Feature | TaskManagement |
| 6 | No "My Tasks" filter on Kanban board | Feature | BoardManagement |
| 7 | Cannot deactivate user or edit email/role | Feature | IdentityAccess |
| 8 | Workflow stages cannot be reordered via drag-and-drop | Feature | BoardManagement |

## 2. Analysis & Solutions

### 2.1 Client handler return type (Bug #1)

**Cause**: `GetClientHandler` and `ListClientsHandler` lack a `use` import for `App\ClientManagement\Domain\Model\Client`. PHP resolves `Client` from the current namespace (`Application\Query`), where no such class exists. The controllers call these handlers and pass results to Twig templates that expect domain objects.

**Fix**: Add `use App\ClientManagement\Domain\Model\Client;` to both handlers.

### 2.2 Stage name resolution (Bug #2)

**Cause**: `TaskDTO.stageId` contains the stage UUID. `GetTaskHandler` has access to `WorkflowRepositoryInterface` but doesn't resolve the stage name.

**Fix**: Inject `WorkflowRepositoryInterface` into `GetTaskHandler`, load the default workflow, and add `stageName` to `TaskDTO`. Add `stageName` field to `TaskDTO`.

### 2.3 Username resolution (Bug #3)

**Cause**: Comment and Worklog VOs store only `userId` (UUID). Template renders `comment.userId` directly.

**Fix**: Inject `UserRepositoryInterface` into `GetTaskHandler`. For each comment/worklog, look up the user by ID and add `userName` to the array. Template renders `@username` prefix.

### 2.4 Drag-and-drop empty column (Bug #4)

**Cause**: `BoardController::moveTask` returns `new Response('', 200)`. The HTMX `onEnd` callback (`_column.html.twig`) sends a PATCH with `target: '#stage-...'` and `swap: 'outerHTML'`, expecting the server to return the column HTML. Because the response is empty, the column gets replaced with nothing.

**Fix**: Change `moveTask` to return the rendered column fragment (reuse the `_column.html.twig` template), similar to `column()` action.

### 2.5 Task assignment UI (Bug #5)

**Cause**: The `app_task_assign` route and `AssignTaskCommand` exist, but the `task/show.html.twig` template has no assign form or dropdown.

**Fix**: Add an assignee dropdown (powered by HTMX) in the task detail sidebar, listing all active users.

### 2.6 My Tasks filter (Feature #6)

**Cause**: `GetBoardQuery` only supports `clientId` filter. No assignee filter exists.

**Fix**: Add `?assigneeId` parameter to `GetBoardQuery` and `GetBoardHandler`. Add "My Tasks" toggle button on the board.

### 2.7 User admin features (Feature #7)

**Cause**: `UserController` only has `index` (list) and `create` actions. No edit, activate/deactivate.

**Fix**: Add `edit` route (email, roles) and `toggle-active` route to `UserController`. Add corresponding templates.

### 2.8 Stage reordering (Feature #8)

**Cause**: Workflow stages have a `position` field but no UI to reorder them. The admin workflow page shows them in list form with a "position" number.

**Fix**: Add a `ReorderStagesCommand`/`ReorderStagesHandler`, PATCH endpoint, and SortableJS list on the workflow admin page.

## 3. Implementation Order

All fixes are independent except #3 depends on #1 (same injected dependency pattern). Implement in the following sequence:

1. Bug #1 — Client handler return type (1 file change each)
2. Bug #2 — Stage name in TaskDTO (GetTaskHandler + TaskDTO + template)
3. Bug #3 — Username in comments/worklogs (GetTaskHandler + template)
4. Bug #4 — Drag-and-drop column fix (BoardController + template)
5. Bug #5 — Task assign form (template only)
6. Feature #6 — My Tasks filter (GetBoardQuery + handler + template)
7. Feature #7 — User admin edit/deactivate (UserController + template)
8. Feature #8 — Stage reordering (new command/handler + controller + template)

