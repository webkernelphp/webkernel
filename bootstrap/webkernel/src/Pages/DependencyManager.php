<?php declare(strict_types=1);
namespace Webkernel\Pages;

use Filament\Actions\Action;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\ActionSize;
use Illuminate\Support\Collection;
use Webkernel\Process;
use Webkernel\Query\QueryModules;
use Webkernel\Traits\HasSelfResolvedView;
use BackedEnum;

class DependencyManager extends Page implements HasForms
{
    use InteractsWithForms, HasSelfResolvedView;

    /**
     * Look for 'dodo.blade.php' in the same folder.
     */
    protected static ?string $dynamicView = 'dodo';

    /**
     * Automatically populated by the trait's initializer.
     */
    protected string $view;

    // ──────────────────────────────────────────────────────────────
    //  Navigation
    // ──────────────────────────────────────────────────────────────
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-cube-transparent';
    protected static ?string $navigationLabel = 'Dependencies';
    protected static ?int    $navigationSort  = 10;



    // ──────────────────────────────────────────────────────────────
    //  UI state
    // ──────────────────────────────────────────────────────────────

    /** Active main tab */
    public string $activeTab = 'dependencies';

    /** Filter slide-over open */
    public bool $filtersOpen = false;

    // Dependency filters
    public string $search         = '';
    public string $typeFilter     = 'all';
    public string $securityFilter = 'all';
    public string $updateFilter   = 'all';
    public string $licenseFilter  = 'all';

    // Store filters
    public string $storeSearch = '';
    public string $storeParty  = 'all';
    public string $storePrice  = 'all';

    // Vendorize modal
    public bool   $vendorModalOpen = false;
    public string $vendorTarget    = '';
    public string $vendorMode      = 'local';

    // ──────────────────────────────────────────────────────────────
    //  Data
    // ──────────────────────────────────────────────────────────────
    public array $release      = [];
    public array $packages     = [];
    public array $cves         = [];
    public array $outdated     = [];
    public array $modules      = [];
    public array $storeModules = [];

    // Aggregated stats
    public int $total           = 0;
    public int $coreCount       = 0;
    public int $webkernelCount  = 0;
    public int $thirdPartyCount = 0;
    public int $vulnerableCount = 0;
    public int $outdatedCount   = 0;
    public int $activeModules   = 0;

    // ──────────────────────────────────────────────────────────────
    //  Boot
    // ──────────────────────────────────────────────────────────────
    public function mount(): void
    {
        $this->release = [
            'semver'   => 'v' . (defined('WEBKERNEL_SEMVER')   ? WEBKERNEL_SEMVER   : '1.3.32+53'),
            'codename' => ucfirst(defined('WEBKERNEL_CODENAME') ? WEBKERNEL_CODENAME : 'Waterfall'),
        ];

        $this->loadPackages();
        $this->loadCves();
        $this->loadOutdated();
        $this->loadModules();
        $this->loadStoreModules();
        $this->computeStats();
    }

    // ──────────────────────────────────────────────────────────────
    //  Loaders
    // ──────────────────────────────────────────────────────────────
    protected function loadPackages(): void
    {
        $rootPath     = base_path('bootstrap/composer.json');
        $rootComposer = is_file($rootPath) ? json_decode(file_get_contents($rootPath), true) : [];
        $corePackages = array_keys($rootComposer['require'] ?? []);
        $vendorDir    = base_path('vendor');

        try {
            $rii = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($vendorDir, \FilesystemIterator::SKIP_DOTS)
            );

            foreach ($rii as $file) {
                if ($file->getFilename() !== 'composer.json') continue;
                if ($file->getPathname() === base_path('composer.json')) continue;

                $json = json_decode(@file_get_contents($file->getPathname()), true);
                if (!is_array($json)) continue;

                $name = $json['name'] ?? null;
                if (!$name || !str_contains($name, '/')) continue;

                [$vendor, $package] = explode('/', $name, 2);

                $version = $json['version'] ?? null;
                if (!$version) {
                    $alias = $json['extra']['branch-alias'] ?? null;
                    if (is_array($alias)) $version = array_values($alias)[0] ?? null;
                }
                $version = $version ?: 'dev';

                $license  = $json['license'] ?? 'unknown';
                $license  = is_array($license) ? implode(', ', $license) : ($license ?: 'unknown');
                $fullName = $vendor . '/' . $package;

                $type = 'third-party';
                if (str_starts_with($vendor, 'webkernel')) $type = 'webkernel';
                if (in_array($fullName, $corePackages, true)) $type = 'core';

                $this->packages[$fullName] = [
                    'vendor'      => $vendor,
                    'package'     => $package,
                    'version'     => $version,
                    'description' => $json['description'] ?? null,
                    'license'     => $license,
                    'type'        => $type,
                    'homepage'    => $json['homepage'] ?? null,
                    'keywords'    => $json['keywords'] ?? [],
                    'author_email'     => $json['authors'][0]['email'] ?? '',
                ];
            }
        } catch (\Throwable) {}
    }

    protected function loadCves(): void
    {
        try {
            $process = Process::fromArray(['composer', 'audit', '--format=json'])->mustRun();
            $audit   = json_decode($process->getOutput(), true);
            foreach ($audit['advisories'] ?? [] as $pkg => $list) {
                foreach ($list as $adv) {
                    $this->cves[$pkg][] = [
                        'title'    => $adv['title']    ?? '',
                        'cve'      => $adv['cve']      ?? null,
                        'severity' => $adv['severity'] ?? 'unknown',
                        'link'     => $adv['link']     ?? null,
                    ];
                }
            }
        } catch (\Throwable) {}
    }

    protected function loadOutdated(): void
    {
        try {
            $process = Process::fromArray(['composer', 'outdated', '--format=json'])->mustRun();
            $result  = json_decode($process->getOutput(), true);
            foreach ($result['installed'] ?? [] as $pkg) {
                $this->outdated[$pkg['name']] = [
                    'current' => $pkg['version']       ?? '?',
                    'latest'  => $pkg['latest']        ?? '?',
                    'status'  => $pkg['latest-status'] ?? 'update-possible',
                ];
            }
        } catch (\Throwable) {}
    }

    protected function loadModules(): void
    {
        try {
            $raw           = QueryModules::make()->get();
            $this->modules = $raw instanceof Collection ? $raw->toArray() : (array) $raw;
        } catch (\Throwable) {
            $catalogPath   = base_path('bootstrap/webkernel/catalog.php');
            $catalog       = is_file($catalogPath) ? require $catalogPath : [];
            $this->modules = $catalog['entries'] ?? [];
        }
    }

    protected function loadStoreModules(): void
    {
        // TODO: replace with HTTP call to webkernelphp.com/api/store/v1/modules
        // Each entry carries media placeholders (image, video_link, screenshots, docs_link, changelog)
        // to be populated once the CDN/API is live.
        $this->storeModules = [
            [
                'id'          => 'webkernel::aptitudes/system',
                'label'       => 'System Manager',
                'description' => 'Core system management — process supervision, cron, health checks.',
                'version'     => '2.0.0',
                'author'      => 'Numerimondes',
                'party'       => 'first',
                'installed'   => true,
                'active'      => true,
                'price'       => 'free',
                'tags'        => ['system', 'core', 'process'],
                // ── Media (populate when CDN is ready) ───────────────
                'image'       => null,   // e.g. 'https://cdn.webkernelphp.com/modules/system-manager/cover.png'
                'video_link'  => null,   // e.g. 'https://www.youtube.com/watch?v=...'
                'docs_link'   => null,   // e.g. 'https://docs.webkernelphp.com/modules/system-manager'
                'changelog'   => null,   // e.g. markdown string or URL
                'screenshots' => [],     // e.g. ['https://cdn.../s1.png', 'https://cdn.../s2.png']
                // ── Meta ─────────────────────────────────────────────
                'rating'        => 4.9,
                'downloads'     => 12400,
                'license'       => 'proprietary',
                'compatibility' => ['php' => '>=8.4', 'laravel' => '>=12.0', 'webkernel' => '>=2.0.0'],
            ],
            [
                'id'          => 'webkernelphp-com::media/manager',
                'label'       => 'Media Manager',
                'description' => 'Advanced media library: S3, optimisation, tagging, EXIF metadata.',
                'version'     => '1.4.2',
                'author'      => 'Numerimondes',
                'party'       => 'first',
                'installed'   => false,
                'active'      => false,
                'price'       => 'free',
                'tags'        => ['media', 'files', 's3'],
                'image'       => null,
                'video_link'  => null,
                'docs_link'   => null,
                'changelog'   => null,
                'screenshots' => [],
                'rating'      => 4.7,
                'downloads'   => 8900,
                'license'     => 'MIT',
                'compatibility' => ['php' => '>=8.2', 'laravel' => '>=11.0', 'webkernel' => '>=1.5.0'],
            ],
            [
                'id'          => 'webkernelphp-com::analytics/pulse',
                'label'       => 'Analytics Pulse',
                'description' => 'Real-time analytics: heatmaps, funnel analysis, exportable reports.',
                'version'     => '0.9.1',
                'author'      => 'Numerimondes',
                'party'       => 'first',
                'installed'   => false,
                'active'      => false,
                'price'       => 'premium',
                'tags'        => ['analytics', 'reports', 'dashboard'],
                'image'       => null,
                'video_link'  => null,
                'docs_link'   => null,
                'changelog'   => null,
                'screenshots' => [],
                'rating'      => 4.5,
                'downloads'   => 3200,
                'license'     => 'commercial',
                'compatibility' => ['php' => '>=8.3', 'laravel' => '>=12.0', 'webkernel' => '>=1.8.0'],
            ],
            [
                'id'          => 'webkernelphp-com::auth/sso',
                'label'       => 'SSO Bridge',
                'description' => 'SAML2 / OIDC / OAuth2 bridge with multi-tenant and JIT provisioning.',
                'version'     => '1.1.0',
                'author'      => 'Numerimondes',
                'party'       => 'first',
                'installed'   => false,
                'active'      => false,
                'price'       => 'premium',
                'tags'        => ['auth', 'sso', 'saml', 'oidc'],
                'image'       => null,
                'video_link'  => null,
                'docs_link'   => null,
                'changelog'   => null,
                'screenshots' => [],
                'rating'      => 4.8,
                'downloads'   => 5100,
                'license'     => 'commercial',
                'compatibility' => ['php' => '>=8.4', 'laravel' => '>=12.0', 'webkernel' => '>=2.0.0'],
            ],
            [
                'id'          => 'webkernelphp-com::devtools/inspector',
                'label'       => 'DevTools Inspector',
                'description' => 'Deep introspection: routes, queries, events, jobs and Livewire.',
                'version'     => '1.0.3',
                'author'      => 'Community',
                'party'       => 'second',
                'installed'   => false,
                'active'      => false,
                'price'       => 'free',
                'tags'        => ['devtools', 'debug', 'inspector'],
                'image'       => null,
                'video_link'  => null,
                'docs_link'   => null,
                'changelog'   => null,
                'screenshots' => [],
                'rating'      => 4.3,
                'downloads'   => 2100,
                'license'     => 'MIT',
                'compatibility' => ['php' => '>=8.2', 'laravel' => '>=11.0', 'webkernel' => '>=1.3.0'],
            ],
            [
                'id'          => 'webkernelphp-com::test/test',
                'label'       => 'Test Module',
                'description' => 'Sandbox for integration testing and module scaffolding experiments.',
                'version'     => '0.1.0',
                'author'      => 'te',
                'party'       => 'second',
                'installed'   => true,
                'active'      => true,
                'price'       => 'free',
                'tags'        => ['test', 'dev'],
                'image'       => null,
                'video_link'  => null,
                'docs_link'   => null,
                'changelog'   => null,
                'screenshots' => [],
                'rating'      => null,
                'downloads'   => 42,
                'license'     => 'proprietary',
                'compatibility' => ['php' => '>=8.4', 'laravel' => '>=12.0', 'webkernel' => '>=1.0.0'],
            ],
        ];
    }

    // ──────────────────────────────────────────────────────────────
    //  Stats
    // ──────────────────────────────────────────────────────────────
    protected function computeStats(): void
    {
        $this->total           = count($this->packages);
        $this->vulnerableCount = count($this->cves);
        $this->outdatedCount   = count($this->outdated);
        $this->activeModules   = count(array_filter($this->modules, fn ($m) => $m['active'] ?? false));

        foreach ($this->packages as $p) {
            match ($p['type']) {
                'core'      => $this->coreCount++,
                'webkernel' => $this->webkernelCount++,
                default     => $this->thirdPartyCount++,
            };
        }
    }

    // ──────────────────────────────────────────────────────────────
    //  Computed properties used in the Blade view
    // ──────────────────────────────────────────────────────────────

    /** Returns packages grouped by vendor, with all filters applied. */
    public function getFilteredGroupedProperty(): array
    {
        $grouped = [];

        foreach ($this->packages as $fullName => $p) {
            // Type
            if ($this->typeFilter !== 'all' && $p['type'] !== $this->typeFilter) continue;
            // License
            if ($this->licenseFilter !== 'all' && $p['license'] !== $this->licenseFilter) continue;
            // Security
            if ($this->securityFilter === 'vulnerable' && !isset($this->cves[$fullName])) continue;
            if ($this->securityFilter === 'secure'     &&  isset($this->cves[$fullName])) continue;
            // Updates
            if ($this->updateFilter === 'outdated' && !isset($this->outdated[$fullName])) continue;
            // Search
            if ($this->search !== '') {
                $q = mb_strtolower($this->search);
                if (!str_contains(mb_strtolower($fullName . ' ' . ($p['description'] ?? '')), $q)) continue;
            }

            $grouped[$p['vendor']][$fullName] = $p;
        }

        ksort($grouped);
        return $grouped;
    }

    public function getFilteredCountProperty(): int
    {
        return array_sum(array_map('count', $this->filteredGrouped));
    }

    public function getUniqueLicensesProperty(): array
    {
        $licenses = array_unique(array_column($this->packages, 'license'));
        sort($licenses);
        return $licenses;
    }

    public function getFilteredStoreModulesProperty(): array
    {
        return array_values(array_filter($this->storeModules, function (array $m) {
            if ($this->storeParty !== 'all' && $m['party'] !== $this->storeParty) return false;
            if ($this->storePrice !== 'all' && $m['price'] !== $this->storePrice) return false;
            if ($this->storeSearch !== '') {
                $q   = mb_strtolower($this->storeSearch);
                $hay = mb_strtolower($m['label'] . ' ' . $m['description'] . ' ' . implode(' ', $m['tags'] ?? []));
                if (!str_contains($hay, $q)) return false;
            }
            return true;
        }));
    }

    public function hasActiveFilters(): bool
    {
        return $this->typeFilter !== 'all'
            || $this->securityFilter !== 'all'
            || $this->updateFilter !== 'all'
            || $this->licenseFilter !== 'all'
            || $this->search !== '';
    }

    // ──────────────────────────────────────────────────────────────
    //  Livewire actions
    // ──────────────────────────────────────────────────────────────
    public function resetFilters(): void
    {
        $this->typeFilter     = 'all';
        $this->securityFilter = 'all';
        $this->updateFilter   = 'all';
        $this->licenseFilter  = 'all';
        $this->search         = '';
    }

    public function openVendorModal(string $package, string $mode = 'local'): void
    {
        $this->vendorTarget    = $package;
        $this->vendorMode      = $mode;
        $this->vendorModalOpen = true;
    }

    public function confirmVendorize(): void
    {
        $instanceId = config('webkernel.instance_id', 'default');
        $user       = get_current_user();
        $dt         = now()->format('Y-m-d_His');

        if ($this->vendorMode === 'local') {
            $dest = "/home/{$user}/webkernel/backup-vendor/{$instanceId}/{$dt}/vendor/{$this->vendorTarget}";
            // TODO: dispatch(new VendorizePackageJob('local', $this->vendorTarget, $dest));
            Notification::make()->title('Local snapshot queued')->body($dest)->success()->send();
        } else {
            // TODO: dispatch(new VendorizePackageJob('github', $this->vendorTarget));
            Notification::make()->title('GitHub snapshot queued')->body($this->vendorTarget)->info()->send();
        }

        $this->vendorModalOpen = false;
        $this->vendorTarget    = '';
    }

    public function refreshCves(): void
    {
        $this->cves           = [];
        $this->vulnerableCount = 0;
        $this->loadCves();
        $this->vulnerableCount = count($this->cves);

        Notification::make()
            ->title('CVE audit complete')
            ->body($this->vulnerableCount . ' vulnerability/ies found.')
            ->{$this->vulnerableCount > 0 ? 'danger' : 'success'}()
            ->send();
    }

    public function refreshOutdated(): void
    {
        $this->outdated      = [];
        $this->outdatedCount = 0;
        $this->loadOutdated();
        $this->outdatedCount = count($this->outdated);

        Notification::make()
            ->title('Update check complete')
            ->body($this->outdatedCount . ' package(s) have updates available.')
            ->success()
            ->send();
    }

    public function installModule(string $moduleId): void
    {
        // TODO: dispatch(new InstallModuleJob($moduleId));
        Notification::make()->title('Install queued')->body("Module {$moduleId} queued.")->info()->send();
    }

    // ──────────────────────────────────────────────────────────────
    //  Header actions
    // ──────────────────────────────────────────────────────────────
    protected function getHeaderActions(): array
    {
        return [
            Action::make('toggleFilters')
                ->label(fn () => $this->filtersOpen ? 'Hide Filters' : 'Filters')
                ->icon(fn () => $this->filtersOpen ? 'heroicon-o-x-mark' : 'heroicon-o-adjustments-horizontal')
                ->color('gray')

                ->action(fn () => $this->filtersOpen = !$this->filtersOpen)
                ->visible(fn () => $this->activeTab === 'dependencies'),

            Action::make('refreshCves')
                ->label('Audit CVE')
                ->icon('heroicon-o-shield-exclamation')
                ->color('danger')

                ->action('refreshCves')
                ->visible(fn () => $this->activeTab === 'dependencies'),

            Action::make('refreshOutdated')
                ->label('Check Updates')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')

                ->action('refreshOutdated')
                ->visible(fn () => $this->activeTab === 'dependencies'),

            Action::make('goToStore')
                ->label('Module Store')
                ->icon('heroicon-o-shopping-bag')
                ->color('primary')

                ->action(fn () => $this->activeTab = 'store'),
        ];
    }
}
