<?php
declare(strict_types=1);

/**
 * FSEngine Capability Map
 *
 * Merged into config('webkernel-capabilities') by the Webkernel service provider.
 *
 * Classification classes:
 *   system  — read: false, write: false  (APP_KEY, passwords, secrets)
 *   locked  — read: true,  write: false  (APP_ENV, framework internals)
 *   runtime — read: true,  write: true   (driver choices, feature flags, etc.)
 *
 * All keys NOT listed here default to classification 'dynamic' (fully open),
 * except for keys matching the sensitive patterns in ConfigCapabilityGate
 * (SECRET, PASSWORD, TOKEN, KEY, SALT, PRIVATE_KEY) which are always 'system'.
 *
 * Add 'allowed' to constrain runtime keys to a specific set of values:
 *   'allowed' => ['sync', 'redis', 'database']
 *
 * This file is loaded at boot by WebkernelServiceProvider::mergeCapabilities().
 * Do NOT place this file inside config/ of the application — it lives inside
 * the Webkernel platform package and is intentionally not overridable by
 * application developers.
 */
return [

    'env' => [

        // ----------------------------------------------------------------
        // SYSTEM — never readable or writable at runtime
        // ----------------------------------------------------------------

        'APP_KEY' => [
            'class' => 'system',
            'read'  => false,
            'write' => false,
        ],

        'DB_PASSWORD' => [
            'class' => 'system',
            'read'  => false,
            'write' => false,
        ],

        'REDIS_PASSWORD' => [
            'class' => 'system',
            'read'  => false,
            'write' => false,
        ],

        'MAIL_PASSWORD' => [
            'class' => 'system',
            'read'  => false,
            'write' => false,
        ],

        'AWS_SECRET_ACCESS_KEY' => [
            'class' => 'system',
            'read'  => false,
            'write' => false,
        ],

        'STRIPE_SECRET' => [
            'class' => 'system',
            'read'  => false,
            'write' => false,
        ],

        // ----------------------------------------------------------------
        // LOCKED — readable but never writable at runtime
        // ----------------------------------------------------------------

        'APP_ENV' => [
            'class' => 'locked',
            'read'  => true,
            'write' => false,
        ],

        'APP_DEBUG' => [
            'class' => 'locked',
            'read'  => true,
            'write' => false,
        ],

        'APP_URL' => [
            'class' => 'locked',
            'read'  => true,
            'write' => false,
        ],

        'DB_CONNECTION' => [
            'class' => 'locked',
            'read'  => true,
            'write' => false,
        ],

        // ----------------------------------------------------------------
        // RUNTIME — writable at runtime (optionally constrained)
        // ----------------------------------------------------------------

        'QUEUE_CONNECTION' => [
            'class'   => 'runtime',
            'read'    => true,
            'write'   => true,
            'allowed' => ['sync', 'database', 'redis', 'sqs', 'beanstalkd'],
        ],

        'CACHE_STORE' => [
            'class'   => 'runtime',
            'read'    => true,
            'write'   => true,
            'allowed' => ['file', 'redis', 'memcached', 'database', 'array'],
        ],

        'SESSION_DRIVER' => [
            'class'   => 'runtime',
            'read'    => true,
            'write'   => true,
            'allowed' => ['file', 'cookie', 'database', 'redis', 'memcached', 'array'],
        ],

        'MAIL_MAILER' => [
            'class'   => 'runtime',
            'read'    => true,
            'write'   => true,
            'allowed' => ['smtp', 'sendmail', 'mailgun', 'ses', 'postmark', 'log', 'array'],
        ],

        'LOG_CHANNEL' => [
            'class' => 'runtime',
            'read'  => true,
            'write' => true,
        ],

        'CONCURRENCY_DRIVER' => [
            'class'   => 'runtime',
            'read'    => true,
            'write'   => true,
            'allowed' => ['process', 'sync', 'redis'],
        ],

    ],

];
