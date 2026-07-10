<?php

declare(strict_types=1);

use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Filament\Resources\UserResource\Pages\ViewUser;
use App\Models\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->user->assignRole('super_admin');
    $this->actingAs($this->user);
});

it('can render the index page', function (): void {
    livewire(ListUsers::class)
        ->assertOk();
});

it('can render the view page', function (): void {
    $record = User::factory()->create();

    livewire(ViewUser::class, ['record' => $record->getKey()])
        ->assertOk();
});

it('has `:dataset` column', function (string $column): void {
    livewire(ListUsers::class)
        ->assertTableColumnExists($column);
})->with(['profile_photo_url', 'first_name', 'last_name', 'email', 'currentTeam.name', 'roles.name', 'email_verified_at', 'created_at']);

it('can render `:dataset` column', function (string $column): void {
    livewire(ListUsers::class)
        ->assertCanRenderTableColumn($column);
})->with(['profile_photo_url', 'first_name', 'last_name', 'email', 'currentTeam.name', 'roles.name']);

it('can search `:dataset` column', function (string $column): void {
    $records = User::factory(3)->create();
    $search = data_get($records->first(), $column);

    livewire(ListUsers::class)
        ->searchTable($search)
        ->assertCanSeeTableRecords($records->filter(fn (User $record) => data_get($record, $column) === $search));
})->with(['first_name', 'last_name', 'email']);

it('can sort `:dataset` column', function (string $column): void {
    User::factory(3)->create();

    livewire(ListUsers::class)
        ->sortTable($column)
        ->assertOk()
        ->sortTable($column, 'desc')
        ->assertOk();
})->with(['first_name', 'last_name', 'email', 'created_at']);

it('has `:dataset` filter', function (string $filter): void {
    livewire(ListUsers::class)
        ->assertTableFilterExists($filter);
})->with(['roles', 'email_verified_at']);

it('shows impersonate action for super admin viewing other users', function (): void {
    $targetUser = User::factory()->create();

    livewire(ListUsers::class)
        ->assertTableActionVisible('impersonate', $targetUser);
});

it('hides impersonate action when viewing self', function (): void {
    livewire(ListUsers::class)
        ->assertTableActionHidden('impersonate', $this->user);
});

it('non-super-admin user cannot impersonate', function (): void {
    $regularUser = User::factory()->create();

    expect($regularUser->canImpersonate())->toBeFalse();
});

it('super admin can impersonate another user', function (): void {
    $targetUser = User::factory()->create();

    expect($this->user->canImpersonate())->toBeTrue()
        ->and($targetUser->canBeImpersonated())->toBeTrue();
});

it('super admin cannot be impersonated', function (): void {
    $otherAdmin = User::factory()->create();
    $otherAdmin->assignRole('super_admin');

    expect($otherAdmin->canBeImpersonated())->toBeFalse();
});
