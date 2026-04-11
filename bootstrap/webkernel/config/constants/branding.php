<?php declare(strict_types=1);

if (! function_exists('webkernelMakeBase64BrandingUrl')) {
    /**
     * Register a branding asset and return its data URI.
     *
     * Calling this function is the only step needed to add a new branding asset:
     * it stores the asset in the in-memory registry (keyed by $key) and returns
     * the data URI so the result can be stored in a constant for direct use.
     *
     * The registry is frozen into WEBKERNEL_BRANDING_ASSETS after all branding
     * files are loaded.  Adding a new asset requires nothing more than one call:
     *
     *   define('MY_CONST', webkernelMakeBase64BrandingUrl('my-key', 'png', '...'));
     *
     * @param string $key    Slug used in the /__webkernel-app/branding/{key} route
     * @param string $format Image format (png, jpeg, webp, svg+xml, …)
     * @param string $base64 Raw base64-encoded image data (no data URI prefix)
     * @return string        Full data URI: data:image/{format};base64,{base64}
     */
    function webkernelMakeBase64BrandingUrl(string $key, string $format, string $base64): string
    {
        $GLOBALS['_wk_branding'][$key] = ['format' => $format, 'data' => $base64];
        return "data:image/{$format};base64,{$base64}";
    }
}

if (! defined('WEBKERNEL_BRANDING_ASSETS')) {
    /* ── Load branding assets (each define() call auto-registers via the function above) ── */
    $GLOBALS['_wk_branding'] = [];
    $_wkBrandPath = __DIR__ . '/../branding/';
    require $_wkBrandPath . 'logo-for-dark-mode.php';
    require $_wkBrandPath . 'logo-for-light-mode.php';
    require $_wkBrandPath . 'bg-login-image-for-dark-mode.php';
    require $_wkBrandPath . 'bg-login-image-for-light-mode.php';
    unset($_wkBrandPath);

    /* Freeze the registry into a constant — read-only everywhere from here on. */
    define('WEBKERNEL_BRANDING_ASSETS', $GLOBALS['_wk_branding']);
    unset($GLOBALS['_wk_branding']);
}

if (! function_exists('webkernelBrandingUrl')) {
    /**
     * Return a cache-busted URL for a registered branding asset.
     *
     * The URL contains ?v={8-char md5 prefix of the base64 data}.  Whenever the
     * image is replaced the hash changes automatically, busting browser/CDN caches
     * with no manual intervention — safe for deployments years apart.
     *
     * @param  string $key  A slug registered via webkernelMakeBase64BrandingUrl()
     * @return string       Absolute URL, or '' if the key is unknown
     */
    function webkernelBrandingUrl(string $key): string
    {
        $asset = WEBKERNEL_BRANDING_ASSETS[$key] ?? null;
        if (!$asset) {
            return '';
        }
        $hash = substr(md5($asset['data']), 0, 8);
        return WebkernelRouter::url('branding/' . $key) . '?v=' . $hash;
    }

    /* ── Register branding routes in WebkernelRouter ──────────────────────────
     * Pure-PHP handlers: no Laravel, no framework overhead, Octane-safe
     * (WEBKERNEL_BRANDING_ASSETS is an immutable constant per worker process).
     * Each asset is served at /__webkernel-app/branding/{key}.
     */
    foreach (WEBKERNEL_BRANDING_ASSETS as $_wkKey => $_wkAsset) {
        WebkernelRouter::register('branding/' . $_wkKey, static function (array $params) use ($_wkAsset): never {
            $etag = '"' . substr(md5($_wkAsset['data']), 0, 16) . '"';
            if (($_SERVER['HTTP_IF_NONE_MATCH'] ?? '') === $etag) {
                http_response_code(304);
                exit(0);
            }
            $binary = base64_decode($_wkAsset['data']);
            header('Content-Type: image/' . $_wkAsset['format']);
            header('Content-Length: ' . strlen($binary));
            header('Cache-Control: public, max-age=31536000, immutable');
            header('ETag: ' . $etag);
            echo $binary;
            exit(0);
        });
    }
    unset($_wkKey, $_wkAsset);
}
