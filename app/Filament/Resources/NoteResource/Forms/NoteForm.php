<?php

declare(strict_types=1);

namespace App\Filament\Resources\NoteResource\Forms;

use App\Models\User;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\RichEditor\MentionProvider;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

final class NoteForm
{
    public static function getFormComponents(array $excludeFields = []): array
    {
        $components = [
            TextInput::make('title')
                ->label('Title')
                ->rules(['max:255'])
                ->columnSpanFull()
                ->required(),

            RichEditor::make('body')
                ->label('Body')
                ->columnSpanFull()
                ->extraInputAttributes([
                    'style' => 'min-height: 60vh; max-height: 80vh; overflow-y: auto;',
                ])
                ->floatingToolbars([
                    'paragraph' => [
                        'bold', 'italic', 'underline', 'strike', 'subscript', 'superscript',
                    ],
                    'heading' => [
                        'h1', 'h2', 'h3',
                    ],
                    'table' => [
                        'tableAddColumnBefore', 'tableAddColumnAfter', 'tableDeleteColumn',
                        'tableAddRowBefore', 'tableAddRowAfter', 'tableDeleteRow',
                        'tableMergeCells', 'tableSplitCell',
                        'tableToggleHeaderRow', 'tableToggleHeaderCell',
                        'tableDelete',
                    ],
                ])
                ->textColors([
                    '#ef4444' => 'Red',
                    '#10b981' => 'Green',
                    '#0ea5e9' => 'Sky',
                ])
                ->maxLength(65000)
                ->mentions([
                    MentionProvider::make('@')
                        ->getSearchResultsUsing(fn (string $search): array => User::role(['admin', 'manager', 'liaison', 'counselor', 'service_user'])
                            ->where('name', 'like', "%{$search}%")
                            ->limit(10)
                            ->pluck('name', 'id')
                            ->all())
                        ->getLabelsUsing(fn (array $ids): array => User::query()
                            ->whereIn('id', $ids)
                            ->pluck('name', 'id')
                            ->all()),
                ])
                ->required(),
        ];

        return $components;
    }

    /**
     * @param  array<string>  $excludeFields  Fields to exclude from the form.
     * @return Schema The modified form instance with the schema applied.
     *
     * @throws \Exception
     */
    public static function get(Schema $schema, array $excludeFields = []): Schema
    {
        return $schema
            ->components(self::getFormComponents($excludeFields))
            ->columns(2);
    }
}
