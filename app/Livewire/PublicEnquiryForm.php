<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Enums\CallerType;
use App\Enums\EnquiryCallType;
use App\Enums\EnquiryCategory;
use App\Enums\EnquiryDirection;
use App\Enums\EnquirySourceType;
use App\Enums\EnquiryStatus;
use App\Models\Enquiry;
use App\Models\Team;
use App\Models\User;
use Livewire\Component;

final class PublicEnquiryForm extends Component
{
    public string $name = '';

    public string $phone = '';

    public string $email = '';

    public string $support_for = 'myself';

    public string $substance = 'drugs';

    public string $preferred_contact = 'phone';

    public bool $is_anonymous = false;

    public bool $urgent = false;

    public string $message = '';

    public bool $submitted = false;

    /**
     * @return array<string, string>
     */
    protected function rules(): array
    {
        return [
            'name' => $this->is_anonymous ? 'nullable|string|max:100' : 'nullable|string|max:100',
            'phone' => 'required|string|min:7|max:30',
            'email' => 'nullable|email|max:120',
            'support_for' => 'required|string|in:myself,family,friend,professional',
            'substance' => 'required|string|in:drugs,alcohol,both,gambling,other',
            'preferred_contact' => 'required|string|in:phone,text,whatsapp,email',
            'message' => 'nullable|string|max:1500',
            'is_anonymous' => 'boolean',
            'urgent' => 'boolean',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'phone.required' => 'Please provide a contact phone number so our team can reach you.',
            'phone.min' => 'Please enter a valid phone number with area code.',
            'email.email' => 'Please enter a valid email address.',
        ];
    }

    public function submit(): void
    {
        $this->validate();

        $team = Team::where('personal_team', false)->first() ?? Team::first();

        $category = match ($this->support_for) {
            'family', 'friend' => EnquiryCategory::FAMILY_ADVICE,
            default => EnquiryCategory::SELF_HELP,
        };

        $callType = $this->urgent ? EnquiryCallType::EMERGENCY : EnquiryCallType::GENERAL;
        $callerType = ($this->is_anonymous || blank($this->name)) ? CallerType::ANONYMOUS : CallerType::KNOWN_PERSON;

        $reason = "Online enquiry via recoverynetwork.com.\n"
            .'Seeking help for: '.ucfirst($this->support_for).".\n"
            .'Focus area: '.ucfirst($this->substance).".\n"
            .'Preferred contact method: '.ucfirst($this->preferred_contact).'.';

        if (filled($this->message)) {
            $reason .= "\n\nAdditional Message:\n".trim($this->message);
        }

        $callerNote = "Online Submission Details:\n"
            .'Submitted by: '.($this->is_anonymous ? 'Anonymous request' : ($this->name ?: 'Not specified'))."\n"
            ."Phone: {$this->phone}\n"
            .'Email: '.($this->email ?: 'Not provided')."\n"
            .'Urgent priority flagged: '.($this->urgent ? 'YES - immediate response requested' : 'Standard triage');

        $assigneeId = $team?->users()->first()?->id
            ?? User::first()?->id;

        if (! $assigneeId) {
            $fallbackUser = User::factory()->create([
                'team_id' => $team?->id ?? 1,
            ]);
            $assigneeId = $fallbackUser->id;
        }

        Enquiry::create([
            'team_id' => $team?->id ?? 1,
            'user_id' => $assigneeId,
            'phone' => $this->phone,
            'occurred_at' => now(),
            'status' => EnquiryStatus::OPEN,
            'source' => EnquirySourceType::ONLINE->value,
            'direction' => EnquiryDirection::INBOUND,
            'call_type' => $callType,
            'caller_type' => $callerType,
            'category' => $category,
            'reason_for_contact' => $reason,
            'caller_note' => $callerNote,
            'safeguarding_flags' => $this->urgent,
        ]);

        $this->submitted = true;
    }

    public function resetForm(): void
    {
        $this->reset(['name', 'phone', 'email', 'support_for', 'substance', 'preferred_contact', 'is_anonymous', 'urgent', 'message', 'submitted']);
    }

    public function render(): mixed
    {
        return view('livewire.public-enquiry-form');
    }
}
