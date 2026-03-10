<?php
return [
    'pages' => [
        'label' => 'Page',
        'plural_label' => 'Pages',
        'sections' => [
            'publish' => 'Publishing & Visibility',
            'attributes' => 'Page Attributes',
            'content' => 'Page Content',
        ],
        'fields' => [
            'title' => 'Title',
            'slug' => 'Slug (URL)',
            'status' => 'Status',
            'visibility' => 'Visibility',
            'password' => 'Access Password',
            'published_at' => 'Published at',
            'parent' => 'Parent Page',
            'author' => 'Author',
            'content_draft' => 'Page Content',
        ],
        'status' => [
            'draft' => 'Draft',
            'published' => 'Published',
            'scheduled' => 'Scheduled',
        ],
        'visibility' => [
            'public' => 'Public',
            'private' => 'Private (Hidden)',
            'password' => 'Password Protected',
        ],
        'actions' => [
            'save' => 'Save Draft',
            'publish' => 'Publish Changes',
            'preview' => 'Live Preview',
            'delete' => 'Delete Page',
        ],
        'modals' => [
            'delete_confirm' => 'Are you sure you want to delete this page?',
            'publish_confirm' => 'This will overwrite the public version with your current draft. Continue?',
        ],
        'notifications' => [
            'published' => 'The page has been successfully published!',
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
        'navigation_label' => 'Settings',
        'title' => 'System Settings',
        'save_button' => 'Save changes',
        'notification_success' => 'Settings have been successfully saved.',
        
        'sections' => [
            'config' => 'Main Configuration',
            'config_desc' => 'Manage core site parameters, such as the default language and the homepage.',
        ],
        
        'fields' => [
            'language' => 'Site Language',
            'homepage' => 'Main Homepage',
        ],
        
        'placeholders' => [
            'select_page' => 'Select a page from the list...',
        ],
    ],
];