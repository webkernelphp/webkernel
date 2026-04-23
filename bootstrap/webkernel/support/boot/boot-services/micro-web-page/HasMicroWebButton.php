<?php declare(strict_types=1);
// ═══════════════════════════════════════════════════════════════════
//  Trait: HasMicroWebButton
//  Action buttons rendered in the card body footer.
//  Sizes: 'sm' | 'md' | 'lg' | 'xl'
//  Styles: 'primary' | 'secondary' | 'danger' | 'ghost'
// ═══════════════════════════════════════════════════════════════════

trait HasMicroWebButton
{
    /** @var list<array{text:string, href:string, size:string, style:string, extraCss:string, target:string}> */
    private array $buttons = [];

    /**
     * Add an action button.
     *
     * @param string $text     Label shown on the button.
     * @param string $href     Destination URL.
     * @param string $size     'sm' | 'md' (default) | 'lg' | 'xl'
     * @param string $style    'primary' (default) | 'secondary' | 'danger' | 'ghost'
     * @param string $target   HTML target attribute, e.g. '_blank'.
     * @param string $extraCss Additional inline CSS appended to the element.
     */
    public function addButton(
        string $text,
        string $href     = '/',
        string $size     = 'md',
        string $style    = 'primary',
        string $target   = '',
        string $extraCss = '',
    ): static {
        $this->buttons[] = compact('text', 'href', 'size', 'style', 'target', 'extraCss');
        return $this;
    }

    // Convenience aliases ─────────────────────────────────────────────────

    public function primaryButton(string $text, string $href = '/', string $size = 'md'): static
    {
        return $this->addButton($text, $href, $size, 'primary');
    }

    public function secondaryButton(string $text, string $href = '/', string $size = 'md'): static
    {
        return $this->addButton($text, $href, $size, 'secondary');
    }

    public function dangerButton(string $text, string $href = '/', string $size = 'md'): static
    {
        return $this->addButton($text, $href, $size, 'danger');
    }

    public function ghostButton(string $text, string $href = '/', string $size = 'md'): static
    {
        return $this->addButton($text, $href, $size, 'ghost');
    }

    // ── Rendering ────────────────────────────────────────────────────────

    protected function buildButtonsHtml(string $accent): string
    {
        if ($this->buttons === []) return '';

        $html = '<div class="wk-actions">';
        foreach ($this->buttons as $btn) {
            $html .= self::renderButton($btn, $accent);
        }
        return $html . '</div>';
    }

    private static function renderButton(array $btn, string $accent): string
    {
        $padding = match ($btn['size']) {
            'sm' => '.3rem .8rem',
            'lg' => '.65rem 1.8rem',
            'xl' => '.8rem 2.2rem',
            default => '.5rem 1.2rem',
        };
        $fontSize = match ($btn['size']) {
            'sm' => '.65rem',
            'lg' => '.78rem',
            'xl' => '.85rem',
            default => '.72rem',
        };
        [$borderColor, $textColor] = match ($btn['style']) {
            'secondary' => ['var(--wk-border)',  'var(--wk-muted)'],
            'danger'    => ['#ef4444',            '#ef4444'],
            'ghost'     => ['transparent',        'var(--wk-muted)'],
            default     => [$accent,              $accent],           // primary
        };

        $target = $btn['target'] !== '' ? ' target="' . htmlspecialchars($btn['target'], ENT_QUOTES, 'UTF-8') . '"' : '';

        return sprintf(
            '<a href="%s"%s class="wk-btn wk-btn-%s" style="padding:%s;font-size:%s;'
            . 'border-color:%s;color:%s;%s">%s</a>',
            htmlspecialchars($btn['href'],     ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            $target,
            htmlspecialchars($btn['style'],    ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            $padding, $fontSize,
            $borderColor, $textColor,
            htmlspecialchars($btn['extraCss'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            htmlspecialchars($btn['text'],     ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
        );
    }

    // ── Gated submit button (static, inline forms) ────────────────────────

    /**
     * Render a submit button that is disabled when any check fails.
     *
     * @param array<callable():bool> $checks
     */
    public static function gatedSubmitButton(
        string $label,
        array  $checks,
        string $accent   = '#3b82f6',
        string $name     = 'submit',
        string $value    = '1',
        string $size     = 'md',
        string $extraCss = '',
    ): string {
        $padding  = match ($size) { 'sm' => '.3rem .8rem', 'lg' => '.65rem 1.8rem', 'xl' => '.8rem 2.2rem', default => '.55rem 1.3rem' };
        $fontSize = match ($size) { 'sm' => '.65rem', 'lg' => '.78rem', 'xl' => '.85rem', default => '.72rem' };

        foreach ($checks as $check) {
            if (!$check()) {
                return sprintf(
                    '<button type="button" disabled'
                    . ' style="padding:%s;background:transparent;border:1px solid var(--wk-border);'
                    . 'color:var(--wk-muted);font-family:inherit;font-size:%s;font-weight:600;'
                    . 'text-transform:uppercase;letter-spacing:.08em;cursor:not-allowed;%s"'
                    . ' title="Action not permitted at this time">%s — NOT PERMITTED</button>',
                    $padding, $fontSize,
                    htmlspecialchars($extraCss, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
                    htmlspecialchars($label,    ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                );
            }
        }

        return sprintf(
            '<button type="submit" name="%s" value="%s"'
            . ' style="padding:%s;background:transparent;border:1px solid %s;color:%s;'
            . 'font-family:inherit;font-size:%s;font-weight:600;text-transform:uppercase;'
            . 'letter-spacing:.08em;cursor:pointer;%s">%s</button>',
            htmlspecialchars($name,     ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            htmlspecialchars($value,    ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            $padding,
            htmlspecialchars($accent,   ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            htmlspecialchars($accent,   ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            $fontSize,
            htmlspecialchars($extraCss, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            htmlspecialchars($label,    ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
        );
    }
}
