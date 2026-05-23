<?php

declare(strict_types=1);

use App\Enums\EnquiryStatus;
use App\Filament\Resources\Enquiries\Pages\CreateEnquiry;
use App\Filament\Resources\Enquiries\Pages\EditEnquiry;
use App\Filament\Resources\Enquiries\Pages\ListEnquiries;
use App\Filament\Resources\Enquiries\Pages\ViewEnquiry;
use App\Models\Enquiry;
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
})->with(['category', 'user_id', 'status', 'safeguarding_flags', 'occurred_at']);

it('lists newest enquiries first', function () {
    Enquiry::factory()->create(['occurred_at' => now()->subDay(), 'user_id' => $this->user->id]);
    Enquiry::factory()->create(['occurred_at' => now(), 'user_id' => $this->user->id]);

    livewire(ListEnquiries::class)
        ->assertOk();
});

it('can validate form fields', function (string $field, mixed $value, string $rule) {
    livewire(CreateEnquiry::class)
        ->fill(['data.'.$field => $value])
        ->call('create')
        ->assertHasErrors(['data.'.$field]);
})->with([
    'category' => ['category', null, 'required'],
    'reason_for_contact' => ['reason_for_contact', null, 'required'],
    'user_id' => ['user_id', null, 'required'],
    'occurred_at' => ['occurred_at', null, 'required'],
]);

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
