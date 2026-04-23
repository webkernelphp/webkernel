<?php declare(strict_types=1);

namespace Webkernel\System\Console;

use Illuminate\Console\Command;

/**
 * Artisan command: bootstrap a fresh Webkernel instance.
 *
 * Orchestrates all one-time setup steps in order:
 *   1. .env from .env.example
 *   2. Application key generation
 *   3. SQLite database file creation
 *   4. Database migrations  ← includes user_privileges table
 *   5. Deployment profile detection (deployment.php)
 *
 * Note: first-user creation is handled by InstallerPage (phase create_user),
 * not by this command, so that it stays interactive and form-validated.
 *
 * Usage:
 *   php artisan webkernel:install
 */
final class Install extends Command
{
    protected $signature   = 'webkernel:install';
    protected $description = 'Bootstrap a fresh Webkernel instance (env, key, SQLite, migrations, deployment profile).';

    public function handle(): int
    {
        $this->info('Installing Webkernel...');
        $this->newLine();

        // 1. .env
        $envPath = base_path('.env');
        if (! is_file($envPath)) {
            $example = base_path('.env.example');
            if (is_file($example)) {
                copy($example, $envPath);
                $this->line('  ✓ Created .env from .env.example');
            } else {
                $this->warn('  ! .env.example not found — skipping .env creation');
            }
        } else {
            $this->line('  · .env already exists');
        }

        // 2. App key — only generate if missing or empty
        $currentKey = trim((string) config('app.key', ''));
        if ($currentKey === '' || ! str_starts_with($currentKey, 'base64:')) {
            $this->call('key:generate', ['--ansi' => true]);
        } else {
            $this->line('  · APP_KEY already set — skipping key:generate');
        }

        // 3. SQLite
        $db = database_path('database.sqlite');
        if (! is_file($db)) {
            touch($db);
            $this->line('  ✓ Created database/database.sqlite');
        } else {
            $this->line('  · database.sqlite already exists');
        }

        // 4. Migrations (includes users + user_privileges tables)
        $this->call('migrate', ['--force' => true]);

        // 5. Deployment profile
        $this->newLine();
        $this->call('webkernel:detect-capabilities');

        $this->newLine();
        $this->info('Webkernel installation complete.');
        $this->comment('  → Next step: create the first administrator account in the installer UI.');

        return self::SUCCESS;
    }
}
