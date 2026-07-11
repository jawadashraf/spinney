@php
    $livewire = $this;
    $activeTab = $livewire->activeServiceUserTab ?? null;
    $tabs = \App\Filament\Resources\ServiceUsers\Schemas\ServiceUserForm::TABS;
    $currentIndex = array_search($activeTab, $tabs, true);

    $hasPrevious = $currentIndex !== false && $currentIndex > 0;
    $hasNext = $currentIndex !== false && $currentIndex < count($tabs) - 1;
@endphp

@if($hasPrevious || $hasNext)
    <div class="flex justify-between items-center py-4">
        <div>
            @if($hasPrevious)
                <x-filament::button
                    color="gray"
                    icon="heroicon-m-arrow-left"
                    wire:click="previousTab"
                    type="button"
                >
                    Back
                </x-filament::button>
            @endif
        </div>
        <div>
            @if($hasNext)
                <x-filament::button
                    icon="heroicon-m-arrow-right"
                    icon-position="after"
                    wire:click="nextTab"
                    type="button"
                >
                    Next
                </x-filament::button>
            @endif
        </div>
    </div>
@endif
