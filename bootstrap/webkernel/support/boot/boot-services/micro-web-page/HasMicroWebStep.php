<?php declare(strict_types=1);
// ═══════════════════════════════════════════════════════════════════
//  Trait: HasMicroWebStep
//  Declarative setup / check steps with OK / FAIL / PENDING states.
// ═══════════════════════════════════════════════════════════════════

trait HasMicroWebStep
{
    /** @var list<array{label:string, closure:\Closure|null, pending:bool}> */
    private array $steps = [];

    /** @var array{label:string, href:string, extraCss:string}|null */
    private ?array $submitStep = null;

    /**
     * Add an executable step.
     * The closure must return true (pass) or a string error message (fail).
     *
     * @param \Closure():(bool|string)|null $closure  null = display-only pending step
     * @param bool                          $pending  Force pending display regardless of closure.
     */
    public function step(string $label, ?\Closure $closure = null, bool $pending = false): static
    {
        $this->steps[] = ['label' => $label, 'closure' => $closure, 'pending' => $pending];
        return $this;
    }

    /**
     * Add a pending (display-only) step — shown greyed-out on all pages.
     */
    public function pendingStep(string $label): static
    {
        return $this->step($label, null, true);
    }

    /**
     * Add a submit / proceed button shown below the steps.
     * Only renders when all steps pass.
     */
    public function submitStep(
        string $label,
        string $href     = '/',
        string $extraCss = '',
    ): static {
        $this->submitStep = ['label' => $label, 'href' => $href, 'extraCss' => $extraCss];
        return $this;
    }

    // ── Guard helpers ─────────────────────────────────────────────────────

    /** @var list<\Closure():bool> */
    private array $guards = [];

    public function guard(\Closure $check): static
    {
        $this->guards[] = $check;
        return $this;
    }

    public function renderIfGuardFails(): static
    {
        foreach ($this->guards as $guard) {
            if (!$guard()) {
                $this->render();
            }
        }
        return $this;
    }

    // ── Rendering ─────────────────────────────────────────────────────────

    protected function buildStepsHtml(string $accent): string
    {
        if ($this->steps === []) return '';

        $html      = '<div class="wk-steps">';
        $allPassed = true;

        foreach ($this->steps as $step) {
            $eLabel = htmlspecialchars($step['label'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

            if ($step['pending'] || $step['closure'] === null) {
                $html .= self::stepRow($eLabel, 'pending', '', $accent);
                continue;
            }

            try   { $result = ($step['closure'])(); }
            catch (\Throwable $ex) { $result = $ex->getMessage(); }

            if ($result === true) {
                $html .= self::stepRow($eLabel, 'ok', '', $accent);
            } else {
                $allPassed = false;
                $eDetail   = htmlspecialchars(is_string($result) ? $result : '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $html     .= self::stepRow($eLabel, 'fail', $eDetail, $accent);
            }
        }

        if ($this->submitStep !== null) {
            if ($allPassed) {
                $html .= sprintf(
                    '<div class="wk-submit-row">'
                    . '<a href="%s" class="wk-proceed-btn" style="%s">%s</a>'
                    . '</div>',
                    htmlspecialchars($this->submitStep['href'],     ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                    htmlspecialchars($this->submitStep['extraCss'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                    htmlspecialchars($this->submitStep['label'],    ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                );
            } else {
                $html .= '<div class="wk-submit-row wk-submit-blocked">'
                       . 'Setup incomplete — correct the errors above before continuing.'
                       . '</div>';
            }
        }

        return $html . '</div>';
    }

    private static function stepRow(string $eLabel, string $status, string $eDetail, string $accent): string
    {
        $icon   = match ($status) { 'ok' => '✓', 'fail' => '✕', default => '⋯' };
        $color  = match ($status) { 'ok' => $accent, 'fail' => '#ef4444', default => 'var(--wk-muted)' };
        $detail = $eDetail !== '' ? '<span class="wk-step-detail">' . $eDetail . '</span>' : '';

        return sprintf(
            '<div class="wk-step wk-step-%s">'
            . '<span class="wk-step-icon" style="color:%s">%s</span>'
            . '<span class="wk-step-label">%s%s</span>'
            . '</div>',
            htmlspecialchars($status, ENT_QUOTES, 'UTF-8'),
            $color, $icon, $eLabel, $detail
        );
    }
}
