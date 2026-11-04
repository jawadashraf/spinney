<?php

declare(strict_types=1);

namespace App\Filament\Actions;

use App\Models\PbxCallLog;
use Filament\Actions\Action;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\View\View;

/**
 * Plays a phone system call recording in a modal. The audio streams straight from the PBX link, so recordings
 * stay links only (see .ai/rules/calls.md).
 */
final class PlayRecordingAction extends Action
{
    public static function getDefaultName(): string
    {
        return 'playRecording';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Play')
            ->icon(Heroicon::OutlinedPlay)
            ->color('primary')
            ->visible(fn (PbxCallLog $record): bool => filled($record->recording_url))
            ->modalHeading('Call recording')
            ->modalDescription(fn (PbxCallLog $record): string => collect([
                $record->person->name ?? $record->company?->name ?? $record->contact_number,
                $record->started_at->format('d M Y, H:i'),
                $record->formattedTalkDuration(),
            ])->filter()->implode(' · '))
            ->modalWidth(Width::Large)
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Close')
            ->modalContent(fn (PbxCallLog $record): View => view('filament.components.call-recording-player', [
                'url' => $record->recording_url,
                'autoplay' => true,
            ]));
    }
}
