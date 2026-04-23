<?php

namespace Webkernel\BackOffice\System\Presentation\Resources\DependencyManager\Pages;

use Carbon\Carbon;
use Webkernel\BackOffice\System\Presentation\Resources\DependencyManager\Models\ComposerPackage;
use Webkernel\BackOffice\System\Presentation\Resources\DependencyManager\Services\ComposerService;
use Webkernel\BackOffice\System\Presentation\Resources\DependencyManager\Actions\UpdateComposerPackageAction;
use Webkernel\BackOffice\System\Presentation\Resources\DependencyManager\Actions\UpdateAllComposerPackagesAction;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DependencyManagerPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $slug = 'composer-manager';

    protected static ?int $navigationSort = 1;

    protected string $view = 'webkernel-system::pages.dependency-manager';

    public function getTitle(): string
    {
        return 'Composer Dependencies';
    }

    public static function getNavigationLabel(): string
    {
        return 'Composer';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Maintenance';
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-code-bracket-square';
    }

    public static function getNavigationBadge(): ?string
    {
        $count = count(app(ComposerService::class)->getOutdatedPackages());

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(ComposerPackage::query())
            ->columns([
                TextColumn::make('name')
                    ->label('Package')
                    ->weight('bold')
                    ->searchable()
                    ->sortable()
                    ->url(fn (ComposerPackage $record) => "https://packagist.org/packages/{$record->name}")
                    ->openUrlInNewTab(),

                TextColumn::make('version')
                    ->label('Installed')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('latest')
                    ->label('Latest')
                    ->badge()
                    ->color('success'),

                TextColumn::make('latest-status')
                    ->label('Update Type')
                    ->badge()
                    ->sortable()
                    ->color(fn (string $state) => match ($state) {
                        'semver-safe-update' => 'warning',
                        'update-possible' => 'danger',
                        default => 'success',
                    })
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'semver-safe-update' => 'Minor Update',
                        'update-possible' => 'Major Update',
                        default => 'Up to date',
                    }),

                TextColumn::make('latest-release-date')
                    ->label('Last Updated')
                    ->formatStateUsing(
                        fn ($state) => $state ? Carbon::parse($state)->diffForHumans() : '—'
                    ),

                TextColumn::make('description')
                    ->label('Description')
                    ->limit(50)
                    ->tooltip(fn ($state) => $state)
                    ->color('gray'),
            ])
            ->actions([
                UpdateComposerPackageAction::make(),

                Action::make('changelog')
                    ->label('Changelog')
                    ->icon('heroicon-o-document-text')
                    ->color('info')
                    ->url(fn (ComposerPackage $record) => "https://github.com/{$record->name}/releases")
                    ->openUrlInNewTab(),
            ])
            ->headerActions([
                UpdateAllComposerPackagesAction::make(),
                Action::make('refresh')
                    ->label('Refresh List')
                    ->icon('heroicon-o-arrow-path')
                    ->action(function () {
                        app(ComposerService::class)->clearCache();
                        $this->resetTable();
                    }),
            ])
            ->filters([
                SelectFilter::make('latest-status')
                    ->label('Update Type')
                    ->options([
                        'semver-safe-update' => 'Minor Update',
                        'update-possible' => 'Major Update',
                    ])
                    ->query(
                        fn (Builder $query, array $data) => $query->when($data['value'] ?? null, fn ($q) => $q->where('latest-status', $data['value']))
                    ),
            ])
            ->emptyStateHeading('No packages found')
            ->emptyStateDescription('All packages are up to date or no packages are installed.')
            ->emptyStateIcon('heroicon-o-check-circle');
    }
}
