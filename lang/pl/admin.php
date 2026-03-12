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
            'save' => 'Zapisz szkic',
            'publish' => 'Publikuj zmiany',
            'preview' => 'Podgląd na żywo',
            'delete' => 'Usuń stronę',
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
];