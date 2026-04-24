<?php declare(strict_types=1);

namespace Webkernel\System\Host\Support;

/**
 * Converts a percentage float to a consistent Tailwind color token.
 *
 * Centralises color logic so Blade views never contain conditionals.
 * Used by the pulse widget view data builders.
 */
final class MetricColor
{
    /**
     * Return a Tailwind color token for a given percentage.
     *
     * Default (higher = worse, e.g. memory usage):
     *   > 90 => red
     *   > 75 => amber
     *   else => emerald
     *
     * Inverse (higher = better, e.g. OPcache hit ratio):
     *   > 90 => emerald
     *   > 70 => amber
     *   else => red
     *
     * @param float $pct     0–100
     * @param bool  $inverse True when a high percentage is a good result.
     */
    public static function forPercentage(float $pct, bool $inverse = false): string
    {
        if ($inverse) {
            return $pct > 90 ? 'emerald' : ($pct > 70 ? 'amber' : 'red');
        }

        return $pct > 90 ? 'red' : ($pct > 75 ? 'amber' : 'emerald');
    }

    /**
     * Shortcut: percentage → CSS rgb() string in one call.
     *
     * This is the method Blade views should call directly:
     *   MetricColor::css($pct)         // higher = worse (memory, CPU, disk)
     *   MetricColor::css($pct, true)   // higher = better (OPcache hit ratio)
     *
     * Always returns a non-empty string — safe for inline style attributes.
     *
     * @param  float  $pct      0–100
     * @param  bool   $inverse  True when a high percentage is a good result
     */
    public static function css(float $pct, bool $inverse = false): string
    {
        return self::toCss(self::forPercentage($pct, $inverse));
    }

    /**
     * Map a Tailwind color token to its CSS rgb() value string.
     *
     * @param  string  $token  One of: red | amber | emerald | gray
     */
    public static function toCss(string $token): string
    {
        return match ($token) {
            'red'     => 'rgb(239,68,68)',
            'amber'   => 'rgb(245,158,11)',
            'emerald' => 'rgb(16,185,129)',
            default   => 'rgb(156,163,175)',
        };
    }
}
