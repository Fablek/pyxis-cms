<?php
return [
    'nav' => [
        'users' => 'Users',
        'settings' => 'Settings',
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