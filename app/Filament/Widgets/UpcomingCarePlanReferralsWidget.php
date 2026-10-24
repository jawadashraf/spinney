<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\ThirdPartyCarePlanStatus;
use App\Filament\Resources\ServiceUsers\ServiceUserResource;
use App\Filament\Resources\ThirdPartyCarePlans\ThirdPartyCarePlanResource;
use App\Models\ThirdPartyCarePlan;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

final class UpcomingCarePlanReferralsWidget extends TableWidget
{
    protected static ?int $sort = 2;

    public function getHeading(): string
    {
        return 'Upcoming Care Plan Referrals';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => ThirdPartyCarePlan::query()
                ->whereIn('status', [ThirdPartyCarePlanStatus::PENDING, ThirdPartyCarePlanStatus::IN_PROGRESS])
                ->orderBy('referral_date')
                ->limit(10))
            ->columns([
                TextColumn::make('serviceUser.name')
                    ->label('Service User')
                    ->searchable()
                    ->sortable()
                    ->url(fn (ThirdPartyCarePlan $record): ?string => $record->people_id ? ServiceUserResource::getUrl('view', ['record' => $record->people_id]) : null),

                TextColumn::make('provider_name')
                    ->label('Provider')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('referral_date')
                    ->date()
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (ThirdPartyCarePlanStatus $state): string => match ($state) {
                        ThirdPartyCarePlanStatus::PENDING => 'warning',
                        ThirdPartyCarePlanStatus::IN_PROGRESS => 'info',
                        default => 'gray',
                    }),
            ])
            ->recordUrl(fn (ThirdPartyCarePlan $record): string => ThirdPartyCarePlanResource::getUrl('view', ['record' => $record]))
            ->headerActions([
                Action::make('view_all_care_plans')
                    ->label('View all in Care Plans')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->url(fn (): string => ThirdPartyCarePlanResource::getUrl('index')),
            ])
            ->recordActions([
                Action::make('view')
                    ->icon('heroicon-o-eye')
                    ->url(fn (ThirdPartyCarePlan $record): string => ThirdPartyCarePlanResource::getUrl('view', ['record' => $record])),
            ]);
    }

    public static function canView(): bool
    {
        return auth()->user()->can('ViewAny:ThirdPartyCarePlan');
    }
}
