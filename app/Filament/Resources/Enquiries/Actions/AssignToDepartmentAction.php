<?php

declare(strict_types=1);

namespace App\Filament\Resources\Enquiries\Actions;

use App\Models\Enquiry;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

final class AssignToDepartmentAction
{
    public static function make(): Action
    {
        return Action::make('assignToDepartment')
            ->label('Assign to Department')
            ->icon(Heroicon::OutlinedUserGroup)
            ->color('info')
            ->modalHeading('Assign to Department')
            ->modalDescription('Assign this enquiry to a department for handling.')
            ->schema([
                Select::make('department_id')
                    ->relationship('department', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->label('Department'),
            ])
            ->action(function (array $data, Enquiry $record): void {
                $record->update(['department_id' => $data['department_id']]);

                $department = $record->department;

                Notification::make()
                    ->title('Enquiry assigned to '.($department?->name ?? 'department'))
                    ->success()
                    ->send();
            });
    }
}
