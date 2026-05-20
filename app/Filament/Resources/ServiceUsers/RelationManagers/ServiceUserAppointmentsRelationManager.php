<?php

declare(strict_types=1);

namespace App\Filament\Resources\ServiceUsers\RelationManagers;

use App\Enums\AppointmentStatus;
use App\Enums\AttendeeType;
use App\Enums\ScheduleType;
use App\Models\Schedule;
use App\Models\User;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

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
                    ->label('Counselor / Staff')
                    ->required()
                    ->live()
                    ->options(User::whereHas('roles', fn ($q) => $q->whereNot('name', 'service_user'))->pluck('name', 'id'))
                    ->afterStateUpdated(fn ($state, $set) => $set('schedulable_type', 'user')),

                TextInput::make('schedulable_type')
                    ->default('user')
                    ->hidden(),

                DatePicker::make('booking_date')
                    ->label('Booking Date')
                    ->required()
                    ->live()
                    ->native(false)
                    ->minDate(now()->toDateString()),

                Select::make('selected_slot')
                    ->label('Available 60-Minute Slots')
                    ->required()
                    ->live()
                    ->options(function (Get $get) {
                        $counselorId = $get('schedulable_id');
                        $date = $get('booking_date');

                        if (! $counselorId || ! $date) {
                            return [];
                        }

                        $counselor = User::find($counselorId);
                        if (! $counselor) {
                            return [];
                        }

                        $slots = $counselor->getBookableSlots($date, 60);

                        return collect($slots)
                            ->filter(fn ($slot) => (bool) ($slot['is_available'] ?? false))
                            ->mapWithKeys(fn ($slot) => [
                                "{$slot['start_time']}-{$slot['end_time']}" => "{$slot['start_time']} - {$slot['end_time']}",
                            ])
                            ->toArray();
                    })
                    ->visible(fn (Get $get): bool => filled($get('booking_date'))),

                TextInput::make('name')
                    ->label('Appointment Name')
                    ->required()
                    ->default('Counseling Session')
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('schedulable.name')
                    ->label('Counselor')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('start_date')
                    ->label('Date')
                    ->date()
                    ->sortable(),
                TextColumn::make('time_range')
                    ->label('Time')
                    ->state(fn (Schedule $record) => Carbon::parse($record->start_date)->format('H:i').' - '.Carbon::parse($record->end_date)->format('H:i')),
                TextColumn::make('metadata.appointment_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'scheduled' => 'info',
                        'confirmed' => 'success',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Book Appointment')
                    ->icon('heroicon-o-calendar')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['schedule_type'] = ScheduleType::APPOINTMENT->value;
                        $data['is_recurring'] = false;

                        $bookingDate = $data['booking_date'] ?? null;
                        $selectedSlot = $data['selected_slot'] ?? null;

                        if ($bookingDate && $selectedSlot) {
                            [$startTime, $endTime] = explode('-', $selectedSlot);
                            $data['start_date'] = "{$bookingDate} {$startTime}:00";
                            $data['end_date'] = "{$bookingDate} {$endTime}:00";
                        }

                        $data['metadata'] = [
                            'attendee_type' => AttendeeType::SERVICE_USER->value,
                            'service_user_id' => $this->getOwnerRecord()->id,
                            'appointment_status' => AppointmentStatus::SCHEDULED->value,
                            'counselor_type' => 'individual',
                            'session_type' => 'individual',
                            'payment_type' => 'free',
                        ];

                        return $data;
                    }),
            ])
            ->actions([
                DeleteAction::make(),
            ]);
    }
}
