<?php

declare(strict_types=1);

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

it('can render the index page', function (): void {
    livewire(ListEnquiries::class)
        ->assertOk();
});

it('can render the view page', function (): void {
    $record = Enquiry::factory()->create();

    livewire(ViewEnquiry::class, ['record' => $record->getKey()])
        ->assertOk();
});

it('can render the create page', function (): void {
    livewire(CreateEnquiry::class)
        ->assertOk();
});

it('can render the edit page', function (): void {
    $record = Enquiry::factory()->create([
        'user_id' => $this->user->id,
    ]);

    livewire(EditEnquiry::class, ['record' => $record->getKey()])
        ->assertOk();
});

it('has correctly configured table columns', function (string $column): void {
    livewire(ListEnquiries::class)
        ->assertTableColumnExists($column);
})->with(['category', 'people.name', 'reason_for_contact', 'safeguarding_flags', 'user.name', 'occurred_at']);

it('has correctly configured table filters', function (string $filter): void {
    livewire(ListEnquiries::class)
        ->assertTableFilterExists($filter);
})->with(['category', 'user_id']);

it('can validate form fields', function (string $field, mixed $value, string $rule): void {
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

it('can create an enquiry', function (): void {
    $newData = Enquiry::factory()->make([
        'user_id' => $this->user->id,
    ]);

    livewire(CreateEnquiry::class)
        ->fill([
            'data.category' => $newData->category,
            'data.occurred_at' => $newData->occurred_at,
            'data.reason_for_contact' => $newData->reason_for_contact,
            'data.user_id' => $newData->user_id,
        ])
        ->call('create')
        ->assertHasNoErrors();

    $this->assertDatabaseHas(Enquiry::class, [
        'reason_for_contact' => $newData->reason_for_contact,
        'user_id' => $newData->user_id,
    ]);
});
