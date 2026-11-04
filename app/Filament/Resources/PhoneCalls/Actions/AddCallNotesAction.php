<?php

declare(strict_types=1);

namespace App\Filament\Resources\PhoneCalls\Actions;

use App\Actions\Calls\AddPhoneCallNote;
use App\Enums\NoteVisibility;
use App\Enums\SupportStatus;
use App\Filament\Resources\Calls\CallResource;
use App\Models\PbxCallLog;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

/**
 * Notes for a finished phone call. A service user's liaison call that is awaiting notes is completed through
 * Record outcome on the call's page; every other call gets a note on the person's or company's record.
 */
final class AddCallNotesAction
{
    public static function make(): Action
    {
        return Action::make('addNotes')
            ->label('Add notes')
            ->icon(Heroicon::OutlinedPencilSquare)
            ->color('success')
            ->visible(fn (PbxCallLog $record): bool => $record->needsNotes() && $record->isVisibleTo(self::user()) && ! $record->hasLiaisonCallAwaitingNotes())
            ->modalHeading(fn (PbxCallLog $record): string => 'Call notes – '.self::contactName($record))
            ->modalDescription(fn (PbxCallLog $record): string => self::describe($record))
            ->modalSubmitActionLabel('Save notes')
            ->modalCancelActionLabel('Later')
            ->modalWidth(Width::TwoExtraLarge)
            ->schema([
                Textarea::make('notes')
                    ->label('Call notes')
                    ->rows(5)
                    ->required()
                    ->maxLength(5000)
                    ->autofocus(),
                Toggle::make('raise_concern')
                    ->label('Raise support / safeguarding concern')
                    ->live()
                    ->visible(fn (PbxCallLog $record): bool => $record->person?->is_service_user === true),
                Select::make('support_status')
                    ->label('Concern level')
                    ->options([
                        SupportStatus::NeedsAttention->value => SupportStatus::NeedsAttention->getLabel(),
                        SupportStatus::UrgentAttention->value => SupportStatus::UrgentAttention->getLabel(),
                    ])
                    ->default(SupportStatus::NeedsAttention->value)
                    ->required()
                    ->visible(fn (Get $get): bool => (bool) $get('raise_concern')),
                ToggleButtons::make('visibility')
                    ->label('Who can read this note?')
                    ->options(NoteVisibility::class)
                    ->default(NoteVisibility::Standard)
                    ->inline()
                    ->helperText('Restricted notes can only be read by managers and admins, and each opening is recorded.')
                    ->visible(fn (): bool => self::user()?->canSeeRestrictedNotes() === true),
            ])
            ->extraModalFooterActions(fn (PbxCallLog $record): array => [
                SkipCallNotesAction::make()
                    ->record($record)
                    ->cancelParentActions(),
            ])
            ->action(function (array $data, PbxCallLog $record): void {
                /** @var User $user */
                $user = auth()->user();

                app(AddPhoneCallNote::class)->handle($record, $user, $data);

                Notification::make()
                    ->title('Call notes saved')
                    ->body('Added to '.self::contactName($record).'\'s notes.')
                    ->success()
                    ->send();
            });
    }

    /**
     * Opens Record outcome on the liaison call, which turns the notes into the service user's call note.
     */
    public static function makeForLiaisonCall(): Action
    {
        return Action::make('addLiaisonCallNotes')
            ->label('Add notes')
            ->icon(Heroicon::OutlinedPencilSquare)
            ->color('success')
            ->visible(fn (PbxCallLog $record): bool => $record->needsNotes() && $record->isVisibleTo(self::user()) && $record->hasLiaisonCallAwaitingNotes())
            ->url(fn (PbxCallLog $record): string => CallResource::getUrl('view', ['record' => $record->call_id, 'action' => 'recordOutcome']));
    }

    public static function contactName(PbxCallLog $record): string
    {
        return $record->person->name ?? $record->company->name ?? 'unknown caller';
    }

    public static function describe(PbxCallLog $record): string
    {
        return collect([
            ucfirst($record->direction).' call',
            $record->started_at->format('d M Y, H:i'),
            $record->isAnswered() ? 'talk time '.$record->formattedTalkDuration() : null,
            $record->contact_number,
        ])->filter()->implode(' · ');
    }

    private static function user(): ?User
    {
        $user = auth()->user();

        return $user instanceof User ? $user : null;
    }
}
