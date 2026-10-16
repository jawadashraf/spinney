<?php

declare(strict_types=1);

namespace App\Http\Requests\Yeastar;

use App\Models\PbxCallLog;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A finished call posted by the PBX's CallJournal scenario. Every value arrives as a string
 * rendered from the template, and empty template variables arrive as empty strings.
 */
final class StoreCallJournalRequest extends FormRequest
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
            'linked_id' => ['required', 'string', 'max:100'],
            'direction' => ['required', 'in:'.PbxCallLog::DIRECTION_INBOUND.','.PbxCallLog::DIRECTION_OUTBOUND],
            'disposition' => ['required', 'string', 'max:20'],
            'status' => ['nullable', 'string', 'max:20'],
            'talk_duration_sec' => ['nullable', 'integer', 'min:0'],
            'started_at' => ['required', 'integer', 'min:1'],
            'ended_at' => ['nullable', 'integer', 'gte:started_at'],
            'crm_user_id' => ['nullable', 'string', 'max:50'],
            'contact_id' => ['nullable', 'string', 'max:50'],
            'contact_number' => ['nullable', 'string', 'max:50'],
            'recording_url' => ['nullable', 'string', 'max:2048'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'direction' => mb_strtolower(mb_trim((string) $this->input('direction'))),
            'disposition' => mb_strtoupper(mb_trim((string) $this->input('disposition'))),
        ]);
    }
}
