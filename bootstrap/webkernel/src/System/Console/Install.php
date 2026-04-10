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
 *   4. Database migrations
 *   5. Deployment profile detection (deployment.php)
 *
 * Usage:
 *   php artisan webkernel:install
 */
final class Install extends Command
{
    protected $signature   = 'webkernel:install';
    protected $description = 'Bootstrap a fresh Webkernel instance (env, key, SQLite, deployment profile).';

    public function handle(): int
    {
        $this->info('Installing Webkernel...');
        $this->newLine();

        // 1. .env
        $envPath = base_path('.env');
        if (!is_file($envPath)) {
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

        // 2. App key
        $this->call('key:generate', ['--ansi' => true]);

        // 3. SQLite
        $db = database_path('database.sqlite');
        if (!is_file($db)) {
            touch($db);
            $this->line('  ✓ Created database/database.sqlite');
        } else {
            $this->line('  · database.sqlite already exists');
        }

        // 4. Migrations
        $this->call('migrate', ['--force' => true]);

        // 5. Deployment profile
        $this->newLine();
        $this->call('webkernel:detect-capabilities');

        $this->newLine();
        $this->info('Webkernel installation complete.');

        return self::SUCCESS;
    }
}
