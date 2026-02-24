<?php

declare(strict_types=1);

namespace App\Support\CustomFields;

use App\Models\CustomField;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;

final class TableBuilder
{
    private ?string $model = null;

    public function forModel(?string $model): static
    {
        $this->model = $model;

        return $this;
    }

    public function columns(): array
    {
        $query = CustomField::query()
            ->where('active', true)
            ->orderBy('sort_order');

        if ($this->model) {
            $query->where('entity_type', $this->model);
        }

        return $query->get()->map(fn (CustomField $field): \Filament\Tables\Columns\TextColumn => $this->createColumn($field))->all();
    }

    public function filters(): array
    {
        $query = CustomField::query()
            ->where('active', true)
            ->whereIn('type', ['select', 'boolean']) // Only certain types are easily filterable
            ->orderBy('sort_order');

        if ($this->model) {
            $query->where('entity_type', $this->model);
        }

        return $query->get()->map(fn (CustomField $field): mixed => $this->createFilter($field))->filter()->all();
    }

    private function createColumn(CustomField $field): TextColumn
    {
        $column = TextColumn::make($field->code)
            ->label($field->name)
            ->toggleable($field->settings->list_toggleable_hidden ?? true);

        // Add specific formatting based on type if needed
        if ($field->type === 'date') {
            $column->date();
        } elseif ($field->type === 'datetime') {
            $column->dateTime();
        }

        return $column;
    }

    private function createFilter(CustomField $field): mixed
    {
        if ($field->type === 'select') {
            return SelectFilter::make($field->code)
                ->label($field->name)
                ->options($field->options->pluck('name', 'id'));
        }

        // Add more filter types as needed
        return null;
    }
}
