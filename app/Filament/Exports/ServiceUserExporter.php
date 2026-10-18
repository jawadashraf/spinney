<?php

declare(strict_types=1);

namespace App\Filament\Exports;

use App\Enums\EngagementStatus;
use App\Enums\ServiceTeam;
use App\Enums\SupportStatus;
use App\Models\People;
use App\Models\ServiceUser;
use App\Support\CustomFields;
use Carbon\Carbon;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Number;

/**
 * Exports service users with their case file and profile details, scoped to the current team.
 */
final class ServiceUserExporter extends BaseExporter
{
    protected static ?string $model = ServiceUser::class;

    /**
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    public static function modifyQuery(Builder $query): Builder
    {
        return $query->with(['profile', 'team']);
    }

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),
            ExportColumn::make('first_name')
                ->label('First Name'),
            ExportColumn::make('last_name')
                ->label('Last Name'),
            ExportColumn::make('name')
                ->label('Full Name'),
            ExportColumn::make('email')
                ->label('Email'),
            ExportColumn::make('phone')
                ->label('Phone'),
            ExportColumn::make('gender')
                ->label('Gender'),
            ExportColumn::make('date_of_birth')
                ->label('Date of Birth')
                ->formatStateUsing(fn (?Carbon $state): ?string => $state?->format('Y-m-d')),
            ExportColumn::make('address')
                ->label('Address'),
            ExportColumn::make('postcode')
                ->label('Postcode'),
            ExportColumn::make('profile.target_service_team')
                ->label('Service Team')
                ->formatStateUsing(fn (?ServiceTeam $state): ?string => $state?->getLabel()),
            ExportColumn::make('profile.engagement_status')
                ->label('Engagement Status')
                ->formatStateUsing(fn (?EngagementStatus $state): ?string => $state?->getLabel()),
            ExportColumn::make('profile.support_status')
                ->label('Support Status')
                ->formatStateUsing(fn (?SupportStatus $state): ?string => $state?->getLabel()),
            ExportColumn::make('profile.treatment_outcome')
                ->label('Treatment Outcome'),
            ExportColumn::make('profile.ethnicity')
                ->label('Ethnicity'),
            ExportColumn::make('profile.registered_with_gp')
                ->label('Registered with GP')
                ->formatStateUsing(fn (?bool $state): ?string => $state ? 'Yes' : 'No'),
            ExportColumn::make('profile.gp_name')
                ->label('GP Name'),
            ExportColumn::make('profile.gp_address')
                ->label('GP Address'),
            ExportColumn::make('profile.emergency_contact_name')
                ->label('Emergency Contact Name'),
            ExportColumn::make('profile.emergency_contact_number')
                ->label('Emergency Contact Phone'),
            ExportColumn::make('profile.emergency_contact_relation_type')
                ->label('Emergency Contact Relation'),
            ExportColumn::make('profile.reason_for_referral')
                ->label('Reason for Referral'),
            ExportColumn::make('created_at')
                ->label('Created At')
                ->formatStateUsing(fn (Carbon $state): string => $state->format('Y-m-d H:i:s')),

            // Include configured custom fields for people
            ...CustomFields::exporter()->forModel(People::class)->columns(),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your service user export has completed and '.Number::format($export->successful_rows).' '.str('row')->plural($export->successful_rows).' exported.';

        if (($failedRowsCount = $export->getFailedRowsCount()) !== 0) {
            $body .= ' '.Number::format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to export.';
        }

        return $body;
    }
}
