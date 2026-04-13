<?php declare(strict_types=1);

if (! function_exists('webkernelAddBrandingSource')) {
    /**
     * Register a branding asset into the global registry.
     */
    function webkernelAddBrandingSource(
        string $brand,
        string $key,
        string $format,
        string $base64
    ): string {
        $GLOBALS['_wk_branding'][$brand][$key] = [
            'format' => $format,
            'data'   => $base64,
        ];

        return "data:image/{$format};base64,{$base64}";
    }
}

if (! function_exists('webkernelMakeBase64BrandingUrl')) {
    /**
     * Register a branding asset and return its data URI.
     * Extracts the brand name from the key prefix (e.g. 'webkernel-logo-light' → 'webkernel').
     * Called by brand asset files in boot-branding/{brand}/*.php.
     */
    function webkernelMakeBase64BrandingUrl(string $key, string $format, string $base64): string
    {
        $brand = explode('-', $key, 2)[0];
        return webkernelAddBrandingSource($brand, $key, $format, $base64);
    }
}

if (! defined('WEBKERNEL_BRANDING_ASSETS')) {
    $GLOBALS['_wk_branding'] = [];

    define('WEBKERNEL_BRANDING_ASSETS', true);
}

if (! function_exists('webkernelBrandingUrl')) {
    function webkernelBrandingUrl(string $brand, string $key): string
    {
        $asset = $GLOBALS['_wk_branding'][$brand][$key] ?? null;

        if (! $asset) {
            return '';
        }

        $hash = substr(md5($asset['data']), 0, 8);

        return WebkernelRouter::url("branding/{$brand}/{$key}") . '?v=' . $hash;
    }
}

if (! function_exists('webkernelRegisterBrandingRoutes')) {
    /**
     * Must be called AFTER all branding sources are loaded.
     */
    function webkernelRegisterBrandingRoutes(): void
    {
        foreach ($GLOBALS['_wk_branding'] as $brand => $assets) {
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
