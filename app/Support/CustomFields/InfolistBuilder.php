<?php

declare(strict_types=1);

namespace App\Support\CustomFields;

use App\Models\Contracts\HasCustomFields as HasCustomFieldsContract;
use App\Models\CustomField;
use App\Models\CustomFieldSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema as SchemaFacade;

final class InfolistBuilder
{
    private ?Schema $schema = null;

    private array $only = [];

    private bool $hiddenLabels = false;

    private bool $visibleWhenFilled = false;

    private bool $withoutSections = false;

    public function forSchema(Schema $schema): static
    {
        $this->schema = $schema;

        return $this;
    }

    public function only(array $only): static
    {
        $this->only = $only;

        return $this;
    }

    public function hiddenLabels(): static
    {
        $this->hiddenLabels = true;

        return $this;
    }

    public function visibleWhenFilled(): static
    {
        $this->visibleWhenFilled = true;

        return $this;
    }

    public function withoutSections(): static
    {
        $this->withoutSections = true;

        return $this;
    }

    public function build(): Grid
    {
        $modelClass = $this->schema instanceof Schema ? $this->schema->getModel() : null;

        if ($modelClass && ! $this->withoutSections) {
            $sections = CustomFieldSection::query()
                ->where('entity_type', $modelClass)
                ->where('active', true)
                ->with(['fields' => fn ($query) => $query->where('active', true)->orderBy('sort_order')])
                ->orderBy('sort_order')
                ->get();

            if ($sections->isNotEmpty()) {
                $sectionComponents = [];
                foreach ($sections as $section) {
                    $fields = $section->fields;

                    if ($this->only !== []) {
                        $fields = $fields->filter(fn (CustomField $field): bool => in_array($field->code, $this->only));
                    }

                    if ($fields->isEmpty()) {
                        continue;
                    }

                    $entryComponents = $fields->map(fn (CustomField $field): mixed => $this->createEntry($field))->toArray();

                    $sectionComponents[] = Section::make($section->name)
                        ->description($section->description)
                        ->schema($entryComponents)
                        ->columns(2);
                }

                if ($sectionComponents !== []) {
                    return Grid::make(1)->components($sectionComponents);
                }
            }
        }

        return Grid::make(2)->components($this->values()->toArray());
    }

    public function values(): Collection
    {
        $modelClass = $this->schema instanceof Schema ? $this->schema->getModel() : null;

        $query = CustomField::query()
            ->where('active', true)
            ->whereNull('custom_field_section_id')
            ->orderBy('sort_order');

        if ($modelClass) {
            $query->where('entity_type', $modelClass);
        }

        $fields = $query->get();

        if ($this->only !== []) {
            $fields = $fields->filter(fn (CustomField $field): bool => in_array($field->code, $this->only));
        }

        return $fields->map(fn (CustomField $field): mixed => $this->createEntry($field));
    }

    private function createEntry(CustomField $field): mixed
    {
        // For simplicity, we use TextEntry for most fields in infolists
        $entry = TextEntry::make($field->code);

        $modelClass = $this->schema instanceof Schema ? $this->schema->getModel() : null;

        if ($modelClass) {
            /** @var Model $modelInstance */
            $modelInstance = new $modelClass;
            $tableName = $modelInstance->getTable();

            if (! SchemaFacade::hasColumn($tableName, $field->code)) {
                $entry->getStateUsing(function (Model $record) use ($field) {
                    if ($record instanceof HasCustomFieldsContract) {
                        return $record->getCustomFieldValue($field);
                    }

                    return null;
                });
            }
        }

        if ($this->hiddenLabels) {
            $entry->hiddenLabel();
        } else {
            $entry->label($field->name);
        }

        if ($this->visibleWhenFilled) {
            $entry->visible(fn ($record): bool => filled($record->{$field->code} ?? null));
        }

        return $entry;
    }
}
