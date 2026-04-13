<?php declare(strict_types=1);
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Blade;

if (!function_exists('tooltip_icon')) {
  /**
   * Renders an icon with an Alpine.js tooltip.
   */
  function tooltip_icon(string $icon, string $tooltip, string $placement = 'left', string $size = '20px'): HtmlString
  {
    return new HtmlString(
      sprintf(
        '<div x-tooltip.placement.%s="\'%s\'" style="display: flex; align-items: center; justify-content: center;">
                %s
            </div>',
        $placement,
        addslashes($tooltip),
        Blade::render("@svg('{$icon}', ['style' => 'width: {$size}; height: {$size};'])"),
      ),
    );
  }
}
