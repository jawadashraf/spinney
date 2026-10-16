<?php

declare(strict_types=1);

namespace App\Filament\Exports;

use App\Enums\CompanyStatus;
use App\Enums\CompanyVerificationStatus;
use App\Models\Company;
use App\Support\CustomFields;
use Carbon\Carbon;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Models\Export;

final class CompanyExporter extends BaseExporter
{
    protected static ?string $model = Company::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),
            ExportColumn::make('name')
                ->label('Company Name'),
            ExportColumn::make('companyType.name')
                ->label('Type'),
            ExportColumn::make('status')
                ->formatStateUsing(fn (CompanyStatus $state): string => $state->getLabel()),
            ExportColumn::make('parentCompany.name')
                ->label('Part Of'),
            ExportColumn::make('verification_status')
                ->label('Verification')
                ->formatStateUsing(fn (CompanyVerificationStatus $state): string => $state->getLabel()),
            ExportColumn::make('verified_at')
                ->label('Verified At'),
            ExportColumn::make('verification_note')
                ->label('Verification Note'),
            ExportColumn::make('source'),
            ExportColumn::make('email')
                ->label('General Email'),
            ExportColumn::make('phone'),
            ExportColumn::make('website'),
            ExportColumn::make('address'),
            ExportColumn::make('postcode'),
            ExportColumn::make('mailing_groups')
                ->label('Mailing Groups')
                ->state(fn (Company $company): string => $company->mailingGroups->pluck('name')->implode(', ')),
            ExportColumn::make('contacts_count')
                ->label('Number of Contacts')
                ->counts('contacts'),
            ExportColumn::make('email_invalid_at')
                ->label('Email Invalid Since'),
            ExportColumn::make('unsubscribed_at')
                ->label('Unsubscribed At'),
            ExportColumn::make('accountOwner.name')
                ->label('Staff Lead'),
            ExportColumn::make('creator.name')
                ->label('Created By'),
            ExportColumn::make('created_at')
                ->label('Created At')
                ->formatStateUsing(fn (Carbon $state): string => $state->format('Y-m-d H:i:s')),
            ExportColumn::make('updated_at')
                ->label('Updated At')
                ->formatStateUsing(fn (Carbon $state): string => $state->format('Y-m-d H:i:s')),
            ExportColumn::make('creation_source')
                ->label('Creation Source')
                ->formatStateUsing(fn (mixed $state): string => $state->value ?? (string) $state),

            // Add all custom fields automatically
            ...CustomFields::exporter()->forModel(self::getModel())->columns(),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your company export has completed and '.number_format($export->successful_rows).' '.str('row')->plural($export->successful_rows).' exported.';

        if (($failedRowsCount = $export->getFailedRowsCount()) !== 0) {
            $body .= ' '.number_format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to export.';
        }

        return $body;
    }
}
