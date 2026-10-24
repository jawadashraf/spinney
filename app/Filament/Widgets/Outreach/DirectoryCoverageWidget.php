<?php

declare(strict_types=1);

namespace App\Filament\Widgets\Outreach;

use App\Enums\CompanyStatus;
use App\Enums\CompanyVerificationStatus;
use App\Filament\Resources\CompanyResource;
use App\Models\Company;
use App\Models\CompanyType;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/**
 * How well each kind of company is covered: how many are active, reachable by email and verified.
 */
final class DirectoryCoverageWidget extends TableWidget
{
    protected static bool $isDiscovered = false;

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    public function getHeading(): string
    {
        return 'Directory coverage by type';
    }

    public function table(Table $table): Table
    {
        $active = fn (Builder $query): Builder => $query->where('status', CompanyStatus::Active);

        return $table
            ->query(fn (): Builder => CompanyType::query()
                ->where('team_id', Filament::getTenant()?->getKey())
                ->withCount([
                    'companies as active_count' => $active,
                    'companies as reachable_count' => fn (Builder $query): Builder => $active($query)
                        ->whereIn('companies.id', Company::query()->reachableByEmail()->select('companies.id')),
                    'companies as verified_count' => fn (Builder $query): Builder => $active($query)->where('verification_status', CompanyVerificationStatus::Verified),
                    'companies as needs_checking_count' => fn (Builder $query): Builder => $active($query)->where('verification_status', CompanyVerificationStatus::NeedsChecking),
                ]))
            ->defaultSort('active_count', 'desc')
            ->paginated(false)
            ->columns([
                TextColumn::make('name')
                    ->label('Type')
                    ->url(fn (CompanyType $record): string => CompanyResource::getUrl('index', [
                        'tableFilters' => ['company_type_id' => ['values' => [$record->id]]],
                    ])),
                TextColumn::make('active_count')
                    ->label('Companies')
                    ->numeric()
                    ->sortable()
                    ->url(fn (CompanyType $record): string => CompanyResource::getUrl('index', [
                        'tableFilters' => ['company_type_id' => ['values' => [$record->id]]],
                    ])),
                TextColumn::make('reachable_count')
                    ->label('Reachable by email')
                    ->numeric()
                    ->sortable()
                    ->description(fn (CompanyType $record): string => self::percentOf($record->getAttribute('reachable_count'), $record->getAttribute('active_count')))
                    ->url(fn (CompanyType $record): string => CompanyResource::getUrl('index', [
                        'tableFilters' => [
                            'company_type_id' => ['values' => [$record->id]],
                            'reachable_by_email' => ['isActive' => true],
                        ],
                    ])),
                TextColumn::make('verified_count')
                    ->label('Verified')
                    ->numeric()
                    ->sortable()
                    ->description(fn (CompanyType $record): string => self::percentOf($record->getAttribute('verified_count'), $record->getAttribute('active_count')))
                    ->url(fn (CompanyType $record): string => CompanyResource::getUrl('index', [
                        'tableFilters' => [
                            'company_type_id' => ['values' => [$record->id]],
                            'verification_status' => ['value' => CompanyVerificationStatus::Verified->value],
                        ],
                    ])),
                TextColumn::make('needs_checking_count')
                    ->label('Need checking')
                    ->numeric()
                    ->sortable()
                    ->color(fn (mixed $state): string => (int) $state > 0 ? 'warning' : 'gray')
                    ->url(fn (CompanyType $record): string => CompanyResource::getUrl('index', [
                        'tableFilters' => [
                            'company_type_id' => ['values' => [$record->id]],
                            'verification_status' => ['value' => CompanyVerificationStatus::NeedsChecking->value],
                        ],
                    ])),
            ])
            ->recordUrl(fn (CompanyType $record): string => CompanyResource::getUrl('index', [
                'tableFilters' => ['company_type_id' => ['values' => [$record->id]]],
            ]))
            ->headerActions([
                Action::make('view_all_companies')
                    ->label('View all in Companies')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->url(fn (): string => CompanyResource::getUrl('index')),
            ]);
    }

    private static function percentOf(mixed $part, mixed $whole): string
    {
        return (int) $whole > 0 ? round((int) $part / (int) $whole * 100).'%' : '-';
    }

    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', Company::class) ?? false;
    }
}
