<?php

declare(strict_types=1);

use App\Enums\EnquiryStatus;
use App\Filament\Resources\Enquiries\Pages\ListEnquiries;
use App\Filament\Resources\Enquiries\Pages\ViewEnquiry;
use App\Models\Enquiry;
use App\Models\People;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->user->assignRole('super_admin');
    actingAs($this->user);
});

it('can close an open enquiry', function () {
    $enquiry = Enquiry::factory()->create([
        'status' => EnquiryStatus::OPEN,
        'user_id' => $this->user->id,
    ]);

    livewire(ListEnquiries::class)
        ->callTableAction('closeEnquiry', $enquiry, data: [
            'closure_notes' => 'Case resolved.',
        ])
        ->assertHasNoTableActionErrors();

    expect($enquiry->fresh()->status)->toBe(EnquiryStatus::CLOSED);
});

it('hides close action for already closed enquiries', function () {
    $enquiry = Enquiry::factory()->create([
        'status' => EnquiryStatus::CLOSED,
        'user_id' => $this->user->id,
    ]);

    livewire(ListEnquiries::class)
        ->assertTableActionHidden('closeEnquiry', $enquiry);
});

it('can link an anonymous enquiry to a person', function () {
    $person = People::factory()->create([
        'phone' => '07700900001',
    ]);

    $enquiry = Enquiry::factory()->create([
        'people_id' => null,
        'phone' => null,
        'status' => EnquiryStatus::OPEN,
        'user_id' => $this->user->id,
    ]);

    livewire(ViewEnquiry::class, ['record' => $enquiry->getKey()])
        ->callAction('linkToPerson', data: [
            'people_id' => $person->id,
        ])
        ->assertHasNoActionErrors();

    $enquiry = $enquiry->fresh();
    expect($enquiry->people_id)->toBe($person->id);
});

it('copies phone from person when linking if enquiry has no phone', function () {
    $person = People::factory()->create([
        'phone' => '07700900001',
    ]);

    $enquiry = Enquiry::factory()->create([
        'people_id' => null,
        'phone' => null,
        'status' => EnquiryStatus::OPEN,
        'user_id' => $this->user->id,
    ]);

    livewire(ViewEnquiry::class, ['record' => $enquiry->getKey()])
        ->callAction('linkToPerson', data: [
            'people_id' => $person->id,
        ]);

    expect($enquiry->fresh()->phone)->toBe('07700900001');
});

it('does not overwrite enquiry phone when linking person', function () {
    $person = People::factory()->create([
        'phone' => '07700900001',
    ]);

    $enquiry = Enquiry::factory()->create([
        'people_id' => null,
        'phone' => '07700999999',
        'status' => EnquiryStatus::OPEN,
        'user_id' => $this->user->id,
    ]);

    livewire(ViewEnquiry::class, ['record' => $enquiry->getKey()])
        ->callAction('linkToPerson', data: [
            'people_id' => $person->id,
        ]);

    expect($enquiry->fresh()->phone)->toBe('07700999999');
});

it('hides link action when enquiry already has a person', function () {
    $person = People::factory()->create();
    $enquiry = Enquiry::factory()->create([
        'people_id' => $person->id,
        'status' => EnquiryStatus::OPEN,
        'user_id' => $this->user->id,
    ]);

    livewire(ViewEnquiry::class, ['record' => $enquiry->getKey()])
        ->assertActionHidden('linkToPerson');
});

it('hides link action when enquiry is not open', function () {
    $enquiry = Enquiry::factory()->create([
        'people_id' => null,
        'status' => EnquiryStatus::CLOSED,
        'user_id' => $this->user->id,
    ]);

    livewire(ViewEnquiry::class, ['record' => $enquiry->getKey()])
        ->assertActionHidden('linkToPerson');
});
