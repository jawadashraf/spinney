<?php

declare(strict_types=1);

namespace App\Filament\Resources\Appointments\Pages;

use App\Enums\AppointmentStatus;
use App\Filament\Concerns\SyncsPermissionTeamId;
use App\Filament\Resources\Appointments\AppointmentResource;
use App\Models\Schedule;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

final class ListAppointments extends ListRecords
{
    use SyncsPermissionTeamId;

    protected static string $resource = AppointmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    /**
     * "Needs recording" is past appointments still scheduled or confirmed: sessions whose outcome was never recorded.
     *
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        $today = now()->toDateString();

        return [
            'upcoming' => Tab::make('Upcoming')
                ->modifyQueryUsing(fn (Builder $query): Builder => Schedule::whereAppointmentStatus($query, Schedule::OPEN_STATUSES)
                    ->where('start_date', '>=', $today)),
            'needs_recording' => Tab::make('Needs recording')
                ->modifyQueryUsing(fn (Builder $query): Builder => Schedule::whereAppointmentStatus($query, Schedule::OPEN_STATUSES)
                    ->where('start_date', '<', $today))
                ->badge(fn (): int => Schedule::whereAppointmentStatus(AppointmentResource::getEloquentQuery(), Schedule::OPEN_STATUSES)
                    ->where('start_date', '<', $today)
                    ->count())
                ->badgeColor('warning'),
            'completed' => Tab::make('Completed')
                ->modifyQueryUsing(fn (Builder $query): Builder => Schedule::whereAppointmentStatus($query, [AppointmentStatus::COMPLETED])),
            'cancelled' => Tab::make('Cancelled')
                ->modifyQueryUsing(fn (Builder $query): Builder => Schedule::whereAppointmentStatus($query, [AppointmentStatus::CANCELLED])),
            'all' => Tab::make('All'),
        ];
    }

    public function getDefaultActiveTab(): string
    {
        return 'upcoming';
    }
}
