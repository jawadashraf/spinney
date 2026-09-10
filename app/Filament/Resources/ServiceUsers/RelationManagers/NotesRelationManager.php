<?php

declare(strict_types=1);

namespace App\Filament\Resources\ServiceUsers\RelationManagers;

use App\Filament\Resources\NoteResource\Forms\NoteForm;
use App\Models\Note;
use App\Models\ServiceUser;
use App\Support\ServiceUserSupportStatus;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\Layout\View;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;

final class NotesRelationManager extends RelationManager
{
    protected static string $relationship = 'notes';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-document-text';

    public function form(Schema $schema): Schema
    {
        return NoteForm::get($schema);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                View::make('filament.resources.service-users.notes-timeline-item-content'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()
                    ->icon('heroicon-o-plus')
                    ->after(fn (Note $record, RelationManager $livewire) => self::flagSupportStatus($record, $livewire)),
            ])
            ->recordActions([
                EditAction::make()
                    ->after(fn (Note $record, RelationManager $livewire) => self::flagSupportStatus($record, $livewire)),
                DeleteAction::make(),
            ])
            ->defaultGroup(
                Group::make('notes.created_at')
                    ->date()
                    ->orderQueryUsing(fn ($query) => $query->orderByDesc('notes.created_at'))
                    ->collapsible()
            )
            ->paginated([10]);
    }

    private static function flagSupportStatus(Note $note, RelationManager $livewire): void
    {
        $owner = $livewire->getOwnerRecord();

        if ($owner instanceof ServiceUser && $note->support_status !== null) {
            ServiceUserSupportStatus::flag($owner, $note, $note->support_status);
        }
    }
}
