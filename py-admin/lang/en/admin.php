<?php
return [
    'pages' => [
        'label' => 'Page',
        'plural_label' => 'Pages',
        'sections' => ['general' => 'General Information'],
        'fields' => [
            'title' => 'Page Title',
            'slug' => 'URL Address (Slug)',
            'status' => 'Status',
            'author' => 'Author',
        ],
        'status' => [
            'draft' => 'Draft',
            'published' => 'Published',
        ],
    ],
    'nav' => [
        'users' => 'Users',
        'settings' => 'Settings',
        'pages' => 'Pages',
    ],
    'users' => [
        'label' => 'User',
        'plural_label' => 'Users',
        'sections' => [
            'basic_data' => 'Basic Data',
            'security' => 'Security',
        ],
        'fields' => [
            'name' => 'Full Name',
            'email' => 'E-mail Address',
            'role' => 'System Role',
            'password' => 'Password',
            'created_at' => 'Created At',
        ],
    ],
    'roles' => [
        'admin' => 'Administrator',
        'editor' => 'Editor',
    ],
    'settings' => [
        'title' => 'System Settings',
        'navigation_label' => 'Settings',
        'section_regionalization' => 'Regionalization',
        'field_language' => 'Panel Interface Language',
        'save_button' => 'Save changes',
        'notification_success' => 'Settings have been successfully updated',
    ],
];