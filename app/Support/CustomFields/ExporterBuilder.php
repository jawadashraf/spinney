<?php

declare(strict_types=1);

namespace App\Support\CustomFields;

use App\Models\CustomField;
use Filament\Actions\Exports\ExportColumn;

final class ExporterBuilder
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

        return $query->get()->map(fn (CustomField $field): \Filament\Actions\Exports\ExportColumn => $this->createColumn($field))->all();
    }

    private function createColumn(CustomField $field): ExportColumn
    {
        return ExportColumn::make($field->code)
            ->label($field->name)
            ->state(fn ($record) => $record->getCustomFieldValue($field));
    }
}
