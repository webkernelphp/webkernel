# Filament Dependency Manager

A plugin to monitor outdated **Composer** and **NPM** dependencies directly from the system panel using native Filament tables.

---

## Table of Contents

- [Features](#features)
- [Screenshots](#screenshots)
- [Requirements](#requirements)
- [Installation](#installation)
- [Usage](#usage)
- [Configuration](#configuration)
- [Environment Variables](#environment-variables)
- [Translations](#translations)
- [Testing](#testing)
- [License](#license)

---

## Features

- ⚡ **Zero Database**: Uses [Sushi](https://github.com/calebporzio/sushi) to provide a seamless Eloquent experience without migrations.
- 📦 **Composer Support**: View outdated packages, current vs latest versions, and release dates.
- 🟢 **NPM Support**: Monitor updates for `dependencies` and `devDependencies`.
- 🔍 **Native Filament Tables**: Supports searching, sorting, and filtering out-of-the-box.
- 📋 **Quick Copy**: Copy update commands (composer require, npm install, etc.) with a single click.
- 🌍 **Multilingual**: Full support for English and Spanish.
- 🚀 **Performance**: Results are cached to ensure instant page loads.

---

## Screenshots

### Composer Dependencies
![Composer Screenshot](art/composer.webp)

### NPM Dependencies
![NPM Screenshot](art/npm.webp)

---

## Requirements

The plugin automatically adapts to your environment:

| Filament Version | PHP Version | Laravel Version | Branch / Tag |
|---|---|---|---|
| **Filament v5** | 8.2+ | 11.x, 12.x, 13.x | `main` / `v5.x` |
| **Filament v4** | 8.2+ | 11.x, 12.x, 13.x | `4.x` |
| **Filament v3** | 8.1+ | 10.x, 11.x, 12.x | `3.x` |

---

## Installation

Install the package via Composer:

```bash
composer require daljo25/filament-dependency-manager

php artisan dependency-manager:install
```

The plugin will automatically install [Sushi](https://github.com/calebporzio/sushi) to handle in-memory data management.

---

## Usage

Register the plugin in your Filament Panel Provider (e.g., `AdminPanelProvider.php`):

```php
use FilamentDependencyManagerPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugin(FilamentDependencyManagerPlugin::make());
}
```
