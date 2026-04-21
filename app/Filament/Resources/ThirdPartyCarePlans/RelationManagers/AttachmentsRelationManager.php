<?php

declare(strict_types=1);

namespace App\Filament\Resources\ThirdPartyCarePlans\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Number;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

final class AttachmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'media';

    protected static ?string $title = 'Attachments';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                TextInput::make('name')
                    ->label('Display Name')
                    ->required()
                    ->maxLength(255),
                Select::make('custom_properties.category')
                    ->label('Category')
                    ->options([
                        'clinical' => 'Clinical Report',
                        'correspondence' => 'Correspondence',
                        'legal' => 'Legal Document',
                        'other' => 'Other',
                    ])
                    ->required(),
                TagsInput::make('custom_properties.tags')
                    ->label('Tags')
                    ->placeholder('Add a tag...')
                    ->separator(','),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Display Name')
                    ->description(fn (Media $record): string => $record->file_name)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('custom_properties.category')
                    ->label('Category')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'clinical' => 'Clinical',
                        'correspondence' => 'Correspondence',
                        'legal' => 'Legal',
                        default => 'Other',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'clinical' => 'success',
                        'correspondence' => 'info',
                        'legal' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('custom_properties.tags')
                    ->label('Tags')
                    ->badge()
                    ->separator(',')
                    ->placeholder('No tags'),
                TextColumn::make('size')
                    ->label('File Info')
                    ->state(fn (Media $record): string => Number::fileSize($record->size) . ' (' . str($record->mime_type)->afterLast('/') . ')')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Uploaded')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Upload Attachment')
                    ->modalHeading('New Attachment')
                    ->form([
                        SpatieMediaLibraryFileUpload::make('attachments')
                            ->collection('attachments')
                            ->multiple()
                            ->required(),
                        Select::make('category')
                            ->options([
                                'clinical' => 'Clinical Report',
                                'correspondence' => 'Correspondence',
                                'legal' => 'Legal Document',
                                'other' => 'Other',
                            ])
                            ->required(),
                        TagsInput::make('tags')
                            ->placeholder('Add tags...')
                            ->separator(','),
                    ])
                    ->action(function (array $data, RelationManager $livewire): void {
                        // Custom action to handle bulk upload with properties
                        // Note: Filament Spatie Media Library plugin handles upload to the ownerRecord automatically via the component.
                        // However, we want to apply category/tags to each uploaded media.
                        // We'll use the 'after' hook or handle it manually.
                    })
                    ->after(function (array $data, RelationManager $livewire): void {
                        // All files uploaded via SpatieMediaLibraryFileUpload are attached to $livewire->ownerRecord
                        // We find the latest uploads and apply categories/tags.
                        $owner = $livewire->getOwnerRecord();
                        $recentMedia = $owner->media()->where('collection_name', 'attachments')->where('created_at', '>=', now()->subMinutes(1))->get();
                        
                        foreach ($recentMedia as $media) {
                            $media->setCustomProperty('category', $data['category']);
                            $media->setCustomProperty('tags', $data['tags']);
                            $media->save();
                        }
                    }),
            ])
            ->recordActions([
                Action::make('open')
                    ->label('Open')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Media $record): string => $record->getUrl())
                    ->openUrlInNewTab(),
                Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(fn (Media $record) => response()->download($record->getPath(), $record->file_name)),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
