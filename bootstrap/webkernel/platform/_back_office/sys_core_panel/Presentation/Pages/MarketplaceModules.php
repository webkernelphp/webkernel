<?php declare(strict_types=1);

namespace Webkernel\BackOffice\System\Presentation\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use UnitEnum;
use Webkernel\BackOffice\System\Config\MarketplaceModules as MarketplaceConfig;

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
    public string $selectedCategory = 'all';
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
    public function availableCategories(): array
    {
        $categories = ['all' => ['label' => 'All Modules', 'icon' => 'heroicon-m-cube']];

        foreach ($this->modules as $module) {
            foreach ($module['tags'] ?? [] as $tag) {
                if (!isset($categories[$tag])) {
                    $categories[$tag] = ['label' => ucfirst($tag), 'icon' => $this->getIconForCategory($tag)];
                }
            }
        }

        return $categories;
    }

    #[\Livewire\Attributes\Computed]
    public function filteredModules(): array
    {
        $filtered = $this->modules;

        if ($this->selectedCategory !== 'all') {
            $filtered = array_filter($filtered, function ($module) {
                return in_array($this->selectedCategory, $module['tags'] ?? []);
            });
        }

        if (!empty($this->searchQuery)) {
            $query = strtolower($this->searchQuery);
            $filtered = array_filter($filtered, function ($module) use ($query) {
                return str_contains(strtolower($module['name']), $query)
                    || str_contains(strtolower($module['description']), $query)
                    || str_contains(strtolower($module['author'] ?? ''), $query);
            });
        }

        return $filtered;
    }

    public function selectCategory(string $category): void
    {
        $this->selectedCategory = $category;
    }

    private function getIconForCategory(string $category): string
    {
        return match($category) {
            'auth' => 'heroicon-m-lock-closed',
            'security' => 'heroicon-m-shield-check',
            'payments' => 'heroicon-m-credit-card',
            'stripe' => 'heroicon-m-credit-card',
            'paypal' => 'heroicon-m-credit-card',
            'notifications' => 'heroicon-m-chat-bubble-left',
            'email' => 'heroicon-m-envelope',
            'sms' => 'heroicon-m-phone',
            'analytics' => 'heroicon-m-chart-bar',
            'oauth2' => 'heroicon-m-key',
            default => 'heroicon-m-cube',
        };
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

    /**
     * Fetch marketplace modules from configured source.
     *
     * Currently sources from: bootstrap/webkernel/platform/system_panel/src/Config/MarketplaceModules.php
     * To integrate a live API, replace MarketplaceConfig::all() with an HTTP request:
     *   return Http::get('https://marketplace.webkernelphp.com/api/modules')->json();
     */
    private function fetchMarketplaceModules(): array
    {
        return MarketplaceConfig::all();
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
