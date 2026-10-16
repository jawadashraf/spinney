<?php

declare(strict_types=1);

namespace App\Filament\Resources\Appointments;

use App\Enums\ScheduleType;
use App\Filament\Resources\Appointments\Pages\CreateAppointment;
use App\Filament\Resources\Appointments\Pages\EditAppointment;
use App\Filament\Resources\Appointments\Pages\ListAppointments;
use App\Filament\Resources\Appointments\Pages\ViewAppointment;
use App\Filament\Resources\Appointments\Schemas\AppointmentForm;
use App\Filament\Resources\Appointments\Schemas\AppointmentInfolist;
use App\Filament\Resources\Appointments\Tables\AppointmentsTable;
use App\Models\Schedule;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class AppointmentResource extends Resource
{
    protected static ?string $model = Schedule::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|\UnitEnum|null $navigationGroup = 'Appointments';

    protected static ?int $navigationSort = 1;

    /**
     * Managers and admins see every appointment. Counsellors and assessors (AC AP-006) see only the
     * appointments they are booked to hold.
     */
    public static function canAccess(): bool
    {
        $user = self::currentUser();

        return $user !== null && ($user->managesAppointments() || $user->can('ViewAny:Appointment'));
    }

    public static function getNavigationLabel(): string
    {
        return self::currentUser()?->managesAppointments() ? 'Appointments' : 'My appointments';
    }

    public static function form(Schema $schema): Schema
    {
        return AppointmentForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AppointmentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AppointmentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->where('schedule_type', ScheduleType::APPOINTMENT->value ?? ScheduleType::APPOINTMENT);
        $user = self::currentUser();

        if ($user !== null && ! $user->managesAppointments()) {
            $query->where('schedulable_type', $user->getMorphClass())->where('schedulable_id', $user->getKey());
        }

        return $query;
    }

    public static function getAuthorizationResponse(string|\UnitEnum $action, ?Model $record = null): Response
    {
        if (self::shouldSkipAuthorization()) {
            return Response::allow();
        }

        $user = self::currentUser();
        if (! $user) {
            return Response::deny();
        }

        if ($record instanceof Schedule && ! $user->managesAppointments() && ! $record->isWithCounsellor($user)) {
            return Response::deny();
        }

        $actionName = $action instanceof \UnitEnum ? $action->name : $action;
        $permissionAction = Str::studly($actionName);

        $permission = "{$permissionAction}:Appointment";

        return $user->can($permission)
            ? Response::allow()
            : Response::deny();
    }

    private static function currentUser(): ?User
    {
        $user = auth()->user();

        return $user instanceof User ? $user : null;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAppointments::route('/'),
            'create' => CreateAppointment::route('/create'),
            'view' => ViewAppointment::route('/{record}'),
            'edit' => EditAppointment::route('/{record}/edit'),
        ];
    }
}
