<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tasks\Actions;

use App\Enums\CreationSource;
use App\Enums\CustomFields\TaskField;
use App\Enums\TaskType;
use App\Models\CustomFieldOption;
use App\Models\Task;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

final class RecordOutcomeAction
{
    public static function make(): Action
    {
        return Action::make('recordOutcome')
            ->label('Record Outcome')
            ->icon('heroicon-o-chat-bubble-left-ellipsis')
            ->color('success')
            ->visible(fn (Task $record): bool => $record->type === TaskType::FollowUpCall)
            ->modalHeading('Record Call Outcome')
            ->modalDescription('Record the outcome of this follow-up call. A note will be created on all linked people records.')
            ->modalSubmitActionLabel('Save Outcome & Mark Done')
            ->form([
                Textarea::make('outcome')
                    ->label('Call Outcome')
                    ->required()
                    ->rows(5)
                    ->columnSpanFull(),
                DatePicker::make('call_date')
                    ->label('Call Date')
                    ->required()
                    ->default(now()->toDateString())
                    ->native(false),
            ])
            ->action(function (array $data, Task $record): void {
                DB::transaction(function () use ($data, $record) {
                    $record->loadMissing('people');

                    foreach ($record->people as $person) {
                        $person->notes()->create([
                            'title' => 'Follow-up Call Outcome: ' . $record->title,
                            'body' => $data['outcome'],
                            'team_id' => $record->team_id,
                            'creator_id' => auth()->id(),
                            'creation_source' => CreationSource::WEB,
                        ]);
                    }

                    // Resolve "Done" status option ID
                    $doneOptionId = CustomFieldOption::query()
                        ->whereHas('customField', fn ($q) => $q->where('code', TaskField::STATUS->value))
                        ->where('name', 'Done')
                        ->value('id');

                    if ($doneOptionId) {
                        // Assuming the Task model has a way to get the CustomField instance by code
                        $statusField = $record->customFields()
                            ->where('code', TaskField::STATUS->value)
                            ->first();

                        if ($statusField) {
                            $record->saveCustomFieldValue($statusField, (string) $doneOptionId);
                        }
                    }
                    
                    // Also save the outcome to the task's own CALL_NOTES custom field if it exists
                    $notesField = $record->customFields()
                        ->where('code', TaskField::CALL_NOTES->value)
                        ->first();
                        
                    if ($notesField) {
                        $record->saveCustomFieldValue($notesField, $data['outcome']);
                    }
                });

                Notification::make()
                    ->title('Outcome recorded & task marked Done')
                    ->success()
                    ->send();
            });
    }
}
