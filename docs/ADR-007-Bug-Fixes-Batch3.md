# ADR-007: Bug Fixes and UX Improvements (Batch 3)

- **Status**: Accepted
- **Date**: 2026-06-08

## Context

Two additional issues:

1. **No way to edit ticket description** — description is set at creation and immutable
2. **TaskMoved event history shows raw stage UUIDs** instead of human-readable stage names

## Issue 1: Edit Ticket Description

### Observation
The Task aggregate has no method to change the description after construction. The description is displayed read-only in `show.html.twig`. Users need inline editing capability.

### Solution
1. Add `changeDescription(TaskDescription)` to `Task` aggregate — updates property, updatedAt, records new event
2. Create `TaskDescriptionChanged` domain event
3. Create `EditDescriptionCommand` / `EditDescriptionHandler`
4. Handle new event in `TaskEventProjector`
5. Add `POST /task/{id}/description` route in `TaskController`
6. Make description field inline-editable in `show.html.twig` using Alpine.js

## Issue 2: Stage Names in TaskMoved History

### Observation
History renders:
```
Moved from 2790040d-a0d9-4e01-8584-b2b4f4fcc64f to 496bd372-f50f-4282-af1a-9599d44f881a
```

The `TaskEventProjector::projectTaskMoved()` stores raw `fromStageId`/`toStageId` UUIDs.

### Solution
Same pattern as TaskAssigned fix (ADR-006):
1. Inject `WorkflowRepositoryInterface` into `TaskController::history`
2. Build stageId→stageName map from the default workflow
3. Add `fromStageName`/`toStageName` to each TaskMoved event's data before passing to template
4. Update `history.html.twig` to render resolved names

## Files Modified

| File | Change |
|------|--------|
| `src/TaskManagement/Domain/Model/Task.php` | Add `changeDescription()` method |
| `src/TaskManagement/Domain/Event/TaskDescriptionChanged.php` | **New** — domain event |
| `src/TaskManagement/Application/Command/EditDescription/EditDescriptionCommand.php` | **New** |
| `src/TaskManagement/Application/Command/EditDescription/EditDescriptionHandler.php` | **New** |
| `src/TaskManagement/Infrastructure/Projection/TaskEventProjector.php` | Handle `TaskDescriptionChanged` |
| `src/TaskManagement/UI/Controller/TaskController.php` | Add `editDescription` route; resolve stage names in `history` |
| `templates/task/show.html.twig` | Inline description editing |
| `templates/task/history.html.twig` | Render `fromStageName`/`toStageName` |
