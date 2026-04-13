<?php declare(strict_types=1);

// ═══════════════════════════════════════════════════════════════════
//  § 4  Branding
//  Depends on: WebkernelRouter (030)
// ═══════════════════════════════════════════════════════════════════

// ── Internal store (static-backed, no globals) ────────────────────

/**
 * Returns a reference to the branding asset store.
 * Using a static variable avoids $GLOBALS and keeps state encapsulated.
 *
 * @return array<string, array<string, array{format: string, data: string}>>
 */
function &_wkBrandingStore(): array
{
    static $store = [];
    return $store;
}

// ── Registry ──────────────────────────────────────────────────────

if (! function_exists('webkernelAddBrandingSource')) {
    function webkernelAddBrandingSource(
        string $brand,
        string $key,
        string $format,
        string $base64
    ): string {
        $store           = &_wkBrandingStore();
        $store[$brand][$key] = ['format' => $format, 'data' => $base64];

        return "data:image/{$format};base64,{$base64}";
    }
}

if (! function_exists('webkernelMakeBase64BrandingUrl')) {
    /**
     * Register a branding asset and return its data URI.
     * Extracts the brand from the key prefix (e.g. 'webkernel-logo-light' → 'webkernel').
     * Called by brand asset files in boot-services/brands/{brand}/*.php.
     */
    function webkernelMakeBase64BrandingUrl(string $key, string $format, string $base64): string
    {
        $brand = explode('-', $key, 2)[0];
        return webkernelAddBrandingSource($brand, $key, $format, $base64);
    }
}

if (! function_exists('webkernelBrandingUrl')) {
    /**
     * Return the router URL for a registered branding asset.
     * The brand is extracted from the key prefix (e.g. 'webkernel-logo-light' → 'webkernel').
     */
    function webkernelBrandingUrl(string $key): string
    {
        $brand = explode('-', $key, 2)[0];
        $store = &_wkBrandingStore();
        $asset = $store[$brand][$key] ?? null;

        if (! $asset) {
            return '';
        }

        $hash = substr(md5($asset['data']), 0, 8);

        return WebkernelRouter::url("branding/{$brand}/{$key}") . '?v=' . $hash;
    }
}

if (! function_exists('webkernelRegisterBrandingRoutes')) {
    function webkernelRegisterBrandingRoutes(): void
    {
        $store = &_wkBrandingStore();

        foreach ($store as $brand => $assets) {
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
}

// ── Load all brand asset files ────────────────────────────────────

$_brandsPath = __DIR__ . '/brands';

foreach (['webkernel', 'numerimondes', 'thebestrecruit'] as $_brand) {
    $_brandDir = $_brandsPath . '/' . $_brand;
    if (! is_dir($_brandDir)) {
        continue;
    }
    foreach (glob($_brandDir . '/*.php') as $_file) {
        require $_file;
    }
}

unset($_brandsPath, $_brand, $_brandDir, $_file);

// ── Register branding routes in WebkernelRouter ───────────────────

webkernelRegisterBrandingRoutes();
