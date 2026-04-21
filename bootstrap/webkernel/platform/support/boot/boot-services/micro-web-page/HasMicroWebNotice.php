<?php declare(strict_types=1);
// ═══════════════════════════════════════════════════════════════════
//  Trait: HasMicroWebNotice
//  Informational bands rendered above the main card body.
//  Levels: 'info' | 'warning' | 'error'
// ═══════════════════════════════════════════════════════════════════

trait HasMicroWebNotice
{
    /** @var list<array{level:string, heading:string, body:string}> */
    private array $notices = [];

    /**
     * Register a notice band.
     *
     * @param 'info'|'warning'|'error' $level
     * @param string $heading  Short bold label (e.g. "Database unreachable")
     * @param string $body     Full explanation; safe HTML allowed.
     */
    public function notice(string $level, string $heading, string $body): static
    {
        $this->notices[] = [
            'level' => $level,
            'heading' => $heading,
            'body' => $body,
        ];
        return $this;
    }

    protected function buildNoticesHtml(): string
    {
        if ($this->notices === []) return '';

        $html = '';
        foreach ($this->notices as $n) {
            $color = match ($n['level']) {
                'warning' => '#f59e0b',
                'error'   => '#ef4444',
                default   => '#3b82f6',
            };
            $bg = match ($n['level']) {
                'warning' => 'rgba(245,158,11,.08)',
                'error'   => 'rgba(239,68,68,.08)',
                default   => 'rgba(59,130,246,.08)',
            };
            $eH = htmlspecialchars($n['heading'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $html .= sprintf(
                '<div class="wk-notice" style="background:%s;border-left:2px solid %s;">'
                . '<span style="color:%s;font-weight:700;text-transform:uppercase;letter-spacing:.07em;font-size:.65rem;">%s</span>'
                . '<span style="display:block;margin-top:.2rem;font-size:.72rem;">%s</span>'
                . '</div>',
                $bg,
                $color,
                $color,
                $eH,
                $n['body']            );
        }
        return $html;
    }

}
