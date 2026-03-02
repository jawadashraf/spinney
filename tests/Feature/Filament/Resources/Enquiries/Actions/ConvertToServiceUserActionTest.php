<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Resources\Enquiries\Actions;

use App\Enums\EngagementStatus;
use App\Enums\EnquiryStatus;
use App\Enums\InjectionHistory;
use App\Enums\ReferralType;
use App\Enums\ServiceTeam;
use App\Enums\SubstanceUseFrequency;
use App\Enums\TreatmentOutcome;
use App\Models\Enquiry;
use App\Models\People;
use App\Models\User;
use App\Notifications\ServiceUserPromotedNotification;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

// Note: uses() is already globally configured in tests/Pest.php

it('can convert an enquiry to a service user with comprehensive data', function () {
    Notification::fake();

    $staff = User::factory()->withPersonalTeam()->create();
    $staff->assignRole('super_admin');
    $team = $staff->personalTeam();
    
    $superAdmin = User::factory()->create(['email' => 'admin@example.com']);
    $superAdmin->assignRole('super_admin');

    $person = People::factory()->create(['team_id' => $team->id, 'email' => 'caller@example.com']);
    $enquiry = Enquiry::factory()->create([
        'people_id' => $person->id,
        'team_id' => $team->id,
        'status' => EnquiryStatus::OPEN,
    ]);

    actingAs($staff);

    \Livewire\Livewire::test(\App\Filament\Resources\Enquiries\Pages\ListEnquiries::class)
        ->assertSuccessful()
        ->callTableAction('convertToServiceUser', $enquiry, data: [
            'email' => 'new-service-user@example.com',
            'password' => 'password123',
            'date_of_birth' => '1990-01-01',
            'gender' => 'male',
            'ethnicity' => 'White British',
            'phone' => '0123456789',
            'postcode' => 'LE1 1AA',
            'address' => '123 Spinney Hill Road',
            'no_fixed_address' => false,
            'emergency_contact_name' => 'Jane Doe',
            'emergency_contact_number' => '0987654321',
            'consent_data_storage' => true,
            'consent_referrals' => true,
            'consent_communications' => false,
            'addictions' => ['drugs', 'gambling'],
            'substances_used' => ['heroin', 'cocaine', 'beer'],
            'frequency_of_use' => SubstanceUseFrequency::DAILY->value,
            'amount_of_use' => 'Large amount',
            'route_of_use' => ['smoke', 'sniff'],
            'age_first_used' => '15',
            'overdosed_last_month' => true,
            'injection_history' => InjectionHistory::PREVIOUSLY->value,
            'registered_with_gp' => true,
            'gp_name' => 'Dr. Smith',
            'gp_address' => 'Local Health Center',
            'referral_type' => ReferralType::AGENCY->value,
            'referral_source_specify' => 'Police Referral',
            'previous_input' => ['gp', 'drug_agency'],
            'other_issues' => ['criminal_justice', 'housing'],
            'reason_for_referral' => 'Needs urgent support.',
            'target_service_team' => ServiceTeam::DRUG_ALCOHOL->value,
            'engagement_status' => EngagementStatus::ACTIVE->value,
            'referral_targets' => ['spiritual', 'turning_point'],
            'referral_agency_specify' => 'Turning Point Leicester',
            'intervention_offered' => ['quran', 'group_therapy'],
            'treatment_outcome' => TreatmentOutcome::DRUG_FREE->value,
            'internal_notes' => 'High priority case.',
        ])
        ->assertHasNoTableActionErrors();

    // Assert User created
    assertDatabaseHas(User::class, [
        'email' => 'new-service-user@example.com',
    ]);

    $newUser = User::where('email', 'new-service-user@example.com')->first();
    expect($newUser->hasRole('service_user'))->toBeTrue();

    // Assert People updated
    $person->refresh();
    expect($person->type)->toBe('service_user');
    expect($person->is_service_user)->toBeTrue();
    expect($person->user_id)->toBe($newUser->id);
    expect($person->date_of_birth->format('Y-m-d'))->toBe('1990-01-01');
    expect($person->addictions)->toBe(['drugs', 'gambling']);
    expect($person->substances_used)->toBe(['heroin', 'cocaine', 'beer']);
    expect($person->frequency_of_use)->toBe(SubstanceUseFrequency::DAILY->value);
    expect($person->overdosed_last_month)->toBeTrue();
    expect($person->registered_with_gp)->toBeTrue();
    expect($person->gp_name)->toBe('Dr. Smith');
    expect($person->consent_data_storage)->toBeTrue();

    // Assert Enquiry updated
    $enquiry->refresh();
    expect($enquiry->status)->toBe(EnquiryStatus::CONVERTED);
    expect($enquiry->converted_at)->not->toBeNull();

    // Assert Notification sent
    Notification::assertSentTo($superAdmin, ServiceUserPromotedNotification::class);
});
