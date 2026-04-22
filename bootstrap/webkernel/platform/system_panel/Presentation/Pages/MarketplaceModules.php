<?php declare(strict_types=1);

namespace Webkernel\Platform\SystemPanel\Presentation\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use UnitEnum;

/**
 * MarketplaceModules
 *
 * Browse and install modules from the official Webkernel marketplace.
 * Displays modules as cards with filtering and searching capabilities.
 */
class MarketplaceModules extends Page
{
    protected string $view = 'webkernel-system::filament.pages.marketplace-modules';

    protected static ?int                       $navigationSort           = 51;
    protected static bool                       $shouldRegisterNavigation = true;
    protected static string|UnitEnum|null       $navigationGroup          = 'Install Module';

    // ── Livewire state ────────────────────────────────────────────────────────

    public string $searchQuery = '';
    public string $sortBy = 'name';
    public array  $modules = [];
    public bool   $isLoading = false;

    // ── Lifecycle ─────────────────────────────────────────────────────────────

    public function mount(): void
    {
        $this->loadModules();
    }

    // ── Module loading ────────────────────────────────────────────────────────

    public function loadModules(): void
    {
        $this->isLoading = true;

        try {
            $this->modules = $this->fetchMarketplaceModules();
            $this->sortModules();
        } catch (\Throwable $e) {
            $this->dispatch('wk-toast', type: 'error', message: 'Failed to load marketplace: ' . $e->getMessage());
            $this->modules = [];
        }

        $this->isLoading = false;
    }

    #[\Livewire\Attributes\Computed]
    public function filteredModules(): array
    {
        if (empty($this->searchQuery)) {
            return $this->modules;
        }

        $query = strtolower($this->searchQuery);

        return array_filter($this->modules, function ($module) use ($query) {
            return str_contains(strtolower($module['name']), $query)
                || str_contains(strtolower($module['description']), $query)
                || str_contains(strtolower($module['author'] ?? ''), $query);
        });
    }

    public function sortModules(): void
    {
        usort($this->modules, function ($a, $b) {
            return match ($this->sortBy) {
                'name'     => strcmp($a['name'], $b['name']),
                'recent'   => strtotime($b['released_at'] ?? '0') <=> strtotime($a['released_at'] ?? '0'),
                'popular'  => ($b['downloads'] ?? 0) <=> ($a['downloads'] ?? 0),
                default    => strcmp($a['name'], $b['name']),
            };
        });
    }

    #[\Livewire\Attributes\On('installModule')]
    public function installModule(string $id): void
    {
        $module = collect($this->modules)->firstWhere('id', $id);

        if (!$module) {
            $this->dispatch('wk-toast', type: 'error', message: 'Module not found');
            return;
        }

        try {
            $installer = \Webkernel\Integration\ModuleInstaller::module(
                from:   'webkernelphp-com',
                vendor: $module['vendor'],
                slug:   $module['slug'],
            )
                ->withBackup(true)
                ->withHooks(true);

            if (!empty($module['version'])) {
                $installer = $installer->toVersion($module['version']);
            }

            $path = $installer->execute();

            $this->dispatch('wk-toast', type: 'success', message: "Module installed at {$path}");
            $this->loadModules();
        } catch (\Throwable $e) {
            $this->dispatch('wk-toast', type: 'error', message: 'Installation failed: ' . $e->getMessage());
        }
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function fetchMarketplaceModules(): array
    {
        return [
            [
                'id' => 'wk-auth-v1',
                'name' => 'Authentication Module',
                'slug' => 'auth',
                'vendor' => 'webkernel',
                'description' => 'Complete authentication system with OAuth2, JWT, and session support.',
                'author' => 'Webkernel Team',
                'version' => '1.0.0',
                'released_at' => '2026-04-01',
                'downloads' => 1240,
                'rating' => 4.8,
                'tags' => ['auth', 'security', 'oauth2'],
            ],
            [
                'id' => 'wk-payments-v1',
                'name' => 'Payments Module',
                'slug' => 'payments',
                'vendor' => 'webkernel',
                'description' => 'Multi-provider payment processing with Stripe, PayPal, and local gateway support.',
                'author' => 'Webkernel Team',
                'version' => '1.1.0',
                'released_at' => '2026-03-20',
                'downloads' => 890,
                'rating' => 4.6,
                'tags' => ['payments', 'stripe', 'paypal'],
            ],
            [
                'id' => 'wk-notifications-v1',
                'name' => 'Notifications Module',
                'slug' => 'notifications',
                'vendor' => 'webkernel',
                'description' => 'Email, SMS, push notifications and in-app messaging in one module.',
                'author' => 'Webkernel Team',
                'version' => '2.0.0',
                'released_at' => '2026-02-15',
                'downloads' => 2150,
                'rating' => 4.9,
                'tags' => ['notifications', 'email', 'sms'],
            ],
        ];
    }

    // ── Navigation ────────────────────────────────────────────────────────────

    public static function getNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return 'heroicon-o-star';
    }

    public static function getNavigationLabel(): string
    {
        return 'Marketplace';
    }

    public function getTitle(): string|Htmlable
    {
        return 'Marketplace';
    }
}
