<?php
declare(strict_types=1);

namespace Webkernel\BackOffice\System\Presentation\Resources\PanelArrays\Schemas;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Webkernel\Panel\PanelArraysDataSource;

/**
 * PanelArraysForm
 *
 * The form schema for panel create/edit.
 *
 * Fields are grouped into named sections. The record is a PanelArraysDataSource
 * instance whose attributes are populated from FSEngine (dynamic panels) or from
 * live Filament introspection (static panels). Filament auto-fills field values
 * from the record — no manual ::get() calls needed.
 *
 * The only thing we do here is:
 *   - define the field shape / validation / UI hints
 *   - gate certain fields as read-only for static sources
 */
class PanelArraysForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            // ------------------------------------------------------------------
            // 1. Identity
            // ------------------------------------------------------------------
            Section::make('Identity')
                ->icon('heroicon-o-identification')
                ->columns(2)
                ->schema([
                    TextInput::make('id')
                        ->label('Panel ID')
                        ->required()
                        ->alphaDash()
                        ->maxLength(64)
                        ->placeholder('tenant')
                        ->helperText('Unique slug — Filament panel id and URL prefix. Immutable after creation.')
                        ->disabledOn('edit'),

                    TextInput::make('path')
                        ->label('URL path')
                        ->required()
                        ->maxLength(64)
                        ->placeholder('tenant'),

                    TextInput::make('name')
                        ->label('Internal name')
                        ->maxLength(128)
                        ->placeholder('Tenant Portal'),

                    TextInput::make('sort_order')
                        ->label('Sort order')
                        ->numeric()
                        ->default(0),

                    Textarea::make('description')
                        ->label('Description')
                        ->maxLength(1000)
                        ->columnSpanFull(),

                    Toggle::make('is_active')
                        ->label('Active')
                        ->default(true)
                        ->helperText('Inactive panels are not registered at boot.')
                        ->inline(false),

                    Toggle::make('is_default')
                        ->label('Default panel')
                        ->default(false)
                        ->helperText('Strips the flag from all other panels on save.')
                        ->inline(false),
                ]),

            // ------------------------------------------------------------------
            // 2. Routing
            // ------------------------------------------------------------------
            Section::make('Routing')
                ->icon('heroicon-o-globe-alt')
                ->columns(1)
                ->schema([
                    TextInput::make('home_url')
                        ->label('Home URL')
                        ->url()
                        ->placeholder('https://app.example.com'),

                    TagsInput::make('domains')
                        ->label('Domains')
                        ->placeholder('app.example.com')
                        ->helperText('One domain per tag. Leave empty for path-based routing.'),
                ]),

            // ------------------------------------------------------------------
            // 3. Brand
            // ------------------------------------------------------------------
            Section::make('Brand')
                ->icon('heroicon-o-paint-brush')
                ->columns(2)
                ->schema([
                    TextInput::make('brand_name')
                        ->label('Brand name')
                        ->maxLength(128)
                        ->placeholder('Tenant Portal'),

                    TextInput::make('brand_logo_height')
                        ->label('Logo height (CSS)')
                        ->maxLength(16)
                        ->placeholder('2rem'),

                    TextInput::make('brand_logo')
                        ->label('Logo — light mode')
                        ->maxLength(512)
                        ->placeholder('/logo.png')
                        ->columnSpanFull(),

                    TextInput::make('brand_logo_dark')
                        ->label('Logo — dark mode')
                        ->maxLength(512)
                        ->placeholder('/logo-dark.png')
                        ->columnSpanFull(),

                    TextInput::make('favicon')
                        ->label('Favicon')
                        ->maxLength(512)
                        ->placeholder('/favicon.ico')
                        ->columnSpanFull(),

                    ColorPicker::make('primary_color')
                        ->label('Primary color')
                        ->columnSpanFull(),
                ]),

            // ------------------------------------------------------------------
            // 4. Layout
            // ------------------------------------------------------------------
            Section::make('Layout')
                ->icon('heroicon-o-squares-2x2')
                ->columns(2)
                ->collapsed()
                ->schema([
                    TextInput::make('sidebar_width')
                        ->label('Sidebar width')
                        ->placeholder('20rem'),

                    TextInput::make('collapsed_sidebar_width')
                        ->label('Collapsed sidebar width')
                        ->placeholder('4.5rem'),

                    Select::make('max_content_width')
                        ->label('Max content width')
                        ->options([
                            ''     => 'Default',
                            'sm'   => 'sm',
                            'md'   => 'md',
                            'lg'   => 'lg',
                            'xl'   => 'xl',
                            '2xl'  => '2xl',
                            '3xl'  => '3xl',
                            '4xl'  => '4xl',
                            '5xl'  => '5xl',
                            '6xl'  => '6xl',
                            '7xl'  => '7xl',
                            'full' => 'full',
                        ]),

                    Toggle::make('sidebar_collapsible')
                        ->label('Sidebar collapsible on desktop')
                        ->inline(false),

                    Toggle::make('sidebar_fully_collapsible')
                        ->label('Sidebar fully collapsible on desktop')
                        ->inline(false),

                    Toggle::make('top_navigation')
                        ->label('Top navigation')
                        ->inline(false),

                    Toggle::make('has_topbar')
                        ->label('Show topbar')
                        ->default(true)
                        ->inline(false),
                ]),

            // ------------------------------------------------------------------
            // 5. Features
            // ------------------------------------------------------------------
            Section::make('Features')
                ->icon('heroicon-o-bolt')
                ->columns(2)
                ->schema([
                    Toggle::make('spa')
                        ->label('SPA mode')
                        ->inline(false),

                    Toggle::make('dark_mode')
                        ->label('Dark mode')
                        ->default(true)
                        ->inline(false),

                    Toggle::make('global_search')
                        ->label('Global search')
                        ->default(true)
                        ->inline(false),

                    Toggle::make('broadcasting')
                        ->label('Broadcasting')
                        ->inline(false),

                    Toggle::make('database_notifications')
                        ->label('Database notifications')
                        ->inline(false),

                    Toggle::make('unsaved_changes_alerts')
                        ->label('Unsaved changes alerts')
                        ->inline(false),

                    Toggle::make('database_transactions')
                        ->label('Database transactions')
                        ->inline(false),
                ]),

            // ------------------------------------------------------------------
            // 6. Authentication
            // ------------------------------------------------------------------
            Section::make('Authentication')
                ->icon('heroicon-o-lock-closed')
                ->columns(2)
                ->schema([
                    Toggle::make('auth')
                        ->label('Require authentication')
                        ->default(true)
                        ->inline(false),

                    Toggle::make('registration')
                        ->label('Allow registration')
                        ->inline(false),

                    Toggle::make('password_reset')
                        ->label('Password reset')
                        ->inline(false),

                    Toggle::make('email_verification')
                        ->label('Email verification')
                        ->inline(false),

                    Toggle::make('mfa_required')
                        ->label('MFA required')
                        ->inline(false),

                    TextInput::make('auth_guard')
                        ->label('Auth guard')
                        ->placeholder('web'),

                    TextInput::make('auth_password_broker')
                        ->label('Password broker')
                        ->placeholder('users'),
                ]),

            // ------------------------------------------------------------------
            // 7. Multi-tenancy
            // ------------------------------------------------------------------
            Section::make('Multi-tenancy')
                ->icon('heroicon-o-building-office')
                ->columns(2)
                ->collapsed()
                ->schema([
                    Toggle::make('tenant_enabled')
                        ->label('Enable multi-tenancy')
                        ->inline(false)
                        ->columnSpanFull(),

                    TextInput::make('tenant_model')
                        ->label('Tenant model class')
                        ->placeholder('App\\Models\\Team'),

                    TextInput::make('tenant_ownership_relation')
                        ->label('Ownership relation name')
                        ->placeholder('teams'),

                    TextInput::make('tenant_domain')
                        ->label('Tenant domain pattern')
                        ->placeholder('{tenant}.example.com'),

                    Toggle::make('tenant_billing')
                        ->label('Billing enabled')
                        ->inline(false),
                ]),

            // ------------------------------------------------------------------
            // 8. Components
            // ------------------------------------------------------------------
            Section::make('Components')
                ->icon('heroicon-o-puzzle-piece')
                ->collapsed()
                ->schema([
                    TagsInput::make('resources')
                        ->label('Resource classes')
                        ->placeholder('App\\Filament\\Resources\\UserResource')
                        ->helperText('Fully-qualified class names. Verified via class_exists() at runtime.'),

                    TagsInput::make('pages')
                        ->label('Page classes')
                        ->placeholder('App\\Filament\\Pages\\Dashboard'),

                    TagsInput::make('widgets')
                        ->label('Widget classes')
                        ->placeholder('App\\Filament\\Widgets\\StatsOverview'),

                    TagsInput::make('plugins')
                        ->label('Plugin classes')
                        ->placeholder('FilamentSpatie\\Tags\\TagsPlugin'),
                ]),

            // ------------------------------------------------------------------
            // 9. Auto-discovery
            // ------------------------------------------------------------------
            Section::make('Auto-discovery')
                ->icon('heroicon-o-magnifying-glass')
                ->columns(2)
                ->collapsed()
                ->schema([
                    TextInput::make('discover_resources_in')
                        ->label('Resources directory')
                        ->placeholder('app/Filament/Resources'),

                    TextInput::make('discover_resources_for')
                        ->label('Resources namespace')
                        ->placeholder('App\\Filament\\Resources'),

                    TextInput::make('discover_pages_in')
                        ->label('Pages directory')
                        ->placeholder('app/Filament/Pages'),

                    TextInput::make('discover_pages_for')
                        ->label('Pages namespace')
                        ->placeholder('App\\Filament\\Pages'),

                    TextInput::make('discover_widgets_in')
                        ->label('Widgets directory')
                        ->placeholder('app/Filament/Widgets'),

                    TextInput::make('discover_widgets_for')
                        ->label('Widgets namespace')
                        ->placeholder('App\\Filament\\Widgets'),
                ]),

            // ------------------------------------------------------------------
            // 10. Feature flags
            // ------------------------------------------------------------------
            Section::make('Feature flags')
                ->icon('heroicon-o-flag')
                ->collapsed()
                ->schema([
                    KeyValue::make('feature_flags')
                        ->label('')
                        ->keyLabel('Flag')
                        ->valueLabel('Enabled (true / false)')
                        ->addActionLabel('Add flag')
                        ->helperText('Free-form flags. Read at runtime with $dto->isFeatureEnabled(\'billing\').'),
                ]),

            // ------------------------------------------------------------------
            // 11. Extra middleware
            // ------------------------------------------------------------------
            Section::make('Extra middleware')
                ->icon('heroicon-o-shield-check')
                ->collapsed()
                ->schema([
                    TagsInput::make('extra_middleware')
                        ->label('Web middleware classes')
                        ->placeholder('App\\Http\\Middleware\\MyMiddleware'),

                    TagsInput::make('extra_auth_middleware')
                        ->label('Auth middleware classes')
                        ->placeholder('App\\Http\\Middleware\\EnsureSubscription'),
                ]),
        ]);
    }
}
