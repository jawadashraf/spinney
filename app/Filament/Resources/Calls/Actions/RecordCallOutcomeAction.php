<?php

declare(strict_types=1);

namespace App\Filament\Resources\Calls\Actions;

use App\Actions\Calls\RecordCallOutcome;
use App\Enums\CallOutcome;
use App\Enums\CallStatus;
use App\Enums\SupportStatus;
use App\Models\Call;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

final class RecordCallOutcomeAction
{
    public static function make(): Action
    {
        return Action::make('recordOutcome')
            ->label('Record outcome')
            ->icon(Heroicon::OutlinedChatBubbleLeftEllipsis)
            ->color('success')
            ->visible(fn (Call $record): bool => $record->isOpen())
            ->authorize('recordOutcome')
            ->modalHeading(fn (Call $record): string => 'Record call – '.($record->serviceUser->name ?? 'service user'))
            ->modalDescription(fn (Call $record): string => 'Attempt '.($record->attempt_count + 1).' of '.$record->maxAttempts()
                .(filled($record->serviceUser?->phone) ? ' · '.$record->serviceUser->phone : ''))
            ->modalSubmitActionLabel('Save outcome')
            ->modalWidth(Width::TwoExtraLarge)
            ->schema([
                ToggleButtons::make('outcome')
                    ->options(CallOutcome::class)
                    ->inline()
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn (Get $get, Set $set, Call $record) => self::fillDefaultDates($get, $set, $record)),
                Textarea::make('notes')
                    ->label('Call notes')
                    ->rows(4)
                    ->required()
                    ->maxLength(5000),
                Textarea::make('action_required')
                    ->label('Action required')
                    ->rows(2)
                    ->maxLength(2000),
                Toggle::make('close_call')
                    ->label('Close this call without further retries')
                    ->live()
                    ->afterStateUpdated(fn (Get $get, Set $set, Call $record) => self::fillDefaultDates($get, $set, $record))
                    ->visible(fn (Get $get): bool => filled($get('outcome')) && ! self::isAnswered($get)),
                DateTimePicker::make('next_follow_up_at')
                    ->label('Next follow-up call')
                    ->seconds(false)
                    ->after('now')
                    ->helperText(fn (Call $record): string => $record->plan !== null
                        ? 'Pre-filled from the '.strtolower($record->plan->frequency->getLabel()).' call plan. Clear it if no follow-up is needed.'
                        : 'Leave empty if no follow-up is needed.')
                    ->visible(fn (Get $get): bool => self::isClosing($get)),
                DateTimePicker::make('retry_at')
                    ->label('Try again at')
                    ->seconds(false)
                    ->after('now')
                    ->required()
                    ->visible(fn (Get $get, Call $record): bool => filled($get('outcome'))
                        && ! self::isClosing($get)
                        && $record->attempt_count + 1 < $record->maxAttempts()),
                Toggle::make('raise_concern')
                    ->label('Raise support / safeguarding concern')
                    ->live(),
                Select::make('support_status')
                    ->label('Concern level')
                    ->options([
                        SupportStatus::NeedsAttention->value => SupportStatus::NeedsAttention->getLabel(),
                        SupportStatus::UrgentAttention->value => SupportStatus::UrgentAttention->getLabel(),
                    ])
                    ->default(SupportStatus::NeedsAttention->value)
                    ->required()
                    ->visible(fn (Get $get): bool => (bool) $get('raise_concern')),
            ])
            ->action(function (array $data, Call $record): void {
                /** @var User $user */
                $user = auth()->user();

                $call = app(RecordCallOutcome::class)->handle($record, $user, $data);

                Notification::make()
                    ->title('Call outcome saved')
                    ->body(self::summary($call))
                    ->success()
                    ->send();
            });
    }

    private static function outcomeValue(Get $get): ?string
    {
        $outcome = $get('outcome');

        return $outcome instanceof CallOutcome ? $outcome->value : $outcome;
    }

    private static function isAnswered(Get $get): bool
    {
        return self::outcomeValue($get) === CallOutcome::Answered->value;
    }

    private static function isClosing(Get $get): bool
    {
        return self::isAnswered($get) || (bool) $get('close_call');
    }

    private static function fillDefaultDates(Get $get, Set $set, Call $record): void
    {
        if (self::isClosing($get) && blank($get('next_follow_up_at')) && $record->plan !== null) {
            $set('next_follow_up_at', $record->plan->nextDueAfter(now()->setTimeFrom($record->due_at))->toDateTimeString());
        }

        if (! self::isClosing($get) && blank($get('retry_at'))) {
            $set('retry_at', RecordCallOutcome::defaultRetryAt($record)->toDateTimeString());
        }
    }

    private static function summary(Call $call): string
    {
        return match ($call->status) {
            CallStatus::Completed => $call->followUpCall !== null
                ? 'Next call scheduled for '.$call->followUpCall->due_at->format('d M Y, H:i').'.'
                : 'Call completed.',
            CallStatus::Missed => 'No contact after '.$call->attempt_count.' attempts – managers have been notified.',
            default => 'Retry scheduled for '.$call->due_at->format('d M Y, H:i').'.',
        };
    }
}
