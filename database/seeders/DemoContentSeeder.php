<?php

namespace Database\Seeders;

use App\Enums\FieldGroupMode;
use App\Enums\PageStatus;
use App\Enums\PageVisibility;
use App\Models\FieldGroup;
use App\Models\Media;
use App\Models\Page;
use App\Models\Setting;
use App\Models\User;
use App\Services\FieldGroupLocator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Demo site presenting Pyxis CMS: content blocks, pages with a subpage and images in the media library.
 * Meant for a fresh installation: run it after `php artisan migrate --seed`.
 */
class DemoContentSeeder extends Seeder
{
    private User $author;

    public function run(): void
    {
        $author = User::query()->whereRelation('role', 'slug', 'admin')->first();

        if ($author === null) {
            $this->command->error('No administrator account. Run `php artisan db:seed` first.');

            return;
        }

        if (Page::query()->exists()) {
            $this->command->warn('Pages already exist, so the demo content was not added. It is meant for a fresh installation.');

            return;
        }

        $this->author = $author;
        $this->createBlocks();

        $images = [
            'pages' => $this->storeImage('panel-strony', 'Lista stron w panelu Pyxis CMS'),
            'editor' => $this->storeImage('edytor-strony', 'Edytor strony z blokami treści'),
            'assistant' => $this->storeImage('asystent-ai', 'Asystent AI proponujący zmiany w treści'),
            'fieldGroups' => $this->storeImage('grupy-pol', 'Definicja grupy pól'),
        ];

        $home = $this->createPage(null, 'Pyxis CMS', null, [
            $this->hero('Treść, którą zarządzasz raz, a pokazujesz wszędzie', 'Pyxis CMS to headless CMS z panelem administracyjnym, REST API dla dowolnego frontendu, systemem wtyczek i asystentem AI.', 'Poznaj projekt', '/o-projekcie'),
            $this->text('Czym jest Pyxis CMS?', '<p>Pyxis CMS oddziela zarządzanie treścią od jej prezentacji. Redaktor buduje strony z bloków w panelu, a frontend – w <strong>Next.js</strong>, <strong>Astro</strong> lub dowolnej innej technologii – pobiera je jako JSON przez API.</p>'),
            ['type' => 'obraz', 'data' => ['obrazek' => $images['editor']->id]],
            $this->text('Najważniejsze funkcje', '<ul><li><p><strong>Własne pola i bloki</strong> – schemat treści definiowany w panelu, bez migracji bazy danych.</p></li><li><p><strong>Szkic i publikacja</strong> – zmiany trafiają na stronę dopiero po opublikowaniu, a podgląd pokazuje szkic.</p></li><li><p><strong>Wtyczki</strong> – rozszerzenia z akcjami i filtrami, bez zmian w rdzeniu.</p></li><li><p><strong>Asystent AI</strong> – szkice, podsumowania, SEO i tłumaczenia proponowane w panelu.</p></li></ul>'),
        ], 'Pyxis CMS – headless CMS z asystentem AI', 'Headless CMS z panelem administracyjnym, REST API dla dowolnego frontendu, systemem wtyczek i asystentem AI.', $images['editor']);

        Setting::set('homepage_id', $home->id);

        $about = $this->createPage('o-projekcie', 'O projekcie', null, [
            $this->text('Praca inżynierska', '<p>Pyxis CMS powstał jako praca inżynierska na kierunku Informatyka Politechniki Śląskiej. Celem było zaprojektowanie modularnego systemu, który łatwo rozszerzyć wtyczkami i który wspiera redaktora sztuczną inteligencją.</p>'),
            ['type' => 'galeria', 'data' => ['zdjecia' => [$images['pages']->id, $images['fieldGroups']->id, $images['assistant']->id]]],
            $this->text('Jak to działa?', '<p>Administrator definiuje <strong>grupy pól</strong>, które stają się blokami treści. Redaktor składa z nich strony, zapisuje szkic i publikuje. Frontend renderuje każdy blok własnym komponentem.</p>'),
        ], 'O projekcie Pyxis CMS', 'Cel pracy inżynierskiej i zasada działania Pyxis CMS.');

        $this->createPage('architektura', 'Architektura', $about, [
            $this->text('Backend', '<p>Laravel 12 i Filament 5 z bazą <strong>PostgreSQL</strong>. Treść stron jest zapisywana w kolumnach JSONB, a każdy rekord ma klucz UUID.</p>'),
            $this->text('Frontend', '<p>Oficjalne startery w Next.js i Astro renderują strony po stronie serwera, obsługują podgląd szkiców i odświeżają cache po publikacji.</p>'),
        ], 'Architektura Pyxis CMS', 'Backend i frontendy Pyxis CMS w skrócie.');

        $this->createPage('kontakt', 'Kontakt', null, [
            $this->hero('Masz pytania?', 'Napisz do autora projektu albo zajrzyj do dokumentacji.', 'Dokumentacja', 'https://github.com/Fablek/pyxis-cms-docs'),
        ], 'Kontakt', 'Kontakt z autorem Pyxis CMS.');

        $this->command->info('Demo content created: 4 pages, 4 content blocks and 4 images.');
    }

    /**
     * Content blocks of the demo pages, available on every page.
     */
    private function createBlocks(): void
    {
        $everyPage = [
            ['conditions' => [['param' => FieldGroupLocator::PARAM_PAGE_TYPE, 'operator' => FieldGroupLocator::OPERATOR_EQUALS, 'value' => FieldGroupLocator::TYPE_TOP_LEVEL]]],
            ['conditions' => [['param' => FieldGroupLocator::PARAM_PAGE_TYPE, 'operator' => FieldGroupLocator::OPERATOR_EQUALS, 'value' => FieldGroupLocator::TYPE_CHILD]]],
        ];

        $blocks = [
            'hero' => ['Hero', [
                ['type' => 'text', 'name' => 'naglowek', 'label' => 'Nagłówek', 'required' => true],
                ['type' => 'textarea', 'name' => 'opis', 'label' => 'Opis', 'required' => false],
                ['type' => 'link', 'name' => 'przycisk', 'label' => 'Przycisk', 'required' => false],
            ]],
            'tekst' => ['Tekst', [
                ['type' => 'text', 'name' => 'naglowek', 'label' => 'Nagłówek', 'required' => false],
                ['type' => 'wysiwyg', 'name' => 'tresc', 'label' => 'Treść', 'required' => true],
            ]],
            'obraz' => ['Obraz', [
                ['type' => 'image', 'name' => 'obrazek', 'label' => 'Obrazek', 'required' => true],
            ]],
            'galeria' => ['Galeria', [
                ['type' => 'gallery', 'name' => 'zdjecia', 'label' => 'Zdjęcia', 'required' => true],
            ]],
        ];

        foreach ($blocks as $slug => [$title, $fields]) {
            FieldGroup::query()->create([
                'title' => $title,
                'slug' => $slug,
                'mode' => FieldGroupMode::BLOCK,
                'fields' => $fields,
                'rules' => $everyPage,
                'is_active' => true,
            ]);
        }
    }

    /**
     * Copies an image from database/seeders/demo to the media library.
     */
    private function storeImage(string $name, string $alt): Media
    {
        $source = database_path("seeders/demo/{$name}.webp");
        $path = "media/{$name}.webp";

        Storage::disk('public')->put($path, file_get_contents($source));

        [$width, $height] = getimagesize($source);

        return Media::query()->create([
            'disk' => 'public',
            'directory' => 'media',
            'visibility' => 'public',
            'name' => $name,
            'path' => $path,
            'type' => 'image/webp',
            'ext' => 'webp',
            'size' => filesize($source),
            'width' => $width,
            'height' => $height,
            'alt' => $alt,
        ]);
    }

    /**
     * Creates a published page whose draft matches the published version.
     *
     * @param  array<int, array{type: string, data: array<string, mixed>}>  $content
     */
    private function createPage(?string $slug, string $title, ?Page $parent, array $content, string $seoTitle, string $seoDescription, ?Media $shareImage = null): Page
    {
        $seo = ['title' => $seoTitle, 'description' => $seoDescription, 'og_image' => $shareImage?->id, 'noindex' => false];

        return Page::query()->create([
            'title' => $title,
            'slug' => $slug,
            'parent_id' => $parent?->id,
            'content' => $content,
            'content_draft' => $content,
            'seo' => $seo,
            'seo_draft' => $seo,
            'status' => PageStatus::PUBLISHED,
            'visibility' => PageVisibility::PUBLIC,
            'published_at' => now(),
            'user_id' => $this->author->id,
        ]);
    }

    /**
     * @return array{type: string, data: array<string, mixed>}
     */
    private function hero(string $heading, string $description, string $buttonTitle, string $buttonUrl): array
    {
        return ['type' => 'hero', 'data' => [
            'naglowek' => $heading,
            'opis' => $description,
            'przycisk' => ['url' => $buttonUrl, 'title' => $buttonTitle, 'new_tab' => str_starts_with($buttonUrl, 'http')],
        ]];
    }

    /**
     * @return array{type: string, data: array<string, mixed>}
     */
    private function text(?string $heading, string $html): array
    {
        return ['type' => 'tekst', 'data' => ['naglowek' => $heading, 'tresc' => $html]];
    }
}
