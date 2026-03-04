<?php
return [
    'pages' => [
        'label' => 'Page',
        'plural_label' => 'Pages',
        'sections' => [
            'publish' => 'Publish',
            'attributes' => 'Page Attributes',
        ],
        'fields' => [
            'title' => 'Title',
            'slug' => 'Slug (URL)',
            'status' => 'Status',
            'visibility' => 'Visibility',
            'password' => 'Access Password',
            'parent' => 'Parent Page',
            'author' => 'Author',
        ],
        'status' => [
            'draft' => 'Draft',
            'published' => 'Published',
        ],
        'visibility' => [
            'public' => 'Public',
            'private' => 'Private',
            'password' => 'Password Protected',
        ],
        'actions' => [
            'save' => 'Save Changes',
            'delete' => 'Delete',
        ],
        'modals' => [
            'delete_confirm' => 'Are you sure you want to delete this page?',
        ],
        'placeholders' => [
            'none_root' => 'None (Root page)',
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