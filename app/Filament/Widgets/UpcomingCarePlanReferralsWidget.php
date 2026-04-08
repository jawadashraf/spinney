<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\ThirdPartyCarePlanStatus;
use App\Filament\Resources\ThirdPartyCarePlans\ThirdPartyCarePlanResource;
use App\Models\ThirdPartyCarePlan;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

final class UpcomingCarePlanReferralsWidget extends TableWidget
{
    protected static ?int $sort = 2;

    public function getHeading(): string
    {
        return 'Upcoming Care Plan Referrals';
    }

    protected function getTableQuery(): Builder
    {
        return ThirdPartyCarePlan::query()
            ->whereIn('status', [ThirdPartyCarePlanStatus::PENDING, ThirdPartyCarePlanStatus::IN_PROGRESS])
            ->orderBy('referral_date')
            ->limit(10);
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('serviceUser.name')
                ->label('Service User')
                ->searchable()
                ->sortable(),

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
        ];
    }

    protected function getTableActions(): array
    {
        return [
            Action::make('view')
                ->icon('heroicon-o-eye')
                ->url(fn (ThirdPartyCarePlan $record): string => ThirdPartyCarePlanResource::getUrl('view', ['record' => $record])),
        ];
    }

    public static function canView(): bool
    {
        return auth()->user()->can('ViewAny:ThirdPartyCarePlan');
    }
}
