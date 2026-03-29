<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Registered Widgets
    |--------------------------------------------------------------------------
    |
    | Widget classes available in the page builder. Each must extend
    | Webkernel\Builders\Website\View\BaseWidget.
    |
    */
    'widgets' => [
        // Content
        \Webkernel\Builders\Website\View\TextWidget::class,
        \Webkernel\Builders\Website\View\HeadingWidget::class,
        \Webkernel\Builders\Website\View\BlurbWidget::class,
        \Webkernel\Builders\Website\View\IconWidget::class,
        \Webkernel\Builders\Website\View\AccordionWidget::class,
        \Webkernel\Builders\Website\View\ToggleWidget::class,
        \Webkernel\Builders\Website\View\TabsWidget::class,
        \Webkernel\Builders\Website\View\PersonWidget::class,
        \Webkernel\Builders\Website\View\TestimonialWidget::class,
        \Webkernel\Builders\Website\View\NumberCounterWidget::class,
        \Webkernel\Builders\Website\View\BarCounterWidget::class,

        // Media
        \Webkernel\Builders\Website\View\ImageWidget::class,
        \Webkernel\Builders\Website\View\GalleryWidget::class,
        \Webkernel\Builders\Website\View\VideoWidget::class,
        \Webkernel\Builders\Website\View\AudioWidget::class,
        \Webkernel\Builders\Website\View\SliderWidget::class,
        \Webkernel\Builders\Website\View\MapWidget::class,

        // Interactive
        \Webkernel\Builders\Website\View\ButtonWidget::class,
        \Webkernel\Builders\Website\View\CallToActionWidget::class,
        \Webkernel\Builders\Website\View\CountdownWidget::class,
        \Webkernel\Builders\Website\View\PricingTableWidget::class,
        \Webkernel\Builders\Website\View\SocialFollowWidget::class,

        // Layout
        \Webkernel\Builders\Website\View\SpacerWidget::class,
        \Webkernel\Builders\Website\View\DividerWidget::class,

        // Advanced
        \Webkernel\Builders\Website\View\HtmlWidget::class,
        \Webkernel\Builders\Website\View\CodeWidget::class,
        \Webkernel\Builders\Website\View\EmbedWidget::class,
        \Webkernel\Builders\Website\View\AlertWidget::class,
        \Webkernel\Builders\Website\View\TableWidget::class,
        \Webkernel\Builders\Website\View\ProgressCircleWidget::class,
        \Webkernel\Builders\Website\View\MenuWidget::class,
        \Webkernel\Builders\Website\View\SearchWidget::class,
        \Webkernel\Builders\Website\View\ContactFormWidget::class,
        \Webkernel\Builders\Website\View\StarRatingWidget::class,
        \Webkernel\Builders\Website\View\LogoGridWidget::class,
        \Webkernel\Builders\Website\View\BlockquoteWidget::class,
        \Webkernel\Builders\Website\View\FeatureListWidget::class,
        \Webkernel\Builders\Website\View\TimelineWidget::class,
        \Webkernel\Builders\Website\View\StatCardWidget::class,
        \Webkernel\Builders\Website\View\MarqueeWidget::class,
        \Webkernel\Builders\Website\View\BeforeAfterWidget::class,
        \Webkernel\Builders\Website\View\TeamGridWidget::class,
        \Webkernel\Builders\Website\View\NotificationBarWidget::class,
        \Webkernel\Builders\Website\View\HeroWidget::class,
        \Webkernel\Builders\Website\View\BreadcrumbsWidget::class,
        \Webkernel\Builders\Website\View\FaqWidget::class,
        \Webkernel\Builders\Website\View\LoginWidget::class,
        \Webkernel\Builders\Website\View\NewsletterWidget::class,
        \Webkernel\Builders\Website\View\PostListWidget::class,
        \Webkernel\Builders\Website\View\SeparatorWidget::class,
        \Webkernel\Builders\Website\View\BackToTopWidget::class,
        \Webkernel\Builders\Website\View\CookieConsentWidget::class,
        \Webkernel\Builders\Website\View\ShareButtonsWidget::class,
        \Webkernel\Builders\Website\View\ModalWidget::class,
        \Webkernel\Builders\Website\View\TypewriterWidget::class,
        \Webkernel\Builders\Website\View\CardWidget::class,
        \Webkernel\Builders\Website\View\TableOfContentsWidget::class,
        \Webkernel\Builders\Website\View\StepProcessWidget::class,
        \Webkernel\Builders\Website\View\GradientTextWidget::class,
        \Webkernel\Builders\Website\View\FlipCardWidget::class,
        \Webkernel\Builders\Website\View\PricingToggleWidget::class,
        \Webkernel\Builders\Website\View\ImageHotspotWidget::class,
        \Webkernel\Builders\Website\View\LottieWidget::class,
        \Webkernel\Builders\Website\View\MasonryWidget::class,
        \Webkernel\Builders\Website\View\RichTextWidget::class,
        \Webkernel\Builders\Website\View\ListWidget::class,
        \Webkernel\Builders\Website\View\AnchorWidget::class,
        \Webkernel\Builders\Website\View\BannerWidget::class,
        \Webkernel\Builders\Website\View\ContentToggleWidget::class,
        \Webkernel\Builders\Website\View\LogoSliderWidget::class,
        \Webkernel\Builders\Website\View\TestimonialSliderWidget::class,
        \Webkernel\Builders\Website\View\IconBoxWidget::class,
        \Webkernel\Builders\Website\View\AnimatedHeadingWidget::class,
        \Webkernel\Builders\Website\View\TestimonialCarouselWidget::class,
        \Webkernel\Builders\Website\View\ComparisonTableWidget::class,
        \Webkernel\Builders\Website\View\FaqWidget::class,
        \Webkernel\Builders\Website\View\VideoPlaylistWidget::class,
        \Webkernel\Builders\Website\View\BadgeWidget::class,
        \Webkernel\Builders\Website\View\AvatarGroupWidget::class,
        \Webkernel\Builders\Website\View\TestimonialGridWidget::class,
        \Webkernel\Builders\Website\View\FileDownloadWidget::class,
        \Webkernel\Builders\Website\View\ChangelogWidget::class,
        \Webkernel\Builders\Website\View\SkillBarWidget::class,
        \Webkernel\Builders\Website\View\PriceWidget::class,
        \Webkernel\Builders\Website\View\HotspotWidget::class,
        \Webkernel\Builders\Website\View\MetricWidget::class,
        \Webkernel\Builders\Website\View\FeatureGridWidget::class,
        \Webkernel\Builders\Website\View\HighlightBoxWidget::class,
        \Webkernel\Builders\Website\View\SocialProofWidget::class,
        \Webkernel\Builders\Website\View\CtaBannerWidget::class,
        \Webkernel\Builders\Website\View\IconListWidget::class,
        \Webkernel\Builders\Website\View\ImageCardWidget::class,
        \Webkernel\Builders\Website\View\ImageTextWidget::class,
        \Webkernel\Builders\Website\View\QuoteCarouselWidget::class,
        \Webkernel\Builders\Website\View\SectionHeadingWidget::class,
        \Webkernel\Builders\Website\View\TextColumnsWidget::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Widget Auto-Discovery
    |--------------------------------------------------------------------------
    |
    | Automatically discovers and registers widget classes from the given
    | namespace/directory. Set to null to disable auto-discovery.
    |
    */
    'widget_discovery' => [
        'namespace' => 'App\\Layup\\Widgets',
        'directory' => null, // defaults to app_path('Layup/Widgets')
    ],

    /*
    |--------------------------------------------------------------------------
    | Pages Configuration
    |--------------------------------------------------------------------------
    |
    | Configurable per-dashboard. If you run multiple Filament panels that
    | each need their own page table, override these values per panel.
    |
    */
    'pages' => [
        'table' => 'layup_pages',
        'model' => \Webkernel\Builders\Website\Models\Page::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Revisions
    |--------------------------------------------------------------------------
    |
    | Automatically save content revisions when a page is updated.
    | Old revisions are pruned when the count exceeds 'max'.
    |
    */
    'revisions' => [
        'enabled' => true,
        'max' => 50,
    ],

    /*
    |--------------------------------------------------------------------------
    | Frontend Rendering
    |--------------------------------------------------------------------------
    |
    | Controls the public-facing page routes. Disable to handle routing
    | yourself, or customize the prefix, middleware, layout, and view.
    |
    | Set 'domain' to serve pages on a specific domain (e.g., for a
    | headless CMS where the frontend lives on a different subdomain).
    |
    */
    'frontend' => [
        'enabled' => true,
        'prefix' => 'pages',
        'middleware' => ['web'],
        'domain' => null,
        'layout' => 'layup::layouts.page',
        'view' => 'layup::frontend.page',
        'max_width' => 'container',
        'include_scripts' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Tailwind Safelist
    |--------------------------------------------------------------------------
    |
    | Layup generates Tailwind utility classes dynamically (column widths,
    | gap values, user-defined classes). Since Tailwind can't scan database
    | content, these classes are written to a safelist file.
    |
    | When 'auto_sync' is enabled, saving a page automatically regenerates
    | the safelist. If new classes are detected, a SafelistChanged event
    | is dispatched so you can trigger a frontend rebuild.
    |
    | Run `php artisan layup:safelist` to manually regenerate.
    |
    */
    'safelist' => [
        'enabled' => true,
        'auto_sync' => true,
        'path' => 'storage/layup-safelist.txt',
        'extra_classes' => [], // Additional classes to always include in the safelist
    ],

    /*
    |--------------------------------------------------------------------------
    | Breakpoints
    |--------------------------------------------------------------------------
    |
    | Responsive preview breakpoints shown in the size toggler.
    |
    */
    'breakpoints' => [
        'sm' => ['label' => 'sm', 'width' => 640, 'icon' => 'heroicon-o-device-phone-mobile'],
        'md' => ['label' => 'md', 'width' => 768, 'icon' => 'heroicon-o-device-tablet'],
        'lg' => ['label' => 'lg', 'width' => 1024, 'icon' => 'heroicon-o-computer-desktop'],
        'xl' => ['label' => 'xl', 'width' => 1280, 'icon' => 'heroicon-o-tv'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Breakpoint
    |--------------------------------------------------------------------------
    */
    'default_breakpoint' => 'lg',

    /*
    |--------------------------------------------------------------------------
    | Row Templates
    |--------------------------------------------------------------------------
    |
    | Predefined column layouts for the "Add Row" picker.
    | Each is an array of column spans (must sum to 12).
    |
    */
    'row_templates' => [
        [12],
        [6, 6],
        [4, 4, 4],
        [3, 3, 3, 3],
        [8, 4],
        [4, 8],
        [3, 6, 3],
        [2, 8, 2],
    ],
];
