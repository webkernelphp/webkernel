<?php

declare(strict_types=1);

namespace Webkernel\Panel\DTO;

/**
 * Immutable value object representing one panel's full configuration.
 *
 * schema()    — typed field definitions driving UI generation and Sushi schema.
 * defaults()  — zero-arg instance used to seed the config file on first boot.
 * toArray()   — flat snake_case map for JSON storage and Sushi rows.
 * fromArray() — hydrate from JSON storage or form submission.
 */
final class PanelDTO
{
    public function __construct(
        // ------------------------------------------------------------------
        // Identity
        // ------------------------------------------------------------------
        public readonly string  $id,
        public readonly string  $path                = '',
        public readonly string  $name                = '',
        public readonly string  $description         = '',
        public readonly int     $sortOrder           = 0,
        public readonly bool    $isActive            = true,
        public readonly bool    $isDefault           = false,
        public readonly string  $source              = 'dynamic',

        // ------------------------------------------------------------------
        // Routing
        // ------------------------------------------------------------------
        public readonly ?string $homeUrl             = null,
        /** @var string[] */
        public readonly array   $domains             = [],

        // ------------------------------------------------------------------
        // Brand
        // ------------------------------------------------------------------
        public readonly string  $brandName           = '',
        public readonly ?string $brandLogo           = null,
        public readonly ?string $brandLogoDark       = null,
        public readonly ?string $brandLogoHeight     = null,
        public readonly ?string $favicon             = null,
        public readonly ?string $primaryColor        = null,

        // ------------------------------------------------------------------
        // Layout
        // ------------------------------------------------------------------
        public readonly ?string $sidebarWidth            = null,
        public readonly ?string $collapsedSidebarWidth   = null,
        public readonly ?string $maxContentWidth         = null,
        public readonly bool    $sidebarCollapsible      = false,
        public readonly bool    $sidebarFullyCollapsible = false,
        public readonly bool    $topNavigation           = false,
        public readonly bool    $hasTopbar               = true,

        // ------------------------------------------------------------------
        // Features
        // ------------------------------------------------------------------
        public readonly bool    $spa                   = false,
        public readonly bool    $darkMode              = true,
        public readonly bool    $globalSearch          = true,
        public readonly bool    $broadcasting          = false,
        public readonly bool    $databaseNotifications = false,
        public readonly bool    $unsavedChangesAlerts  = false,
        public readonly bool    $databaseTransactions  = false,

        // ------------------------------------------------------------------
        // Authentication
        // ------------------------------------------------------------------
        public readonly bool    $auth               = true,
        public readonly bool    $registration       = false,
        public readonly bool    $passwordReset      = false,
        public readonly bool    $emailVerification  = false,
        public readonly bool    $mfaRequired        = false,
        public readonly ?string $authGuard          = null,
        public readonly ?string $authPasswordBroker = null,

        // ------------------------------------------------------------------
        // Multi-tenancy
        // ------------------------------------------------------------------
        public readonly bool    $tenantEnabled           = false,
        public readonly ?string $tenantModel             = null,
        public readonly ?string $tenantOwnershipRelation = null,
        public readonly ?string $tenantDomain            = null,
        public readonly bool    $tenantBilling           = false,

        // ------------------------------------------------------------------
        // Registered components
        // ------------------------------------------------------------------
        /** @var class-string[] */
        public readonly array   $resources = [],
        /** @var class-string[] */
        public readonly array   $pages     = [],
        /** @var class-string[] */
        public readonly array   $widgets   = [],
        /** @var class-string[] */
        public readonly array   $plugins   = [],

        // ------------------------------------------------------------------
        // Auto-discovery
        // ------------------------------------------------------------------
        public readonly ?string $discoverResourcesIn  = null,
        public readonly ?string $discoverResourcesFor = null,
        public readonly ?string $discoverPagesIn      = null,
        public readonly ?string $discoverPagesFor     = null,
        public readonly ?string $discoverWidgetsIn    = null,
        public readonly ?string $discoverWidgetsFor   = null,

        // ------------------------------------------------------------------
        // Feature flags
        // ------------------------------------------------------------------
        /** @var array<string, bool> */
        public readonly array   $featureFlags = [],

        // ------------------------------------------------------------------
        // Middleware
        // ------------------------------------------------------------------
        /** @var class-string[] */
        public readonly array   $extraMiddleware     = [],
        /** @var class-string[] */
        public readonly array   $extraAuthMiddleware = [],
    ) {}

    // -------------------------------------------------------------------------
    // Schema — typed field definitions
    // -------------------------------------------------------------------------

    /**
     * Full typed schema for all panel config fields.
     *
     * Each entry:
     *   type        — string | bool | int | url | color | path | enum | class-list | key-value
     *   group       — identity | routing | brand | layout | features | auth | tenancy | components | middleware
     *   label       — human-readable label for UI generation
     *   sushi_type  — 'string' | 'integer' | 'boolean'  (for FSDataSource::getSushiSchema())
     *   json        — true when the value must be JSON-encoded in Sushi's SQLite store
     *   nullable    — true when null is a valid value
     *   readonly    — true when the field should not be editable from the UI
     *   allowed     — for enum fields, the list of valid values
     *
     * @return array<string, array<string, mixed>>
     */
    public static function schema(): array
    {
        return [
            // Identity
            'id'                         => ['type' => 'string',     'group' => 'identity',   'label' => 'Panel ID',                   'sushi_type' => 'string',  'json' => false, 'nullable' => false, 'readonly' => true],
            'path'                       => ['type' => 'string',     'group' => 'identity',   'label' => 'URL Path',                   'sushi_type' => 'string',  'json' => false, 'nullable' => false],
            'name'                       => ['type' => 'string',     'group' => 'identity',   'label' => 'Display Name',               'sushi_type' => 'string',  'json' => false, 'nullable' => false],
            'description'                => ['type' => 'string',     'group' => 'identity',   'label' => 'Description',                'sushi_type' => 'string',  'json' => false, 'nullable' => false],
            'sort_order'                 => ['type' => 'int',        'group' => 'identity',   'label' => 'Sort Order',                 'sushi_type' => 'integer', 'json' => false, 'nullable' => false],
            'is_active'                  => ['type' => 'bool',       'group' => 'identity',   'label' => 'Active',                     'sushi_type' => 'boolean', 'json' => false, 'nullable' => false],
            'is_default'                 => ['type' => 'bool',       'group' => 'identity',   'label' => 'Default Panel',              'sushi_type' => 'boolean', 'json' => false, 'nullable' => false, 'readonly' => true],
            'source'                     => ['type' => 'enum',       'group' => 'identity',   'label' => 'Source',                     'sushi_type' => 'string',  'json' => false, 'nullable' => false, 'readonly' => true, 'allowed' => ['static', 'dynamic']],

            // Routing
            'home_url'                   => ['type' => 'url',        'group' => 'routing',    'label' => 'Home URL',                   'sushi_type' => 'string',  'json' => false, 'nullable' => true],
            'domains'                    => ['type' => 'array',      'group' => 'routing',    'label' => 'Domains',                    'sushi_type' => 'string',  'json' => true,  'nullable' => false],

            // Brand
            'brand_name'                 => ['type' => 'string',     'group' => 'brand',      'label' => 'Brand Name',                 'sushi_type' => 'string',  'json' => false, 'nullable' => false],
            'brand_logo'                 => ['type' => 'url',        'group' => 'brand',      'label' => 'Logo (light)',               'sushi_type' => 'string',  'json' => false, 'nullable' => true],
            'brand_logo_dark'            => ['type' => 'url',        'group' => 'brand',      'label' => 'Logo (dark)',                'sushi_type' => 'string',  'json' => false, 'nullable' => true],
            'brand_logo_height'          => ['type' => 'string',     'group' => 'brand',      'label' => 'Logo Height (CSS)',          'sushi_type' => 'string',  'json' => false, 'nullable' => true],
            'favicon'                    => ['type' => 'url',        'group' => 'brand',      'label' => 'Favicon',                    'sushi_type' => 'string',  'json' => false, 'nullable' => true],
            'primary_color'              => ['type' => 'color',      'group' => 'brand',      'label' => 'Primary Color',              'sushi_type' => 'string',  'json' => false, 'nullable' => true],

            // Layout
            'sidebar_width'              => ['type' => 'string',     'group' => 'layout',     'label' => 'Sidebar Width (CSS)',        'sushi_type' => 'string',  'json' => false, 'nullable' => true],
            'collapsed_sidebar_width'    => ['type' => 'string',     'group' => 'layout',     'label' => 'Collapsed Sidebar Width',   'sushi_type' => 'string',  'json' => false, 'nullable' => true],
            'max_content_width'          => ['type' => 'string',     'group' => 'layout',     'label' => 'Max Content Width',         'sushi_type' => 'string',  'json' => false, 'nullable' => true],
            'sidebar_collapsible'        => ['type' => 'bool',       'group' => 'layout',     'label' => 'Sidebar Collapsible',        'sushi_type' => 'boolean', 'json' => false, 'nullable' => false],
            'sidebar_fully_collapsible'  => ['type' => 'bool',       'group' => 'layout',     'label' => 'Sidebar Fully Collapsible',  'sushi_type' => 'boolean', 'json' => false, 'nullable' => false],
            'top_navigation'             => ['type' => 'bool',       'group' => 'layout',     'label' => 'Top Navigation',             'sushi_type' => 'boolean', 'json' => false, 'nullable' => false],
            'has_topbar'                 => ['type' => 'bool',       'group' => 'layout',     'label' => 'Show Topbar',                'sushi_type' => 'boolean', 'json' => false, 'nullable' => false],

            // Features
            'spa'                        => ['type' => 'bool',       'group' => 'features',   'label' => 'SPA Mode',                   'sushi_type' => 'boolean', 'json' => false, 'nullable' => false],
            'dark_mode'                  => ['type' => 'bool',       'group' => 'features',   'label' => 'Dark Mode',                  'sushi_type' => 'boolean', 'json' => false, 'nullable' => false],
            'global_search'              => ['type' => 'bool',       'group' => 'features',   'label' => 'Global Search',              'sushi_type' => 'boolean', 'json' => false, 'nullable' => false],
            'broadcasting'               => ['type' => 'bool',       'group' => 'features',   'label' => 'Broadcasting',               'sushi_type' => 'boolean', 'json' => false, 'nullable' => false],
            'database_notifications'     => ['type' => 'bool',       'group' => 'features',   'label' => 'Database Notifications',     'sushi_type' => 'boolean', 'json' => false, 'nullable' => false],
            'unsaved_changes_alerts'     => ['type' => 'bool',       'group' => 'features',   'label' => 'Unsaved Changes Alerts',     'sushi_type' => 'boolean', 'json' => false, 'nullable' => false],
            'database_transactions'      => ['type' => 'bool',       'group' => 'features',   'label' => 'Database Transactions',      'sushi_type' => 'boolean', 'json' => false, 'nullable' => false],

            // Auth
            'auth'                       => ['type' => 'bool',       'group' => 'auth',       'label' => 'Login Enabled',              'sushi_type' => 'boolean', 'json' => false, 'nullable' => false],
            'registration'               => ['type' => 'bool',       'group' => 'auth',       'label' => 'Registration',               'sushi_type' => 'boolean', 'json' => false, 'nullable' => false],
            'password_reset'             => ['type' => 'bool',       'group' => 'auth',       'label' => 'Password Reset',             'sushi_type' => 'boolean', 'json' => false, 'nullable' => false],
            'email_verification'         => ['type' => 'bool',       'group' => 'auth',       'label' => 'Email Verification',         'sushi_type' => 'boolean', 'json' => false, 'nullable' => false],
            'mfa_required'               => ['type' => 'bool',       'group' => 'auth',       'label' => 'MFA Required',               'sushi_type' => 'boolean', 'json' => false, 'nullable' => false],
            'auth_guard'                 => ['type' => 'string',     'group' => 'auth',       'label' => 'Auth Guard',                 'sushi_type' => 'string',  'json' => false, 'nullable' => true],
            'auth_password_broker'       => ['type' => 'string',     'group' => 'auth',       'label' => 'Password Broker',            'sushi_type' => 'string',  'json' => false, 'nullable' => true],

            // Tenancy
            'tenant_enabled'             => ['type' => 'bool',       'group' => 'tenancy',    'label' => 'Multi-tenancy Enabled',      'sushi_type' => 'boolean', 'json' => false, 'nullable' => false],
            'tenant_model'               => ['type' => 'class',      'group' => 'tenancy',    'label' => 'Tenant Model',               'sushi_type' => 'string',  'json' => false, 'nullable' => true],
            'tenant_ownership_relation'  => ['type' => 'string',     'group' => 'tenancy',    'label' => 'Ownership Relation',         'sushi_type' => 'string',  'json' => false, 'nullable' => true],
            'tenant_domain'              => ['type' => 'string',     'group' => 'tenancy',    'label' => 'Tenant Domain',              'sushi_type' => 'string',  'json' => false, 'nullable' => true],
            'tenant_billing'             => ['type' => 'bool',       'group' => 'tenancy',    'label' => 'Tenant Billing',             'sushi_type' => 'boolean', 'json' => false, 'nullable' => false],

            // Components
            'resources'                  => ['type' => 'class-list', 'group' => 'components', 'label' => 'Resources',                  'sushi_type' => 'string',  'json' => true,  'nullable' => false],
            'pages'                      => ['type' => 'class-list', 'group' => 'components', 'label' => 'Pages',                      'sushi_type' => 'string',  'json' => true,  'nullable' => false],
            'widgets'                    => ['type' => 'class-list', 'group' => 'components', 'label' => 'Widgets',                    'sushi_type' => 'string',  'json' => true,  'nullable' => false],
            'plugins'                    => ['type' => 'class-list', 'group' => 'components', 'label' => 'Plugins',                    'sushi_type' => 'string',  'json' => true,  'nullable' => false],
            'discover_resources_in'      => ['type' => 'path',       'group' => 'components', 'label' => 'Discover Resources In',      'sushi_type' => 'string',  'json' => false, 'nullable' => true],
            'discover_resources_for'     => ['type' => 'string',     'group' => 'components', 'label' => 'Discover Resources For',     'sushi_type' => 'string',  'json' => false, 'nullable' => true],
            'discover_pages_in'          => ['type' => 'path',       'group' => 'components', 'label' => 'Discover Pages In',          'sushi_type' => 'string',  'json' => false, 'nullable' => true],
            'discover_pages_for'         => ['type' => 'string',     'group' => 'components', 'label' => 'Discover Pages For',         'sushi_type' => 'string',  'json' => false, 'nullable' => true],
            'discover_widgets_in'        => ['type' => 'path',       'group' => 'components', 'label' => 'Discover Widgets In',        'sushi_type' => 'string',  'json' => false, 'nullable' => true],
            'discover_widgets_for'       => ['type' => 'string',     'group' => 'components', 'label' => 'Discover Widgets For',       'sushi_type' => 'string',  'json' => false, 'nullable' => true],

            // Flags & Middleware
            'feature_flags'              => ['type' => 'key-value',  'group' => 'flags',      'label' => 'Feature Flags',              'sushi_type' => 'string',  'json' => true,  'nullable' => false],
            'extra_middleware'           => ['type' => 'class-list', 'group' => 'middleware', 'label' => 'Extra Middleware',            'sushi_type' => 'string',  'json' => true,  'nullable' => false],
            'extra_auth_middleware'      => ['type' => 'class-list', 'group' => 'middleware', 'label' => 'Extra Auth Middleware',       'sushi_type' => 'string',  'json' => true,  'nullable' => false],
        ];
    }

    /**
     * Sushi column type map derived from schema().
     * Used by FSDataSource::getSushiSchema().
     *
     * @return array<string, string>
     */
    public static function sushiSchema(): array
    {
        return array_map(static fn (array $f): string => $f['sushi_type'], self::schema());
    }

    /**
     * Columns that must be JSON-encoded for Sushi's SQLite store.
     * Used by FSDataSource::getJsonColumns().
     *
     * @return list<string>
     */
    public static function jsonColumns(): array
    {
        return array_keys(array_filter(self::schema(), static fn (array $f): bool => $f['json'] === true));
    }

    /**
     * Zero-argument instance with all defaults.
     * Used to seed the panel config file on first boot.
     */
    public static function defaults(string $id = ''): self
    {
        return new self(id: $id);
    }

    // -------------------------------------------------------------------------
    // Construction helpers
    // -------------------------------------------------------------------------

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $b = static fn (mixed $v): bool => (bool) $v;
        $s = static fn (mixed $v): ?string => ($v !== null && $v !== '' && ! is_array($v)) ? (string) $v : null;

        return new self(
            id:                       (string) ($data['id'] ?? ''),
            path:                     (string) ($data['path'] ?? ''),
            name:                     (string) ($data['name'] ?? ''),
            description:              (string) ($data['description'] ?? ''),
            sortOrder:                (int)    ($data['sort_order'] ?? $data['sortOrder'] ?? 0),
            isActive:                 $b($data['is_active'] ?? $data['isActive'] ?? true),
            isDefault:                $b($data['is_default'] ?? $data['isDefault'] ?? false),
            source:                   (string) ($data['source'] ?? 'dynamic'),

            homeUrl:                  $s($data['home_url'] ?? $data['homeUrl'] ?? null),
            domains:                  self::toStringArray($data['domains'] ?? []),

            brandName:                (string) ($data['brand_name'] ?? $data['brandName'] ?? ''),
            brandLogo:                $s($data['brand_logo'] ?? $data['brandLogo'] ?? null),
            brandLogoDark:            $s($data['brand_logo_dark'] ?? $data['brandLogoDark'] ?? null),
            brandLogoHeight:          $s($data['brand_logo_height'] ?? $data['brandLogoHeight'] ?? null),
            favicon:                  $s($data['favicon'] ?? null),
            primaryColor:             $s($data['primary_color'] ?? $data['primaryColor'] ?? null),

            sidebarWidth:             $s($data['sidebar_width'] ?? $data['sidebarWidth'] ?? null),
            collapsedSidebarWidth:    $s($data['collapsed_sidebar_width'] ?? $data['collapsedSidebarWidth'] ?? null),
            maxContentWidth:          $s($data['max_content_width'] ?? $data['maxContentWidth'] ?? null),
            sidebarCollapsible:       $b($data['sidebar_collapsible'] ?? $data['sidebarCollapsible'] ?? false),
            sidebarFullyCollapsible:  $b($data['sidebar_fully_collapsible'] ?? $data['sidebarFullyCollapsible'] ?? false),
            topNavigation:            $b($data['top_navigation'] ?? $data['topNavigation'] ?? false),
            hasTopbar:                $b($data['has_topbar'] ?? $data['hasTopbar'] ?? true),

            spa:                      $b($data['spa'] ?? false),
            darkMode:                 $b($data['dark_mode'] ?? $data['darkMode'] ?? true),
            globalSearch:             $b($data['global_search'] ?? $data['globalSearch'] ?? true),
            broadcasting:             $b($data['broadcasting'] ?? false),
            databaseNotifications:    $b($data['database_notifications'] ?? $data['databaseNotifications'] ?? false),
            unsavedChangesAlerts:     $b($data['unsaved_changes_alerts'] ?? $data['unsavedChangesAlerts'] ?? false),
            databaseTransactions:     $b($data['database_transactions'] ?? $data['databaseTransactions'] ?? false),

            auth:                     $b($data['auth'] ?? true),
            registration:             $b($data['registration'] ?? false),
            passwordReset:            $b($data['password_reset'] ?? $data['passwordReset'] ?? false),
            emailVerification:        $b($data['email_verification'] ?? $data['emailVerification'] ?? false),
            mfaRequired:              $b($data['mfa_required'] ?? $data['mfaRequired'] ?? false),
            authGuard:                $s($data['auth_guard'] ?? $data['authGuard'] ?? null),
            authPasswordBroker:       $s($data['auth_password_broker'] ?? $data['authPasswordBroker'] ?? null),

            tenantEnabled:            $b($data['tenant_enabled'] ?? $data['tenantEnabled'] ?? false),
            tenantModel:              $s($data['tenant_model'] ?? $data['tenantModel'] ?? null),
            tenantOwnershipRelation:  $s($data['tenant_ownership_relation'] ?? $data['tenantOwnershipRelation'] ?? null),
            tenantDomain:             $s($data['tenant_domain'] ?? $data['tenantDomain'] ?? null),
            tenantBilling:            $b($data['tenant_billing'] ?? $data['tenantBilling'] ?? false),

            resources:                self::toStringArray($data['resources'] ?? []),
            pages:                    self::toStringArray($data['pages'] ?? []),
            widgets:                  self::toStringArray($data['widgets'] ?? []),
            plugins:                  self::toStringArray($data['plugins'] ?? []),

            discoverResourcesIn:      $s($data['discover_resources_in'] ?? $data['discoverResourcesIn'] ?? null),
            discoverResourcesFor:     $s($data['discover_resources_for'] ?? $data['discoverResourcesFor'] ?? null),
            discoverPagesIn:          $s($data['discover_pages_in'] ?? $data['discoverPagesIn'] ?? null),
            discoverPagesFor:         $s($data['discover_pages_for'] ?? $data['discoverPagesFor'] ?? null),
            discoverWidgetsIn:        $s($data['discover_widgets_in'] ?? $data['discoverWidgetsIn'] ?? null),
            discoverWidgetsFor:       $s($data['discover_widgets_for'] ?? $data['discoverWidgetsFor'] ?? null),

            featureFlags:             self::toBoolMap($data['feature_flags'] ?? $data['featureFlags'] ?? []),

            extraMiddleware:          self::toStringArray($data['extra_middleware'] ?? $data['extraMiddleware'] ?? []),
            extraAuthMiddleware:      self::toStringArray($data['extra_auth_middleware'] ?? $data['extraAuthMiddleware'] ?? []),
        );
    }

    // -------------------------------------------------------------------------
    // Serialisation
    // -------------------------------------------------------------------------

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id'                         => $this->id,
            'path'                       => $this->path,
            'name'                       => $this->name,
            'description'                => $this->description,
            'sort_order'                 => $this->sortOrder,
            'is_active'                  => (int) $this->isActive,
            'is_default'                 => (int) $this->isDefault,
            'source'                     => $this->source,

            'home_url'                   => $this->homeUrl,
            'domains'                    => $this->domains,

            'brand_name'                 => $this->brandName,
            'brand_logo'                 => $this->brandLogo,
            'brand_logo_dark'            => $this->brandLogoDark,
            'brand_logo_height'          => $this->brandLogoHeight,
            'favicon'                    => $this->favicon,
            'primary_color'              => $this->primaryColor,

            'sidebar_width'              => $this->sidebarWidth,
            'collapsed_sidebar_width'    => $this->collapsedSidebarWidth,
            'max_content_width'          => $this->maxContentWidth,
            'sidebar_collapsible'        => (int) $this->sidebarCollapsible,
            'sidebar_fully_collapsible'  => (int) $this->sidebarFullyCollapsible,
            'top_navigation'             => (int) $this->topNavigation,
            'has_topbar'                 => (int) $this->hasTopbar,

            'spa'                        => (int) $this->spa,
            'dark_mode'                  => (int) $this->darkMode,
            'global_search'              => (int) $this->globalSearch,
            'broadcasting'               => (int) $this->broadcasting,
            'database_notifications'     => (int) $this->databaseNotifications,
            'unsaved_changes_alerts'     => (int) $this->unsavedChangesAlerts,
            'database_transactions'      => (int) $this->databaseTransactions,

            'auth'                       => (int) $this->auth,
            'registration'               => (int) $this->registration,
            'password_reset'             => (int) $this->passwordReset,
            'email_verification'         => (int) $this->emailVerification,
            'mfa_required'               => (int) $this->mfaRequired,
            'auth_guard'                 => $this->authGuard,
            'auth_password_broker'       => $this->authPasswordBroker,

            'tenant_enabled'             => (int) $this->tenantEnabled,
            'tenant_model'               => $this->tenantModel,
            'tenant_ownership_relation'  => $this->tenantOwnershipRelation,
            'tenant_domain'              => $this->tenantDomain,
            'tenant_billing'             => (int) $this->tenantBilling,

            'resources'                  => $this->resources,
            'pages'                      => $this->pages,
            'widgets'                    => $this->widgets,
            'plugins'                    => $this->plugins,

            'discover_resources_in'      => $this->discoverResourcesIn,
            'discover_resources_for'     => $this->discoverResourcesFor,
            'discover_pages_in'          => $this->discoverPagesIn,
            'discover_pages_for'         => $this->discoverPagesFor,
            'discover_widgets_in'        => $this->discoverWidgetsIn,
            'discover_widgets_for'       => $this->discoverWidgetsFor,

            'feature_flags'              => $this->featureFlags,

            'extra_middleware'           => $this->extraMiddleware,
            'extra_auth_middleware'      => $this->extraAuthMiddleware,
        ];
    }

    // -------------------------------------------------------------------------
    // Feature flag helper
    // -------------------------------------------------------------------------

    public function isFeatureEnabled(string $flag): bool
    {
        return (bool) ($this->featureFlags[$flag] ?? false);
    }

    // -------------------------------------------------------------------------
    // Private coercion helpers
    // -------------------------------------------------------------------------

    /** @return string[] */
    private static function toStringArray(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter(array_map('strval', $value)));
    }

    /** @return array<string, bool> */
    private static function toBoolMap(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $result = [];
        foreach ($value as $k => $v) {
            $result[(string) $k] = (bool) $v;
        }

        return $result;
    }
}
