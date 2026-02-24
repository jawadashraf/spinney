<?php

declare(strict_types=1);

namespace App\Support\CustomFields;

use App\Enums\CustomFields\CustomFieldWidth;
use App\Models\CustomField;
use App\Models\CustomFieldSection;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class FormBuilder
{
    private ?Schema $schema = null;

    private array $except = [];

    private array $only = [];

    private bool $columnSpanFull = false;

    public function forSchema(Schema $schema): static
    {
        $this->schema = $schema;

        return $this;
    }

    public function except(array $except): static
    {
        $this->except = $except;

        return $this;
    }

    public function only(array $only): static
    {
        $this->only = $only;

        return $this;
    }

    public function columnSpanFull(bool $condition = true): static
    {
        $this->columnSpanFull = $condition;

        return $this;
    }

    public function build(): Grid
    {
        $modelClass = $this->schema instanceof \Filament\Schemas\Schema ? $this->schema->getModel() : null;

        if ($modelClass) {
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

                    if ($this->except !== []) {
                        $fields = $fields->reject(fn (CustomField $field): bool => in_array($field->code, $this->except));
                    }

                    if ($this->only !== []) {
                        $fields = $fields->filter(fn (CustomField $field): bool => in_array($field->code, $this->only));
                    }

                    if ($fields->isEmpty()) {
                        continue;
                    }

                    $fieldComponents = $fields->map(fn (CustomField $field): mixed => $this->createComponent($field))->toArray();

                    $sectionComponents[] = Section::make($section->name)
                        ->description($section->description)
                        ->schema($fieldComponents)
                        ->columns(2);
                }

                if ($sectionComponents !== []) {
                    $grid = Grid::make(1)->components($sectionComponents);
                    if ($this->columnSpanFull) {
                        $grid->columnSpanFull();
                    }

                    return $grid;
                }
            }
        }

        // Fallback or No sections case
        $query = CustomField::query()
            ->where('active', true)
            ->whereNull('custom_field_section_id')
            ->orderBy('sort_order');

        if ($modelClass) {
            $query->where('entity_type', $modelClass);
        }

        $fields = $query->get();

        if ($this->except !== []) {
            $fields = $fields->reject(fn (CustomField $field): bool => in_array($field->code, $this->except));
        }

        if ($this->only !== []) {
            $fields = $fields->filter(fn (CustomField $field): bool => in_array($field->code, $this->only));
        }

        $components = $fields->map(fn (CustomField $field): mixed => $this->createComponent($field))->toArray();

        $grid = Grid::make(2)->components($components);

        if ($this->columnSpanFull) {
            $grid->columnSpanFull();
        }

        return $grid;
    }

    protected function createComponent(CustomField $field): mixed
    {
        $component = match ($field->type) {
            'text', 'string', 'url', 'email' => TextInput::make($field->code),
            'richtext', 'longText' => RichEditor::make($field->code),
            'textarea' => Textarea::make($field->code),
            'select' => Select::make($field->code)
                ->options($field->options->pluck('name', 'id'))
                ->searchable(),
            'toggle', 'boolean' => Toggle::make($field->code),
            'date' => DatePicker::make($field->code),
            'datetime' => DateTimePicker::make($field->code),
            'tags' => TagsInput::make($field->code),
            default => TextInput::make($field->code),
        };

        $component->label($field->name);

        if ($field->validation_rules) {
            $component->rules($field->validation_rules);
        }

        if ($field->width) {
            $widthEnum = CustomFieldWidth::tryFrom((string) $field->width);
            if ($widthEnum) {
                $component->columnSpan($widthEnum->getSpanValue());
            }
        }

        return $component;
    }
}
