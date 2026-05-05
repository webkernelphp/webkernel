<p align="center">
  <a href="https://github.com/webkernelphp/composer-merger" target="_blank">
    <img src="https://raw.githubusercontent.com/numerimondes/.github/refs/heads/main/assets/brands/numerimondes/identity/logos/v2/faviconV2_Numerimondes.png" width="70" alt="Webkernel Logo">
  </a>
</p>

<h1 align="center">Webkernel Composer Merger</h1>

<p align="center">
Composer plugin to merge multiple <code>composer.json</code> files at runtime —<br>
Webkernel edition with bundled core dependencies for the entire ecosystem.
</p>

<p align="center">
  <a href="https://packagist.org/packages/webkernel/composer-merger">
    <img src="https://img.shields.io/packagist/v/webkernel/composer-merger.svg?style=flat" alt="Latest Stable Version">
  </a>
  <a href="https://packagist.org/packages/webkernel/composer-merger">
    <img src="https://img.shields.io/packagist/dt/webkernel/composer-merger?color=blue" alt="Total Downloads">
  </a>
  <a href="https://github.com/webkernelphp/composer-merger/blob/master/LICENSE">
    <img src="https://img.shields.io/packagist/l/webkernel/composer-merger.svg?style=flat" alt="License">
  </a>
  <a href="https://github.com/webkernelphp/composer-merger/actions/workflows/CI.yaml">
    <img src="https://github.com/webkernelphp/composer-merger/actions/workflows/CI.yaml/badge.svg" alt="Build Status">
  </a>
</p>

---

## What is this?

**Webkernel Composer Merger** is a fork of [wikimedia/composer-merge-plugin](https://github.com/wikimedia/composer-merge-plugin) extended for the [Webkernel](https://github.com/webkernelphp) ecosystem.

It does two things in one package:

- **Merges** multiple `composer.json` files at Composer runtime — `bootstrap/composer.json`, module-level configs, sub-packages
- **Bundles** all core dependencies required by the Webkernel ecosystem so that individual modules and applications never need to redeclare them

The result: your root `composer.json` requires exactly one package. Everything else flows from there.

---

## Bundled dependencies

| Package | Version |
|---|---|
| `laravel/framework` | `^12.0` |
| `filament/filament` | `^5.0` |
| `laravel/octane` | `^2.12` |
| `laravel/sanctum` | `^4.0` |
| `laravel/tinker` | `^2.10.1` |
| `laravel/prompts` | `^0.3.8` |
| `calebporzio/sushi` | `^2.5` |
| `jeremykendall/php-domain-parser` | `^6.4` |
| `nikic/php-parser` | `^5.7` |

---

## Requirements

- PHP `^8.4`
- Composer `^2.0`

---

## Installation
```bash
composer require webkernel/composer-merger
```

### Root `composer.json`

Your root `composer.json` should look like this:
```json
{
    "name": "webkernel/webkernel",
    "type": "project",
    "require": {
        "webkernel/composer-merger": "^0.1.0"
    },
    "scripts": {
        "post-autoload-dump": [
            "Illuminate\\Foundation\\ComposerScripts::postAutoloadDump",
            "@php artisan package:discover --ansi"
        ],
        "w-clear": ["... clears all Laravel, Filament, icon caches ..."],
        "w-cache": ["... warms all caches after clearing ..."],
        "dev":     ["... starts server, queue, pail, vite concurrently ..."],
        "test":    ["... runs the test suite ..."]
    },
    "extra": {
        "merge-plugin": {
            "include": [
                "bootstrap/composer.json",
                "modules/**/**/**/composer.json"
            ],
            "recurse": false,
            "replace": false,
            "ignore-duplicates": false,
            "merge-dev": true,
            "merge-extra": true,
            "merge-extra-deep": true,
            "merge-scripts": true
        }
    },
    "config": {
        "optimize-autoloader": true,
        "preferred-install": "dist",
        "sort-packages": true,
        "allow-plugins": {
            "pestphp/pest-plugin": true,
            "php-http/discovery": true,
            "webkernel/composer-merger": true
        }
    },
    "minimum-stability": "stable",
    "prefer-stable": true
}
```

### `bootstrap/composer.json`

The bootstrap file handles autoload namespaces, Laravel extra config, dev dependencies, second-level module merge and specific webkernel versions dependencies if any. It does **not** redeclare anything already bundled by this plugin.
```json
{
    "require": {},
    "autoload": {
        "psr-4": {
        }
    },
    "extra": {
        "laravel": {
            "dont-discover": []
        }
    }
}
```

---

## How the merge chain works
```
root composer.json
  └── requires webkernel/composer-merger        → brings all core deps transitively
  └── merge-plugin includes:
        ├── bootstrap/composer.json              → autoload, Laravel config, dev deps
        └── modules/**/**/composer.json          → each module's own deps & providers
```

Composer processes this in a single `composer install`. No two-step install, no manual merging.

---

## Plugin configuration reference

All settings live under the `merge-plugin` key in your `extra` section.

### `include`
Glob patterns pointing to `composer.json` files to merge. The following sections are merged as though declared directly in the root: `require`, `require-dev`, `autoload`, `autoload-dev`, `conflict`, `provide`, `replace`, `repositories`, `suggest`, `extra`, `scripts`.

### `require`
Same as `include` but throws an error if a pattern matches no file.

### `recurse`
Default `true`. If a merged file itself contains a `merge-plugin` section it will also be processed. Set to `false` to disable.

### `replace`
Default `false`. When `true`, last version found wins for duplicate package declarations across merged files.

### `ignore-duplicates`
Default `false`. When `true`, first version found wins. Mutually exclusive with `replace` — if both are set, `ignore-duplicates` takes precedence.

### `merge-dev`
Default `true`. Set to `false` to skip merging `require-dev` and `autoload-dev` sections.

### `merge-extra`
Default `false`. Set to `true` to merge `extra` sections. Combine with `merge-extra-deep: true` for deep recursive merge.

### `merge-replace`
Default `true`. Set to `false` to skip merging `replace` sections.

### `merge-scripts`
Default `false`. Set to `true` to merge `scripts` sections. Note: merged custom commands are available via `composer run-script my-command` but not as `composer my-command` shortcuts.

---

## Running tests
```bash
composer install
composer test
```

---

## Contributing

Issues and pull requests are welcome on the [GitHub project](https://github.com/webkernelphp/composer-merger).

- Follow the [PSR-2 Coding Standard](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md) — validated via [PHP_CodeSniffer](http://pear.php.net/package/PHP_CodeSniffer)
- Include tests with your changes
- Keep this README up to date
- One pull request per feature

---

## Credits

Originally forked from [wikimedia/composer-merge-plugin](https://github.com/wikimedia/composer-merge-plugin) by [Bryan Davis](mailto:bd808@wikimedia.org) & the Wikimedia Foundation.  
Extended and maintained by [Numerimondes](https://github.com/numerimondes) for the Webkernel ecosystem.

---

## License

Licensed under the [Eclipse Public License 2.0](https://www.eclipse.org/legal/epl-2.0/).

---

[Composer]: https://getcomposer.org/
[GitHub project]: https://github.com/webkernelphp/composer-merger
[PSR-2 Coding Standard]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md
[PHP_CodeSniffer]: http://pear.php.net/package/PHP_CodeSniffer
