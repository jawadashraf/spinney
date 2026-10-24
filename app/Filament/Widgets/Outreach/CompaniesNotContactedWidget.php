<?php

declare(strict_types=1);

namespace App\Filament\Widgets\Outreach;

use App\Enums\CompanyStatus;
use App\Enums\CompanyVerificationStatus;
use App\Enums\OutreachRecipientStatus;
use App\Filament\Resources\CompanyResource;
use App\Models\Company;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/**
 * Active companies with no logged engagement and no outreach email in the last six months: who to contact next.
 */
final class CompaniesNotContactedWidget extends TableWidget
{
    public const int MONTHS = 6;

    protected static bool $isDiscovered = false;

    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    public function getHeading(): string
    {
        return 'Not contacted in the last '.self::MONTHS.' months';
    }

    public function table(Table $table): Table
    {
        $since = now()->subMonthsNoOverflow(self::MONTHS);

        return $table
            ->query(fn (): Builder => Company::query()
                ->where('team_id', Filament::getTenant()?->getKey())
                ->where('status', CompanyStatus::Active)
                ->whereDoesntHave('engagements', fn (Builder $query): Builder => $query->where('occurred_on', '>=', $since->startOfDay()))
                ->whereDoesntHave('outreachRecipients', fn (Builder $query): Builder => $query
                    ->where('status', OutreachRecipientStatus::Sent)
                    ->where('sent_at', '>=', $since))
                ->with('companyType')
                ->withMax('engagements', 'occurred_on'))
            ->defaultSort('name')
            ->paginated([10, 25, 50])
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->url(fn (Company $record): string => CompanyResource::getUrl('view', ['record' => $record])),
                TextColumn::make('companyType.name')
                    ->label('Type')
                    ->badge()
                    ->url(fn (Company $record): ?string => $record->company_type_id ? CompanyResource::getUrl('index', [
                        'tableFilters' => ['company_type_id' => ['values' => [$record->company_type_id]]],
                    ]) : null),
                TextColumn::make('email')
                    ->label('General email')
                    ->placeholder('No email'),
                TextColumn::make('phone')
                    ->placeholder('-'),
                TextColumn::make('engagements_max_occurred_on')
                    ->label('Last engagement')
                    ->date()
                    ->placeholder('Never'),
                TextColumn::make('verification_status')
                    ->label('Verification')
                    ->badge()
                    ->url(fn (Company $record): ?string => $record->verification_status ? CompanyResource::getUrl('index', [
                        'tableFilters' => ['verification_status' => ['value' => $record->verification_status->value]],
                    ]) : null),
            ])
            ->recordUrl(fn (Company $record): string => CompanyResource::getUrl('view', ['record' => $record]))
            ->headerActions([
                Action::make('view_all_companies')
                    ->label('View all in Companies')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->url(fn (): string => CompanyResource::getUrl('index')),
            ])
            ->filters([
                SelectFilter::make('company_type_id')
                    ->label('Type')
                    ->relationship('companyType', 'name', fn (Builder $query): Builder => $query->where('team_id', Filament::getTenant()?->getKey())),
                SelectFilter::make('verification_status')
                    ->label('Verification')
                    ->options(CompanyVerificationStatus::class),
            ]);
    }

    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', Company::class) ?? false;
    }
}
