<?php

declare(strict_types=1);

use App\Enums\CallerType;
use App\Enums\EnquiryCallType;
use App\Enums\EnquiryDirection;
use App\Enums\EnquirySourceType;
use App\Enums\EnquiryStatus;
use App\Filament\Resources\Enquiries\Pages\CreateEnquiry;
use App\Filament\Resources\Enquiries\Pages\EditEnquiry;
use App\Filament\Resources\Enquiries\Pages\ListEnquiries;
use App\Filament\Resources\Enquiries\Pages\ViewEnquiry;
use App\Models\Department;
use App\Models\Enquiry;
use App\Models\People;
use App\Models\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->user->assignRole('super_admin');
    $this->actingAs($this->user);
});

it('can render the index page', function () {
    livewire(ListEnquiries::class)
        ->assertOk();
});

it('can render the view page', function () {
    $record = Enquiry::factory()->create();

    livewire(ViewEnquiry::class, ['record' => $record->getKey()])
        ->assertOk();
});

it('can render the create page', function () {
    livewire(CreateEnquiry::class)
        ->assertOk();
});

it('can render the edit page', function () {
    $record = Enquiry::factory()->create([
        'user_id' => $this->user->id,
    ]);

    livewire(EditEnquiry::class, ['record' => $record->getKey()])
        ->assertOk();
});

it('has correctly configured table columns', function (string $column) {
    livewire(ListEnquiries::class)
        ->assertTableColumnExists($column);
})->with(['category', 'people.name', 'reason_for_contact', 'safeguarding_flags', 'user.name', 'occurred_at', 'status', 'created_at']);

it('has correctly configured table filters', function (string $filter) {
    livewire(ListEnquiries::class)
        ->assertTableFilterExists($filter);
})->with(['category', 'user_id', 'status', 'safeguarding_flags', 'occurred_at', 'direction', 'call_type', 'source', 'department_id']);

it('lists newest enquiries first', function () {
    Enquiry::factory()->create(['occurred_at' => now()->subDay(), 'user_id' => $this->user->id]);
    Enquiry::factory()->create(['occurred_at' => now(), 'user_id' => $this->user->id]);

    livewire(ListEnquiries::class)
        ->assertOk();
});

it('stores caller_note correctly on model', function () {
    $enquiry = Enquiry::factory()->create([
        'user_id' => $this->user->id,
        'caller_note' => 'Walk-in, elderly woman',
    ]);

    expect($enquiry->fresh()->caller_note)->toBe('Walk-in, elderly woman');
});

it('stores caller_note correctly', function () {
    $enquiry = Enquiry::factory()->create([
        'user_id' => $this->user->id,
        'caller_note' => 'Walk-in, elderly woman',
    ]);

    expect($enquiry->fresh()->caller_note)->toBe('Walk-in, elderly woman');
});

it('shows anonymous in caller column when people_id is null', function () {
    Enquiry::factory()->create([
        'people_id' => null,
        'user_id' => $this->user->id,
    ]);

    livewire(ListEnquiries::class)
        ->assertOk();
});

it('displays safeguarding filter on list page', function () {
    livewire(ListEnquiries::class)
        ->assertTableFilterExists('safeguarding_flags');
});

it('displays date range filter on list page', function () {
    livewire(ListEnquiries::class)
        ->assertTableFilterExists('occurred_at');
});

it('shows close enquiry action only for open enquiries', function () {
    $openEnquiry = Enquiry::factory()->create([
        'status' => EnquiryStatus::OPEN,
        'user_id' => $this->user->id,
    ]);

    $closedEnquiry = Enquiry::factory()->create([
        'status' => EnquiryStatus::CLOSED,
        'user_id' => $this->user->id,
    ]);

    livewire(ListEnquiries::class)
        ->assertOk();
});

it('displays referral type and destination in view page when set', function () {
    $record = Enquiry::factory()->create([
        'user_id' => $this->user->id,
        'referral_type' => 'internal',
        'referral_destination' => 'Local Authority',
    ]);

    livewire(ViewEnquiry::class, ['record' => $record->getKey()])
        ->assertOk();
});

it('hides referral section when referral_type is null', function () {
    $record = Enquiry::factory()->create([
        'user_id' => $this->user->id,
        'referral_type' => null,
        'referral_destination' => null,
    ]);

    livewire(ViewEnquiry::class, ['record' => $record->getKey()])
        ->assertOk();
});

it('auto-sets caller type to anonymous when people_id is null', function () {
    $enquiry = Enquiry::factory()->create([
        'people_id' => null,
        'user_id' => $this->user->id,
    ]);

    expect($enquiry->fresh()->caller_type)->toBe(CallerType::ANONYMOUS);
});

it('auto-sets safeguarding flags when call type is emergency', function () {
    $enquiry = Enquiry::factory()->create([
        'call_type' => EnquiryCallType::EMERGENCY,
        'safeguarding_flags' => false,
        'user_id' => $this->user->id,
    ]);

    expect($enquiry->fresh()->safeguarding_flags)->toBeTrue();
});

it('auto-sets caller type to known person when people_id is set', function () {
    $person = People::factory()->create(['is_service_user' => false]);

    $enquiry = Enquiry::factory()->create([
        'people_id' => $person->id,
        'user_id' => $this->user->id,
    ]);

    expect($enquiry->fresh()->caller_type)->toBe(CallerType::KNOWN_PERSON);
});

it('auto-sets caller type to service user when person is service user', function () {
    $person = People::factory()->create(['is_service_user' => true]);

    $enquiry = Enquiry::factory()->create([
        'people_id' => $person->id,
        'user_id' => $this->user->id,
    ]);

    expect($enquiry->fresh()->caller_type)->toBe(CallerType::SERVICE_USER);
});

it('has new table columns for liaison fields', function (string $column) {
    livewire(ListEnquiries::class)
        ->assertTableColumnExists($column);
})->with(['direction', 'call_type', 'source', 'department.name', 'due_date', 'outcome']);

it('can store liaison fields', function () {
    $enquiry = Enquiry::factory()->create([
        'user_id' => $this->user->id,
        'direction' => EnquiryDirection::OUTBOUND,
        'call_type' => EnquiryCallType::FOLLOW_UP,
        'source' => EnquirySourceType::PHONE,
        'due_date' => now()->addDay(),
    ]);

    expect($enquiry->fresh()->direction)->toBe(EnquiryDirection::OUTBOUND);
    expect($enquiry->fresh()->call_type)->toBe(EnquiryCallType::FOLLOW_UP);
    expect($enquiry->fresh()->source)->toBe(EnquirySourceType::PHONE);
});

it('parent enquiry relationship works', function () {
    $parent = Enquiry::factory()->create(['user_id' => $this->user->id]);
    $child = Enquiry::factory()->create([
        'parent_enquiry_id' => $parent->id,
        'user_id' => $this->user->id,
    ]);

    expect($child->fresh()->parentEnquiry->id)->toBe($parent->id);
    expect($parent->fresh()->childEnquiries->count())->toBe(1);
});

it('department relationship works', function () {
    $department = Department::factory()->create(['team_id' => $this->user->currentTeam?->id ?? 1]);
    $enquiry = Enquiry::factory()->create([
        'department_id' => $department->id,
        'user_id' => $this->user->id,
    ]);

    expect($enquiry->fresh()->department->id)->toBe($department->id);
});
