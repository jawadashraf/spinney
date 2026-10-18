<?php

declare(strict_types=1);

use App\Enums\EnquiryCallType;
use App\Enums\EnquiryCategory;
use App\Enums\EnquiryDirection;
use App\Enums\EnquirySourceType;
use App\Enums\EnquiryStatus;
use App\Livewire\PublicEnquiryForm;
use App\Models\Enquiry;
use App\Models\Team;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->team = Team::factory()->create([
        'name' => 'Spinney Hill Recovery Network',
        'personal_team' => false,
    ]);
});

it('renders the Recovery Network landing page for guest visitors', function (): void {
    $this->get('/')
        ->assertOk()
        ->assertSee('Recovery')
        ->assertSee('Network')
        ->assertSee('Support, Hope')
        ->assertSee('0300 124 0945')
        ->assertSeeLivewire(PublicEnquiryForm::class);
});

it('submits a confidential enquiry from the public landing page', function (): void {
    livewire(PublicEnquiryForm::class)
        ->set('name', 'Tariq Brother')
        ->set('phone', '07123456789')
        ->set('email', 'tariq@example.com')
        ->set('support_for', 'myself')
        ->set('substance', 'both')
        ->set('preferred_contact', 'whatsapp')
        ->set('message', 'Looking for immediate advice and confidential assessment.')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('submitted', true)
        ->assertSee('We Have Received Your Message');

    $enquiry = Enquiry::query()->latest('id')->first();
    expect($enquiry)->not->toBeNull()
        ->and($enquiry->phone)->toBe('07123456789')
        ->and($enquiry->status)->toBe(EnquiryStatus::OPEN)
        ->and($enquiry->source)->toBe(EnquirySourceType::ONLINE)
        ->and($enquiry->direction)->toBe(EnquiryDirection::INBOUND)
        ->and($enquiry->category)->toBe(EnquiryCategory::SELF_HELP)
        ->and($enquiry->caller_note)->toContain('Tariq Brother')
        ->and($enquiry->reason_for_contact)->toContain('Both');
});

it('categorizes enquiry as family advice when seeking help for family member', function (): void {
    livewire(PublicEnquiryForm::class)
        ->set('name', 'Concerned Parent')
        ->set('phone', '07987654321')
        ->set('support_for', 'family')
        ->set('substance', 'drugs')
        ->set('urgent', true)
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('submitted', true);

    $enquiry = Enquiry::query()->latest('id')->first();
    expect($enquiry)->not->toBeNull()
        ->and($enquiry->category)->toBe(EnquiryCategory::FAMILY_ADVICE)
        ->and($enquiry->call_type)->toBe(EnquiryCallType::EMERGENCY)
        ->and($enquiry->safeguarding_flags)->toBeTrue();
});

it('validates that a contact phone number is required', function (): void {
    livewire(PublicEnquiryForm::class)
        ->set('phone', '')
        ->call('submit')
        ->assertHasErrors(['phone' => 'required']);

    expect(Enquiry::query()->count())->toBe(0);
});
