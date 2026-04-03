<?php declare(strict_types=1);

namespace Webkernel\Aptitudes\System\Presentation\Widgets;

use Filament\Widgets\Widget;

/**
 * SystemPulseWidget
 *
 * Thin Livewire widget. All metric collection and formatting logic
 * lives in HasSystemMetrics. This class is a pure wiring layer.
 *
 * Data hierarchy passed to the view:
 *   app.memory    primary — PHP process memory vs limit
 *   app.opcache   primary — OPcache status
 *   app.limits    primary — PHP ini limits row
 *   system.*      context — CPU, RAM, disk, FPM, swap
 *   host.*        scalars — uptime, processes, entropy, OS
 *   php.*         scalars — version, SAPI, ini file
 *   laravel.*     scalars — env, debug, drivers
 *   server.*      scalars — server software, address, port
 *   meta.*        flags   — degraded, reader_mode
 */
class SystemPulseWidget extends Widget
{
    protected string           $view       = 'webkernel-system::filament.widgets.system-pulse';
    protected string|array|int $columnSpan = 'full';

    /** Wire:poll interval in milliseconds. */
    public int $pollMs = 5000;
}
