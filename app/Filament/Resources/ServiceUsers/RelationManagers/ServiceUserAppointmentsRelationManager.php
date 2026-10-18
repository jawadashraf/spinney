<?php

declare(strict_types=1);

namespace App\Filament\Resources\ServiceUsers\RelationManagers;

use App\Actions\Appointments\BookAppointment;
use App\Enums\AttendeeType;
use App\Enums\CounselorType;
use App\Enums\PaymentType;
use App\Enums\SessionType;
use App\Filament\Resources\Appointments\AppointmentResource;
use App\Models\People;
use App\Models\Schedule;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * The service user's appointments: book one, and see each appointment's status and attendance.
 * Session notes are only shown on the appointment itself, to the counsellor, managers and admins.
 */
final class ServiceUserAppointmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'serviceUserAppointments';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-calendar';

    protected static ?string $title = 'Booked Appointments';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Select::make('schedulable_id')
                    ->label('Counsellor')
                    ->required()
                    ->live()
                    ->options(fn (): array => User::query()
                        ->whereHas('roles', fn ($query) => $query->whereNot('name', 'service_user'))
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all()),

                Select::make('counselor_type')
                    ->label('Appointment Type')
                    ->options(CounselorType::class)
                    ->required(),

                DatePicker::make('booking_date')
                    ->label('Booking Date')
                    ->required()
                    ->live()
                    ->native(false)
                    ->minDate(now()->toDateString()),

                Select::make('selected_slot')
                    ->label('Available 60-Minute Slots')
                    ->required()
                    ->options(function (Get $get): array {
                        $counselor = User::query()->find($get('schedulable_id'));
                        $date = $get('booking_date');

                        if (! $counselor instanceof User || blank($date)) {
                            return [];
                        }

                        /** @var array<int, array<string, mixed>> $slots */
                        $slots = $counselor->getBookableSlots($date, 60);

                        return collect($slots)
                            ->filter(fn (array $slot): bool => (bool) ($slot['is_available'] ?? false))
                            ->mapWithKeys(fn (array $slot): array => [
                                "{$slot['start_time']}-{$slot['end_time']}" => "{$slot['start_time']} - {$slot['end_time']}",
                            ])
                            ->all();
                    })
                    ->visible(fn (Get $get): bool => filled($get('schedulable_id')) && filled($get('booking_date'))),

                TextInput::make('name')
                    ->label('Appointment Name')
                    ->required()
                    ->default('Counselling session')
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->modifyQueryUsing(fn ($query) => $query->with(['schedulable', 'periods']))
            ->defaultSort('start_date', 'desc')
            ->columns([
                TextColumn::make('start_date')
                    ->label('Date')
                    ->date()
                    ->sortable(),
                TextColumn::make('time_range')
                    ->label('Time')
                    ->state(fn (Schedule $record): string => $record->timeRange()),
                TextColumn::make('schedulable.name')
                    ->label('Counsellor'),
                TextColumn::make('type')
                    ->label('Type')
                    ->state(fn (Schedule $record): ?string => $record->getCounselorType()?->getLabel())
                    ->placeholder('-'),
                TextColumn::make('status')
                    ->badge()
                    ->state(fn (Schedule $record): string => $record->getAppointmentStatus()->getLabel())
                    ->color(fn (Schedule $record): string => $record->getAppointmentStatus()->getColor()),
                TextColumn::make('attendance')
                    ->badge()
                    ->state(fn (Schedule $record): ?string => $record->getAttendance()?->getLabel())
                    ->color(fn (Schedule $record): string => $record->getAttendance()?->getColor() ?? 'gray')
                    ->placeholder('-'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Book Appointment')
                    ->modalHeading('Book an appointment')
                    ->modalSubmitActionLabel('Book')
                    ->createAnother(false)
                    ->icon('heroicon-o-calendar')
                    ->using(fn (array $data): Model => $this->book($data)),
            ])
            ->recordActions([
                Action::make('open')
                    ->label('Open')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Schedule $record): string => AppointmentResource::getUrl('view', ['record' => $record]))
                    ->visible(fn (Schedule $record): bool => AppointmentResource::canView($record)),
                DeleteAction::make(),
            ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function book(array $data): Schedule
    {
        /** @var People $serviceUser */
        $serviceUser = $this->getOwnerRecord();

        [$startTime, $endTime] = explode('-', (string) $data['selected_slot']);

        return app(BookAppointment::class)->handle(
            schedulable: User::query()->findOrFail((int) $data['schedulable_id']),
            date: (string) $data['booking_date'],
            startTime: $startTime,
            endTime: $endTime,
            name: (string) $data['name'],
            metadata: [
                'attendee_type' => AttendeeType::SERVICE_USER->value,
                'service_user_id' => $serviceUser->getKey(),
                'service_user_name' => $serviceUser->name,
                'counselor_type' => $data['counselor_type'] instanceof CounselorType ? $data['counselor_type']->value : $data['counselor_type'],
                'session_type' => SessionType::INDIVIDUAL->value,
                'payment_type' => PaymentType::FREE->value,
            ],
            teamId: (int) (Filament::getTenant()?->getKey() ?? $serviceUser->team_id),
        );
    }
}
