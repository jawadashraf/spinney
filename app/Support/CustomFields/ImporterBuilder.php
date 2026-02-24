<?php

declare(strict_types=1);

namespace App\Support\CustomFields;

use App\Models\CustomField;
use App\Support\CustomFields\Support\Imports\ImportDataStorage;
use Filament\Actions\Imports\ImportColumn;
use Illuminate\Database\Eloquent\Model;

final class ImporterBuilder
{
    private ?Model $record = null;

    private ?string $modelClass = null;

    public function forModel(mixed $model): static
    {
        if ($model instanceof Model) {
            $this->record = $model;
            $this->modelClass = $model::class;
        } else {
            $this->modelClass = $model;
        }

        return $this;
    }

    public function columns(): array
    {
        $query = CustomField::query()
            ->where('active', true)
            ->orderBy('sort_order');

        if ($this->modelClass) {
            $query->where('entity_type', $this->modelClass);
        }

        return $query->get()->map(fn (CustomField $field): ImportColumn => $this->createColumn($field))->all();
    }

    private function createColumn(CustomField $field): ImportColumn
    {
        return ImportColumn::make($field->code)
            ->label($field->name)
            ->fillRecordUsing(function (Model $record, mixed $state) use ($field): void {
                ImportDataStorage::set($record, $field->code, $state);
            });
    }

    public function saveValues(): void
    {
        if (! $this->record instanceof \Illuminate\Database\Eloquent\Model) {
            return;
        }

        $values = ImportDataStorage::pull($this->record);

        if ($values === []) {
            return;
        }

        /** @var \App\Models\Contracts\HasCustomFields $record */
        $record = $this->record;
        $record->saveCustomFields($values);
    }
}
