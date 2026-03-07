<?php
return [
    'pages' => [
        'label' => 'Stronę',
        'plural_label' => 'Strony',
        'sections' => [
            'publish' => 'Opublikuj',
            'attributes' => 'Atrybuty strony',
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
        ],
        'status' => [
            'draft' => 'Szkic',
            'published' => 'Opublikowano',
            'scheduled' => 'Zaplanowano',
        ],
        'visibility' => [
            'public' => 'Publiczne',
            'private' => 'Prywatne',
            'password' => 'Chronione hasłem',
        ],
        'actions' => [
            'save' => 'Zapisz zmiany',
            'delete' => 'Usuń',
        ],
        'modals' => [
            'delete_confirm' => 'Czy na pewno chcesz usunąć tę stronę?',
        ],
        'placeholders' => [
            'none_root' => 'Brak (strona główna)',
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