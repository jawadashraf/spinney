<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use App\Models\CustomField;
use App\Models\CustomFieldValue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

interface HasCustomFields
{
    /**
     * @return MorphMany<CustomFieldValue, Model>
     */
    public function customFieldValues(): MorphMany;

    public function getCustomFieldValue(CustomField $field): mixed;

    public function saveCustomFieldValue(CustomField $field, mixed $value): void;

    /**
     * @param  array<string, mixed>  $values
     */
    public function saveCustomFields(array $values): void;
}
