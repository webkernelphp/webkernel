<?php declare(strict_types=1);

namespace Webkernel\Platform\SystemPanel\Presentation\Installer\Concerns;

use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

/**
 * Form definitions for the installer.
 *
 * The main form (phase = 'setup') is a Filament Wizard with four steps:
 *   1. Identity  — who is performing this setup?
 *   2. Account   — name / email / password (the person at the keyboard)
 *   3. Mailer    — SMTP configuration (optional, skip by leaving blank)
 *   4. Business  — first workspace (optional, skip by leaving blank)
 *
 * verify_token phase reuses a simple one-field form.
 */
trait HasInstallerForms
{
    public function form(Schema $schema): Schema
    {
        return match ($this->phase) {
            'verify_token' => $this->tokenForm($schema),
            'setup'        => $this->wizardForm($schema),
            default        => $schema->components([]),
        };
    }

    // ── Token form ────────────────────────────────────────────────────────────

    private function tokenForm(Schema $schema): Schema
    {
        return $schema
            ->statePath('setupTokenInput')
            ->components([
                TextInput::make('setupTokenInput')
                    ->label('Setup Token')
                    ->password()
                    ->revealable()
                    ->required()
                    ->placeholder('One-time token from your environment'),
            ]);
    }

    // ── Wizard form ───────────────────────────────────────────────────────────

    private function wizardForm(Schema $schema): Schema
    {
        return $schema
            ->statePath('wizardData')
            ->components([
                Wizard::make([

                    // ── Step 1: Identity ──────────────────────────────────────
                    Step::make('Who are you?')
                        ->icon('heroicon-o-identification')
                        ->description('Your relationship to this instance')
                        ->schema([
                            Radio::make('deployer_role')
                                ->label('')
                                ->options([
                                    'owner'    => 'I am the App Owner',
                                    'sysadmin' => 'I am deploying this for someone else',
                                ])
                                ->descriptions([
                                    'owner'    => 'You will create your own account and own this instance.',
                                    'sysadmin' => 'You are a sysadmin or deployer. You still create the first account — the actual owner can be assigned from the System panel after setup.',
                                ])
                                ->default('owner')
                                ->required()
                                ->live()
                                ->extraAttributes(['class' => 'wds-claim-radio']),
                        ]),

                    // ── Step 2: Account ───────────────────────────────────────
                    Step::make('Your Account')
                        ->icon('heroicon-o-user-circle')
                        ->description('The first account on this instance')
                        ->schema([
                            Grid::make(2)->schema([
                                TextInput::make('name')
                                    ->label('Full name')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Your Name'),
                                TextInput::make('email')
                                    ->label('Email')
                                    ->email()
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(table: 'users', column: 'email')
                                    ->placeholder('you@example.com'),
                                TextInput::make('password')
                                    ->label('Password')
                                    ->password()
                                    ->revealable()
                                    ->required()
                                    ->minLength(12)
                                    ->maxLength(255)
                                    ->columnSpanFull(),
                            ]),
                        ]),

                    // ── Step 3: Mailer ────────────────────────────────────────
                    Step::make('Mailer')
                        ->icon('heroicon-o-envelope')
                        ->description('Optional — leave blank to skip')
                        ->schema([
                            Grid::make(2)->schema([
                                TextInput::make('smtp_host')
                                    ->label('SMTP Host')
                                    ->placeholder('smtp.example.com'),
                                TextInput::make('smtp_port')
                                    ->label('Port')
                                    ->default('587'),
                                TextInput::make('smtp_username')
                                    ->label('Username'),
                                TextInput::make('smtp_password')
                                    ->label('Password')
                                    ->password()
                                    ->revealable(),
                                Select::make('smtp_encryption')
                                    ->label('Encryption')
                                    ->options(['tls' => 'TLS', 'ssl' => 'SSL', 'none' => 'None'])
                                    ->default('tls')
                                    ->native(false),
                                TextInput::make('smtp_from_email')
                                    ->label('From Email')
                                    ->email(),
                                TextInput::make('smtp_from_name')
                                    ->label('From Name')
                                    ->columnSpanFull(),
                            ]),
                        ]),

                    // ── Step 4: Business ──────────────────────────────────────
                    Step::make('Business')
                        ->icon('heroicon-o-building-office')
                        ->description('Optional — leave blank to skip')
                        ->schema([
                            Grid::make(2)->schema([
                                TextInput::make('biz_name')
                                    ->label('Business Name')
                                    ->maxLength(255)
                                    ->placeholder('Acme Corp')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, callable $set): void {
                                        if (! empty($state)) {
                                            $set('biz_slug', strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $state), '-')));
                                        }
                                    }),
                                TextInput::make('biz_slug')
                                    ->label('Slug')
                                    ->maxLength(63)
                                    ->placeholder('acme-corp')
                                    ->helperText('Lowercase, hyphens only.'),
                                TextInput::make('biz_admin_email')
                                    ->label('Business-Admin Email')
                                    ->email()
                                    ->placeholder('admin@example.com')
                                    ->helperText('Invite sent by email once mailer is configured.')
                                    ->columnSpanFull(),
                            ]),
                        ]),

                ])
                ->submitAction(new HtmlString(
                    '<button type="button" wire:click="runCompleteSetup" wire:loading.attr="disabled"'
                    . ' class="fi-btn fi-btn-size-md fi-color-success fi-btn-color-success">'
                    . '<span wire:loading.remove wire:target="runCompleteSetup">Complete setup</span>'
                    . '<span wire:loading wire:target="runCompleteSetup">Setting up…</span>'
                    . '</button>'
                )),
            ]);
    }
}
