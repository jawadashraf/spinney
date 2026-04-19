<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\CustomField;
use App\Models\CustomFieldValue;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasCustomFields
{
    /**
     * @return MorphMany<CustomFieldValue, $this>
     */
    public function customFieldValues(): MorphMany
    {
        return $this->morphMany(CustomFieldValue::class, 'entity');
    }

    /**
     * Get a custom field value.
     */
    public function getCustomFieldValue(CustomField $field): mixed
    {
        $valueColumn = $this->getValueColumnForType($field->type);

        $valueRecord = $this->customFieldValues()
            ->where('custom_field_id', $field->id)
            ->first();

        return $valueRecord?->{$valueColumn};
    }

    public function saveCustomFields(array $values): void
    {
        foreach ($values as $code => $value) {
            $field = CustomField::query()
                ->where('code', $code)
                ->where('entity_type', static::class)
                ->first();

            if ($field) {
                $this->saveCustomFieldValue($field, $value);
            }
        }
    }

    /**
     * Save a custom field value for this entity.
     */
    public function saveCustomFieldValue(CustomField $field, mixed $value): void
    {
        $valueColumn = $this->getValueColumnForType($field->type);

        $this->customFieldValues()->updateOrCreate(
            [
                'custom_field_id' => $field->id,
            ],
            [
                $valueColumn => $value,
            ]
        );
    }

    /**
     * Get the database column name for a given custom field type.
     */
    protected function getValueColumnForType(string $type): string
    {
        return match ($type) {
            'text', 'string', 'url', 'email' => 'string_value',
            'longText', 'richtext', 'textarea' => 'text_value',
            'boolean', 'toggle' => 'boolean_value',
            'integer', 'number', 'select' => 'integer_value',
            'float', 'decimal' => 'float_value',
            'date' => 'date_value',
            'datetime' => 'datetime_value',
            'json', 'tags' => 'json_value',
            default => 'string_value',
        };
    }
}
