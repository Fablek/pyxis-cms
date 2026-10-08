# Pyxis CMS

Pyxis CMS is an engineering thesis project: a headless CMS (Laravel + Filament admin, REST API) consumed by separate frontends (`../pyxis-view-next`, `../pyxis-view-astro`).

## Documentation Is the Specification

- The documentation lives in the separate repository `../pyxis-cms-docs` (Astro Starlight, Polish and English).
- You MUST activate the `pyxis-docs` skill before planning or changing any domain behavior (models, migrations, Filament resources, API, settings, media, roles, content workflow).
- If the code and the docs disagree, stop and ask the user which side is correct. Never silently change either one to match the other.
- Every code change that alters documented behavior must update the docs (both `pl/` and `en/`) in the same task.

## Git Flow (pyxis-cms only)

This repository uses Git Flow, configured for the `git flow` CLI (`main`, `develop`, prefixes `feature/`, `release/`, `hotfix/`, version tags without a prefix, e.g. `1.3.1`).

- Never commit directly to `main` or `develop`. If you are on one of them and need to change code, create the proper branch first.
- `feature/<kebab-name>`: branch from `develop`, merge back into `develop` (via a GitHub pull request or `git flow feature finish`). Keep it up to date by merging `develop` into it.
- `release/<X.Y.Z>`: branch from `develop`, merge into `main` and `develop`, tag `X.Y.Z` (semantic versioning).
- `hotfix/<kebab-name>`: branch from `main`, merge into `main` and `develop`, bump the patch version tag.
- Commit messages follow Conventional Commits: `feat:`, `fix:`, `refactor:`, `style:`, `test:`, `docs:`, `chore:`.
- The user makes all commits. Never commit, push, open pull requests or finish branches yourself; leave changes uncommitted and suggest a Conventional Commit message instead.
- Before starting work, check the current branch. If the task does not belong to it (e.g. tooling changes on a feature branch), tell the user and propose the correct Git Flow branch before making changes.
- Other repositories (`pyxis-cms-docs`, `pyxis-view-*`) do not use Git Flow: commit directly to `main`.
