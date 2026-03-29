<?php

declare(strict_types=1);

namespace Webkernel\Builders\Website\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MakeWidgetCommand extends Command
{
    protected $signature = 'layup:make-widget {name : Widget class name (e.g. BannerWidget)}';

    protected $description = 'Scaffold a new Layup widget (PHP class + Blade view)';

    public function handle(): int
    {
        $name = $this->argument('name');
        $className = Str::studly($name);
        if (! str_ends_with($className, 'Widget')) {
            $className .= 'Widget';
        }

        $type = Str::kebab(Str::replaceLast('Widget', '', $className));
        $namespace = 'App\\Layup\\Widgets';
        $phpPath = app_path("Layup/Widgets/{$className}.php");
        $bladePath = resource_path("views/components/layup/{$type}.blade.php");

        if (file_exists($phpPath)) {
            $this->error(__('layup::commands.widget_exists', ['path' => $phpPath]));

            return self::FAILURE;
        }

        // Create PHP class
        $phpDir = dirname($phpPath);
        if (! is_dir($phpDir)) {
            mkdir($phpDir, 0755, true);
        }

        $stub = $this->generatePhpStub($namespace, $className, $type);
        file_put_contents($phpPath, $stub);
        $this->info(__('layup::commands.widget_created', ['path' => $phpPath]));

        // Create Blade view
        $bladeDir = dirname($bladePath);
        if (! is_dir($bladeDir)) {
            mkdir($bladeDir, 0755, true);
        }

        $bladeStub = $this->generateBladeStub($type);
        file_put_contents($bladePath, $bladeStub);
        $this->info(__('layup::commands.blade_created', ['path' => $bladePath]));

        $this->newLine();
        $this->comment(__('layup::commands.next_steps'));
        $this->line("  1. Edit {$phpPath} to add your form fields");
        $this->line("  2. Edit {$bladePath} to customize the frontend HTML");
        $this->line('  3. The widget will be auto-discovered from App\\Layup\\Widgets');
        $this->line('     Or add it to config/layup.php widgets array:');
        $this->line("     \\{$namespace}\\{$className}::class,");

        return self::SUCCESS;
    }

    protected function generatePhpStub(string $namespace, string $className, string $type): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace};

use Crumbls\\Layup\\View\\BaseWidget;
use Filament\\Forms\\Components\\TextInput;

class {$className} extends BaseWidget
{
    public static function getType(): string
    {
        return '{$type}';
    }

    public static function getLabel(): string
    {
        return __('layup::widgets.labels.{$type}');
    }

    public static function getIcon(): string
    {
        return 'heroicon-o-cube';
    }

    public static function getCategory(): string
    {
        return 'content';
    }

    public static function getContentFormSchema(): array
    {
        return [
            TextInput::make('title')
                ->label('Title')
                ->required(),
        ];
    }

    public static function getDefaultData(): array
    {
        return [
            'title' => '',
        ];
    }

    public static function getPreview(array \$data): string
    {
        return \$data['title'] ?? '(empty {$type})';
    }

    /**
     * Override to use a custom view path.
     * Default: components.layup.{$type}
     */
    public static function getViewName(): string
    {
        return 'components.layup.{$type}';
    }
}

PHP;
    }

    protected function generateBladeStub(string $type): string
    {
        return <<<'BLADE'
@php $vis = \Webkernel\Builders\Website\View\BaseView::visibilityClasses($data['hide_on'] ?? []); @endphp
<div @if(!empty($data['id']))id="{{ $data['id'] }}"@endif
     class="{{ $vis }} {{ $data['class'] ?? '' }}"
     style="{{ \Webkernel\Builders\Website\View\BaseView::buildInlineStyles($data) }}"
     {!! \Webkernel\Builders\Website\View\BaseView::animationAttributes($data) !!}
>
    {{ $data['title'] ?? '' }}
</div>
BLADE;
    }

    protected function humanize(string $kebab): string
    {
        return Str::title(str_replace('-', ' ', $kebab));
    }
}
