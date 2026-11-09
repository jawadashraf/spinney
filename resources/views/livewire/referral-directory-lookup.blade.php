<div class="space-y-4">
    {{-- Search & Filter Controls --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 bg-gray-50 dark:bg-gray-900/50 p-3 rounded-lg border border-gray-200 dark:border-gray-800">
        <div>
            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Search Directory</label>
            <input 
                type="text" 
                wire:model.live.debounce.300ms="search" 
                placeholder="Search service, need, notes..." 
                class="w-full text-sm rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
            />
        </div>

        <div>
            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Support Area</label>
            <select 
                wire:model.live="selectedArea" 
                class="w-full text-sm rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
            >
                <option value="">All Support Areas</option>
                @foreach ($areas as $area)
                    <option value="{{ $area }}">{{ $area }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Priority</label>
            <div class="flex gap-2">
                <select 
                    wire:model.live="selectedPriority" 
                    class="w-full text-sm rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500"
                >
                    <option value="">All Priorities</option>
                    @foreach ($priorities as $priority)
                        <option value="{{ $priority->value }}">{{ $priority->getLabel() }}</option>
                    @endforeach
                </select>

                @if ($search || $selectedArea || $selectedPriority)
                    <button 
                        type="button" 
                        wire:click="resetFilters" 
                        class="px-2.5 py-1.5 text-xs text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white underline whitespace-nowrap self-end mb-1"
                    >
                        Clear
                    </button>
                @endif
            </div>
        </div>
    </div>

    {{-- Results Counter --}}
    <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 px-1">
        <span>Found <strong>{{ $organisations->total() }}</strong> organisation(s)</span>
        @if ($selectedArea || $selectedPriority || $search)
            <span>Filtered results</span>
        @endif
    </div>

    {{-- Organisations List --}}
    <div class="space-y-3">
        @forelse ($organisations as $org)
            <div class="p-4 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm transition hover:border-gray-300 dark:hover:border-gray-700">
                <div class="flex flex-wrap items-start justify-between gap-2 mb-2">
                    <div>
                        <h4 class="text-base font-semibold text-gray-900 dark:text-white">
                            {{ $org->name }}
                        </h4>
                        <div class="flex flex-wrap items-center gap-1.5 mt-1">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200">
                                {{ $org->area_of_support }}
                            </span>

                            @php
                                $badgeClasses = match($org->priority->value) {
                                    'EMERGENCY' => 'bg-red-100 text-red-800 dark:bg-red-900/60 dark:text-red-200',
                                    'Urgent' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/60 dark:text-amber-200',
                                    'Routine / urgent' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/60 dark:text-blue-200',
                                    default => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
                                };
                            @endphp
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $badgeClasses }}">
                                {{ $org->priority->getLabel() }}
                            </span>
                        </div>
                    </div>

                    {{-- Contact Actions --}}
                    <div class="flex items-center gap-2">
                        @if ($org->telephone)
                            <a 
                                href="tel:{{ preg_replace('/[^0-9+]/', '', $org->telephone) }}" 
                                class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium rounded-md bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-950/50 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 transition"
                            >
                                <x-heroicon-m-phone class="w-3.5 h-3.5" />
                                {{ $org->telephone }}
                            </a>
                        @endif

                        @if ($org->website_url)
                            <a 
                                href="{{ $org->website_url }}" 
                                target="_blank" 
                                rel="noopener noreferrer" 
                                class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium rounded-md bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 border border-gray-200 dark:border-gray-700 transition"
                            >
                                <x-heroicon-m-arrow-top-right-on-square class="w-3.5 h-3.5" />
                                Website
                            </a>
                        @endif
                    </div>
                </div>

                {{-- Description --}}
                <p class="text-sm text-gray-600 dark:text-gray-300 mt-2">
                    {{ $org->what_they_help_with }}
                </p>

                {{-- Volunteer Notes (Highlighted) --}}
                @if ($org->volunteer_notes)
                    <div class="mt-3 p-2.5 rounded-lg bg-amber-50/80 dark:bg-amber-950/40 border border-amber-200/80 dark:border-amber-900/50 text-xs text-amber-900 dark:text-amber-200 flex items-start gap-2">
                        <x-heroicon-m-shield-exclamation class="w-4 h-4 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5" />
                        <div>
                            <span class="font-bold">Volunteer Note:</span> {{ $org->volunteer_notes }}
                        </div>
                    </div>
                @endif

                {{-- How to Refer & Extra Details --}}
                <div class="mt-2.5 pt-2 border-t border-gray-100 dark:border-gray-800 flex flex-wrap items-center justify-between text-xs text-gray-500 dark:text-gray-400 gap-2">
                    <div>
                        <span class="font-medium text-gray-700 dark:text-gray-300">How to refer:</span>
                        {{ $org->how_to_refer ?? 'Contact directly' }}
                    </div>

                    @if ($org->address)
                        <div class="flex items-center gap-1">
                            <x-heroicon-m-map-pin class="w-3 h-3 text-gray-400" />
                            <span>{{ $org->address }}</span>
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="text-center py-8 bg-gray-50 dark:bg-gray-900/30 rounded-xl border border-dashed border-gray-300 dark:border-gray-700">
                <x-heroicon-o-magnifying-glass class="w-8 h-8 mx-auto text-gray-400 mb-2" />
                <p class="text-sm font-medium text-gray-600 dark:text-gray-300">No organisations match your search</p>
                <p class="text-xs text-gray-400 mt-1">Try broadening your keywords or clearing the filters.</p>
                @if ($search || $selectedArea || $selectedPriority)
                    <button 
                        type="button" 
                        wire:click="resetFilters" 
                        class="mt-3 inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md bg-primary-50 text-primary-700 hover:bg-primary-100 dark:bg-primary-950 dark:text-primary-300 transition"
                    >
                        Reset All Filters
                    </button>
                @endif
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if ($organisations->hasPages())
        <div class="pt-2">
            {{ $organisations->links() }}
        </div>
    @endif
</div>

