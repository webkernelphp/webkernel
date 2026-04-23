<?php

namespace Webkernel\Builders\DBStudio\FieldTypes\Types;

use Filament\Forms\Components\Hidden;
use Filament\Schemas\Components\Component;
use Filament\Tables\Columns\Column;
use Filament\Tables\Filters\BaseFilter;
use Webkernel\Builders\DBStudio\Enums\EavCast;
use Webkernel\Builders\DBStudio\FieldTypes\AbstractFieldType;

class HiddenFieldType extends AbstractFieldType
{
    public static string $key = 'hidden';

    public static string $label = 'Hidden';

    public static string $icon = 'heroicon-o-eye-slash';

    public static EavCast $eavCast = EavCast::Text;

    public static string $category = 'presentation';

    public static function settingsSchema(): array
    {
        return [];
    }

    public function toFilamentComponent(): Component
    {
        return Hidden::make($this->field->column_name);
    }

    public function toTableColumn(): ?Column
    {
        return null;
    }

    public function toFilter(): ?BaseFilter
    {
        return null;
    }
}
