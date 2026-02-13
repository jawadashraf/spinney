<?php

declare(strict_types=1);

use App\Enums\EnquiryStatus;
use App\Enums\ServiceTeam;
use App\Filament\Resources\Enquiries\Actions\ConvertToServiceUserAction;
use App\Filament\Resources\Enquiries\Pages\ListEnquiries;
use App\Models\CustomField;
use App\Models\Enquiry;
use App\Models\People;
use App\Models\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->user->assignRole('super_admin');
    $this->actingAs($this->user);

    // Ensure custom fields are seeded for the test team
    $this->seed(\Database\Seeders\ServiceUserCustomFieldSeeder::class);
});

it('hides conversion action for already converted enquiries', function (): void {
    $enquiry = Enquiry::factory()->create([
        'status' => EnquiryStatus::CONVERTED,
        'people_id' => People::factory()->create()->id,
    ]);

    livewire(ListEnquiries::class)
        ->assertTableActionHidden(ConvertToServiceUserAction::class, $enquiry);
});

it('hides conversion action for enquiries without linked caller', function (): void {
    $enquiry = Enquiry::factory()->create([
        'status' => EnquiryStatus::OPEN,
        'people_id' => null,
    ]);

    livewire(ListEnquiries::class)
        ->assertTableActionHidden(ConvertToServiceUserAction::class, $enquiry);
});

it('can convert open enquiry to service user', function (): void {
    $person = People::factory()->create([
        'is_service_user' => false,
    ]);

    $enquiry = Enquiry::factory()->create([
        'status' => EnquiryStatus::OPEN,
        'people_id' => $person->id,
        'user_id' => $this->user->id,
        'reason_for_contact' => 'Needs help with housing',
        'risk_flags' => 'Low risk',
    ]);

    livewire(ListEnquiries::class)
        ->assertTableActionExists('convertToServiceUser')
        ->mountTableAction('convertToServiceUser', $enquiry)
        ->dump()
        ->setTableActionData([
            'consent_data_storage' => true,
            'consent_referrals' => true,
            'consent_communications' => false,
            'presenting_issues' => 'Needs help with housing',
            'risk_summary' => 'Low risk',
            'target_service_team' => ServiceTeam::ASSESSMENT->value,
            'engagement_status' => 'active',
        ])
        ->callMountedTableAction()
        ->assertHasNoTableActionErrors();

    // Verify Enquiry status
    $this->assertDatabaseHas('enquiries', [
        'id' => $enquiry->id,
        'status' => EnquiryStatus::CONVERTED,
    ]);
    expect($enquiry->refresh()->converted_at)->not->toBeNull();

    // Verify Person status
    $this->assertDatabaseHas('people', [
        'id' => $person->id,
        'is_service_user' => true,
    ]);

    // Verify Custom Fields
    $consentField = CustomField::where('code', 'consent_data_storage')->first();
    $teamField = CustomField::where('code', 'service_team')->first();

    expect($person->refresh()->getCustomFieldValue($consentField))->toBeTrue();
    expect($person->getCustomFieldValue($teamField))->toBe(ServiceTeam::ASSESSMENT->value);
});
