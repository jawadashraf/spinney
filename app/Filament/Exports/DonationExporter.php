<?php

declare(strict_types=1);

namespace App\Filament\Exports;

use App\Enums\DonationPaymentMethod;
use App\Enums\DonationPurpose;
use App\Enums\GiftAidStatus;
use App\Models\Donation;
use App\Models\People;
use Carbon\Carbon;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Donations as filtered on the list, with the donor's name and declared address so Gift Aid claims can be
 * prepared from it.
 */
final class DonationExporter extends BaseExporter
{
    protected static ?string $model = Donation::class;

    /**
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    public static function modifyQuery(Builder $query): Builder
    {
        return $query->with(['donor', 'giftAidDeclaration', 'creator']);
    }

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),
            ExportColumn::make('donated_at')
                ->label('Date Received')
                ->formatStateUsing(fn (Carbon $state): string => $state->format('Y-m-d')),
            ExportColumn::make('donor_name')
                ->label('Donor')
                ->state(fn (Donation $donation): string => $donation->donorName()),
            ExportColumn::make('donor_type')
                ->label('Donor Type')
                ->formatStateUsing(fn (string $state): string => $state === 'company' ? 'Company' : 'Person'),
            ExportColumn::make('first_name')
                ->label('First Name')
                ->state(fn (Donation $donation): ?string => $donation->donor instanceof People ? $donation->donor->first_name : null),
            ExportColumn::make('last_name')
                ->label('Last Name')
                ->state(fn (Donation $donation): ?string => $donation->donor instanceof People ? $donation->donor->last_name : null),
            ExportColumn::make('amount'),
            ExportColumn::make('purpose')
                ->formatStateUsing(fn (DonationPurpose $state): string => $state->getLabel()),
            ExportColumn::make('payment_method')
                ->label('Paid By')
                ->formatStateUsing(fn (DonationPaymentMethod $state): string => $state->getLabel()),
            ExportColumn::make('gift_aid_status')
                ->label('Gift Aid')
                ->formatStateUsing(fn (GiftAidStatus $state): string => $state->getLabel()),
            ExportColumn::make('giftAidDeclaration.declared_on')
                ->label('Declaration Date')
                ->formatStateUsing(fn (?Carbon $state): ?string => $state?->format('Y-m-d')),
            ExportColumn::make('giftAidDeclaration.address')
                ->label('Declared Address'),
            ExportColumn::make('giftAidDeclaration.postcode')
                ->label('Declared Postcode'),
            ExportColumn::make('reference'),
            ExportColumn::make('notes'),
            ExportColumn::make('creator.name')
                ->label('Recorded By'),
            ExportColumn::make('created_at')
                ->label('Recorded At')
                ->formatStateUsing(fn (Carbon $state): string => $state->format('Y-m-d H:i:s')),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your donation export has completed and '.number_format($export->successful_rows).' '.str('row')->plural($export->successful_rows).' exported.';

        if (($failedRowsCount = $export->getFailedRowsCount()) !== 0) {
            $body .= ' '.number_format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to export.';
        }

        return $body;
    }
}
