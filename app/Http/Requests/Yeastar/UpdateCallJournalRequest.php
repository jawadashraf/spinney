<?php

declare(strict_types=1);

namespace App\Http\Requests\Yeastar;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Later details for a journalled call, sent by the PBX's CallJournalUpdate request once the recording is ready.
 */
final class UpdateCallJournalRequest extends FormRequest
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
            'recording_url' => ['nullable', 'string', 'max:2048'],
        ];
    }
}
