<?php

namespace Webkernel\BackOffice\System\Presentation\Resources\DependencyManager\Pages;

use Webkernel\BackOffice\System\Presentation\Resources\DependencyManager\Models\NpmPackage;
use Webkernel\BackOffice\System\Presentation\Resources\DependencyManager\Services\NpmService;
use Webkernel\BackOffice\System\Presentation\Resources\DependencyManager\Actions\UpdateNpmPackageAction;
use Webkernel\BackOffice\System\Presentation\Resources\DependencyManager\Actions\UpdateAllNpmPackagesAction;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class NpmDependencyManagerPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $slug = 'npm-manager';

    protected static ?int $navigationSort = 2;

    protected string $view = 'webkernel-system::pages.npm-dependency-manager';

    public function getTitle(): string
    {
        return 'NPM Dependencies';
    }

    public static function getNavigationLabel(): string
    {
        return 'NPM';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Maintenance';
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-cube';
    }

    public static function getNavigationBadge(): ?string
    {
        $count = count(app(NpmService::class)->getOutdatedPackages());

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(NpmPackage::query())
            ->columns([
                TextColumn::make('name')
                    ->label('Package')
                    ->sortable()
                    ->searchable()
                    ->weight('bold'),

                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'dependencies' => 'info',
                        'devDependencies' => 'gray',
                        default => 'gray',
                    }),

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
            ])
            ->actions([
                UpdateNpmPackageAction::make(),

                Action::make('npm_page')
                    ->label('View on NPM')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('info')
                    ->url(fn (NpmPackage $record) => "https://www.npmjs.com/package/{$record->name}")
                    ->openUrlInNewTab(),
            ])
            ->headerActions([
                UpdateAllNpmPackagesAction::make(),
                Action::make('refresh')
                    ->label('Refresh List')
                    ->icon('heroicon-o-arrow-path')
                    ->action(function () {
                        $service = app(NpmService::class);
                        $service->clearCache();
                        \Illuminate\Support\Facades\Cache::forget('filament-dependency-manager:npm-all');
                        $this->resetTable();
                    }),
            ])
            ->filters([
                SelectFilter::make('has_update')
                    ->label('Status')
                    ->options([
                        '1' => 'Has Update',
                        '0' => 'Up to Date',
                    ])
                    ->query(
                        fn (Builder $query, array $data) => $query->when($data['value'] !== null, fn ($q) => $q->where('has_update', $data['value'] === '1'))
                    ),

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
