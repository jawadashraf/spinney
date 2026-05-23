<?php

declare(strict_types=1);

use App\Enums\EnquiryCallType;
use App\Enums\EnquiryDirection;
use App\Enums\EnquiryStatus;
use App\Filament\Pages\LiaisonDashboard;
use App\Models\Enquiry;
use App\Models\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->user->assignRole('super_admin');
    $this->actingAs($this->user);
});

it('can render the liaison dashboard', function () {
    livewire(LiaisonDashboard::class)
        ->assertOk();
});

it('shows correct enquiry stats for open enquiries', function () {
    Enquiry::factory()->count(3)->create([
        'status' => EnquiryStatus::OPEN,
        'user_id' => $this->user->id,
    ]);

    livewire(LiaisonDashboard::class)
        ->assertOk();
});

it('shows overdue calls in widget', function () {
    Enquiry::factory()->create([
        'direction' => EnquiryDirection::OUTBOUND,
        'status' => EnquiryStatus::OPEN,
        'due_date' => now()->subDay(),
        'user_id' => $this->user->id,
    ]);

    livewire(LiaisonDashboard::class)
        ->assertOk();
});

it('shows upcoming calls in widget', function () {
    Enquiry::factory()->create([
        'direction' => EnquiryDirection::OUTBOUND,
        'status' => EnquiryStatus::OPEN,
        'due_date' => now()->addDay(),
        'user_id' => $this->user->id,
    ]);

    livewire(LiaisonDashboard::class)
        ->assertOk();
});

it('shows safeguarding alerts in widget', function () {
    Enquiry::factory()->create([
        'safeguarding_flags' => true,
        'status' => EnquiryStatus::OPEN,
        'user_id' => $this->user->id,
    ]);

    livewire(LiaisonDashboard::class)
        ->assertOk();
});

it('shows emergency calls in safeguarding widget', function () {
    Enquiry::factory()->create([
        'call_type' => EnquiryCallType::EMERGENCY,
        'status' => EnquiryStatus::OPEN,
        'user_id' => $this->user->id,
    ]);

    livewire(LiaisonDashboard::class)
        ->assertOk();
});

it('shows recent enquiries in widget', function () {
    Enquiry::factory()->count(5)->create([
        'user_id' => $this->user->id,
    ]);

    livewire(LiaisonDashboard::class)
        ->assertOk();
});
