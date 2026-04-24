<?php declare(strict_types=1);

namespace Webkernel\BackOffice\System\Presentation\Pages;

use Filament\Pages\Page;

class ProcessUpgrade extends Page
{
    protected string $view = 'webkernel-system::filament.pages.process-upgrade';

    protected static bool $shouldRegisterNavigation = false;

    public string $operationTitle    = 'System Update';
    public string $secondaryLogo     = '';
    public string $status            = '';
    public string $error             = '';
    public int    $progressPercent   = 0;

    public function mount(): void
    {
        $this->status         = session('upgrade.status', '');
        $this->error          = session('upgrade.error', '');
        $this->progressPercent = session('upgrade.progress', 0);
        $this->operationTitle = session('upgrade.title', 'System Update');
        $this->secondaryLogo  = session('upgrade.secondaryLogo', '');
    }
}
