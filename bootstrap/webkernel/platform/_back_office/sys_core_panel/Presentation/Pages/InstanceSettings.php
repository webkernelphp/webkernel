<?php declare(strict_types=1);

namespace Webkernel\BackOffice\System\Presentation\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use UnitEnum;
use Webkernel\BackOffice\System\Models\WebkernelSetting;

class InstanceSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'webkernel-system::filament.pages.instance-settings';

    protected static ?int $navigationSort = 7;
    protected static bool $shouldRegisterNavigation = true;
    protected static string|UnitEnum|null $navigationGroup = 'System';

    public array $data = [];

    public function mount(): void
    {
        if (!Schema::connection('webkernel_sqlite')->hasTable('inst_webkernel_settings')) {
            return;
        }

        $settings = WebkernelSetting::all();
        foreach ($settings as $setting) {
            $this->data["{$setting->category}.{$setting->key}"] = $setting->resolvedValue();
        }

        $this->form->fill($this->data);
    }

    protected function getFormSchema(): array
    {
        if (!Schema::connection('webkernel_sqlite')->hasTable('inst_webkernel_settings')) {
            return [];
        }

        $settings = WebkernelSetting::all();
        $grouped = $settings->groupBy('category');

        $sections = [];

        foreach ($grouped as $category => $items) {
            $fields = [];

            foreach ($items as $setting) {
                $fieldKey = "{$setting->category}.{$setting->key}";

                $field = match($setting->type) {
                    'password' => TextInput::make($fieldKey)
                        ->label($setting->label)
                        ->password()
                        ->revealable()
                        ->hint($setting->description),

                    'boolean' => Toggle::make($fieldKey)
                        ->label($setting->label)
                        ->hint($setting->description),

                    'integer' => TextInput::make($fieldKey)
                        ->label($setting->label)
                        ->integer()
                        ->hint($setting->description),

                    'select' => Select::make($fieldKey)
                        ->label($setting->label)
                        ->options($this->parseSelectOptions($setting->options_json))
                        ->hint($setting->description),

                    'textarea' => Textarea::make($fieldKey)
                        ->label($setting->label)
                        ->rows(5)
                        ->hint($setting->description),

                    default => TextInput::make($fieldKey)
                        ->label($setting->label)
                        ->hint($setting->description),
                };

                if ($setting->is_sensitive) {
                    $field->dehydrateStateUsing(fn($state) => $state ? '••••••••' : '');
                }

                $fields[] = $field;
            }

            $sections[] = Section::make($this->humanizeCategory($category))
                ->schema($fields)
                ->collapsible();
        }

        return $sections;
    }

    public function saveSettings(): void
    {
        $data = $this->form->getState();

        foreach ($data as $dotKey => $value) {
            WebkernelSetting::set($dotKey, $value, filament()->auth()?->user()?->email ?? 'system');
        }

        Notification::make()
            ->title('Settings saved')
            ->success()
            ->send();
    }

    /** @return array<int, Action> */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('defaults')
                ->label('Set Defaults')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->outlined()
                ->requiresConfirmation()
                ->modalHeading('Reset to Default Settings?')
                ->modalDescription('This will reload all default settings from the system. Any custom values will be preserved if they have defaults defined.')
                ->modalSubmitActionLabel('Reset')
                ->action(fn() => $this->setDefaults()),

            Action::make('save')
                ->label('Save Settings')
                ->icon('heroicon-o-check')
                ->color('primary')
                ->action(fn() => $this->saveSettings()),
        ];
    }

    public function setDefaults(): void
    {
        WebkernelSetting::seedDefaults();

        $this->mount();
        $this->form->fill($this->data);

        Notification::make()
            ->title('Default settings loaded')
            ->success()
            ->send();
    }

    public static function getNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return 'heroicon-o-cog-6-tooth';
    }

    public static function getNavigationLabel(): string
    {
        return 'Instance Settings';
    }

    public function getTitle(): string|Htmlable
    {
        return 'Instance Settings';
    }

    private function parseSelectOptions(?string $json): array
    {
        if (!$json) {
            return [];
        }

        $options = json_decode($json, true);
        if (!is_array($options)) {
            return [];
        }

        $result = [];
        foreach ($options as $opt) {
            if (isset($opt['value']) && isset($opt['label'])) {
                $result[$opt['value']] = $opt['label'];
            }
        }

        return $result;
    }

    private function humanizeCategory(string $category): string
    {
        return match($category) {
            'smtp' => 'Email (SMTP)',
            'app' => 'Application',
            'security' => 'Security',
            'backups' => 'Backups',
            default => str($category)->headline()->toString(),
        };
    }
}
