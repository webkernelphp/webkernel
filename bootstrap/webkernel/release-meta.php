<?php declare(strict_types=1);

/**
 * For Numerimondes Only
 *
 * We Fill this before running the release command.
 * The data is embedded in the annotated git tag and retrieved
 * by remote Webkernel instances via the GitHub API.
 * After release, we clear notes and video back to ''.
 */
return [
    'notes' => 'First Webkernel Release

    This first Webkernel Release will be ...',
    'doc_links' => [
        ['icon' => 'heroicon-o-book-open',          'label' => 'Documentation',    'url' => 'https://webkernelphp.com/docs'],
        ['icon' => 'heroicon-o-globe-alt',           'label' => 'webkernelphp.com', 'url' => 'https://webkernelphp.com/'],
        ['icon' => 'heroicon-o-squares-plus',        'label' => 'Marketplace',      'url' => 'https://webkernelphp.com/marketplace'],
        ['icon' => 'heroicon-o-code-bracket-square', 'label' => 'GitHub',           'url' => 'https://github.com/webkernelphp/webkernel'],
        ['icon' => 'heroicon-o-archive-box',         'label' => 'Packagist',        'url' => 'https://packagist.org/packages/webkernel/webkernel'],
        ['icon' => 'heroicon-o-building-office-2',   'label' => 'Numerimondes',     'url' => 'https://webkernelphp.com/about'],
    ],
    'features' => [
        [
            'icon'  => 'heroicon-o-cube-transparent',
            'title' => 'Modular Architecture',
            'body'  => 'Install, enable or disable packages via Composer. Each module is a self-contained unit with its own routes, views and migrations.',
        ],
        [
            'icon'  => 'heroicon-o-lock-closed',
            'title' => 'Digital Sovereignty',
            'body'  => 'No forced PaaS. No vendor lock-in. Your data stays on your own infrastructure, in the jurisdiction you choose.',
        ],
        [
            'icon'  => 'heroicon-o-bolt',
            'title' => 'Octane-Ready Boot',
            'body'  => 'Constants, config and module manifests are resolved once, cached and never re-parsed on warm requests.',
        ],
        [
            'icon'  => 'heroicon-o-shield-exclamation',
            'title' => 'Integrity at Boot',
            'body'  => 'CoreManifest + SealEnforcer verify cryptographic fingerprints on every startup, before any user request hits your application.',
        ],
        [
            'icon'  => 'heroicon-o-users',
            'title' => 'Multi-Role Panels',
            'body'  => 'Filament 5 panels per role — Admin, Client, Partner — each with its own data scope and permission model.',
        ],
        [
            'icon'  => 'heroicon-o-arrow-path',
            'title' => 'Resilient Installer',
            'body'  => 'Progress is saved to disk. Close the browser mid-install and it resumes exactly where it stopped.',
        ],
    ],
    'video' => 'https://www.youtube.com/watch?v=FdZikRU97CI',   // https:// URL — YouTube, CDN, or any direct link
];
