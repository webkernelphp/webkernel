<?php declare(strict_types=1);

if (!function_exists('webkernel')) {
    function webkernel(): \Webkernel\System\WebernelAPI
    {
        return app(\Webkernel\System\WebernelAPI::class);
    }
}

if (!function_exists('aptitude_path')) {
    function aptitude_path(string $path = ''): string
    {
        $base = WEBKERNEL_APTITUDES_ROOT;
        return $path === '' ? $base : $base . '/' . ltrim(str_replace('\\', '/', $path), '/');
    }
}

if (!function_exists('tooltip_icon')) {
    function tooltip_icon(
        string $icon,
        string $tooltip,
        string $placement = 'left',
        string $size = '20px'
    ): \Illuminate\Support\HtmlString {
        return new \Illuminate\Support\HtmlString(sprintf(
            '<div x-tooltip.placement.%s="\'%s\'" style="display:flex;align-items:center;justify-content:center;">%s</div>',
            $placement,
            addslashes($tooltip),
            \Illuminate\Support\Facades\Blade::render("@svg('{$icon}', ['style' => 'width:{$size};height:{$size};'])"),
        ));
    }
}

if (!function_exists('extractClassFromFile')) {
    function extractClassFromFile(string $file): ?string
    {
        $src = file_get_contents($file);
        if ($src === false) {
            return null;
        }
        $namespace = null;
        if (preg_match('/^namespace\s+(.+?);/m', $src, $m)) {
            $namespace = trim($m[1]);
        }
        if (preg_match('/^\s*(?:(?:final|abstract|readonly)\s+)*class\s+(\w+)/m', $src, $m)) {
            $class = trim($m[1]);
            return $namespace !== null ? $namespace . '\\' . $class : $class;
        }
        return null;
    }
}
