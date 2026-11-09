<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Enums\ReferralPriority;
use App\Models\ReferralOrganisation;
use Filament\Facades\Filament;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithPagination;

final class ReferralDirectoryLookup extends Component
{
    use WithPagination;

    public string $search = '';

    public string $selectedArea = '';

    public string $selectedPriority = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingSelectedArea(): void
    {
        $this->resetPage();
    }

    public function updatingSelectedPriority(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'selectedArea', 'selectedPriority']);
        $this->resetPage();
    }

    public function render(): View
    {
        $tenant = Filament::getTenant();

        $query = ReferralOrganisation::query()
            ->where('is_active', true)
            ->when($tenant !== null, fn (Builder $q) => $q->where('team_id', $tenant->getKey()))
            ->when(filled($this->search), function (Builder $q): void {
                $term = '%'.trim($this->search).'%';
                $q->where(function (Builder $sub) use ($term): void {
                    $sub->where('name', 'like', $term)
                        ->orWhere('area_of_support', 'like', $term)
                        ->orWhere('what_they_help_with', 'like', $term)
                        ->orWhere('volunteer_notes', 'like', $term)
                        ->orWhere('telephone', 'like', $term);
                });
            })
            ->when(filled($this->selectedArea), fn (Builder $q) => $q->where('area_of_support', $this->selectedArea))
            ->when(filled($this->selectedPriority), fn (Builder $q) => $q->where('priority', $this->selectedPriority))
            ->orderByRaw("CASE 
                WHEN priority = 'EMERGENCY' THEN 1 
                WHEN priority = 'Urgent' THEN 2 
                WHEN priority = 'Routine / urgent' THEN 3 
                WHEN priority = 'Routine' THEN 4 
                ELSE 5 END")
            ->orderBy('name');

        /** @var Collection<int, string> $areas */
        $areas = ReferralOrganisation::query()
            ->when($tenant !== null, fn (Builder $q) => $q->where('team_id', $tenant->getKey()))
            ->where('is_active', true)
            ->distinct()
            ->orderBy('area_of_support')
            ->pluck('area_of_support');

        return view('livewire.referral-directory-lookup', [
            'organisations' => $query->paginate(15),
            'areas' => $areas,
            'priorities' => ReferralPriority::cases(),
        ]);
    }
}

