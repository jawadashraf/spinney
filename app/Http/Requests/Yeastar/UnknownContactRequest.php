<?php

declare(strict_types=1);

namespace App\Http\Requests\Yeastar;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The PBX's CreateNewContact request for a number it could not match.
 */
final class UnknownContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'phone' => ['nullable', 'string', 'max:50'],
        ];
    }
}
