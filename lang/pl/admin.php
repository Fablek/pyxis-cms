<?php

return [
    'pages' => [
        'label' => 'Strona',
        'plural_label' => 'Strony',
        'sections' => [
            'publish' => 'Publikacja i widoczność',
            'attributes' => 'Atrybuty strony',
            'content' => 'Treść strony',
        ],
        'fields' => [
            'title' => 'Tytuł',
            'slug' => 'Slug (URL)',
            'status' => 'Status',
            'visibility' => 'Widoczność',
            'password' => 'Hasło dostępu',
            'published_at' => 'Data publikacji',
            'parent' => 'Strona nadrzędna',
            'author' => 'Autor',
            'content_draft' => 'Treść strony',
        ],
        'status' => [
            'draft' => 'Szkic',
            'published' => 'Opublikowano',
            'scheduled' => 'Zaplanowano',
        ],
        'visibility' => [
            'public' => 'Publiczna',
            'private' => 'Prywatna (Ukryta)',
            'password' => 'Chroniona hasłem',
        ],
        'actions' => [
            'draft' => 'Zapisz szkic',
            'save' => 'Zapisz',
            'cancel' => 'Anuluj',
            'publish' => 'Publikuj zmiany',
            'preview' => 'Podgląd na żywo',
            'delete' => 'Usuń stronę',
            'create' => 'Utwórz',
            'create_another' => 'Utwórz i utwórz kolejny',
        ],
        'modals' => [
            'delete_confirm' => 'Czy na pewno chcesz usunąć tę stronę?',
            'publish_confirm' => 'Ta akcja nadpisze publiczną wersję strony aktualnym szkicem. Kontynuować?',
        ],
        'notifications' => [
            'published' => 'Strona została pomyślnie opublikowana!',
        ],
        'placeholders' => [
            'none_root' => 'Brak (Strona główna)',
        ],
    ],
    'nav' => [
        'users' => 'Użytkownicy',
        'settings' => 'Ustawienia',
        'pages' => 'Strony',
        'media' => 'Media',
    ],
    'users' => [
        'label' => 'Użytkownik',
        'plural_label' => 'Użytkownicy',
        'sections' => [
            'basic_data' => 'Dane podstawowe',
            'security' => 'Bezpieczeństwo',
        ],
        'fields' => [
            'name' => 'Imię i nazwisko',
            'email' => 'Adres e-mail',
            'role' => 'Rola w systemie',
            'password' => 'Hasło',
            'created_at' => 'Utworzono',
        ],
    ],
    'roles' => [
        'admin' => 'Administrator',
        'editor' => 'Redaktor',
    ],
    'settings' => [
        'navigation_label' => 'Ustawienia',
        'title' => 'Ustawienia systemu',
        'save_button' => 'Zapisz zmiany',
        'notification_success' => 'Ustawienia zostały pomyślnie zapisane.',

        'sections' => [
            'config' => 'Główna konfiguracja',
            'config_desc' => 'Zarządzaj podstawowymi parametrami strony, takimi jak język domyślny i strona główna.',
        ],

        'fields' => [
            'language' => 'Język strony',
            'homepage' => 'Strona główna',
        ],

        'placeholders' => [
            'select_page' => 'Wybierz stronę z listy...',
        ],
    ],
    'nav' => [
        'users' => 'Użytkownicy',
        'settings' => 'Ustawienia',
        'pages' => 'Strony',
    ],
    'users' => [
        'label' => 'Użytkownik',
        'plural_label' => 'Użytkownicy',
        'sections' => [
            'basic_data' => 'Dane podstawowe',
            'security' => 'Bezpieczeństwo',
        ],
        'fields' => [
            'name' => 'Imię i nazwisko',
            'email' => 'Adres e-mail',
            'role' => 'Rola systemowa',
            'password' => 'Hasło',
            'created_at' => 'Data utworzenia',
        ],
    ],
    'roles' => [
        'admin' => 'Administrator',
        'editor' => 'Redaktor',
    ],
    'settings' => [
        'navigation_label' => 'Ustawienia',
        'title' => 'Ustawienia systemu',
        'save_button' => 'Zapisz zmiany',
        'notification_success' => 'Ustawienia zostały pomyślnie zapisane.',

        'sections' => [
            'config' => 'Konfiguracja główna',
            'config_desc' => 'Zarządzaj podstawowymi parametrami witryny, takimi jak język domyślny oraz strona główna.',
        ],

        'fields' => [
            'language' => 'Język witryny',
            'homepage' => 'Strona główna serwisu',
        ],

        'placeholders' => [
            'select_page' => 'Wybierz stronę z listy...',
        ],
    ],
    'media' => [
        'label' => 'Media',
        'plural' => 'Biblioteka mediów',
        'nav_label' => 'Biblioteka mediów',
    ],

    'field_groups' => [
        'label' => 'Grupa pól',
        'plural_label' => 'Grupy pól',
        'nav_label' => 'Grupy pól',
        'fields_count' => ':count pole|:count pola|:count pól',
        'sections' => [
            'fields' => 'Pola',
            'location' => 'Reguły lokalizacji',
            'location_desc' => 'Pokaż tę grupę pól, jeśli spełniony jest każdy z warunków.',
            'status' => 'Status',
            'settings' => 'Ustawienia grupy',
        ],
        'fields' => [
            'title' => 'Tytuł',
            'title_placeholder' => 'Tytuł grupy pól',
            'slug' => 'Slug',
            'is_active' => 'Aktywna (widoczna)',
            'is_active_help' => 'Nieaktywna grupa nie pojawia się w formularzach stron.',
            'updated_at' => 'Ostatnia zmiana: :time',
            'created_at' => 'Utworzono',
            'updated_at_column' => 'Zmieniono',
        ],
        'location' => [
            'param' => 'Parametr',
            'operator' => 'Operator',
            'value' => 'Wartość',
            'add' => 'Dodaj warunek',
            'params' => [
                'page_id' => 'Strona',
            ],
            'operators' => [
                'equals' => 'jest równa',
                'not_equals' => 'nie jest równa',
            ],
        ],
        'preview' => [
            'label' => 'Podgląd formularza',
            'description' => 'Tak redaktor zobaczy te pola podczas edycji strony. Wartości nie są zapisywane.',
            'close' => 'Zamknij',
            'empty' => 'Brak pól do wyświetlenia. Dodaj pole i uzupełnij jego nazwę.',
        ],
    ],
    'custom_fields' => [
        'tabs' => [
            'general' => 'Ogólne',
            'validation' => 'Walidacja',
            'presentation' => 'Prezentacja',
        ],
        'definition' => [
            'type' => 'Typ pola',
            'label' => 'Etykieta',
            'label_placeholder' => 'np. Nagłówek sekcji',
            'name' => 'Nazwa systemowa',
            'name_placeholder' => 'np. naglowek_sekcji',
            'name_help' => 'Klucz w API. Zmiana odetnie zapisane wartości.',
            'name_regex' => 'Nazwa musi zaczynać się od litery i może zawierać tylko małe litery, cyfry i podkreślenia.',
            'required' => 'Pole wymagane',
            'instructions' => 'Instrukcja dla redaktora',
            'width' => 'Szerokość w formularzu',
            'add' => 'Dodaj pole',
            'new_field' => 'Nowe pole',
            'sub_fields' => 'Pola podrzędne',
            'unique_names' => 'Nazwy pól muszą być unikalne. Powtórzone: :names.',
        ],
        'types' => [
            'text' => 'Tekst',
            'textarea' => 'Obszar tekstowy',
            'number' => 'Liczba',
            'wysiwyg' => 'Edytor WYSIWYG',
            'image' => 'Obraz',
            'gallery' => 'Galeria',
            'select' => 'Lista wyboru',
            'toggle' => 'Przełącznik',
            'link' => 'Link',
            'page' => 'Strona (relacja)',
            'repeater' => 'Repeater (lista)',
            'group' => 'Grupa',
        ],
        'settings' => [
            'default_value' => 'Wartość domyślna',
            'default_on' => 'Domyślnie włączony',
            'max_length' => 'Maksymalna liczba znaków',
            'placeholder' => 'Placeholder',
            'rows' => 'Liczba wierszy',
            'min' => 'Minimum',
            'max' => 'Maksimum',
            'step' => 'Krok',
            'suffix' => 'Jednostka (sufiks)',
            'suffix_placeholder' => 'np. zł, %, px',
            'max_images' => 'Maksymalna liczba obrazów',
            'options' => 'Opcje',
            'option_value' => 'Wartość',
            'option_label' => 'Etykieta',
            'add_option' => 'Dodaj opcję',
            'multiple' => 'Wielokrotny wybór',
            'multiple_pages' => 'Wiele stron',
            'min_items' => 'Minimalna liczba elementów',
            'max_items' => 'Maksymalna liczba elementów',
            'button_label' => 'Tekst przycisku dodawania',
            'add_item' => 'Dodaj element',
        ],
        'link' => [
            'url' => 'Adres URL',
            'url_placeholder' => 'https:// lub /sciezka',
            'title' => 'Tekst linku',
            'new_tab' => 'Otwórz w nowej karcie',
        ],
    ],
];
