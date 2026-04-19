<?php

declare(strict_types=1);

namespace App\Support\CustomFields;

use App\Models\Contracts\HasCustomFields as HasCustomFieldsContract;
use App\Models\CustomField;

final class ValueResolver
{
    public function resolve(HasCustomFieldsContract $record, CustomField $customField): mixed
    {
        /** @var \App\Models\CustomFieldValue|null $valueModel */
        $valueModel = $record->relationLoaded('customFieldValues')
            ? $record->customFieldValues->where('custom_field_id', $customField->id)->first()
            : $record->customFieldValues()
                ->where('custom_field_id', $customField->id)
                ->first();

        if (! $valueModel) {
            return null;
        }

        $value = $valueModel->getValue();

        if ($customField->type === 'select' && ! is_null($value)) {
            $option = $customField->options()->find($value);

            return $option ? $option->name : $value;
        }

        return $value;
    }
}
