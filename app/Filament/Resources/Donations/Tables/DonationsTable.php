<?php

declare(strict_types=1);

namespace App\Filament\Resources\Donations\Tables;

use App\Enums\DonationPurpose;
use App\Enums\GiftAidStatus;
use App\Models\Company;
use App\Models\Donation;
use App\Models\People;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\BaseFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Carbon;

/**
 * The donations list doubles as the donation report (AC DN-003): filter by date, donor and Gift Aid status,
 * group by donor, purpose or Gift Aid status, and read the totals underneath.
 */
final class DonationsTable
{
    /**
     * Gift Aid adds 25p for every £1 given (basic rate tax at 20%).
     */
    public const float GIFT_AID_RATE = 0.25;

    public static function configure(Table $table): Table
    {
        return $table
            ->columns(self::columns(withDonor: true))
            ->defaultSort('donated_at', 'desc')
            ->filters([
                self::dateFilter(),
                Filter::make('donor')
                    ->schema([
                        Select::make('donor')
                            ->label('Donor')
                            ->searchable()
                            ->getSearchResultsUsing(fn (string $search): array => self::searchDonors($search))
                            ->getOptionLabelUsing(fn (?string $value): ?string => self::donorLabel($value)),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $donor = self::parseDonorKey($data['donor'] ?? null);

                        return $donor === null
                            ? $query
                            : $query->where('donor_type', $donor[0])->where('donor_id', $donor[1]);
                    })
                    ->indicateUsing(fn (array $data): ?string => filled($label = self::donorLabel($data['donor'] ?? null)) ? "Donor: {$label}" : null),
                ...self::sharedFilters(),
            ])
            ->groups([
                Group::make('donor')
                    ->label('Donor')
                    ->getKeyFromRecordUsing(fn (Donation $record): string => "{$record->donor_type}:{$record->donor_id}")
                    ->getTitleFromRecordUsing(fn (Donation $record): string => $record->donorName())
                    ->groupQueryUsing(fn (QueryBuilder $query) => $query->groupBy('donor_type', 'donor_id'))
                    ->orderQueryUsing(fn (Builder $query, string $direction) => $query
                        ->orderBy('donor_type', $direction === 'desc' ? 'desc' : 'asc')
                        ->orderBy('donor_id', $direction === 'desc' ? 'desc' : 'asc'))
                    ->scopeQueryUsing(fn (Builder $query, Donation $record) => $query->where('donor_type', $record->donor_type)->where('donor_id', $record->donor_id))
                    ->scopeQueryByKeyUsing(function (Builder $query, ?string $key): Builder {
                        $donor = self::parseDonorKey($key);

                        return $donor === null
                            ? $query->whereRaw('1 = 0')
                            : $query->where('donor_type', $donor[0])->where('donor_id', $donor[1]);
                    }),
                Group::make('purpose')
                    ->label('Purpose'),
                Group::make('gift_aid_status')
                    ->label('Gift Aid'),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ]);
    }

    /**
     * @return list<Column>
     */
    public static function columns(bool $withDonor): array
    {
        return [
            TextColumn::make('donated_at')
                ->label('Date')
                ->date()
                ->sortable(),
            ...$withDonor ? [
                TextColumn::make('donor_name')
                    ->label('Donor')
                    ->state(fn (Donation $record): string => $record->donorName())
                    ->description(fn (Donation $record): string => $record->donor_type === 'company' ? 'Company' : 'Person')
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->whereHasMorph(
                        'donor',
                        [People::class, Company::class],
                        fn (Builder $donorQuery, string $type) => $type === People::class
                            ? $donorQuery->where(fn (Builder $nameQuery) => $nameQuery
                                ->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('name', 'like', "%{$search}%"))
                            : $donorQuery->where('name', 'like', "%{$search}%"),
                    )),
            ] : [],
            TextColumn::make('amount')
                ->money('GBP')
                ->sortable()
                ->summarize([
                    Sum::make('total')
                        ->label('Total')
                        ->money('GBP'),
                    Summarizer::make('gift_aid_value')
                        ->label('Gift Aid value')
                        ->money('GBP')
                        ->using(fn (QueryBuilder $query): float => round(
                            (float) $query->whereIn('gift_aid_status', [GiftAidStatus::Eligible->value, GiftAidStatus::Claimed->value])->sum('amount') * self::GIFT_AID_RATE,
                            2,
                        )),
                ]),
            TextColumn::make('purpose')
                ->badge()
                ->color('gray'),
            TextColumn::make('gift_aid_status')
                ->label('Gift Aid')
                ->badge()
                ->sortable(),
            TextColumn::make('payment_method')
                ->label('Paid by')
                ->toggleable(),
            TextColumn::make('reference')
                ->placeholder('-')
                ->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('creator.name')
                ->label('Recorded by')
                ->placeholder('-')
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    /**
     * @return list<BaseFilter>
     */
    public static function sharedFilters(): array
    {
        return [
            SelectFilter::make('gift_aid_status')
                ->label('Gift Aid')
                ->options(GiftAidStatus::class)
                ->multiple(),
            SelectFilter::make('purpose')
                ->options(DonationPurpose::class)
                ->multiple(),
        ];
    }

    public static function dateFilter(): Filter
    {
        return Filter::make('donated_at')
            ->schema([
                DatePicker::make('from')
                    ->label('Received from'),
                DatePicker::make('until')
                    ->label('Received until'),
            ])
            ->query(fn (Builder $query, array $data): Builder => $query
                ->when($data['from'] ?? null, fn (Builder $query, string $date) => $query->whereDate('donated_at', '>=', $date))
                ->when($data['until'] ?? null, fn (Builder $query, string $date) => $query->whereDate('donated_at', '<=', $date)))
            ->indicateUsing(fn (array $data): array => array_values(array_filter([
                filled($data['from'] ?? null) ? 'From '.Carbon::parse($data['from'])->toFormattedDateString() : null,
                filled($data['until'] ?? null) ? 'Until '.Carbon::parse($data['until'])->toFormattedDateString() : null,
            ])));
    }

    /**
     * @return array<string, string>
     */
    private static function searchDonors(string $search): array
    {
        $teamId = Filament::getTenant()?->getKey();

        $people = People::query()
            ->where('team_id', $teamId)
            ->where(fn (Builder $query) => $query
                ->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%"))
            ->limit(25)
            ->get()
            ->mapWithKeys(fn (People $person): array => ["people:{$person->id}" => $person->name]);

        $companies = Company::query()
            ->where('team_id', $teamId)
            ->where('name', 'like', "%{$search}%")
            ->limit(25)
            ->pluck('name', 'id')
            ->mapWithKeys(fn (string $name, int $id): array => ["company:{$id}" => "{$name} (company)"]);

        return $people->merge($companies)->all();
    }

    private static function donorLabel(?string $key): ?string
    {
        $donor = self::parseDonorKey($key);

        if ($donor === null) {
            return null;
        }

        $teamId = Filament::getTenant()?->getKey();

        return $donor[0] === 'people'
            ? People::query()->where('team_id', $teamId)->find($donor[1])?->name
            : Company::query()->where('team_id', $teamId)->find($donor[1])?->name;
    }

    /**
     * Read a "people:12" or "company:3" donor key, ignoring anything else.
     *
     * @return array{0: 'people'|'company', 1: int}|null
     */
    private static function parseDonorKey(?string $key): ?array
    {
        if (blank($key) || preg_match('/^(people|company):(\d+)$/', $key, $matches) !== 1) {
            return null;
        }

        /** @var 'people'|'company' $type */
        $type = $matches[1];

        return [$type, (int) $matches[2]];
    }
}
