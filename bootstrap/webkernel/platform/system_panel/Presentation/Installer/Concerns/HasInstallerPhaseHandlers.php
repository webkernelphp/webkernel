<?php declare(strict_types=1);

namespace Webkernel\Platform\SystemPanel\Presentation\Installer\Concerns;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Filament\Notifications\Notification;
use Webkernel\Businesses\Models\Business;
use Webkernel\Connectors\Mailer;
use Webkernel\Users\Models\User;

/**
 * Livewire action handlers for installer phase transitions.
 */
trait HasInstallerPhaseHandlers
{
    // ── pre → installing → setup (or verify_token) ───────────────────────────

    public function runInstall(): void
    {
        if ($this->phase !== 'pre') {
            return;
        }

        if (! collect($this->buildRequirements())->every(fn ($r) => $r['ok'])) {
            Notification::make()->title('Requirements not met')->danger()->send();
            return;
        }

        $this->phase = 'installing';

        try {
            Artisan::call('webkernel:install');
            $this->artisanOutput = Artisan::output();
            $this->phase         = $this->resolvePostInstallPhase();

            if ($this->phase === 'verify_token') {
                $this->setupTokenInput = $this->resolveSetupToken();
            }

            Notification::make()->title('Infrastructure ready')->success()->send();
        } catch (\Throwable $e) {
            $this->phase        = 'error';
            $this->errorMessage = $e->getMessage();
            Notification::make()->title('Installation failed')->danger()->send();
        }
    }

    // ── verify_token → setup ─────────────────────────────────────────────────

    public function runValidateToken(): void
    {
        if ($this->phase !== 'verify_token') {
            return;
        }

        $expected = $this->resolveSetupToken();

        if (! hash_equals($expected, (string) $this->setupTokenInput)) {
            Notification::make()->title('Invalid Setup Token')->danger()->send();
            return;
        }

        $tokenFile = storage_path('webkernel/.setup_token');
        if (file_exists($tokenFile)) {
            @unlink($tokenFile);
        }

        $this->phase = 'setup';
    }

    // ── Wizard final submit: setup → done ────────────────────────────────────

    public function runCompleteSetup(): void
    {
        if ($this->phase !== 'setup') {
            return;
        }

        $d = $this->wizardData;

        try {
            // ── Account (required) ────────────────────────────────────────────
            /** @var User $user */
            $user = User::create([
                'name'     => $d['name'],
                'email'    => $d['email'],
                'password' => Hash::make($d['password']),
            ]);

            $user->bootstrapAsAppOwner();

            // ── Mailer (optional — only if host is provided) ──────────────────
            if (! empty(trim((string) $d['smtp_host']))) {
                try {
                    Mailer::configure([
                        'host'       => $d['smtp_host'],
                        'port'       => $d['smtp_port'],
                        'username'   => $d['smtp_username'],
                        'password'   => $d['smtp_password'],
                        'encryption' => $d['smtp_encryption'],
                        'from_name'  => $d['smtp_from_name'],
                        'from_email' => $d['smtp_from_email'],
                    ]);
                } catch (\Throwable $e) {
                    // Mailer error is non-fatal — log and continue.
                    Notification::make()
                        ->title('Mailer not saved')
                        ->body($e->getMessage())
                        ->warning()
                        ->send();
                }
            }

            // ── Business (optional — only if name is provided) ────────────────
            if (! empty(trim((string) $d['biz_name']))) {
                try {
                    $slug = ! empty(trim((string) $d['biz_slug']))
                        ? $d['biz_slug']
                        : Str::slug($d['biz_name']);

                    $business = Business::create([
                        'name'        => $d['biz_name'],
                        'slug'        => $slug,
                        'admin_email' => $d['biz_admin_email'],
                        'created_by'  => $user->getKey(),
                    ]);

                    if (Mailer::isConfigured() && ! empty($d['biz_admin_email'])) {
                        Mailer::sendHtml(
                            to:      $d['biz_admin_email'],
                            subject: 'You have been invited to manage ' . $business->name,
                            html:    $this->buildBusinessInviteHtml($business->name),
                        );
                    }
                } catch (\Throwable $e) {
                    Notification::make()
                        ->title('Business not created')
                        ->body($e->getMessage())
                        ->warning()
                        ->send();
                }
            }

            $this->phase = 'done';

            Notification::make()
                ->title(sprintf('Welcome, %s — setup complete', $user->name))
                ->success()
                ->persistent()
                ->send();

        } catch (\Throwable $e) {
            Notification::make()
                ->title('Setup failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    // ── error → pre ──────────────────────────────────────────────────────────

    public function resetToPreFlight(): void
    {
        $this->phase         = 'pre';
        $this->errorMessage  = '';
        $this->artisanOutput = '';
    }

    // ── Private ──────────────────────────────────────────────────────────────

    private function buildBusinessInviteHtml(string $businessName): string
    {
        return sprintf(
            '<p>You have been invited to manage <strong>%s</strong> on this Webkernel instance.</p>'
            . '<p>Create your account at: <a href="%s">%s</a></p>',
            htmlspecialchars($businessName, ENT_QUOTES, 'UTF-8'),
            url('/'),
            url('/'),
        );
    }
}
