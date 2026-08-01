<?php

declare(strict_types=1);

namespace App\Support\CustomFields;

use App\Enums\CustomFieldType;
use App\Models\Contracts\HasCustomFields as HasCustomFieldsContract;
use App\Models\CustomField;
use Filament\Support\Colors\Color;
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

    /**
     * @return array<int, TextColumn>
     */
    public function columns(): array
    {
        $query = CustomField::query()
            ->where('active', true)
            ->orderBy('sort_order')
            ->with('options');

        if ($this->model) {
            $query->where('entity_type', $this->model);
        }

        return $query->get()->map(fn (CustomField $field): TextColumn => $this->createColumn($field))->all();
    }

    /**
     * @return array<int, SelectFilter>
     */
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
        /** @var array<string, mixed> $fieldSettings */
        $fieldSettings = (array) $field->settings;

        $column = TextColumn::make($field->code)
            ->label($field->name)
            ->getStateUsing(fn (HasCustomFieldsContract $record): mixed => app(ValueResolver::class)->resolve($record, $field))
            ->toggleable($fieldSettings['list_toggleable_hidden'] ?? true);

        // Add specific formatting based on type if needed
        if ($field->type === 'date') {
            $column->date();
        } elseif ($field->type === 'datetime' || $field->type === CustomFieldType::DATE_TIME->value) {
            $column->dateTime();
        } elseif ($field->type === CustomFieldType::RICH_EDITOR->value || $field->type === CustomFieldType::MARKDOWN_EDITOR->value) {
            $column->html();
        }

        if ($fieldSettings['enable_option_colors'] ?? false) {
            $column->badge()
                ->color(function (HasCustomFieldsContract $record) use ($field): string|array|null {
                    $option = app(ValueResolver::class)->resolveOption($record, $field);
                    /** @var array<string, mixed> $optionSettings */
                    $optionSettings = (array) ($option->settings ?? []);
                    $color = $optionSettings['color'] ?? null;

                    if ($color && str_starts_with($color, '#')) {
                        return Color::hex($color);
                    }

                    return $color;
                });
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
