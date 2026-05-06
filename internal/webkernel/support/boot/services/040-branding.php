<?php declare(strict_types=1);
// ═══════════════════════════════════════════════════════════════════
//  § 4  Branding
//  Depends on: WebkernelRouter (030)
// ═══════════════════════════════════════════════════════════════════

/** @internal Holds registered branding assets, keyed by brand then asset key. */
$_brandingStore = new class {
    /** @var array<string, array<string, array{format: string, data: string}>> */
    public array $store = [];

    public function add(string $brand, string $key, string $format, string $base64): string
    {
        $this->store[$brand][$key] = ['format' => $format, 'data' => $base64];
        return "data:image/{$format};base64,{$base64}";
    }

    public function url(string $key): string
    {
        $brand = explode('-', $key, 2)[0];
        $asset = $this->store[$brand][$key] ?? null;
        if (! $asset) { return ''; }
        return WebkernelRouter::url("branding/{$brand}/{$key}") . '?v=' . substr(md5($asset['data']), 0, 8);
    }

    public function registerRoutes(): void
    {
        foreach ($this->store as $brand => $assets) {
            foreach ($assets as $key => $asset) {
                WebkernelRouter::register("branding/{$brand}/{$key}", static function () use ($asset): never {
                    $etag = '"' . substr(md5($asset['data']), 0, 16) . '"';
                    if (($_SERVER['HTTP_IF_NONE_MATCH'] ?? '') === $etag) {
                        http_response_code(304);
                        exit(0);
                    }
                    $binary = base64_decode($asset['data']);
                    header('Content-Type: image/' . $asset['format']);
                    header('Content-Length: ' . strlen($binary));
                    header('Cache-Control: public, max-age=31536000, immutable');
                    header('ETag: ' . $etag);
                    echo $binary;
                    exit(0);
                });
            }
        }
    }
};

// ── Registry ──────────────────────────────────────────────────────
if (! function_exists('webkernelAddBrandingSource')) {
    function webkernelAddBrandingSource(string $brand, string $key, string $format, string $base64): string
    {
        global $_brandingStore;
        return $_brandingStore->add($brand, $key, $format, $base64);
    }
}

if (! function_exists('webkernelMakeBase64BrandingUrl')) {
    /**
     * Register a branding asset and return its data URI.
     * Brand is inferred from the key prefix (e.g. 'webkernel-logo-light' → 'webkernel').
     */
    function webkernelMakeBase64BrandingUrl(string $key, string $format, string $base64): string
    {
        return webkernelAddBrandingSource(explode('-', $key, 2)[0], $key, $format, $base64);
    }
}

if (! function_exists('webkernelBrandingUrl')) {
    /**
     * Return the router URL for a registered branding asset.
     * Brand is inferred from the key prefix (e.g. 'webkernel-logo-light' → 'webkernel').
     */
    function webkernelBrandingUrl(string $key): string
    {
        global $_brandingStore;
        return $_brandingStore->url($key);
    }
}

if (! function_exists('webkernelRegisterBrandingRoutes')) {
    /** Expose every registered asset through WebkernelRouter with HTTP caching. */
    function webkernelRegisterBrandingRoutes(): void
    {
        global $_brandingStore;
        $_brandingStore->registerRoutes();
    }
}

// ── Load all brand asset files ────────────────────────────────────
$_brandsPath = __DIR__ . '/brands';
foreach (['webkernel', 'numerimondes', 'thebestrecruit'] as $_brand) {
    $_brandDir = "{$_brandsPath}/{$_brand}";
    if (! is_dir($_brandDir)) { continue; }
    foreach (glob("{$_brandDir}/*.php") as $_file) { require $_file; }
}
unset($_brandsPath, $_brand, $_brandDir, $_file);

// ── Register branding routes in WebkernelRouter ───────────────────
webkernelRegisterBrandingRoutes();
