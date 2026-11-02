# Pyxis CMS

A headless CMS with an admin panel, a REST API for separate frontends, a plugin system and a built-in AI assistant.

Pyxis CMS is an engineering thesis project developed at the **Silesian University of Technology** (Computer Science, 2025/2026) by Sebastian Pytka, supervised by dr inż. Sebastian Pabiasz.

## Features

- **Custom fields and a block builder** – field groups defined in the panel, assigned to pages by location rules, used as static fields or as content blocks.
- **Headless REST API** – pages served as structured JSON. Published content is public, the draft preview requires a preview token.
- **Draft and publish** – every page keeps an edited draft of its content, fields and SEO next to the published version (Shadow Content).
- **SEO** – title, description, share image and `noindex` per page, returned by the API in a fixed shape.
- **Plugins** – extensions in `py-content/plugins`, loaded without changes to the core, with actions and filters (hooks). Example plugins: `hello-pyxis` and `pyxis-forms` (contact forms).
- **AI assistant** – a side panel in the page form that drafts new blocks, summarizes the page, fills in SEO, translates and changes the style of texts. The editor applies or rejects each proposed change before saving the draft.
- **Roles** – administrators manage users, field groups, plugins and AI settings; editors work with the content.
- **Cache revalidation** – after publishing, the frontends are asked to refresh the changed paths.

## Tech stack

- PHP 8.4, Laravel 12, Filament 5, Livewire 4
- PostgreSQL 16 (JSONB columns, UUID keys)
- `laravel/ai` for the AI providers (Anthropic, OpenAI, Google Gemini, Mistral)
- Pest for tests
- Docker Compose for the development environment

## Related repositories

| Repository | Description |
| :--- | :--- |
| [pyxis-cms-docs](https://github.com/Fablek/pyxis-cms-docs) | Documentation (Astro Starlight, Polish and English) – the specification of the system |
| [pyxis-view-next](https://github.com/Fablek/pyxis-view-next) | Next.js starter frontend |
| [pyxis-view-astro](https://github.com/Fablek/pyxis-view-astro) | Astro starter frontend |

## Getting started

Requirements: Docker with Docker Compose, and PHP 8.4, Composer and Node.js on the host (for building the panel assets and running the tests).

1. Create the shared Docker network used by the backend and the frontends:

   ```bash
   docker network create pyxis-network
   ```

2. Create the environment file and set the frontend variables (`FRONTEND_URL`, `REVALIDATE_URLS`, `REVALIDATE_TOKEN`), described in the [environment variables](https://github.com/Fablek/pyxis-cms-docs/blob/main/src/content/docs/en/architecture/env.md) page:

   ```bash
   cp .env.example .env
   ```

   The database connection is set by `docker-compose.yml`.

3. Install the dependencies and build the panel assets:

   ```bash
   composer install
   npm install
   npm run build
   ```

4. Start the containers and prepare the application:

   ```bash
   docker compose up -d --build
   docker exec pyxis-admin php artisan key:generate
   docker exec pyxis-admin php artisan migrate --seed
   docker exec pyxis-admin php artisan storage:link
   ```

The panel is available at [http://localhost:8000](http://localhost:8000) and the API at `http://localhost:8000/api`. The seeder creates the administrator and editor roles and an administrator account (see `database/seeders/DatabaseSeeder.php`); change its password after the first sign-in.

To use the AI assistant, an administrator enables AI in **Settings**, selects a provider and enters its API key. The key is stored encrypted.

## Tests

Run the tests on the host. They use an in-memory SQLite database:

```bash
php artisan test --compact
```

Do not run them inside the `pyxis-admin` container. Its environment points to the development PostgreSQL database, and the tests would erase it.

## Contributing

The repository uses Git Flow (`main`, `develop`, `feature/*`, `release/*`, `hotfix/*`, version tags such as `1.6.0`) and Conventional Commits (`feat:`, `fix:`, `docs:` and others). Code style is checked with Laravel Pint.

The documentation is the specification: a change to documented behavior also updates the Polish and English pages in `pyxis-cms-docs`.
