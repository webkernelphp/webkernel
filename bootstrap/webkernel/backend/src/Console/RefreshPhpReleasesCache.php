<?php declare(strict_types=1);

namespace Webkernel\System\Console;

use Illuminate\Console\Command;
use Webkernel\System\Services\PhpReleasesService;

/**
 * Artisan command: manually refresh the php.net releases cache.
 *
 * Usage:
 *   php artisan webkernel:refresh-php-releases
 *
 * Recommended schedule (add to App\Console\Kernel or routes/console.php):
 *   Schedule::command('webkernel:refresh-php-releases')->daily();
 */
final class RefreshPhpReleasesCache extends Command
{
    protected $signature   = 'webkernel:refresh-php-releases';
    protected $description = 'Fetch the latest PHP release list from php.net and refresh the local cache.';

    public function handle(PhpReleasesService $service): int
    {
        $this->info('Fetching PHP releases from php.net...');

        $releases = $service->releases();

        if (empty($releases)) {
            $this->error('Failed to fetch or parse releases data.');
            return self::FAILURE;
        }

        $this->info('Cached ' . count($releases) . ' release entries.');

        return self::SUCCESS;
    }
}
