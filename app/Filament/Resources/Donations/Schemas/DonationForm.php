<?php

declare(strict_types=1);

namespace App\Filament\Resources\Donations\Schemas;

use App\Enums\CreationSource;
use App\Enums\DonationPaymentMethod;
use App\Enums\DonationPurpose;
use App\Enums\GiftAidStatus;
use App\Models\Company;
use App\Models\GiftAidDeclaration;
use App\Models\People;
use App\Support\PhoneNumber;
use Closure;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\MorphToSelect;
use Filament\Forms\Components\MorphToSelect\Type;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

final class DonationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                MorphToSelect::make('donor')
                    ->label('Donor')
                    ->types([
                        Type::make(People::class)
                            ->label('Person')
                            ->titleAttribute('first_name')
                            ->searchColumns(['first_name', 'last_name', 'name', 'email'])
                            ->getOptionLabelFromRecordUsing(fn (People $person): string => $person->name)
                            ->modifyOptionsQueryUsing(fn (Builder $query) => $query->where('team_id', Filament::getTenant()?->getKey()))
                            ->modifyKeySelectUsing(fn (Select $select) => $select
                                ->createOptionForm([
                                    TextInput::make('first_name')->required()->maxLength(255)->live(onBlur: true),
                                    TextInput::make('last_name')->required()->maxLength(255)->live(onBlur: true),
                                    TextInput::make('email')->email()->maxLength(255)->live(onBlur: true),
                                    TextInput::make('phone')->tel()->maxLength(50)->live(onBlur: true),
                                    TextInput::make('postcode')->maxLength(20),
                                    Textarea::make('address')->rows(2),
                                    self::possibleDuplicatesCallout(fn (Get $get): Collection => self::peopleMatching(
                                        $get('first_name'),
                                        $get('last_name'),
                                        $get('email'),
                                        $get('phone'),
                                    )->map(fn (People $person): string => self::describeMatch($person->name, $person->email, $person->postcode))),
                                ])
                                ->createOptionUsing(function (array $data): int|string {
                                    $person = new People;
                                    $person->forceFill([
                                        ...$data,
                                        'team_id' => Filament::getTenant()?->getKey(),
                                        'creation_source' => CreationSource::WEB,
                                    ]);
                                    $person->save();

                                    return $person->getKey();
                                })
                                ->createOptionModalHeading('Add a new person')
                            ),
                        Type::make(Company::class)
                            ->label('Company')
                            ->titleAttribute('name')
                            ->searchColumns(['name', 'email'])
                            ->modifyOptionsQueryUsing(fn (Builder $query) => $query->where('team_id', Filament::getTenant()?->getKey()))
                            ->modifyKeySelectUsing(fn (Select $select) => $select
                                ->createOptionForm([
                                    TextInput::make('name')->required()->maxLength(255)->live(onBlur: true),
                                    TextInput::make('email')->email()->maxLength(255)->live(onBlur: true),
                                    TextInput::make('phone')->tel()->maxLength(50),
                                    self::possibleDuplicatesCallout(fn (Get $get): Collection => self::companiesMatching(
                                        $get('name'),
                                        $get('email'),
                                    )->map(fn (Company $company): string => self::describeMatch($company->name, $company->email))),
                                ])
                                ->createOptionUsing(function (array $data): int|string {
                                    $company = new Company;
                                    $company->forceFill([
                                        ...$data,
                                        'team_id' => Filament::getTenant()?->getKey(),
                                        'creation_source' => CreationSource::WEB,
                                    ]);
                                    $company->save();

                                    return $company->getKey();
                                })
                                ->createOptionModalHeading('Add a new company')
                            ),
                    ])
                    ->typeSelectToggleButtons()
                    ->modifyTypeSelectUsing(fn (ToggleButtons $toggleButtons): ToggleButtons => $toggleButtons->default('people'))
                    ->searchable()
                    ->live()
                    ->required()
                    ->columnSpanFull(),
                ...self::detailFields(fn (Get $get): ?Model => self::donorFromState($get('donor_type'), $get('donor_id'))),
            ])
            ->columns(2);
    }

    /**
     * The fields for a donation whose donor is already chosen, shared with the donations tabs on people and
     * companies. Gift Aid is offered only for people, and only with a declaration covering the date.
     *
     * @param  Closure(Get): (Model|null)  $donor
     * @return list<Component>
     */
    public static function detailFields(Closure $donor): array
    {
        return [
            TextInput::make('amount')
                ->numeric()
                ->prefix('£')
                ->minValue(0.01)
                ->required(),
            DatePicker::make('donated_at')
                ->label('Date received')
                ->default(today())
                ->maxDate(today())
                ->required()
                ->live(),
            Select::make('payment_method')
                ->options(DonationPaymentMethod::class)
                ->required(),
            Select::make('purpose')
                ->options(DonationPurpose::class)
                ->default(DonationPurpose::General)
                ->required(),
            Select::make('gift_aid_status')
                ->label('Gift Aid')
                ->options(GiftAidStatus::class)
                ->placeholder('Set from their declaration')
                ->helperText('Leave blank to mark it eligible when a Gift Aid declaration covers the date.')
                ->visible(fn (Get $get): bool => $donor($get) instanceof People)
                ->rules([
                    fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($donor, $get): void {
                        $status = $value instanceof GiftAidStatus ? $value : GiftAidStatus::tryFrom((string) $value);
                        $person = $donor($get);

                        if (! $status?->needsDeclaration() || ! $person instanceof People || blank($get('donated_at'))) {
                            return;
                        }

                        if (! $person->giftAidDeclarationCovering(Carbon::parse($get('donated_at'))) instanceof GiftAidDeclaration) {
                            $fail('No Gift Aid declaration from this donor covers this date.');
                        }
                    },
                ]),
            TextInput::make('reference')
                ->helperText('Receipt number or bank reference.')
                ->maxLength(255),
            Textarea::make('notes')
                ->rows(3)
                ->columnSpanFull(),
        ];
    }

    /**
     * Warns before a donor is added again, so their gifts and Gift Aid declarations stay on one record.
     *
     * @param  Closure(Get): Collection<int, string>  $matches
     */
    private static function possibleDuplicatesCallout(Closure $matches): Callout
    {
        return Callout::make('Already on record?')
            ->key('possibleDuplicates')
            ->warning()
            ->description(fn (Get $get): string => 'Close this window and search for them instead of adding them again: '.$matches($get)->implode('; ').'.')
            ->visible(fn (Get $get): bool => $matches($get)->isNotEmpty());
    }

    /**
     * People in this team with the same email, phone number or full name.
     *
     * @return Collection<int, People>
     */
    private static function peopleMatching(?string $firstName, ?string $lastName, ?string $email, ?string $phone): Collection
    {
        $phoneNormalized = PhoneNumber::normalize($phone);
        $hasFullName = filled($firstName) && filled($lastName);

        if (! $hasFullName && blank($email) && blank($phoneNormalized)) {
            return collect();
        }

        return People::query()
            ->where('team_id', Filament::getTenant()?->getKey())
            ->where(fn (Builder $query) => $query
                ->when(filled($email), fn (Builder $query) => $query->orWhereRaw('LOWER(email) = ?', [mb_strtolower(mb_trim((string) $email))]))
                ->when(filled($phoneNormalized), fn (Builder $query) => $query->orWhere('phone_normalized', $phoneNormalized))
                ->when($hasFullName, fn (Builder $query) => $query->orWhere(fn (Builder $query) => $query
                    ->whereRaw('LOWER(first_name) = ?', [mb_strtolower(mb_trim((string) $firstName))])
                    ->whereRaw('LOWER(last_name) = ?', [mb_strtolower(mb_trim((string) $lastName))]))))
            ->limit(5)
            ->get();
    }

    /**
     * Companies in this team with the same name or email.
     *
     * @return Collection<int, Company>
     */
    private static function companiesMatching(?string $name, ?string $email): Collection
    {
        if (blank($name) && blank($email)) {
            return collect();
        }

        return Company::query()
            ->where('team_id', Filament::getTenant()?->getKey())
            ->where(fn (Builder $query) => $query
                ->when(filled($name), fn (Builder $query) => $query->orWhereRaw('LOWER(name) = ?', [mb_strtolower(mb_trim((string) $name))]))
                ->when(filled($email), fn (Builder $query) => $query->orWhereRaw('LOWER(email) = ?', [mb_strtolower(mb_trim((string) $email))])))
            ->limit(5)
            ->get();
    }

    private static function describeMatch(string $name, ?string ...$details): string
    {
        $details = array_filter($details, filled(...));

        return $details === [] ? $name : $name.' ('.implode(', ', $details).')';
    }

    private static function donorFromState(mixed $type, mixed $id): ?Model
    {
        $model = match ($type) {
            'people' => People::class,
            'company' => Company::class,
            default => null,
        };

        if ($model === null || blank($id)) {
            return null;
        }

        return $model::query()->where('team_id', Filament::getTenant()?->getKey())->whereKey($id)->first();
    }
}
