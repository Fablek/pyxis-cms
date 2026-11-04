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
 * Demo site presenting Pyxis CMS: a landing page, subpages, every field type in content blocks and static
 * page fields, and images in the media library. Meant for a fresh installation: run it after `php artisan migrate --seed`.
 */
class DemoContentSeeder extends Seeder
{
    private User $author;

    /**
     * @var array<string, Media>
     */
    private array $images = [];

    /**
     * @var array<string, Page>
     */
    private array $pages = [];

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

        $this->storeImages();
        $this->createPages();
        $this->createFieldGroups();
        $this->fillPages();

        Setting::set('homepage_id', $this->pages['home']->id);

        $this->command->info(sprintf(
            'Demo content created: %d pages, %d field groups and %d images.',
            count($this->pages),
            FieldGroup::query()->count(),
            count($this->images),
        ));
    }

    /**
     * Copies the images from database/seeders/demo to the media library.
     */
    private function storeImages(): void
    {
        $images = [
            'pages' => ['panel-strony', 'Lista stron w panelu Pyxis CMS'],
            'editor' => ['edytor-strony', 'Edytor strony z blokami treści'],
            'assistant' => ['asystent-ai', 'Asystent AI proponujący zmianę opisu SEO'],
            'fieldGroups' => ['grupy-pol', 'Edytor grupy pól z regułami lokalizacji'],
            'media' => ['biblioteka-mediow', 'Biblioteka mediów w panelu'],
            'plugins' => ['wtyczki', 'Lista wtyczek w panelu'],
            'developers' => ['dla-deweloperow', 'Laptop z kodem na biurku'],
            'editors' => ['dla-redaktorow', 'Osoba pracująca przy laptopie'],
            'teams' => ['dla-zespolow', 'Zespół pracujący wspólnie przy laptopie'],
        ];

        foreach ($images as $key => [$name, $alt]) {
            $source = database_path("seeders/demo/{$name}.webp");
            $path = "media/{$name}.webp";

            Storage::disk('public')->put($path, file_get_contents($source));

            [$width, $height] = getimagesize($source);

            $this->images[$key] = Media::query()->create([
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
    }

    /**
     * Creates the pages first, so the blocks can link to each other through page relation fields.
     */
    private function createPages(): void
    {
        $pages = [
            'home' => [null, 'Pyxis CMS', null],
            'features' => ['funkcje', 'Funkcje', null],
            'assistant' => ['asystent-ai', 'Asystent AI', 'features'],
            'plugins' => ['wtyczki', 'Wtyczki', 'features'],
            'developers' => ['dla-deweloperow', 'Dla deweloperów', null],
            'faq' => ['faq', 'Najczęstsze pytania', null],
            'about' => ['o-projekcie', 'O projekcie', null],
            'contact' => ['kontakt', 'Kontakt', null],
        ];

        foreach ($pages as $key => [$slug, $title, $parent]) {
            $this->pages[$key] = Page::query()->create([
                'title' => $title,
                'slug' => $slug,
                'parent_id' => $parent !== null ? $this->pages[$parent]->id : null,
                'status' => PageStatus::PUBLISHED,
                'visibility' => PageVisibility::PUBLIC,
                'published_at' => now(),
                'user_id' => $this->author->id,
            ]);
        }
    }

    /**
     * Content blocks available on every page, which together use every field type, and the static fields of the contact page.
     */
    private function createFieldGroups(): void
    {
        $everyPage = [
            ['conditions' => [['param' => FieldGroupLocator::PARAM_PAGE_TYPE, 'operator' => FieldGroupLocator::OPERATOR_EQUALS, 'value' => FieldGroupLocator::TYPE_TOP_LEVEL]]],
            ['conditions' => [['param' => FieldGroupLocator::PARAM_PAGE_TYPE, 'operator' => FieldGroupLocator::OPERATOR_EQUALS, 'value' => FieldGroupLocator::TYPE_CHILD]]],
        ];

        $buttonStyle = $this->field('select', 'styl', 'Styl', options: ['glowny' => 'Główny', 'drugorzedny' => 'Drugorzędny']);

        $blocks = [
            'hero' => ['Hero', [
                $this->field('text', 'etykieta', 'Etykieta'),
                $this->field('text', 'naglowek', 'Nagłówek', required: true),
                $this->field('textarea', 'opis', 'Opis', ['rows' => 3]),
                $this->field('image', 'obraz', 'Obraz'),
                $this->field('repeater', 'przyciski', 'Przyciski', ['max_items' => 2, 'button_label' => 'Dodaj przycisk', 'sub_fields' => [
                    $this->field('link', 'link', 'Link', required: true),
                    $buttonStyle,
                ]]),
            ]],
            'liczby' => ['Liczby', [
                $this->field('repeater', 'elementy', 'Elementy', ['min_items' => 1, 'max_items' => 4, 'sub_fields' => [
                    $this->field('number', 'wartosc', 'Wartość', ['min' => 0], required: true),
                    $this->field('text', 'sufiks', 'Sufiks', ['max_length' => 4]),
                    $this->field('text', 'opis', 'Opis', required: true),
                ]]),
            ]],
            'funkcje' => ['Funkcje', [
                $this->field('text', 'naglowek', 'Nagłówek'),
                $this->field('textarea', 'wstep', 'Wstęp', ['rows' => 2]),
                $this->field('repeater', 'elementy', 'Elementy', ['button_label' => 'Dodaj funkcję', 'sub_fields' => [
                    $this->field('select', 'ikona', 'Ikona', options: [
                        'bloki' => 'Bloki', 'szkic' => 'Szkic', 'ai' => 'AI', 'wtyczka' => 'Wtyczka', 'kod' => 'Kod',
                        'seo' => 'SEO', 'media' => 'Media', 'role' => 'Role', 'jezyki' => 'Języki',
                    ]),
                    $this->field('text', 'tytul', 'Tytuł', required: true),
                    $this->field('textarea', 'opis', 'Opis', ['rows' => 2]),
                    $this->field('page', 'strona', 'Strona ze szczegółami'),
                ]]),
            ]],
            'dla_kogo' => ['Dla kogo', [
                $this->field('text', 'naglowek', 'Nagłówek'),
                $this->field('repeater', 'elementy', 'Elementy', ['max_items' => 3, 'sub_fields' => [
                    $this->field('image', 'zdjecie', 'Zdjęcie', required: true),
                    $this->field('text', 'tytul', 'Tytuł', required: true),
                    $this->field('textarea', 'opis', 'Opis', ['rows' => 3]),
                    $this->field('link', 'link', 'Link'),
                ]]),
            ]],
            'tekst_obraz' => ['Tekst z obrazem', [
                $this->field('text', 'etykieta', 'Etykieta'),
                $this->field('text', 'naglowek', 'Nagłówek', required: true),
                $this->field('wysiwyg', 'tresc', 'Treść'),
                $this->field('image', 'obraz', 'Obraz', required: true),
                $this->field('select', 'uklad', 'Układ', options: ['obraz_prawo' => 'Obraz po prawej', 'obraz_lewo' => 'Obraz po lewej']),
                $this->field('toggle', 'wyroznione', 'Wyróżnione tło'),
            ]],
            'kod' => ['Kod', [
                $this->field('text', 'tytul', 'Tytuł'),
                $this->field('select', 'jezyk', 'Język', options: ['json' => 'JSON', 'php' => 'PHP', 'typescript' => 'TypeScript', 'astro' => 'Astro', 'bash' => 'Bash']),
                $this->field('textarea', 'kod', 'Kod', ['rows' => 12], required: true),
                $this->field('textarea', 'opis', 'Opis', ['rows' => 2]),
            ]],
            'kroki' => ['Kroki', [
                $this->field('text', 'naglowek', 'Nagłówek'),
                $this->field('repeater', 'elementy', 'Kroki', ['button_label' => 'Dodaj krok', 'sub_fields' => [
                    $this->field('text', 'tytul', 'Tytuł', required: true),
                    $this->field('textarea', 'opis', 'Opis', ['rows' => 2]),
                ]]),
            ]],
            'faq' => ['FAQ', [
                $this->field('text', 'naglowek', 'Nagłówek'),
                $this->field('repeater', 'pytania', 'Pytania', ['button_label' => 'Dodaj pytanie', 'sub_fields' => [
                    $this->field('text', 'pytanie', 'Pytanie', required: true),
                    $this->field('wysiwyg', 'odpowiedz', 'Odpowiedź', required: true),
                ]]),
            ]],
            'os_czasu' => ['Oś czasu', [
                $this->field('text', 'naglowek', 'Nagłówek'),
                $this->field('repeater', 'wydarzenia', 'Wydarzenia', ['button_label' => 'Dodaj wydarzenie', 'sub_fields' => [
                    $this->field('text', 'etykieta', 'Etykieta', required: true),
                    $this->field('text', 'data', 'Data'),
                    $this->field('textarea', 'opis', 'Opis', ['rows' => 2]),
                ]]),
            ]],
            'zobacz_tez' => ['Zobacz też', [
                $this->field('text', 'naglowek', 'Nagłówek'),
                $this->field('page', 'strony', 'Strony', ['multiple' => true], required: true),
            ]],
            'cta' => ['Wezwanie do działania', [
                $this->field('text', 'naglowek', 'Nagłówek', required: true),
                $this->field('textarea', 'opis', 'Opis', ['rows' => 2]),
                $this->field('group', 'przycisk', 'Przycisk', ['sub_fields' => [
                    $this->field('link', 'link', 'Link'),
                    $buttonStyle,
                ]]),
                $this->field('toggle', 'ciemne', 'Ciemne tło', ['default_value' => true]),
            ]],
            'tekst' => ['Tekst', [
                $this->field('text', 'naglowek', 'Nagłówek'),
                $this->field('wysiwyg', 'tresc', 'Treść', required: true),
            ]],
            'obraz' => ['Obraz', [
                $this->field('image', 'obrazek', 'Obrazek', required: true),
            ]],
            'galeria' => ['Galeria', [
                $this->field('text', 'naglowek', 'Nagłówek'),
                $this->field('gallery', 'zdjecia', 'Zdjęcia', required: true),
            ]],
        ];

        foreach ($blocks as $slug => [$title, $fields]) {
            $this->createFieldGroup($slug, $title, FieldGroupMode::BLOCK, $fields, $everyPage);
        }

        $this->createFieldGroup('kontakt', 'Dane kontaktowe', FieldGroupMode::STATIC, [
            $this->field('link', 'repozytorium', 'Repozytorium'),
            $this->field('link', 'dokumentacja', 'Dokumentacja'),
            $this->field('link', 'zgloszenia', 'Zgłoszenia błędów'),
            $this->field('group', 'uczelnia', 'Uczelnia', ['sub_fields' => [
                $this->field('text', 'nazwa', 'Nazwa'),
                $this->field('text', 'kierunek', 'Kierunek'),
                $this->field('text', 'rok', 'Rok akademicki'),
            ]]),
        ], [
            ['conditions' => [['param' => FieldGroupLocator::PARAM_PAGE, 'operator' => FieldGroupLocator::OPERATOR_EQUALS, 'value' => $this->pages['contact']->id]]],
        ]);
    }

    private function fillPages(): void
    {
        $github = 'https://github.com/Fablek/pyxis-cms';
        $docs = 'https://pyxis-cms-docs.vercel.app/pl/intro';

        $this->fillPage('home', 'Pyxis CMS – headless CMS z asystentem AI', 'Headless CMS z panelem administracyjnym, REST API dla dowolnego frontendu, systemem wtyczek i asystentem AI.', [
            $this->block('hero', [
                'etykieta' => 'Headless CMS · wersja 1.7',
                'naglowek' => 'Treść w panelu, prezentacja w dowolnym frontendzie.',
                'opis' => 'Pyxis CMS przechowuje strony, własne pola i media, a frontend pobiera je przez REST API jako JSON. Projekt jest pracą inżynierską na kierunku Informatyka Politechniki Śląskiej.',
                'obraz' => $this->images['editor']->id,
                'przyciski' => [
                    ['link' => $this->link('/funkcje', 'Funkcje'), 'styl' => 'glowny'],
                    ['link' => $this->link($github, 'Kod źródłowy'), 'styl' => 'drugorzedny'],
                ],
            ]),
            $this->block('liczby', ['elementy' => [
                ['wartosc' => 12, 'sufiks' => '', 'opis' => 'typów pól, od tekstu po repeatery'],
                ['wartosc' => 2, 'sufiks' => '', 'opis' => 'oficjalne startery: Next.js i Astro'],
                ['wartosc' => 4, 'sufiks' => '', 'opis' => 'dostawców AI do wyboru'],
                ['wartosc' => 150, 'sufiks' => '+', 'opis' => 'testów automatycznych'],
            ]]),
            $this->block('funkcje', [
                'naglowek' => 'Moduły systemu',
                'wstep' => 'Panel administracyjny w Filament, treść w PostgreSQL (JSONB), dostęp przez REST API. Każdy moduł jest opisany w dokumentacji.',
                'elementy' => [
                    ['ikona' => 'bloki', 'tytul' => 'Własne pola i bloki', 'opis' => 'Grupy pól definiowane w panelu, 12 typów pól, reguły lokalizacji. Bez migracji bazy danych.', 'strona' => $this->pages['features']->id],
                    ['ikona' => 'szkic', 'tytul' => 'Szkic i publikacja', 'opis' => 'Osobne kolumny szkicu i wersji opublikowanej. Podgląd szkicu przez podpisany link.', 'strona' => $this->pages['features']->id],
                    ['ikona' => 'ai', 'tytul' => 'Asystent AI', 'opis' => 'Propozycje zmian w polach strony do zaakceptowania lub odrzucenia. Do wyboru czterech dostawców.', 'strona' => $this->pages['assistant']->id],
                    ['ikona' => 'wtyczka', 'tytul' => 'Wtyczki', 'opis' => 'Akcje i filtry, trasy API, migracje i własne typy pól. Instalacja z archiwum ZIP.', 'strona' => $this->pages['plugins']->id],
                    ['ikona' => 'kod', 'tytul' => 'REST API', 'opis' => 'Strony pod adresem /api/pages z blokami, polami, SEO, obrazami i linkami do stron.', 'strona' => $this->pages['developers']->id],
                    ['ikona' => 'role', 'tytul' => 'Role i uprawnienia', 'opis' => 'Administrator zarządza kontami, grupami pól i wtyczkami. Redaktor pracuje z treścią.', 'strona' => null],
                ],
            ]),
            $this->block('dla_kogo', [
                'naglowek' => 'Trzy role w projekcie',
                'elementy' => [
                    ['zdjecie' => $this->images['developers']->id, 'tytul' => 'Deweloperzy', 'opis' => 'Frontend w dowolnej technologii. Startery w Next.js i Astro mają gotowe pobieranie stron, podgląd i rewalidację.', 'link' => $this->link('/dla-deweloperow', 'Zacznij od startera')],
                    ['zdjecie' => $this->images['editors']->id, 'tytul' => 'Redaktorzy', 'opis' => 'Strony składane z bloków, szkic sprawdzany w podglądzie, publikacja z datą i widocznością.', 'link' => $this->link('/funkcje', 'Zobacz panel')],
                    ['zdjecie' => $this->images['teams']->id, 'tytul' => 'Administratorzy', 'opis' => 'Schemat treści i konta po stronie administratora, treść po stronie redaktorów, funkcje dodatkowe we wtyczkach.', 'link' => $this->link('/funkcje/wtyczki', 'Poznaj wtyczki')],
                ],
            ]),
            $this->block('tekst_obraz', [
                'etykieta' => 'Panel',
                'naglowek' => 'Lista i hierarchia stron',
                'tresc' => '<p>Lista stron pokazuje status, widoczność i autora, a hierarchia stron buduje adresy takie jak <code>/funkcje/asystent-ai</code>.</p><ul><li><p>publikacja z datą i widocznością (publiczna, prywatna, chroniona hasłem),</p></li><li><p>podgląd szkicu na żywo w starterze,</p></li><li><p>SEO i obrazek udostępniania dla każdej strony.</p></li></ul>',
                'obraz' => $this->images['pages']->id,
                'uklad' => 'obraz_prawo',
                'wyroznione' => false,
            ]),
            $this->block('cta', [
                'naglowek' => 'Uruchomienie lokalne',
                'opis' => 'Docker Compose, migracja bazy i seeder z tymi danymi demo. Kolejne kroki są na stronie dla deweloperów.',
                'przycisk' => ['link' => $this->link('/dla-deweloperow', 'Instrukcja'), 'styl' => 'glowny'],
                'ciemne' => true,
            ]),
        ], $this->images['editor']);

        $this->fillPage('features', 'Funkcje Pyxis CMS', 'Własne pola i bloki, szkice i publikacja, biblioteka mediów, wtyczki i asystent AI.', [
            $this->block('hero', [
                'etykieta' => 'Funkcje',
                'naglowek' => 'Od schematu treści do publikacji.',
                'opis' => 'Grupy pól, szkice, biblioteka mediów i wtyczki. Poniżej opis każdego modułu ze zrzutem z panelu.',
                'obraz' => null,
                'przyciski' => [['link' => $this->link('/dla-deweloperow', 'Dla deweloperów'), 'styl' => 'glowny']],
            ]),
            $this->block('tekst_obraz', [
                'etykieta' => 'Schemat treści',
                'naglowek' => 'Własne pola i bloki',
                'tresc' => '<p>Administrator tworzy <strong>grupy pól</strong> z 12 typów pól, od tekstu i obrazu po repeatery i grupy. Reguły lokalizacji decydują, przy których stronach grupa jest dostępna.</p><p>Grupa działa jako <strong>stałe pola strony</strong> albo jako <strong>blok treści</strong>, który redaktor dodaje dowolną liczbę razy.</p>',
                'obraz' => $this->images['fieldGroups']->id,
                'uklad' => 'obraz_prawo',
                'wyroznione' => false,
            ]),
            $this->block('tekst_obraz', [
                'etykieta' => 'Praca redaktora',
                'naglowek' => 'Szkic, podgląd, publikacja',
                'tresc' => '<p>Każda strona ma wersję roboczą i opublikowaną (<em>Shadow Content</em>). „Zapisz szkic” nie zmienia strony widocznej dla odwiedzających, a podgląd na żywo otwiera szkic w starterze.</p><p>Po publikacji CMS prosi frontendy o odświeżenie zmienionych adresów.</p>',
                'obraz' => $this->images['editor']->id,
                'uklad' => 'obraz_lewo',
                'wyroznione' => true,
            ]),
            $this->block('tekst_obraz', [
                'etykieta' => 'Media',
                'naglowek' => 'Biblioteka mediów',
                'tresc' => '<p>Obrazy trafiają do jednej biblioteki z opisem alternatywnym i wymiarami. API zwraca je jako gotowe obiekty z adresem, więc frontend nie musi niczego składać.</p>',
                'obraz' => $this->images['media']->id,
                'uklad' => 'obraz_prawo',
                'wyroznione' => false,
            ]),
            $this->block('galeria', [
                'naglowek' => 'Panel w zrzutach ekranu',
                'zdjecia' => [$this->images['pages']->id, $this->images['fieldGroups']->id, $this->images['assistant']->id, $this->images['media']->id, $this->images['plugins']->id, $this->images['editor']->id],
            ]),
            $this->block('zobacz_tez', [
                'naglowek' => 'Szczegóły modułów',
                'strony' => [$this->pages['assistant']->id, $this->pages['plugins']->id, $this->pages['developers']->id],
            ]),
        ]);

        $this->fillPage('assistant', 'Asystent AI w Pyxis CMS', 'Asystent AI proponuje szkice, podsumowania, SEO i tłumaczenia, a redaktor decyduje, co trafia na stronę.', [
            $this->block('hero', [
                'etykieta' => 'Asystent AI',
                'naglowek' => 'Asystent proponuje zmiany, redaktor je zatwierdza.',
                'opis' => 'Panel asystenta działa obok formularza strony. Każda zmiana to karta „Było / Będzie”, którą redaktor stosuje albo odrzuca.',
                'obraz' => $this->images['assistant']->id,
                'przyciski' => [],
            ]),
            $this->block('funkcje', [
                'naglowek' => 'Szybkie akcje',
                'wstep' => 'Gotowe polecenia w panelu asystenta. Można też wpisać własne.',
                'elementy' => [
                    ['ikona' => 'seo', 'tytul' => 'Uzupełnij SEO', 'opis' => 'Tytuł i opis w zalecanych limitach znaków, na podstawie treści strony.', 'strona' => null],
                    ['ikona' => 'bloki', 'tytul' => 'Szkic strony', 'opis' => 'Nowe bloki z typów dostępnych na stronie, z wypełnionymi polami tekstowymi.', 'strona' => null],
                    ['ikona' => 'szkic', 'tytul' => 'Podsumuj', 'opis' => 'Krótkie podsumowanie treści w odpowiedzi, bez zmian w polach.', 'strona' => null],
                    ['ikona' => 'jezyki', 'tytul' => 'Tłumaczenia', 'opis' => 'Przekład treści na polski lub angielski z zachowaniem formatowania.', 'strona' => null],
                ],
            ]),
            $this->block('kroki', [
                'naglowek' => 'Jak to działa',
                'elementy' => [
                    ['tytul' => 'Otwórz asystenta', 'opis' => 'Ikona w nagłówku formularza strony wysuwa panel z prawej strony.'],
                    ['tytul' => 'Wybierz akcję lub napisz polecenie', 'opis' => 'Asystent widzi aktualne, także niezapisane wartości pól.'],
                    ['tytul' => 'Przejrzyj propozycje', 'opis' => 'Każda zmiana pokazuje wartość przed i po. Stosujesz wybrane albo wszystkie naraz.'],
                    ['tytul' => 'Zapisz szkic', 'opis' => 'Zastosowane zmiany trafiają do formularza i są zapisywane dopiero razem ze szkicem.'],
                ],
            ]),
            $this->block('tekst', [
                'naglowek' => 'Dostawca i klucz API',
                'tresc' => '<p>Administrator wybiera dostawcę (<strong>Anthropic, OpenAI, Google Gemini lub Mistral</strong>) i wpisuje klucz API, który jest przechowywany w bazie w postaci zaszyfrowanej i nigdy nie wraca do przeglądarki.</p>',
            ]),
            $this->block('zobacz_tez', [
                'naglowek' => 'Zobacz też',
                'strony' => [$this->pages['plugins']->id, $this->pages['features']->id],
            ]),
        ]);

        $this->fillPage('plugins', 'Wtyczki w Pyxis CMS', 'System wtyczek Pyxis CMS: akcje, filtry, trasy API, migracje i własne typy pól bez zmian w rdzeniu.', [
            $this->block('hero', [
                'etykieta' => 'Wtyczki',
                'naglowek' => 'Rozszerzenia bez zmian w rdzeniu.',
                'opis' => 'Wtyczka to katalog w py-content/plugins albo archiwum ZIP wgrane w panelu. Włączasz ją jednym przełącznikiem.',
                'obraz' => $this->images['plugins']->id,
                'przyciski' => [['link' => $this->link('https://pyxis-cms-docs.vercel.app/pl/dev/plugins', 'Jak napisać wtyczkę'), 'styl' => 'glowny']],
            ]),
            $this->block('kod', [
                'tytul' => 'Filtr i akcja w kilku linijkach',
                'jezyk' => 'php',
                'kod' => <<<'PHP'
                    use App\Models\Page;
                    use App\Plugins\Hook;

                    // Adds an estimated reading time to every page returned by the API.
                    Hook::filter('page.api', function (array $data, Page $page): array {
                        $data['reading_time'] = 3;

                        return $data;
                    });

                    // Reacts to every published page.
                    Hook::action('page.published', function (Page $page): void {
                        logger("Published: {$page->title}");
                    });
                    PHP,
                'opis' => 'Filtr zmienia wartość i zwraca nową, akcja tylko reaguje na zdarzenie.',
            ]),
            $this->block('funkcje', [
                'naglowek' => 'Co może wtyczka',
                'wstep' => null,
                'elementy' => [
                    ['ikona' => 'kod', 'tytul' => 'Trasy API', 'opis' => 'Własne punkty końcowe pod /api/plugins/{slug}.', 'strona' => null],
                    ['ikona' => 'bloki', 'tytul' => 'Typy pól', 'opis' => 'Nowe typy pól w grupach pól, np. wybór formularza.', 'strona' => null],
                    ['ikona' => 'media', 'tytul' => 'Panel', 'opis' => 'Zasoby i strony w panelu administracyjnym.', 'strona' => null],
                    ['ikona' => 'role', 'tytul' => 'Migracje', 'opis' => 'Własne tabele, cofane przy usuwaniu wtyczki z danymi.', 'strona' => null],
                ],
            ]),
            $this->block('cta', [
                'naglowek' => 'Przykładowa wtyczka',
                'opis' => 'Pyxis Forms dodaje formularze kontaktowe: typ pola, trasy API, powiadomienia e-mail i zgłoszenia w panelu.',
                'przycisk' => ['link' => $this->link('https://pyxis-cms-docs.vercel.app/pl/dev/plugin-example', 'Przykład: Pyxis Forms'), 'styl' => 'glowny'],
                'ciemne' => false,
            ]),
        ]);

        $this->fillPage('developers', 'Pyxis CMS dla deweloperów', 'REST API, startery Next.js i Astro, podgląd szkiców i rewalidacja cache. Instrukcja uruchomienia Pyxis CMS.', [
            $this->block('hero', [
                'etykieta' => 'Dla deweloperów',
                'naglowek' => 'REST API i dwa startery.',
                'opis' => 'Treść jest zwracana jako JSON. Startery w Next.js i Astro zawierają klienta API, komponenty bloków, podgląd szkiców i rewalidację cache.',
                'obraz' => null,
                'przyciski' => [
                    ['link' => $this->link('https://github.com/Fablek/pyxis-view-next', 'Starter Next.js'), 'styl' => 'glowny'],
                    ['link' => $this->link('https://github.com/Fablek/pyxis-view-astro', 'Starter Astro'), 'styl' => 'drugorzedny'],
                ],
            ]),
            $this->block('kroki', [
                'naglowek' => 'Uruchomienie',
                'elementy' => [
                    ['tytul' => 'Sieć Dockera', 'opis' => 'docker network create pyxis-network: wspólna sieć backendu i starterów.'],
                    ['tytul' => 'Kontenery CMS', 'opis' => 'docker compose up -d --build w repozytorium pyxis-cms.'],
                    ['tytul' => 'Baza i dane demo', 'opis' => 'php artisan migrate --seed, a potem db:seed --class=DemoContentSeeder.'],
                    ['tytul' => 'Starter', 'opis' => 'docker compose up -d w repozytorium startera. Strona jest dostępna na porcie 3000 (Astro) lub 3001 (Next.js).'],
                ],
            ]),
            $this->block('kod', [
                'tytul' => 'GET /api/pages/kontakt',
                'jezyk' => 'json',
                'kod' => <<<'JSON'
                    {
                        "data": {
                            "title": "Kontakt",
                            "full_url": "/kontakt",
                            "content": [
                                { "type": "tekst", "data": { "naglowek": "Kontakt z projektem", "tresc": "<p>…</p>" } }
                            ],
                            "fields": {
                                "kontakt": { "repozytorium": { "url": "https://github.com/…", "title": "GitHub", "new_tab": true } }
                            },
                            "seo": { "title": "Kontakt – Pyxis CMS", "description": "…", "og_image": null, "noindex": false }
                        }
                    }
                    JSON,
                'opis' => 'Bloki treści w content, stałe pola w fields, a obrazy i linki do stron już przekształcone.',
            ]),
            $this->block('kod', [
                'tytul' => 'Komponent bloku w Astro',
                'jezyk' => 'astro',
                'kod' => <<<'ASTRO'
                    ---
                    import type { PyxisBlock } from '@/lib/pyxis/types';

                    interface Props {
                      block: PyxisBlock<{ naglowek: string | null }>;
                    }

                    const { naglowek } = Astro.props.block.data;
                    ---

                    <section class="py-16">
                      <h2 class="text-3xl font-bold">{naglowek}</h2>
                    </section>
                    ASTRO,
                'opis' => 'Komponent rejestruje się w blocks/index.ts pod slugiem grupy pól.',
            ]),
            $this->block('cta', [
                'naglowek' => 'Pełna dokumentacja',
                'opis' => 'API, startery, wtyczki i architektura, po polsku i po angielsku.',
                'przycisk' => ['link' => $this->link($docs, 'Otwórz dokumentację'), 'styl' => 'glowny'],
                'ciemne' => true,
            ]),
        ]);

        $this->fillPage('faq', 'FAQ – Pyxis CMS', 'Odpowiedzi na najczęstsze pytania o Pyxis CMS: frontendy, szkice, AI, wtyczki i bezpieczeństwo.', [
            $this->block('faq', [
                'naglowek' => null,
                'pytania' => [
                    ['pytanie' => 'Czym headless CMS różni się od tradycyjnego?', 'odpowiedz' => '<p>Tradycyjny CMS sam renderuje strony. Headless CMS przechowuje treść i udostępnia ją przez API, a wygląd tworzy osobny frontend w dowolnej technologii.</p>'],
                    ['pytanie' => 'Jakiego frontendu mogę użyć?', 'odpowiedz' => '<p>Dowolnego, który potrafi pobrać JSON. Oficjalne startery są w <strong>Next.js</strong> i <strong>Astro</strong> i wyglądają tak samo. Ta strona działa na obu.</p>'],
                    ['pytanie' => 'Czy zmiany od razu trafiają na stronę?', 'odpowiedz' => '<p>Nie. „Zapisz szkic” zapisuje wersję roboczą, a odwiedzający widzą ją dopiero po publikacji. Szkic sprawdzisz wcześniej w podglądzie na żywo.</p>'],
                    ['pytanie' => 'Czy asystent AI może sam coś opublikować?', 'odpowiedz' => '<p>Nie. Asystent tylko proponuje zmiany, a redaktor stosuje je w formularzu. Nawet zastosowana zmiana trafia najpierw do szkicu.</p>'],
                    ['pytanie' => 'Czy muszę płacić za AI?', 'odpowiedz' => '<p>Funkcje AI są opcjonalne i korzystają z Twojego klucza u wybranego dostawcy. Bez klucza CMS działa normalnie, a asystent jest ukryty.</p>'],
                    ['pytanie' => 'Jak dodać nowe funkcje bez zmieniania kodu CMS?', 'odpowiedz' => '<p>Przez wtyczki. Wtyczka korzysta z akcji i filtrów, może dodać trasy API, tabele, typy pól i strony w panelu.</p>'],
                    ['pytanie' => 'Kto może zmieniać schemat treści i konta?', 'odpowiedz' => '<p>Tylko administrator. Redaktor pracuje ze stronami i mediami, ale nie widzi użytkowników, grup pól, wtyczek ani ustawień AI.</p>'],
                ],
            ]),
            $this->block('cta', [
                'naglowek' => 'Inne pytania',
                'opis' => 'Dokumentacja opisuje każdy moduł. Pozostałe pytania można zgłosić w repozytorium.',
                'przycisk' => ['link' => $this->link('/kontakt', 'Kontakt'), 'styl' => 'glowny'],
                'ciemne' => false,
            ]),
        ]);

        $this->fillPage('about', 'O projekcie Pyxis CMS', 'Pyxis CMS jako praca inżynierska na Politechnice Śląskiej: cel projektu i historia wydań.', [
            $this->block('tekst_obraz', [
                'etykieta' => 'Praca inżynierska',
                'naglowek' => 'Cel projektu',
                'tresc' => '<p>Pyxis CMS powstał jako praca inżynierska na kierunku <strong>Informatyka</strong> Politechniki Śląskiej (rok akademicki 2025/2026). Autor: Sebastian Pytka, promotor: dr inż. Sebastian Pabiasz.</p><p>Celem było zaprojektowanie modularnego systemu, który oddziela treść od prezentacji, daje się rozszerzać wtyczkami i wspiera redaktora sztuczną inteligencją.</p>',
                'obraz' => $this->images['teams']->id,
                'uklad' => 'obraz_prawo',
                'wyroznione' => false,
            ]),
            $this->block('liczby', ['elementy' => [
                ['wartosc' => 8, 'sufiks' => '', 'opis' => 'wydań od kwietnia 2026'],
                ['wartosc' => 4, 'sufiks' => '', 'opis' => 'repozytoria: CMS, dwa startery i dokumentacja'],
                ['wartosc' => 2, 'sufiks' => '', 'opis' => 'języki dokumentacji'],
            ]]),
            $this->block('os_czasu', [
                'naglowek' => 'Historia wydań',
                'wydarzenia' => [
                    ['etykieta' => '1.0.0', 'data' => 'kwiecień 2026', 'opis' => 'Fundament: panel administracyjny, strony z hierarchią, ustawienia, użytkownicy i role.'],
                    ['etykieta' => '1.1–1.3', 'data' => 'kwiecień 2026', 'opis' => 'Biblioteka mediów i pełne tłumaczenie panelu.'],
                    ['etykieta' => '1.4.0', 'data' => 'październik 2026', 'opis' => 'Własne pola: grupy pól, reguły lokalizacji, bloki treści i wartości w API.'],
                    ['etykieta' => '1.5.0', 'data' => 'październik 2026', 'opis' => 'System wtyczek: akcje i filtry, archiwa ZIP, własne typy pól.'],
                    ['etykieta' => '1.6.0', 'data' => 'listopad 2026', 'opis' => 'SEO stron i asystent AI z propozycjami zmian.'],
                    ['etykieta' => '1.7.0', 'data' => 'listopad 2026', 'opis' => 'Ustawienia serwisu w API i dane demo, które właśnie oglądasz.'],
                ],
            ]),
            $this->block('cta', [
                'naglowek' => 'Kod źródłowy',
                'opis' => 'CMS, oba startery i dokumentacja są w publicznych repozytoriach na GitHubie.',
                'przycisk' => ['link' => $this->link($github, 'Repozytorium na GitHubie'), 'styl' => 'glowny'],
                'ciemne' => true,
            ]),
        ]);

        $this->fillPage('contact', 'Kontakt – Pyxis CMS', 'Repozytorium, dokumentacja i zgłoszenia błędów Pyxis CMS.', [
            $this->block('tekst', [
                'naglowek' => 'Kontakt z projektem',
                'tresc' => '<p>Pytania i błędy najlepiej zgłaszać w repozytorium projektu. Dane poniżej to <strong>stałe pola strony</strong>: redaktor zmienia je w formularzu strony, bez dodawania bloków.</p>',
            ]),
        ], fields: ['kontakt' => [
            'repozytorium' => $this->link($github, 'github.com/Fablek/pyxis-cms'),
            'dokumentacja' => $this->link($docs, 'pyxis-cms-docs.vercel.app'),
            'zgloszenia' => $this->link("{$github}/issues", 'Zgłoś błąd lub pomysł'),
            'uczelnia' => ['nazwa' => 'Politechnika Śląska', 'kierunek' => 'Informatyka', 'rok' => '2025/2026'],
        ]]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $fields
     * @param  array<int, array<string, mixed>>  $rules
     */
    private function createFieldGroup(string $slug, string $title, FieldGroupMode $mode, array $fields, array $rules): void
    {
        FieldGroup::query()->create([
            'title' => $title,
            'slug' => $slug,
            'mode' => $mode,
            'fields' => $fields,
            'rules' => $rules,
            'is_active' => true,
        ]);
    }

    /**
     * Publishes the page with its content; the draft equals the published version.
     *
     * @param  array<int, array{type: string, data: array<string, mixed>}>  $content
     * @param  array<string, array<string, mixed>>|null  $fields
     */
    private function fillPage(string $key, string $seoTitle, string $seoDescription, array $content, ?Media $shareImage = null, ?array $fields = null): void
    {
        $seo = ['title' => $seoTitle, 'description' => $seoDescription, 'og_image' => $shareImage?->id, 'noindex' => false];

        $this->pages[$key]->update([
            'content' => $content,
            'content_draft' => $content,
            'fields' => $fields,
            'fields_draft' => $fields,
            'seo' => $seo,
            'seo_draft' => $seo,
        ]);
    }

    /**
     * A field definition in the format of the field group editor.
     *
     * @param  array<string, mixed>  $settings
     * @param  array<string, string>  $options
     * @return array<string, mixed>
     */
    private function field(string $type, string $name, string $label, array $settings = [], bool $required = false, array $options = []): array
    {
        if ($options !== []) {
            $settings['options'] = collect($options)
                ->map(fn (string $optionLabel, string $value): array => ['value' => $value, 'label' => $optionLabel])
                ->values()
                ->all();
        }

        return ['type' => $type, 'name' => $name, 'label' => $label, 'required' => $required, ...$settings];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{type: string, data: array<string, mixed>}
     */
    private function block(string $type, array $data): array
    {
        return ['type' => $type, 'data' => $data];
    }

    /**
     * @return array{url: string, title: string, new_tab: bool}
     */
    private function link(string $url, string $title): array
    {
        return ['url' => $url, 'title' => $title, 'new_tab' => str_starts_with($url, 'http')];
    }
}
