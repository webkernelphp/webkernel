<?php declare(strict_types=1);
namespace Webkernel\BackOffice\System\Presentation\Resources\DatabaseConnections\Schemas;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Webkernel\Panel\DatabaseConnectionsDataSource;

/**
 * DatabaseConnectionForm
 *
 * Form schema for database connection create/edit.
 *
 * Filament auto-fills all fields from the DatabaseConnectionsDataSource record.
 * We only declare field shape, validation, and UI hints.
 * Gate helpers (locked/env-backed) read directly from the record attributes.
 */
class DatabaseConnectionForm
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
                    TextInput::make('name')
                        ->label('Connection name')
                        ->required()
                        ->alphaDash()
                        ->maxLength(64)
                        ->placeholder('tenant_db')
                        ->helperText('Must match the key in config/database.php. Immutable after creation.')
                        ->disabledOn('edit')
                        ->disabled(fn (?DatabaseConnectionsDataSource $record): bool =>
                            $record !== null && in_array('name', (array) $record->locked_fields, true)
                        ),

                    TextInput::make('label')
                        ->label('Display label')
                        ->maxLength(128)
                        ->placeholder('Tenant Database'),

                    Select::make('driver')
                        ->label('Driver')
                        ->required()
                        ->options([
                            'mysql'   => 'MySQL / MariaDB',
                            'pgsql'   => 'PostgreSQL',
                            'sqlite'  => 'SQLite',
                            'sqlsrv'  => 'SQL Server',
                            'mongodb' => 'MongoDB',
                        ])
                        ->disabled(fn (?DatabaseConnectionsDataSource $record): bool =>
                            $record !== null && in_array('driver', (array) $record->locked_fields, true)
                        ),

                    Toggle::make('is_active')
                        ->label('Active')
                        ->default(true)
                        ->inline(false),
                ]),

            // ------------------------------------------------------------------
            // 2. Connection
            // ------------------------------------------------------------------
            Section::make('Connection')
                ->icon('heroicon-o-server')
                ->columns(2)
                ->schema([
                    TextInput::make('host')
                        ->label('Host')
                        ->maxLength(255)
                        ->placeholder('127.0.0.1')
                        ->helperText(fn (?DatabaseConnectionsDataSource $record): ?string =>
                            isset(((array) $record?->env_map)['host'])
                                ? 'Currently read from env: ' . ((array) $record->env_map)['host']
                                : null
                        )
                        ->disabled(fn (?DatabaseConnectionsDataSource $record): bool =>
                            $record !== null && in_array('host', (array) $record->locked_fields, true)
                        ),

                    TextInput::make('port')
                        ->label('Port')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(65535)
                        ->placeholder('3306')
                        ->helperText(fn (?DatabaseConnectionsDataSource $record): ?string =>
                            isset(((array) $record?->env_map)['port'])
                                ? 'Currently read from env: ' . ((array) $record->env_map)['port']
                                : null
                        )
                        ->disabled(fn (?DatabaseConnectionsDataSource $record): bool =>
                            $record !== null && in_array('port', (array) $record->locked_fields, true)
                        ),

                    TextInput::make('database')
                        ->label('Database name')
                        ->maxLength(255)
                        ->placeholder('app')
                        ->columnSpanFull()
                        ->helperText(fn (?DatabaseConnectionsDataSource $record): ?string =>
                            isset(((array) $record?->env_map)['database'])
                                ? 'Currently read from env: ' . ((array) $record->env_map)['database']
                                : null
                        )
                        ->disabled(fn (?DatabaseConnectionsDataSource $record): bool =>
                            $record !== null && in_array('database', (array) $record->locked_fields, true)
                        ),

                    TextInput::make('username')
                        ->label('Username')
                        ->maxLength(255)
                        ->placeholder('root')
                        ->helperText(fn (?DatabaseConnectionsDataSource $record): ?string =>
                            isset(((array) $record?->env_map)['username'])
                                ? 'Currently read from env: ' . ((array) $record->env_map)['username']
                                : null
                        )
                        ->disabled(fn (?DatabaseConnectionsDataSource $record): bool =>
                            $record !== null && in_array('username', (array) $record->locked_fields, true)
                        ),

                    // Password is write-only. When env-backed, submitting a value writes to .env.
                    TextInput::make('password')
                        ->label('Password')
                        ->password()
                        ->revealable()
                        ->maxLength(255)
                        ->placeholder(fn (?DatabaseConnectionsDataSource $record): string =>
                            isset(((array) $record?->env_map)['password'])
                                ? 'Stored in env ' . ((array) $record->env_map)['password'] . ' — enter to change'
                                : 'Enter password'
                        )
                        ->helperText(fn (?DatabaseConnectionsDataSource $record): string =>
                            isset(((array) $record?->env_map)['password'])
                                ? 'Leave blank to keep the current env value.'
                                : 'Stored encrypted. Leave blank to keep the current value.'
                        )
                        ->dehydrateStateUsing(fn (?string $state): ?string => $state ?: null)
                        ->disabled(fn (?DatabaseConnectionsDataSource $record): bool =>
                            $record !== null && in_array('password', (array) $record->locked_fields, true)
                        ),

                    TextInput::make('unix_socket')
                        ->label('Unix socket')
                        ->maxLength(512)
                        ->placeholder('/var/run/mysqld/mysqld.sock')
                        ->helperText('When set, host and port are ignored.')
                        ->columnSpanFull(),
                ]),

            // ------------------------------------------------------------------
            // 3. Encoding
            // ------------------------------------------------------------------
            Section::make('Encoding')
                ->icon('heroicon-o-language')
                ->columns(2)
                ->collapsed()
                ->schema([
                    TextInput::make('charset')
                        ->label('Charset')
                        ->placeholder('utf8mb4')
                        ->maxLength(32),

                    TextInput::make('collation')
                        ->label('Collation')
                        ->placeholder('utf8mb4_unicode_ci')
                        ->maxLength(64),

                    TextInput::make('prefix')
                        ->label('Table prefix')
                        ->maxLength(32),

                    TextInput::make('schema')
                        ->label('Schema (PostgreSQL)')
                        ->placeholder('public')
                        ->maxLength(64),

                    Select::make('ssl_mode')
                        ->label('SSL mode')
                        ->options([
                            ''        => 'Default',
                            'prefer'  => 'prefer',
                            'require' => 'require',
                            'disable' => 'disable',
                        ]),
                ]),

            // ------------------------------------------------------------------
            // 4. Locked fields (read-only info panel)
            // ------------------------------------------------------------------
            Section::make('Read-only fields')
                ->icon('heroicon-o-lock-closed')
                ->collapsed()
                ->visible(fn (?DatabaseConnectionsDataSource $record): bool =>
                    $record !== null && ! empty((array) $record->locked_fields)
                )
                ->schema([
                    Placeholder::make('locked_info')
                        ->label('')
                        ->content(fn (?DatabaseConnectionsDataSource $record): string =>
                            'The following fields cannot be changed through the UI: '
                            . implode(', ', (array) ($record?->locked_fields ?? []))
                            . '. To change them, edit the source config file directly.'
                        ),
                ]),

            // ------------------------------------------------------------------
            // 5. Env-variable mapping (read-only info panel)
            // ------------------------------------------------------------------
            Section::make('Environment variable mapping')
                ->icon('heroicon-o-variable')
                ->collapsed()
                ->visible(fn (?DatabaseConnectionsDataSource $record): bool =>
                    $record !== null && ! empty((array) $record->env_map)
                )
                ->schema([
                    Placeholder::make('env_map_info')
                        ->label('')
                        ->content(fn (?DatabaseConnectionsDataSource $record): string =>
                            collect((array) ($record?->env_map ?? []))
                                ->map(fn ($envKey, $field) => "{$field} → {$envKey}")
                                ->implode("\n")
                        ),
                ]),

            // ------------------------------------------------------------------
            // 6. Extra driver options
            // ------------------------------------------------------------------
            Section::make('Driver options')
                ->icon('heroicon-o-cog-6-tooth')
                ->collapsed()
                ->schema([
                    KeyValue::make('options')
                        ->label('')
                        ->keyLabel('Option')
                        ->valueLabel('Value')
                        ->addActionLabel('Add option')
                        ->helperText('Arbitrary driver-specific options (e.g. PDO::ATTR_EMULATE_PREPARES).'),
                ]),
        ]);
    }
}
