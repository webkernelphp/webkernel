<?php declare(strict_types=1);

/**
 * Build a base64 Data URI.
 *
 * @param string $format Image format (png, jpeg, gif, svg+xml, webp, etc.)
 * @param string $base64 Encoded data
 * @return string Full Data URI
 */
 function makeBase64(string $format = 'png', string $base64): string
{
    return "data:image/{$format};base64,{$base64}";
}

/* Webkernel — Branding & Logos */
$brandPath = __DIR__ . '/../branding/';
require $brandPath . 'logo-for-dark-mode.php';
require $brandPath . 'logo-for-light-mode.php';
unset($brandPath);
