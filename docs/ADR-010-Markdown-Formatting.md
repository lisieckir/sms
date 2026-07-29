# Architecture Decision Record (ADR)

## ADR-010: Markdown Formatting for Task Description

**Status:** Accepted
**Date:** 2026-06-08

## 1. Context

The task description field is currently plain text with no formatting options. Users want to format descriptions with:

- Bold, italic, inline code
- Headings, lists
- Links
- Code blocks

Direct HTML input is rejected because it introduces XSS risk — a user could paste `<script>` tags, broken HTML that breaks the layout, or styling that interferes with the page.

### Constraints

- The frontend uses Twig + HTMX + Alpine.js (no SPA framework, no heavy JS editor)
- Descriptions are edited inline via an Alpine.js component (`show.html.twig`)
- The existing `TaskDescription` value object validates max 5000 chars only
- Raw HTML in user input must be escaped or stripped — never rendered unsanitized
- The solution must be maintainable and familiar to non-technical users

## 2. Decision

Use **Markdown** as the formatting syntax, rendered server-side by `league/commonmark`, with a second security layer via `symfony/html-sanitizer`.

### Why Markdown over alternatives

| Option | Pros | Cons |
|--------|------|------|
| **Markdown** | Simple syntax, well-known, safe by default, server-side rendering, no JS dependency | Users must learn Markdown syntax |
| WYSIWYG editor (TinyMCE, Quill) | Rich UI, no syntax learning | Heavy JS bundle, larger XSS surface, breaks HTMX/Alpine patterns |
| BBCode | Safe parsing | Outdated, unfamiliar to most users |
| Raw HTML subset (allowlisted tags) | Maximum flexibility | Complex sanitizer config, easy to miss dangerous combinations |

Markdown is the best fit because:
- The existing `league/commonmark` library escapes raw HTML by default — safe out of the box
- No JavaScript WYSIWYG editor needed — the existing Alpine.js inline edit remains unchanged
- Rendered server-side via a Twig filter, consistent with the Twig + HTMX stack
- Syntax is familiar from GitHub, GitLab, and most developer tools

### Two-layer security

1. **`league/commonmark`** with default configuration — any raw HTML in the Markdown source is **escaped** (rendered as text, not executed)
2. **`symfony/html-sanitizer`** — a configurable allowlist of safe HTML tags applied to the final HTML output as defense-in-depth

## 3. Detailed Design

### 3.1 Storage

The raw Markdown is stored in the `description` field of the `tasks` PocketBase collection (and the `TaskDescription` value object). **HTML is never stored** — only the Markdown source. The rendered HTML is computed at read time.

### 3.2 Rendering

Rendering happens at the DTO layer (`GetTaskHandler`, `ListTasksByStageHandler`):

```
Task.description  (raw Markdown)
    → league/commonmark (escape HTML by default)
    → symfony/html-sanitizer (allowlist filter)
    → TaskDTO.descriptionHtml (safe HTML string)
```

A Twig extension (`App\Core\UI\Twig\MarkdownExtension`) provides a `|markdown` filter for templates, used both by handlers and directly in Twig.

### 3.3 HtmlSanitizer Allowlist

Only these tags are allowed:

```
p, br, strong, em, a, ul, ol, li, h1-h6, blockquote, code, pre, hr
```

Attributes allowed only on `<a>`: `href` (http/https/mailto only).

All other tags (`div`, `script`, `img`, `style`, `table`, `input`, `form`, `on*` event handlers) are stripped.

### 3.4 Inline Editing UX

The inline edit component in `show.html.twig` is updated to:

- **View mode:** Render `descriptionHtml` via Alpine.js `x-html` (safe, already sanitized)
- **Edit mode:** Show a `<textarea>` with the raw Markdown source
- **Toggle:** Switch between view and edit; on save, POST the Markdown source
- **Optional toolbar:** Small Alpine.js component with formatting helper buttons (B, I, `code`, link, list) that insert Markdown syntax at cursor position

### 3.5 Files Changed

| File | Change |
|------|--------|
| `composer.json` | Add `league/commonmark`, `symfony/html-sanitizer` |
| `src/Core/UI/Twig/MarkdownExtension.php` | **New** — Twig `markdown` filter |
| `src/TaskManagement/Application/DTO/TaskDTO.php` | Add `descriptionHtml` field |
| `src/TaskManagement/Application/Query/GetTaskHandler.php` | Render description HTML |
| `src/TaskManagement/Application/Query/ListTasksByStageHandler.php` | Render description HTML |
| `templates/task/show.html.twig` | Render `descriptionHtml`; add Markdown toolbar |
| `config/services.yaml` | Register MarkdownExtension |
| `config/packages/framework.yaml` | Add HtmlSanitizer config (if needed) |

### 3.6 Test Changes

- **New:** `MarkdownExtensionTest` — verify `|markdown` filter escapes HTML, renders basic Markdown, respects whitelist
- **Update:** `EditDescriptionHandlerTest` — no change needed (handler stores raw Markdown, not HTML)

## 4. Consequences

### Positive

- Users can format task descriptions with bold, lists, code blocks, headings
- Zero XSS risk — raw HTML is escaped by CommonMark, sanitizer is defense-in-depth
- No JavaScript dependencies added — rendering is server-side
- Existing inline editing UX is preserved
- Markdown syntax is portable and future-proof

### Risks

- Non-technical users may not know Markdown syntax → the optional toolbar mitigates this
- CommonMark + sanitizer adds ~1s to container build (Composer install)
- Rendered HTML is computed at read time — negligible performance impact for a single-user app

## 5. Considered Alternatives

| Alternative | Rejection Reason |
|-------------|-----------------|
| Client-side Markdown (JS library) | Duplicates rendering logic, harder to test, breaks HTMX patterns |
| Store HTML directly | XSS risk at write time, bloats storage, cannot easily change renderer |
| Allow no formatting (status quo) | Feature requested by user |
| Restrict to bold/italic only (custom syntax parser) | Too limited, reinventing the wheel |
