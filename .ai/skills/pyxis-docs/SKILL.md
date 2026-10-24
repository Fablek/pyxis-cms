---
name: pyxis-docs
description: Keeps Pyxis CMS code and its documentation (the separate pyxis-cms-docs repository) in sync. Activate before planning or implementing any feature, fix or refactor in pyxis-cms that touches models, migrations, Filament resources, API endpoints, API resources, settings, media, roles, content workflow or configuration, and whenever answering how a Pyxis feature is supposed to work. Also activate when writing or updating documentation pages.
---

# Pyxis Docs Sync

The documentation in `pyxis-cms-docs` is part of the engineering thesis and describes how Pyxis CMS is **supposed** to work. Code and docs must never silently drift apart.

## Where the docs live

- Repository: `../pyxis-cms-docs` (sibling of `pyxis-cms`), Astro + Starlight.
- Content: `src/content/docs/pl/**` and `src/content/docs/en/**`. Every page exists in **both** languages with the same path.
- Navigation: the `sidebar` array in `astro.config.mjs` (English `label` + Polish `translations.pl`).
- Always-relevant pages:
  - `requirements.md` – functional scope of the thesis (e.g. Custom Fields / Flexible Content / Block Builder).
  - `dev/standards.md` – coding, API and Git standards.
  - `dev/testing.md` – testing conventions.

### Code area → docs pages

| Code area | Docs pages |
| :--- | :--- |
| `app/Models/Page.php`, page Filament resource, content builder | `concepts/pages.md`, `concepts/shadow-content.md` |
| Custom fields / field groups / blocks | `requirements.md` (section 1), `concepts/shadow-content.md`, `api/pages.md` |
| `app/Http/Controllers/Api/**`, API resources, routes/api.php | `api/*.md`, `frontend/*.md`, `starters/*.md` |
| Migrations, UUIDs, JSONB columns | `architecture/database.md`, `concepts/uuid.md` |
| Media | `concepts/media.md`, `architecture/media-processing.md` |
| Settings, homepage | `concepts/settings.md` |
| Roles, users, auth | `concepts/roles.md`, `getting-started/admin-panel.md` |
| SEO | `concepts/seo.md` |
| Translations / locale | `concepts/localization.md` |
| Cache revalidation, preview | `architecture/revalidation.md`, `api/preview.md`, `frontend/preview.md` |
| Env variables, Docker | `architecture/env.md`, `architecture/docker-networking.md` |

If a topic is not listed, search for it: `grep -rni '<keyword>' ../pyxis-cms-docs/src/content/docs/pl`.

## Workflow

### 1. Before writing code

1. Find the docs pages relevant to the task (table above + grep). Read the Polish version; it is the primary one.
2. Always read `requirements.md` and `dev/standards.md` alongside them.
3. Compare what the docs describe with what the code currently does.

### 2. When code and docs disagree – stop and ask

Do **not** silently pick a side, "fix" the code to match the docs, or rewrite the docs to match the code.

Report the divergence to the user:
- what the docs say (file path + short quote),
- what the code does (file path + line),
- why it matters for the current task,
- a recommendation which side should change.

Wait for the user's decision before continuing with the affected part. Unrelated parts of the task may continue.

### 3. While implementing

- Follow the architecture the docs describe (e.g. Shadow Content: editable data goes to `content_draft`, published by copying to `content`; API responses through `JsonResource`; UUID primary keys; `isLive()` as the single visibility check).
- If the task introduces something the docs do not cover yet, note it – it will need a new docs section.

### 4. After the code change – update the docs in the same task

1. Update every affected page in **both** `pl/` and `en/`. Keep the structure of both versions identical; translate, do not summarise.
2. New page → create it in both languages with `title` and `description` frontmatter, and add it to the `sidebar` in `astro.config.mjs` (English `label` + `translations.pl`).
3. API changes → update the example JSON so it matches the actual `JsonResource` output, plus status codes. Check whether `starters/*.md` and `frontend/*.md` need changes too.
4. Match the existing style: Polish headings, short paragraphs, bold key terms, tables for parameters, `---` between major sections.
5. Do not document things that are not implemented yet, unless marked clearly (Starlight badge `TBD` in the sidebar or a `> ⚠️` note).

### 5. Git

- `pyxis-cms-docs` has no branching model: work on `main`, Conventional Commits with the `docs:` prefix (e.g. `docs: custom fields concept`).
- `pyxis-cms` follows Git Flow (see the project guidelines in `AGENTS.md`).
- The user makes all commits in both repositories. Never commit or push; leave the changes uncommitted and suggest commit messages for the code and the matching docs change.

### 6. Final summary

End every task that touched domain behavior with a short "Docs" line: which pages were updated, or "no docs change needed" with a reason.
