<?php declare(strict_types=1);

namespace Webkernel\BackOffice\System\Presentation\Pages;

use BackedEnum;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use UnitEnum;

/**
 * ServerInfo
 *
 * Read-only server information page.
 * No shell_exec. No mutations. All data from PHP built-ins and /proc.
 * Page load target: < 90 ms with OPcache.
 *
 * Tabs:
 *   PHP         — version lifecycle, php.ini key settings, OPcache, FFI
 *   Memory      — system RAM breakdown, PHP process allocation
 *   CPU         — load averages, core count, estimated utilisation
 *   Disk        — all visible mountpoints, usage and free space
 *   Extensions  — loaded extensions, critical extension status
 *   Environment — sanitised env vars, Laravel config summary
 *   Network     — web server, SAPI, safe request headers
 *   Instance    — Webkernel identity, OS info, composer packages
 *
 * @property-read Schema $form
 */
class ServerInfo extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'webkernel-system::filament.pages.server-info';

    protected static ?int                 $navigationSort           = -1;
    protected static bool                 $shouldRegisterNavigation = true;
    protected static string|UnitEnum|null $navigationGroup          = 'System';

    public array $formData = [];

    public function mount(): void
    {
        $this->form->fill([]);
    }

    /**
     * @return array<int, string>
     */
    protected function getForms(): array
    {
        return ['form'];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('formData')
            ->schema([
                Tabs::make('serverInfoTabs')
                    ->contained(false)
                    ->scrollable(true)
                    ->persistTabInQueryString('stab')
                    ->tabs([
                        // Tabs assembled via trait concerns once re-attached:
                        // $this->buildPhpTab(),
                        // $this->buildMemoryTab(),
                        // $this->buildCpuTab(),
                        // $this->buildDiskTab(),
                        // $this->buildExtensionsTab(),
                        // $this->buildEnvironmentTab(),
                        // $this->buildNetworkTab(),
                        // $this->buildInstanceTab(),
                    ]),
            ]);
    }

    // ── Safe request headers ──────────────────────────────────────────────────

    /**
     * Returns sanitised request headers for display in the Network tab.
     * Sensitive header values are replaced with "(masked)".
     *
     * @return array<int, array{name: string, value: string, sensitive: bool}>
     */
    protected function getSafeRequestHeaders(): array
    {
        $sensitiveHeaders = ['authorization', 'cookie', 'x-api-key', 'x-auth-token', 'x-csrf-token'];
        $headers          = [];

        foreach ($_SERVER as $key => $value) {
            $isHttp = str_starts_with((string) $key, 'HTTP_');
            $isMeta = in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'], true);

            if (! $isHttp && ! $isMeta) {
                continue;
            }

            $name        = strtolower(str_replace(['HTTP_', '_'], ['', '-'], (string) $key));
            $isSensitive = in_array($name, $sensitiveHeaders, true);

            $headers[] = [
                'name'      => $name,
                'value'     => $isSensitive ? '(masked)' : (string) $value,
                'sensitive' => $isSensitive,
            ];
        }

        return $headers;
    }

    // ── Navigation ────────────────────────────────────────────────────────────

    public static function getNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return 'heroicon-o-server';
    }

    public static function getNavigationLabel(): string
    {
        return 'Server Info';
    }

    public function getTitle(): string|Htmlable
    {
        return 'Server Information';
    }
}
