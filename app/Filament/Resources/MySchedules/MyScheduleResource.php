<?php

declare(strict_types=1);

namespace App\Filament\Resources\MySchedules;

use App\Filament\Resources\MySchedules\Pages\CreateMySchedule;
use App\Filament\Resources\MySchedules\Pages\EditMySchedule;
use App\Filament\Resources\MySchedules\Pages\ListMySchedules;
use App\Filament\Resources\MySchedules\Schemas\MyScheduleForm;
use App\Filament\Resources\MySchedules\Tables\MySchedulesTable;
use App\Models\Schedule;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class MyScheduleResource extends Resource
{
    protected static ?string $model = Schedule::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendar;

    protected static string|\UnitEnum|null $navigationGroup = 'Workspace';

    protected static ?string $navigationLabel = 'My Schedules';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return MyScheduleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MySchedulesTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('schedulable_type', (new User)->getMorphClass())
            ->where('schedulable_id', auth()->id())
            ->availability();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMySchedules::route('/'),
            'create' => CreateMySchedule::route('/create'),
            'edit' => EditMySchedule::route('/{record}/edit'),
        ];
    }
}
