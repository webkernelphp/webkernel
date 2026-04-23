<?php declare(strict_types=1);

/* --- Assets --- */

defined('SVG_COLLECTION_PATHS')
    || define('SVG_COLLECTION_PATHS', [
        'bootstrap/webkernel/support/boot/_dist/export-svg/custom',
        'bootstrap/webkernel/support/boot/_dist/export-svg/lucide',
        'bootstrap/webkernel/support/boot/_dist/export-svg/simple-icons',
    ]);

/**
 * Grap an SVG icon from the Webkernel collections.
 *
 * @param string $filename Name of the SVG file (without extension).
 * @return string|null SVG contents or null if not found.
 */
function grap_webkernel_icon(string $filename): ?string
{
    if (!defined('SVG_COLLECTION_PATHS')) {
        throw new RuntimeException('SVG_COLLECTION_PATHS not defined.');
    }
    foreach (SVG_COLLECTION_PATHS as $path) {
        $fullPath = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename . '.svg';
        if (is_file($fullPath)) {
            return file_get_contents($fullPath);
        }
    }
    return null; // Not found
}
