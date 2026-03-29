<?php

declare(strict_types=1);

namespace Webkernel\Panel;

use Filament\Facades\Filament;
use Webkernel\Database\FSEngine\FSTemplate;
use Webkernel\Panel\DTO\PanelDTO;
use Webkernel\Panel\Support\PanelConfigRepository;

/**
 * In-memory Sushi model representing Filament panel rows.
 *
 * Rows are the union of:
 *   - Live Filament panel introspection (source = 'static')
 *   - PanelConfigStore overrides (source = 'dynamic', values win)
 *
 * Schema and JSON columns are derived from PanelDTO::schema() — no duplication.
 */
class PanelArraysDataSource extends FSTemplate
{
    protected $primaryKey = 'id';
    protected $keyType    = 'string';

    protected $casts = [
        'is_active'                 => 'boolean',
        'is_default'                => 'boolean',
        'auth'                      => 'boolean',
        'registration'              => 'boolean',
        'password_reset'            => 'boolean',
        'email_verification'        => 'boolean',
        'mfa_required'              => 'boolean',
        'spa'                       => 'boolean',
        'dark_mode'                 => 'boolean',
        'global_search'             => 'boolean',
        'broadcasting'              => 'boolean',
        'database_notifications'    => 'boolean',
        'unsaved_changes_alerts'    => 'boolean',
        'database_transactions'     => 'boolean',
        'sidebar_collapsible'       => 'boolean',
        'sidebar_fully_collapsible' => 'boolean',
        'top_navigation'            => 'boolean',
        'has_topbar'                => 'boolean',
        'tenant_enabled'            => 'boolean',
        'tenant_billing'            => 'boolean',
        'domains'                   => 'array',
        'resources'                 => 'array',
        'pages'                     => 'array',
        'widgets'                   => 'array',
        'plugins'                   => 'array',
        'extra_middleware'          => 'array',
        'extra_auth_middleware'     => 'array',
        'feature_flags'             => 'array',
    ];

    // -------------------------------------------------------------------------
    // FSDataSource contract — derived from PanelDTO schema
    // -------------------------------------------------------------------------

    protected function getPrimaryKeyName(): string
    {
        return 'id';
    }

    protected function getSushiSchema(): array
    {
        return PanelDTO::sushiSchema();
    }

    protected function getJsonColumns(): array
    {
        return PanelDTO::jsonColumns();
    }

    // -------------------------------------------------------------------------
    // FSTemplate contract
    // -------------------------------------------------------------------------

    protected static function loadInspectorRows(): array
    {
        $stored   = PanelConfigRepository::all();
        $defaults = PanelDTO::defaults()->toArray();

        return collect(Filament::getPanels())
            ->map(function (object $panel) use ($stored, $defaults): array {
                $id        = $panel->getId();
                $persisted = $stored[$id] ?? null;

                $introspected = array_merge($defaults, [
                    'id'           => $id,
                    'path'         => method_exists($panel, 'getPath')      ? (string) $panel->getPath()              : '',
                    'brand_name'   => method_exists($panel, 'getBrandName') ? (string) ($panel->getBrandName() ?? '') : '',
                    'is_default'   => $panel->isDefault() ? 1 : 0,
                    'is_active'    => 1,
                    'source'       => $persisted !== null ? 'dynamic' : 'static',
                ]);

                return $persisted !== null
                    ? array_merge($introspected, $persisted->toArray(), ['source' => 'dynamic'])
                    : $introspected;
            })
            ->sortBy('sort_order')
            ->values()
            ->all();
    }
}
