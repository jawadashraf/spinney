<?php

declare(strict_types=1);

namespace App\Filament\Resources\ServiceUsers\RelationManagers;

use App\Enums\SupportStatus;
use App\Filament\Resources\NoteResource\Forms\NoteForm;
use App\Models\Note;
use App\Models\People;
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
                    ->after(function ($action, \App\Models\Note $record, \Filament\Resources\RelationManagers\RelationManager $livewire): void {
                        $owner = $livewire->getOwnerRecord();
                        if ($owner instanceof \App\Models\ServiceUser && $record->support_status) {
                            $profile = $owner->profile;
                            if ($profile) {
                                if ($record->support_status === \App\Enums\SupportStatus::UrgentAttention) {
                                    $profile->update(['support_status' => \App\Enums\SupportStatus::UrgentAttention]);
                                } elseif ($record->support_status === \App\Enums\SupportStatus::NeedsAttention && $profile->support_status !== \App\Enums\SupportStatus::UrgentAttention) {
                                    $profile->update(['support_status' => \App\Enums\SupportStatus::NeedsAttention]);
                                }
                            }
                        }
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->after(function ($action, \App\Models\Note $record, \Filament\Resources\RelationManagers\RelationManager $livewire): void {
                        $owner = $livewire->getOwnerRecord();
                        if ($owner instanceof \App\Models\ServiceUser && $record->support_status) {
                            $profile = $owner->profile;
                            if ($profile) {
                                if ($record->support_status === \App\Enums\SupportStatus::UrgentAttention) {
                                    $profile->update(['support_status' => \App\Enums\SupportStatus::UrgentAttention]);
                                } elseif ($record->support_status === \App\Enums\SupportStatus::NeedsAttention && $profile->support_status !== \App\Enums\SupportStatus::UrgentAttention) {
                                    $profile->update(['support_status' => \App\Enums\SupportStatus::NeedsAttention]);
                                }
                            }
                        }
                    }),
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
}
