<?php

declare(strict_types=1);

namespace App\Filament\Exports;

use App\Enums\CallOutcome;
use App\Enums\CallStatus;
use App\Models\Call;
use Carbon\Carbon;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Number;

/**
 * Exports calls as filtered on the list, scoped to the current team.
 */
final class CallExporter extends BaseExporter
{
    protected static ?string $model = Call::class;

    /**
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    public static function modifyQuery(Builder $query): Builder
    {
        return $query->with(['serviceUser', 'assignee', 'completedBy', 'plan', 'team']);
    }

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),
            ExportColumn::make('serviceUser.name')
                ->label('Service User'),
            ExportColumn::make('serviceUser.phone')
                ->label('Service User Phone'),
            ExportColumn::make('plan.frequency')
                ->label('Plan Frequency'),
            ExportColumn::make('assignee.name')
                ->label('Liaison'),
            ExportColumn::make('reason')
                ->label('Reason'),
            ExportColumn::make('due_at')
                ->label('Due At')
                ->formatStateUsing(fn (?Carbon $state): ?string => $state?->format('Y-m-d H:i:s')),
            ExportColumn::make('status')
                ->formatStateUsing(fn (?CallStatus $state): ?string => $state?->getLabel()),
            ExportColumn::make('outcome')
                ->formatStateUsing(fn (?CallOutcome $state): ?string => $state?->getLabel()),
            ExportColumn::make('attempt_count')
                ->label('Attempts'),
            ExportColumn::make('last_attempt_at')
                ->label('Last Attempt At')
                ->formatStateUsing(fn (?Carbon $state): ?string => $state?->format('Y-m-d H:i:s')),
            ExportColumn::make('completed_at')
                ->label('Completed At')
                ->formatStateUsing(fn (?Carbon $state): ?string => $state?->format('Y-m-d H:i:s')),
            ExportColumn::make('completedBy.name')
                ->label('Completed By'),
            ExportColumn::make('safeguarding_raised')
                ->label('Safeguarding Raised')
                ->formatStateUsing(fn (bool $state): string => $state ? 'Yes' : 'No'),
            ExportColumn::make('notes')
                ->label('Notes'),
            ExportColumn::make('action_required')
                ->label('Action Required'),
            ExportColumn::make('next_follow_up_at')
                ->label('Next Follow-up At')
                ->formatStateUsing(fn (?Carbon $state): ?string => $state?->format('Y-m-d')),
            ExportColumn::make('created_at')
                ->label('Created At')
                ->formatStateUsing(fn (Carbon $state): string => $state->format('Y-m-d H:i:s')),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your call export has completed and '.Number::format($export->successful_rows).' '.str('row')->plural($export->successful_rows).' exported.';

        if (($failedRowsCount = $export->getFailedRowsCount()) !== 0) {
            $body .= ' '.Number::format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to export.';
        }

        return $body;
    }
}
