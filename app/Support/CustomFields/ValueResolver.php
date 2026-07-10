<?php

declare(strict_types=1);

namespace App\Support\CustomFields;

use App\Models\Contracts\HasCustomFields as HasCustomFieldsContract;
use App\Models\CustomField;
use App\Models\CustomFieldOption;
use Illuminate\Database\Eloquent\Model;

final class ValueResolver
{
    public function resolve(HasCustomFieldsContract $record, CustomField $customField): mixed
    {
        if (! $record instanceof Model) {
            return null;
        }

        $valueModel = $record->customFieldValues()
            ->where('custom_field_id', $customField->id)
            ->first();

        if (! $valueModel) {
            return null;
        }

        $value = $valueModel->getValue();

        if ($customField->type === 'select' && ! is_null($value)) {
            $option = $this->resolveOption($record, $customField);

            return $option instanceof CustomFieldOption ? $option->name : $value;
        }

        return $value;
    }

    public function resolveOption(HasCustomFieldsContract $record, CustomField $customField): ?CustomFieldOption
    {
        if (! $record instanceof Model) {
            return null;
        }

        $valueModel = $record->customFieldValues()
            ->where('custom_field_id', $customField->id)
            ->first();

        if (! $valueModel) {
            return null;
        }

        $value = $valueModel->getValue();

        if ($customField->type === 'select' && ! is_null($value)) {
            return $customField->options->firstWhere('id', (int) $value);
        }

        return null;
    }
}
