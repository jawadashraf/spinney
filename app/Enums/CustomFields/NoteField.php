<?php

declare(strict_types=1);

namespace App\Enums\CustomFields;

/**
 * Note custom field codes
 */
enum NoteField: string
{
    use CustomFieldTrait;

    case BODY = 'body';

    public function getFieldType(): string
    {
        return 'note';
    }
}
