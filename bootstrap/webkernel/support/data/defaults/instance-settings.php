<?php declare(strict_types=1);

return [

    'app' => [
        'meta' => [
            'label' => 'Application',
            'icon' => 'heroicon-o-cog-6-tooth',
            'sort_order' => 10,
            'is_system' => true,
        ],

        'items' => [

            [
                'key' => 'app_name',
                'type' => 'text',
                'label' => 'Application Name',
                'description' => 'The name of your Webkernel instance',
                'default_value' => 'Webkernel',
            ],

            [
                'key' => 'timezone',
                'type' => 'select',
                'label' => 'Timezone',
                'description' => 'Server timezone for all timestamps',
                'default_value' => 'UTC',
                'options_json' => array_map(
                    fn ($tz) => ['value' => $tz, 'label' => $tz],
                    timezone_identifiers_list()
                ),
            ],

            [
                'key' => 'app_url',
                'type' => 'text',
                'label' => 'Application URL',
                'description' => 'The primary URL where this instance is accessed',
                'default_value' => env('APP_URL', 'http://localhost'),
            ],

            [
                'key' => 'debug_mode',
                'type' => 'boolean',
                'label' => 'Debug Mode',
                'description' => 'Show detailed error messages (disable in production)',
                'default_value' => false,
            ],

        ],
    ],

    'smtp' => [
        'meta' => [
            'label' => 'SMTP',
            'icon' => 'heroicon-o-envelope',
            'sort_order' => 20,
            'is_system' => true,
        ],

        'items' => [

            [
                'key' => 'enabled',
                'type' => 'boolean',
                'label' => 'Enable SMTP',
                'description' => 'Enable SMTP mail sending',
                'default_value' => true,
            ],

            [
                'key' => 'host',
                'type' => 'text',
                'label' => 'SMTP Host',
                'description' => 'Mail server hostname',
                'default_value' => env('MAIL_HOST', 'localhost'),
                'meta_json' => [
                    'depends_on' => ['key' => 'enabled', 'value' => true],
                ],
            ],

            [
                'key' => 'port',
                'type' => 'integer',
                'label' => 'SMTP Port',
                'description' => 'Mail server port (25, 465, 587)',
                'default_value' => (int) env('MAIL_PORT', 587),
                'meta_json' => [
                    'depends_on' => ['key' => 'enabled', 'value' => true],
                ],
            ],

            [
                'key' => 'encryption',
                'type' => 'select',
                'label' => 'Encryption',
                'description' => 'TLS or SSL encryption',
                'default_value' => env('MAIL_ENCRYPTION', 'tls'),
                'options_json' => [
                    ['value' => 'tls', 'label' => 'TLS'],
                    ['value' => 'ssl', 'label' => 'SSL'],
                    ['value' => 'none', 'label' => 'None'],
                ],
                'meta_json' => [
                    'depends_on' => ['key' => 'enabled', 'value' => true],
                ],
            ],

            [
                'key' => 'username',
                'type' => 'text',
                'label' => 'Username',
                'description' => 'SMTP authentication username',
                'default_value' => env('MAIL_USERNAME', ''),
                'meta_json' => [
                    'depends_on' => ['key' => 'enabled', 'value' => true],
                ],
            ],

            [
                'key' => 'password',
                'type' => 'password',
                'label' => 'Password',
                'description' => 'SMTP authentication password',
                'default_value' => env('MAIL_PASSWORD', ''),
                'is_sensitive' => true,
                'meta_json' => [
                    'depends_on' => ['key' => 'enabled', 'value' => true],
                ],
            ],

            [
                'key' => 'from_name',
                'type' => 'text',
                'label' => 'From Name',
                'description' => 'Sender name for outgoing emails',
                'default_value' => env('MAIL_FROM_NAME', 'Webkernel'),
                'meta_json' => [
                    'depends_on' => ['key' => 'enabled', 'value' => true],
                ],
            ],

            [
                'key' => 'from_address',
                'type' => 'text',
                'label' => 'From Address',
                'description' => 'Sender email address',
                'default_value' => env('MAIL_FROM_ADDRESS', 'noreply@webkernel.local'),
                'meta_json' => [
                    'depends_on' => ['key' => 'enabled', 'value' => true],
                ],
            ],

        ],
    ],

    'security' => [
        'meta' => [
            'label' => 'Security',
            'icon' => 'heroicon-o-shield-check',
            'sort_order' => 30,
            'is_system' => true,
        ],

        'items' => [

            [
                'key' => 'session_lifetime',
                'type' => 'integer',
                'label' => 'Session Lifetime (minutes)',
                'description' => 'How long users stay logged in without activity',
                'default_value' => 120,
            ],

            [
                'key' => 'force_https',
                'type' => 'boolean',
                'label' => 'Force HTTPS',
                'description' => 'Redirect all HTTP traffic to HTTPS',
                'default_value' => env('APP_ENV') === 'production',
            ],

            [
                'key' => 'password_min_length',
                'type' => 'integer',
                'label' => 'Minimum Password Length',
                'description' => 'Minimum characters required for user passwords',
                'default_value' => 8,
            ],

        ],
    ],

    'backups' => [
        'meta' => [
            'label' => 'Backups',
            'icon' => 'heroicon-o-circle-stack',
            'sort_order' => 40,
            'is_system' => true,
        ],

        'items' => [

            [
                'key' => 'enabled',
                'type' => 'boolean',
                'label' => 'Enable Backups',
                'description' => 'Automatically backup the database and files',
                'default_value' => true,
            ],

            [
                'key' => 'schedule',
                'type' => 'select',
                'label' => 'Backup Schedule',
                'description' => 'How often to run automatic backups',
                'default_value' => 'daily',
                'options_json' => [
                    ['value' => 'hourly', 'label' => 'Every Hour'],
                    ['value' => 'daily', 'label' => 'Daily'],
                    ['value' => 'weekly', 'label' => 'Weekly'],
                    ['value' => 'monthly', 'label' => 'Monthly'],
                ],
                'meta_json' => [
                    'depends_on' => ['key' => 'enabled', 'value' => true],
                ],
            ],

            [
                'key' => 'retention_days',
                'type' => 'integer',
                'label' => 'Retention (days)',
                'description' => 'Delete backups older than this many days',
                'default_value' => 30,
                'meta_json' => [
                    'depends_on' => ['key' => 'enabled', 'value' => true],
                ],
            ],

        ],
    ],

];
