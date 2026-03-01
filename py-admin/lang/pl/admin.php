<?php
return [
    'nav' => [
        'users' => 'Użytkownicy',
        'settings' => 'Ustawienia',
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
        'title' => 'Ustawienia systemu',
        'navigation_label' => 'Ustawienia',
        'section_regionalization' => 'Regionalizacja',
        'field_language' => 'Język interfejsu panelu',
        'save_button' => 'Zapisz zmiany',
        'notification_success' => 'Ustawienia zostały pomyślnie zaktualizowane',
    ],
];