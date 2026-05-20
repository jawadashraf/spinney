<?php

declare(strict_types=1);

use App\Filament\Resources\CompanyResource\Pages\ListCompanies;
use App\Filament\Resources\CompanyResource\Pages\ViewCompany;
use App\Models\Company;
use App\Models\Team;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->team = Team::first() ?? Team::factory()->create();
    $this->user = User::factory()->create(['current_team_id' => $this->team->id]);
    $this->user->teams()->attach($this->team, ['role' => 'admin']);
    $this->user->assignRole('super_admin');
    $this->actingAs($this->user);
    Filament::setTenant($this->team);
});

it('can render the index page', function (): void {
    livewire(ListCompanies::class)
        ->assertOk();
});

it('can render the view page', function (): void {
    $record = Company::factory()->create();

    livewire(ViewCompany::class, ['record' => $record->getKey()])
        ->assertOk();
});

it('can render `:dataset` column', function (string $column): void {
    livewire(ListCompanies::class)
        ->assertCanRenderTableColumn($column);
})->with(['logo', 'name', 'accountOwner.name', 'creator.name', 'created_at', 'updated_at']);

it('cannot render `:dataset` column', function (string $column): void {
    livewire(ListCompanies::class)
        ->assertCanNotRenderTableColumn($column);
})->with(['deleted_at']);

it('has `:dataset` column', function (string $column): void {
    livewire(ListCompanies::class)
        ->assertTableColumnExists($column);
})->with(['logo', 'name', 'accountOwner.name', 'creator.name', 'deleted_at', 'created_at', 'updated_at']);

it('shows `:dataset` column', function (string $column): void {
    livewire(ListCompanies::class)
        ->assertTableColumnVisible($column);
})->with(['logo', 'name', 'accountOwner.name', 'creator.name', 'deleted_at', 'created_at', 'updated_at']);

it('can sort `:dataset` column', function (string $column): void {
    $records = Company::factory(3)->create();

    $sortingKey = data_get($records->first(), $column) instanceof BackedEnum
        ? fn (Model $record) => data_get($record, $column)->value
        : $column;

    livewire(ListCompanies::class)
        ->sortTable($column)
        ->assertCanSeeTableRecords($records->sortBy($sortingKey), inOrder: true)
        ->sortTable($column, 'desc')
        ->assertCanSeeTableRecords($records->sortByDesc($sortingKey), inOrder: true);
})->with(['name', 'accountOwner.name', 'creator.name', 'deleted_at', 'created_at', 'updated_at']);

it('can search `:dataset` column', function (string $column): void {
    $records = Company::factory(3)->create();
    $search = data_get($records->first(), $column);

    $visibleRecords = $records->filter(fn (Model $record) => data_get($record, $column) === $search);

    livewire(ListCompanies::class)
        ->searchTable($search instanceof BackedEnum ? $search->value : $search)
        ->assertCanSeeTableRecords($visibleRecords)
        ->assertCountTableRecords($visibleRecords->count());
})->with(['name', 'accountOwner.name', 'creator.name']);

it('cannot display trashed records by default', function (): void {
    $records = Company::factory()->count(4)->create();
    $trashedRecords = Company::factory()->trashed()->count(6)->create();

    livewire(ListCompanies::class)
        ->assertCanSeeTableRecords($records)
        ->assertCanNotSeeTableRecords($trashedRecords)
        ->assertCountTableRecords(4);
});

it('can paginate records', function (): void {
    $records = Company::factory(20)->create();

    // Fetch records with the same sort order as the table (created_at DESC)
    $sortedRecords = Company::query()
        ->whereIn('id', $records->pluck('id'))
        ->orderBy('created_at', 'desc')
        ->get();

    livewire(ListCompanies::class)
        ->assertCanSeeTableRecords($sortedRecords->take(10), inOrder: true)
        ->call('gotoPage', 2)
        ->assertCanSeeTableRecords($sortedRecords->skip(10)->take(10), inOrder: true);
});

it('can bulk delete records', function (): void {
    $records = Company::factory(5)->create();

    livewire(ListCompanies::class)
        ->assertCanSeeTableRecords($records)
        ->selectTableRecords($records)
        // NOTE: Using direct action array instead of TestAction::make()->bulk()
        // because TestAction triggers unnecessary form building during bulk actions
        ->callAction([['name' => 'delete', 'context' => ['table' => true, 'bulk' => true]]])
        ->assertNotified()
        ->assertCanNotSeeTableRecords($records);

    $this->assertSoftDeleted($records);
});

it('has `:dataset` filter', function (string $filter): void {
    livewire(ListCompanies::class)
        ->assertTableFilterExists($filter);
})->with(['creation_source', 'trashed']);
