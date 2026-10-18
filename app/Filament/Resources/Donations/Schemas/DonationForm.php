<?php

declare(strict_types=1);

namespace App\Filament\Resources\Donations\Schemas;

use App\Enums\DonationPaymentMethod;
use App\Enums\DonationPurpose;
use App\Enums\GiftAidStatus;
use App\Models\Company;
use App\Models\GiftAidDeclaration;
use App\Models\People;
use Closure;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\MorphToSelect;
use Filament\Forms\Components\MorphToSelect\Type;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

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
                            ->modifyOptionsQueryUsing(fn (Builder $query) => $query->where('team_id', Filament::getTenant()?->getKey())),
                        Type::make(Company::class)
                            ->label('Company')
                            ->titleAttribute('name')
                            ->searchColumns(['name', 'email'])
                            ->modifyOptionsQueryUsing(fn (Builder $query) => $query->where('team_id', Filament::getTenant()?->getKey())),
                    ])
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
