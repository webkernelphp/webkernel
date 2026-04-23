<?php declare(strict_types=1);

/**
 * Consolidated helper functions for the Website module.
 *
 * Combines:
 *  - addToUrl() : add query params to a URL
 *  - randomImage() : generate random placeholder image URLs
 *  - randomLinearGradient() and related helpers : gradient registry & generation
 *  - svgToDataUri() and srcSVG() : convert SVG to data URI
 *
 * Keep these helpers framework-agnostic where possible. Some helpers (getSiteTenantUrl)
 * depend on Filament and the website Site model; those functions validate inputs at runtime.
 */

/*
|--------------------------------------------------------------------------
| URL Helpers
|--------------------------------------------------------------------------
*/
if (!function_exists('addToUrl')) {
    /**
     * Append or replace query parameters on a URL.
     *
     * Examples:
     *   addToUrl('https://example.com/path?foo=1', ['bar' => '2']) => https://example.com/path?foo=1&bar=2
     *   addToUrl('/path', ['a' => 'b']) => /path?a=b
     *
     * @param string $url
     * @param array<string,mixed> $params
     * @param bool $replaceExisting If true, replace existing params with same keys; otherwise keep existing
     * @return string
     */
    function addToUrl(string $url, array $params, bool $replaceExisting = true): string
    {
        if (empty($params)) {
            return $url;
        }

        $parts = parse_url($url);

        $query = $parts['query'] ?? '';
        parse_str($query, $existing);

        if ($replaceExisting) {
            $merged = array_merge($existing, $params);
        } else {
            $merged = $existing + $params;
        }

        $newQuery = http_build_query($merged);

        $scheme   = $parts['scheme'] ?? null;
        $host     = $parts['host'] ?? null;
        $port     = isset($parts['port']) ? ':' . $parts['port'] : '';
        $user     = $parts['user'] ?? null;
        $pass     = isset($parts['pass']) ? ':' . $parts['pass']  : '';
        $pass     = ($user || $pass) ? "$pass@" : '';
        $path     = $parts['path'] ?? '';
        $fragment = isset($parts['fragment']) ? '#' . $parts['fragment'] : '';

        $auth = '';
        if ($user !== null) {
            $auth = $user . ($parts['pass'] ?? '') ? ':' . ($parts['pass'] ?? '') . '@' : '';
        }

        if ($scheme && $host) {
            $base = $scheme . '://' . $host . $port;
            $result = $base . $path;
        } else {
            // Relative URL
            $result = $path === '' ? ($parts['path'] ?? '') : $path;
        }

        if ($newQuery !== '') {
            $result .= '?' . $newQuery;
        }

        $result .= $fragment;

        return $result;
    }
}

if (!function_exists('getSiteTenantUrl')) {
    /**
     * Build a tenant-specific URL for the current Filament panel + site.
     *
     * This mirrors previous helper behaviour but references the relocated Domain model.
     *
     * @param string $path Path relative to panel root (no leading slash required)
     * @return string
     *
     * @throws \RuntimeException when Filament panel or current_site are not available / invalid
     */
    function getSiteTenantUrl(string $path): string
    {
        if (!function_exists('\\Filament\\Facades\\Filament')) {
            // If Filament not available, build simple URL
            return '/' . ltrim($path, '/');
        }

        if (!app()->bound('current_site')) {
            throw new \RuntimeException('No current site bound in container. ResolveSiteMiddleware not executed.');
        }

        $site = app('current_site');

        // Accept either domain models from previous or new namespace but validate minimal contract
        if (!is_object($site)) {
            throw new \RuntimeException('Invalid current_site instance in container.');
        }

        $panel = \Filament\Facades\Filament::getCurrentPanel();

        if (!$panel) {
            throw new \RuntimeException('No current Filament panel resolved.');
        }

        $panelUrl = rtrim($panel->getUrl() ?? '', '/');

        $path = ltrim($path, '/');

        return $panelUrl . '/' . $path;
    }
}

/*
|--------------------------------------------------------------------------
| Image Helpers
|--------------------------------------------------------------------------
*/
if (!function_exists('randomImage')) {
    /**
     * Generate a random image URL for placeholder or example usage.
     *
     * @param int $width
     * @param int $height
     * @param string $source 'picsum'|'unsplash'|'placeholder'|'loremflickr'
     * @param array<string,mixed> $options Extra provider options (bg, fg, text)
     * @return string
     */
    function randomImage(int $width = 800, int $height = 600, string $source = 'picsum', array $options = []): string
    {
        // Create a reasonably unique seed
        $seed = isset($options['seed']) ? (string)$options['seed'] : bin2hex(random_bytes(5));

        switch (strtolower($source)) {
            case 'picsum':
                return "https://picsum.photos/seed/{$seed}/{$width}/{$height}";

            case 'unsplash':
                // Unsplash Source requires width x height
                return "https://source.unsplash.com/{$width}x{$height}/?random&sig={$seed}";

            case 'loremflickr':
                $tags = isset($options['tags']) ? urlencode((string)$options['tags']) : 'nature';
                return "https://loremflickr.com/{$width}/{$height}/{$tags}?lock={$seed}";

            case 'placeholder':
                $bg = $options['bg'] ?? 'cccccc';
                $fg = $options['fg'] ?? '000000';
                $text = isset($options['text']) ? urlencode((string)$options['text']) : "{$width}x{$height}";
                return "https://via.placeholder.com/{$width}x{$height}/{$bg}/{$fg}?text={$text}";

            default:
                // Fallback to a local asset path that may exist in projects
                return '/images/placeholders/default.jpg';
        }
    }
}

/*
|--------------------------------------------------------------------------
| Gradient Helpers (randomLinearGradient + registry)
|--------------------------------------------------------------------------
|
| The registry below is a compact but expressive set of gradients. The
| functions support filter keywords, generation modes, entropy and helper
| selection mechanisms.
|
*/
if (!function_exists('randomLinearGradient')) {
    /**
     * Get a random linear gradient from registry with advanced filtering and entropy controls.
     *
     * Supports:
     *  - string|array keywords to match against name/category/subcategory/tags
     *  - 'generate:light' | 'generate:dark' | 'generate:any' to procedurally create gradients
     *  - boolean true to return from entire registry
     *
     * @param string|array|bool $filter
     * @param int $selectionEntropy 0 = deterministic first, 1 = uniform random, >1 = weighted by score
     * @param int $categoryEntropy 1 = strict filter, >1 = broaden when insufficient matches
     * @param bool $returnString If true, returns the CSS gradient string
     * @return array|string
     */
    function randomLinearGradient($filter = true, int $selectionEntropy = 1, int $categoryEntropy = 1, bool $returnString = false)
    {
        $registry = _getGradientRegistry();

        // Procedural generation mode
        if (is_string($filter) && preg_match('/^generate:(light|dark|any)$/i', $filter, $m)) {
            $result = _generateGradient(strtolower($m[1]));
            return $returnString ? $result['value'] : $result;
        }

        $all = _flattenRegistry($registry);

        $keywords = _normalizeKeywords($filter);
        $matches = _filterByKeywords($all, $keywords);

        if ($categoryEntropy > 1 && count($matches) < $categoryEntropy) {
            $matches = $all;
        }

        if (empty($matches)) {
            $result = $all[array_rand($all)];
            return $returnString ? $result['value'] : $result;
        }

        $result = _applySelectionEntropy($matches, $selectionEntropy);
        return $returnString ? $result['value'] : $result;
    }
}

if (!function_exists('_getGradientRegistry')) {
    /**
     * Return a structured gradient registry.
     *
     * @return array<string,array<string,array<array{name:string,value:string,tags:array<string>,luminosity:string}>>>
     */
    function _getGradientRegistry(): array
    {
        return [
            'pink' => [
                'soft' => [
                    [
                        'name' => 'Candy Pink',
                        'value' => 'linear-gradient(to bottom right, #FF61D2, #FE9090)',
                        'tags' => ['sweet', 'pastel', 'romantic', 'feminine'],
                        'luminosity' => 'light',
                    ],
                    [
                        'name' => 'Rose Blush',
                        'value' => 'linear-gradient(to bottom right, #FAD0C4, #FFD1FF)',
                        'tags' => ['delicate', 'pale', 'wedding', 'romantic'],
                        'luminosity' => 'light',
                    ],
                ],
                'vivid' => [
                    [
                        'name' => 'Hot Magenta',
                        'value' => 'linear-gradient(to bottom right, #FF0080, #FF8C00)',
                        'tags' => ['bold', 'vibrant', 'neon', 'energetic'],
                        'luminosity' => 'medium',
                    ],
                ],
            ],
            'blue' => [
                'light' => [
                    [
                        'name' => 'Sky Breeze',
                        'value' => 'linear-gradient(to bottom right, #89F7FE, #66A6FF)',
                        'tags' => ['sky', 'airy', 'fresh', 'daytime'],
                        'luminosity' => 'light',
                    ],
                ],
                'dark' => [
                    [
                        'name' => 'Blue Galaxy',
                        'value' => 'linear-gradient(to bottom right, #00C0FF, #4218B8)',
                        'tags' => ['space', 'cosmic', 'deep', 'night'],
                        'luminosity' => 'dark',
                    ],
                ],
            ],
            'green' => [
                'fresh' => [
                    [
                        'name' => 'Spring Meadow',
                        'value' => 'linear-gradient(to bottom right, #56ab2f, #a8e063)',
                        'tags' => ['spring', 'grass', 'natural', 'fresh'],
                        'luminosity' => 'medium',
                    ],
                ],
                'deep' => [
                    [
                        'name' => 'Emerald Depths',
                        'value' => 'linear-gradient(to bottom right, #093028, #237A57)',
                        'tags' => ['emerald', 'luxury', 'rich', 'elegant'],
                        'luminosity' => 'dark',
                    ],
                ],
            ],
            'neutral' => [
                'cool' => [
                    [
                        'name' => 'Silver Cloud',
                        'value' => 'linear-gradient(to bottom right, #ECE9E6, #FFFFFF)',
                        'tags' => ['silver', 'cloud', 'clean', 'minimal'],
                        'luminosity' => 'light',
                    ],
                    [
                        'name' => 'Slate Gray',
                        'value' => 'linear-gradient(to bottom right, #485563, #29323C)',
                        'tags' => ['slate', 'gray', 'professional', 'modern'],
                        'luminosity' => 'dark',
                    ],
                ],
            ],
            'multicolor' => [
                'rainbow' => [
                    [
                        'name' => 'Pride Spectrum',
                        'value' => 'linear-gradient(to bottom right, #FF0018, #FFA52C, #FFFF41, #008018, #0000F9, #86007D)',
                        'tags' => ['rainbow', 'pride', 'colorful', 'vibrant'],
                        'luminosity' => 'medium',
                    ],
                ],
            ],
        ];
    }
}

if (!function_exists('_flattenRegistry')) {
    /**
     * Flatten the registry into a single list with metadata.
     *
     * @param array $registry
     * @return array
     */
    function _flattenRegistry(array $registry): array
    {
        $all = [];
        foreach ($registry as $category => $subcats) {
            foreach ($subcats as $subcategory => $items) {
                foreach ($items as $item) {
                    $item['category'] = $category;
                    $item['subcategory'] = $subcategory;
                    $all[] = $item;
                }
            }
        }
        return $all;
    }
}

if (!function_exists('_normalizeKeywords')) {
    /**
     * Normalize filter input into an array of lowercase keywords.
     *
     * @param string|array|bool $filter
     * @return array<string>
     */
    function _normalizeKeywords($filter): array
    {
        if ($filter === true || $filter === null) {
            return [];
        }

        if (is_string($filter)) {
            $items = preg_split('/[\s,\.]+/', strtolower($filter));
            return array_values(array_filter(array_map('trim', $items)));
        }

        if (is_array($filter)) {
            return array_values(array_filter(array_map(function ($v) {
                return is_string($v) ? trim(strtolower($v)) : null;
            }, $filter)));
        }

        return [];
    }
}

if (!function_exists('_filterByKeywords')) {
    /**
     * Filter gradients by keywords and score matches.
     *
     * @param array $gradients
     * @param array<string> $keywords
     * @return array
     */
    function _filterByKeywords(array $gradients, array $keywords): array
    {
        if (empty($keywords)) {
            return $gradients;
        }

        $matches = [];
        foreach ($gradients as $g) {
            $haystack = strtolower($g['name'] . ' ' . $g['category'] . ' ' . $g['subcategory'] . ' ' . implode(' ', $g['tags']) . ' ' . ($g['luminosity'] ?? ''));
            $score = 0;
            foreach ($keywords as $k) {
                if ($k !== '' && strpos($haystack, $k) !== false) {
                    $score++;
                }
            }
            if ($score > 0) {
                $g['score'] = $score;
                $matches[] = $g;
            }
        }
        return $matches;
    }
}

if (!function_exists('_applySelectionEntropy')) {
    /**
     * Choose one gradient from matches applying entropy/weights.
     *
     * @param array $matches
     * @param int $selectionEntropy
     * @return array
     */
    function _applySelectionEntropy(array $matches, int $selectionEntropy): array
    {
        if (empty($matches)) {
            return [
                'name' => 'transparent',
                'value' => 'linear-gradient(to bottom right, rgba(0,0,0,0), rgba(0,0,0,0))',
                'category' => 'none',
                'subcategory' => 'none',
                'tags' => [],
                'luminosity' => 'medium',
            ];
        }

        if ($selectionEntropy <= 0) {
            return $matches[0];
        }

        if ($selectionEntropy === 1) {
            return $matches[array_rand($matches)];
        }

        // Weighted selection: higher score more likely
        $weights = [];
        foreach ($matches as $i => $m) {
            $weights[$i] = pow($m['score'] ?? 1, $selectionEntropy);
        }
        $total = array_sum($weights);
        $r = mt_rand() / mt_getrandmax() * $total;
        foreach ($weights as $i => $w) {
            $r -= $w;
            if ($r <= 0) {
                return $matches[$i];
            }
        }
        return $matches[array_rand($matches)];
    }
}

if (!function_exists('_generateGradient')) {
    /**
     * Procedurally generate a gradient string and metadata.
     *
     * @param string $mode 'light'|'dark'|'any'
     * @return array
     */
    function _generateGradient(string $mode): array
    {
        $mode = strtolower($mode);
        $stopCount = mt_rand(2, 4);
        $colors = [];
        if ($mode === 'light') {
            for ($i = 0; $i < $stopCount; $i++) {
                $colors[] = _generateLightColor();
            }
            $luminosity = 'light';
        } elseif ($mode === 'dark') {
            for ($i = 0; $i < $stopCount; $i++) {
                $colors[] = _generateDarkColor();
            }
            $luminosity = 'dark';
        } else {
            for ($i = 0; $i < $stopCount; $i++) {
                $colors[] = _generateRandomColor();
            }
            $luminosity = 'medium';
        }

        $direction = _getRandomDirection();
        $colorString = implode(', ', $colors);

        return [
            'name' => sprintf('Generated %s Gradient (%d stops)', ucfirst($mode), $stopCount),
            'value' => sprintf('linear-gradient(%s, %s)', $direction, $colorString),
            'category' => 'generated',
            'subcategory' => $mode,
            'tags' => ['procedural', 'generated', $mode, $stopCount . '-stops'],
            'luminosity' => $luminosity,
        ];
    }
}

if (!function_exists('_generateLightColor')) {
    function _generateLightColor(): string
    {
        $r = mt_rand(180, 255);
        $g = mt_rand(180, 255);
        $b = mt_rand(180, 255);
        return sprintf('#%02X%02X%02X', $r, $g, $b);
    }
}

if (!function_exists('_generateDarkColor')) {
    function _generateDarkColor(): string
    {
        $r = mt_rand(0, 80);
        $g = mt_rand(0, 80);
        $b = mt_rand(0, 80);
        return sprintf('#%02X%02X%02X', $r, $g, $b);
    }
}

if (!function_exists('_generateRandomColor')) {
    function _generateRandomColor(): string
    {
        $r = mt_rand(0, 255);
        $g = mt_rand(0, 255);
        $b = mt_rand(0, 255);
        return sprintf('#%02X%02X%02X', $r, $g, $b);
    }
}

if (!function_exists('_getRandomDirection')) {
    function _getRandomDirection(): string
    {
        $directions = [
            'to bottom right',
            'to bottom left',
            'to top right',
            'to top left',
            'to right',
            'to left',
            'to bottom',
            'to top',
            '45deg',
            '135deg',
            '225deg',
            '315deg',
        ];
        return $directions[array_rand($directions)];
    }
}

/*
|--------------------------------------------------------------------------
| SVG Helpers
|--------------------------------------------------------------------------
*/
if (!function_exists('svgToDataUri')) {
    /**
     * Convert an SVG string (or array with first element containing SVG) to a base64 data URI.
     *
     * Adds xmlns/width/height attributes if missing and supports simple color replacements.
     *
     * @param string|array $svg
     * @param int|null $width
     * @param int|null $height
     * @param string|null $fillColor
     * @param string|null $strokeColor
     * @return string
     */
    function svgToDataUri(
        string|array $svg,
        ?int $width = null,
        ?int $height = null,
        ?string $fillColor = null,
        ?string $strokeColor = null
    ): string {
        if (is_array($svg)) {
            $svg = $svg[0] ?? '';
        }

        $svg = trim((string)$svg);

        if ($svg === '') {
            return 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg"></svg>');
        }

        if (!str_contains($svg, '<svg')) {
            $svg = '<svg>' . $svg . '</svg>';
        }

        preg_match('/<svg([^>]*)>/i', $svg, $m);
        $attributes = $m[1] ?? '';

        if (!str_contains($attributes, 'xmlns')) {
            $attributes .= ' xmlns="http://www.w3.org/2000/svg"';
        }

        if ($width !== null) {
            if (preg_match('/width="[^"]*"/', $attributes)) {
                $attributes = preg_replace('/width="[^"]*"/', 'width="' . $width . '"', $attributes);
            } else {
                $attributes .= ' width="' . $width . '"';
            }
        } elseif (!str_contains($attributes, 'width')) {
            $attributes .= ' width="100"';
        }

        if ($height !== null) {
            if (preg_match('/height="[^"]*"/', $attributes)) {
                $attributes = preg_replace('/height="[^"]*"/', 'height="' . $height . '"', $attributes);
            } else {
                $attributes .= ' height="' . $height . '"';
            }
        } elseif (!str_contains($attributes, 'height')) {
            $attributes .= ' height="100"';
        }

        $svg = preg_replace('/<svg[^>]*>/i', '<svg' . $attributes . '>', $svg, 1);

        if ($fillColor !== null) {
            $svg = preg_replace('/fill="[^"]*"(?!\s*none)/i', 'fill="' . $fillColor . '"', $svg);
            $svg = preg_replace('/fill:#[0-9a-fA-F]{3,6}(?!none)/i', 'fill:' . $fillColor, $svg);
        }

        if ($strokeColor !== null) {
            $svg = preg_replace('/stroke="[^"]*"(?!\s*none)/i', 'stroke="' . $strokeColor . '"', $svg);
            $svg = preg_replace('/stroke:#[0-9a-fA-F]{3,6}(?!none)/i', 'stroke:' . $strokeColor, $svg);
        }

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
}

if (!function_exists('srcSVG')) {
    /**
     * Alias for svgToDataUri for cleaner template usage.
     *
     * @see svgToDataUri
     */
    function srcSVG(
        string|array $svg,
        ?int $width = null,
        ?int $height = null,
        ?string $fillColor = null,
        ?string $strokeColor = null
    ): string {
        return svgToDataUri($svg, $width, $height, $fillColor, $strokeColor);
    }
}
