<?php declare(strict_types=1);

namespace Webkernel\System\Host\Support;

/**
 * Logarithmic progress bar scale helper.
 *
 * Maps a 0–100 input percentage to a 0–100 output value using a
 * logarithmic curve. This expands the visual range at the low end so
 * values like 2% or 5% remain visible in a progress bar rather than
 * appearing as a single-pixel sliver.
 *
 * Formula: output = 100 * ln(1 + x/100 * (e - 1))
 */
final class LogScale
{
    /**
     * Apply logarithmic scaling to a percentage.
     *
     * @param float $pct  Input percentage 0–100.
     * @return float      Output percentage 0–100, rounded to 2 decimal places.
     */
    public static function apply(float $pct): float
    {
        if ($pct <= 0.0) {
            return 0.0;
        }

        if ($pct >= 100.0) {
            return 100.0;
        }

        $x = $pct / 100.0;

        return round(min(100.0, log(1.0 + $x * (M_E - 1.0)) * 100.0), 2);
    }
}
