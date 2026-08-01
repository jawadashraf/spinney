<?php

declare(strict_types=1);

namespace App\Filament\Resources\ServiceUsers\RelationManagers;

use App\Enums\SupportStatus;
use App\Events\ServiceUserNeedsAttention;
use App\Filament\Resources\NoteResource\Forms\NoteForm;
use App\Models\Note;
use App\Models\ServiceUser;
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
                    ->after(function ($action, Note $record, RelationManager $livewire): void {
                        $owner = $livewire->getOwnerRecord();
                        if ($owner instanceof ServiceUser) {
                            $status = $record->support_status;
                            if ($status) {
                                $profile = $owner->profile;
                                $profileStatus = $profile?->support_status;
                                if ($profile) {
                                    if ($status === SupportStatus::UrgentAttention) {
                                        $profile->update([
                                            'support_status' => SupportStatus::UrgentAttention,
                                            'support_flagged_at' => now(),
                                            'support_resolved_at' => null,
                                        ]);
                                    } elseif ($status === SupportStatus::NeedsAttention && $profileStatus !== SupportStatus::UrgentAttention) {
                                        $profile->update([
                                            'support_status' => SupportStatus::NeedsAttention,
                                            'support_flagged_at' => now(),
                                            'support_resolved_at' => null,
                                        ]);
                                    }
                                }

                                if (in_array($status, [SupportStatus::NeedsAttention, SupportStatus::UrgentAttention], true)) {
                                    event(new ServiceUserNeedsAttention($owner, $record, $status));
                                }
                            }
                        }
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->after(function ($action, Note $record, RelationManager $livewire): void {
                        $owner = $livewire->getOwnerRecord();
                        if ($owner instanceof ServiceUser) {
                            $status = $record->support_status;
                            if ($status) {
                                $profile = $owner->profile;
                                $profileStatus = $profile?->support_status;
                                if ($profile) {
                                    if ($status === SupportStatus::UrgentAttention) {
                                        $profile->update([
                                            'support_status' => SupportStatus::UrgentAttention,
                                            'support_flagged_at' => now(),
                                            'support_resolved_at' => null,
                                        ]);
                                    } elseif ($status === SupportStatus::NeedsAttention && $profileStatus !== SupportStatus::UrgentAttention) {
                                        $profile->update([
                                            'support_status' => SupportStatus::NeedsAttention,
                                            'support_flagged_at' => now(),
                                            'support_resolved_at' => null,
                                        ]);
                                    }
                                }

                                if (in_array($status, [SupportStatus::NeedsAttention, SupportStatus::UrgentAttention], true)) {
                                    event(new ServiceUserNeedsAttention($owner, $record, $status));
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
