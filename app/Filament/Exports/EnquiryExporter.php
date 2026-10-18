<?php

declare(strict_types=1);

namespace App\Filament\Exports;

use App\Enums\CallerType;
use App\Enums\EnquiryCallType;
use App\Enums\EnquiryCategory;
use App\Enums\EnquiryDirection;
use App\Enums\EnquiryOutcome;
use App\Enums\EnquirySourceType;
use App\Enums\EnquiryStatus;
use App\Models\Enquiry;
use Carbon\Carbon;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Number;

/**
 * Exports enquiries as filtered on the list, scoped to the current team.
 */
final class EnquiryExporter extends BaseExporter
{
    protected static ?string $model = Enquiry::class;

    /**
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    public static function modifyQuery(Builder $query): Builder
    {
        return $query->with(['people', 'user', 'department', 'team']);
    }

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),
            ExportColumn::make('occurred_at')
                ->label('Occurred At')
                ->formatStateUsing(fn (?Carbon $state): ?string => $state?->format('Y-m-d H:i:s')),
            ExportColumn::make('direction')
                ->formatStateUsing(fn (?EnquiryDirection $state): ?string => $state?->getLabel()),
            ExportColumn::make('call_type')
                ->label('Call Type')
                ->formatStateUsing(fn (?EnquiryCallType $state): ?string => $state?->getLabel()),
            ExportColumn::make('category')
                ->formatStateUsing(fn (?EnquiryCategory $state): ?string => $state?->getLabel()),
            ExportColumn::make('caller_type')
                ->label('Caller Type')
                ->formatStateUsing(fn (?CallerType $state): ?string => $state?->getLabel()),
            ExportColumn::make('caller_name')
                ->label('Caller')
                ->state(fn (Enquiry $record): string => $record->people?->name ?? 'Anonymous'),
            ExportColumn::make('phone')
                ->label('Phone'),
            ExportColumn::make('reason_for_contact')
                ->label('Reason for Contact'),
            ExportColumn::make('safeguarding_flags')
                ->label('Safeguarding')
                ->formatStateUsing(fn (bool $state): string => $state ? 'Yes' : 'No'),
            ExportColumn::make('advice_given')
                ->label('Advice Given'),
            ExportColumn::make('action_taken')
                ->label('Action Taken'),
            ExportColumn::make('referral_type')
                ->label('Referral Type'),
            ExportColumn::make('referral_destination')
                ->label('Referral Destination'),
            ExportColumn::make('user.name')
                ->label('Staff Member'),
            ExportColumn::make('department.name')
                ->label('Department'),
            ExportColumn::make('due_date')
                ->label('Due Date')
                ->formatStateUsing(fn (?Carbon $state): ?string => $state?->format('Y-m-d H:i:s')),
            ExportColumn::make('status')
                ->formatStateUsing(fn (?EnquiryStatus $state): ?string => $state?->getLabel()),
            ExportColumn::make('outcome')
                ->formatStateUsing(fn (?EnquiryOutcome $state): ?string => $state?->getLabel()),
            ExportColumn::make('source')
                ->formatStateUsing(fn (?EnquirySourceType $state): ?string => $state?->getLabel()),
            ExportColumn::make('created_at')
                ->label('Created At')
                ->formatStateUsing(fn (Carbon $state): string => $state->format('Y-m-d H:i:s')),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your enquiry export has completed and '.Number::format($export->successful_rows).' '.str('row')->plural($export->successful_rows).' exported.';

        if (($failedRowsCount = $export->getFailedRowsCount()) !== 0) {
            $body .= ' '.Number::format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to export.';
        }

        return $body;
    }
}
