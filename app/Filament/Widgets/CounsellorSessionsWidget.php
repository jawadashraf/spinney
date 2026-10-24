<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\AppointmentStatus;
use App\Enums\AttendanceStatus;
use App\Enums\ScheduleType;
use App\Filament\Resources\Appointments\AppointmentResource;
use App\Models\Schedule;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/**
 * Each counsellor's track: sessions held and no-shows over the last 90 days, cancellations, upcoming load,
 * and past appointments whose outcome has not been recorded.
 */
final class CounsellorSessionsWidget extends TableWidget
{
    public const int DAYS = 90;

    protected static bool $isDiscovered = false;

    protected static ?int $sort = 8;

    protected int|string|array $columnSpan = 'full';

    public function getHeading(): string
    {
        return 'Counsellor sessions (last '.self::DAYS.' days)';
    }

    public function table(Table $table): Table
    {
        $teamId = Filament::getTenant()?->getKey();
        $today = now()->toDateString();
        $since = now()->subDays(self::DAYS)->toDateString();

        $appointments = fn (Builder $query): Builder => $query
            ->where('schedule_type', ScheduleType::APPOINTMENT->value)
            ->where('team_id', $teamId);

        return $table
            ->query(fn (): Builder => User::query()
                ->whereHas('schedules', $appointments)
                ->withCount([
                    'schedules as upcoming_count' => fn (Builder $query): Builder => Schedule::whereAppointmentStatus($appointments($query), Schedule::OPEN_STATUSES)
                        ->where('start_date', '>=', $today),
                    'schedules as needs_recording_count' => fn (Builder $query): Builder => Schedule::whereAppointmentStatus($appointments($query), Schedule::OPEN_STATUSES)
                        ->where('start_date', '<', $today),
                    'schedules as attended_count' => fn (Builder $query): Builder => $appointments($query)
                        ->where('metadata->attendance', AttendanceStatus::ATTENDED->value)
                        ->where('start_date', '>=', $since),
                    'schedules as no_show_count' => fn (Builder $query): Builder => $appointments($query)
                        ->where('metadata->attendance', AttendanceStatus::NO_SHOW->value)
                        ->where('start_date', '>=', $since),
                    'schedules as cancelled_count' => fn (Builder $query): Builder => Schedule::whereAppointmentStatus($appointments($query), [AppointmentStatus::CANCELLED])
                        ->where('start_date', '>=', $since),
                ]))
            ->defaultSort('name')
            ->paginated([10, 25])
            ->recordUrl(fn (User $record): string => AppointmentResource::getUrl('index', ['tableFilters' => ['counsellor' => ['value' => $record->id]]]))
            ->headerActions([
                Action::make('view_all_appointments')
                    ->label('View all in Appointments')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->url(fn (): string => AppointmentResource::getUrl('index')),
            ])
            ->columns([
                TextColumn::make('name')
                    ->label('Counsellor')
                    ->searchable()
                    ->sortable()
                    ->url(fn (User $record): string => AppointmentResource::getUrl('index', ['tableFilters' => ['counsellor' => ['value' => $record->id]]])),
                TextColumn::make('attended_count')
                    ->label('Sessions held')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('no_show_count')
                    ->label('No-shows')
                    ->numeric()
                    ->sortable()
                    ->description(fn (User $record): string => self::noShowRate($record)),
                TextColumn::make('cancelled_count')
                    ->label('Cancelled')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('upcoming_count')
                    ->label('Upcoming')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('needs_recording_count')
                    ->label('Needs recording')
                    ->numeric()
                    ->sortable()
                    ->color(fn (mixed $state): string => (int) $state > 0 ? 'warning' : 'gray'),
            ]);
    }

    private static function noShowRate(User $user): string
    {
        $held = (int) $user->getAttribute('attended_count');
        $noShows = (int) $user->getAttribute('no_show_count');

        return $held + $noShows > 0 ? round($noShows / ($held + $noShows) * 100).'% no-show rate' : '';
    }

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->managesAppointments();
    }
}
