<?php declare(strict_types=1);
// ═══════════════════════════════════════════════════════════════════
//  Trait: HasMicroWebLogo
//  Logo registration, base64 inlining, and dual-image theme swap.
// ═══════════════════════════════════════════════════════════════════

trait HasMicroWebLogo
{
    private ?string $logoLight = null;
    private ?string $logoDark  = null;

    /**
     * Set light and/or dark logo.
     * Accepts: absolute file path, URL, or data: URI.
     * File paths are automatically base64-inlined.
     */
    public function logo(?string $light = null, ?string $dark = null): static
    {
        $this->logoLight = self::resolveLogoSrc($light ?? $dark);
        $this->logoDark  = self::resolveLogoSrc($dark  ?? $light);
        return $this;
    }

    /**
     * Build two <img> tags (one per theme).
     * JS in HasMicroWebTheme::themeSwitcherJs() shows/hides them based on
     * the active theme, so logo swaps correctly even when the user toggles
     * the theme switcher (prefers-color-scheme alone cannot do this).
     */
    protected function buildLogoHtml(): string
    {
        $lightSrc = $this->logoLight ?? $this->logoDark ?? '';
        $darkSrc  = $this->logoDark  ?? $this->logoLight ?? '';

        if ($lightSrc === '' && $darkSrc === '') return '';

        $eLight = htmlspecialchars($lightSrc, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $eDark  = htmlspecialchars($darkSrc,  ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return sprintf(
            '<img id="wk-logo-light" src="%s" alt="Logo" loading="eager"'
            . ' style="max-width:160px;width:100%%;height:auto;display:block;margin:0 auto .85rem;opacity:.85"/>'
            . '<img id="wk-logo-dark"  src="%s" alt="Logo" loading="eager"'
            . ' style="max-width:160px;width:100%%;height:auto;display:none;margin:0 auto .85rem;opacity:.85"/>',
            $eLight, $eDark
        );
    }

    private static function resolveLogoSrc(?string $src): ?string
    {
        if ($src === null)             return null;
        if (str_starts_with($src, 'data:')) return $src;
        if (is_file($src)) {
            $mime = match (strtolower(pathinfo($src, PATHINFO_EXTENSION))) {
                'svg'         => 'image/svg+xml',
                'webp'        => 'image/webp',
                'jpg', 'jpeg' => 'image/jpeg',
                default       => 'image/png',
            };
            $raw = @file_get_contents($src);
            if ($raw !== false) return 'data:' . $mime . ';base64,' . base64_encode($raw);
        }
        return $src;
    }
}
