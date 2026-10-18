@php
    $data = $this->getReportData();
@endphp

<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <x-filament::icon
                        icon="heroicon-o-users"
                        class="h-5 w-5 text-emerald-500"
                    />
                    <span class="text-base font-semibold">Service User Engagement Report</span>
                </div>
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-800 px-2.5 py-1 rounded-full">
                    {{ $data['dateRangeLabel'] }}
                </span>
            </div>
        </x-slot>

        <!-- KPI Metrics Grid -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="rounded-xl p-4 bg-gradient-to-br from-teal-50 to-emerald-50/50 dark:from-teal-950/20 dark:to-emerald-950/10 border border-teal-100 dark:border-teal-900/30">
                <div class="text-xs font-medium uppercase tracking-wider text-teal-600 dark:text-teal-400">Total Service Users</div>
                <div class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($data['totalServiceUsers']) }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Registered clients</div>
            </div>

            <div class="rounded-xl p-4 bg-gradient-to-br from-emerald-50 to-green-50/50 dark:from-emerald-950/20 dark:to-green-950/10 border border-emerald-100 dark:border-emerald-900/30">
                <div class="text-xs font-medium uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Active Engagement</div>
                <div class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($data['activeCount']) }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Currently engaged</div>
            </div>

            <div class="rounded-xl p-4 bg-gradient-to-br from-amber-50 to-orange-50/50 dark:from-amber-950/20 dark:to-orange-950/10 border border-amber-100 dark:border-amber-900/30">
                <div class="text-xs font-medium uppercase tracking-wider text-amber-600 dark:text-amber-400">Pending Intake</div>
                <div class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($data['pendingCount']) }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Awaiting assessment</div>
            </div>

            <div class="rounded-xl p-4 bg-gradient-to-br from-violet-50 to-purple-50/50 dark:from-violet-950/20 dark:to-purple-950/10 border border-violet-100 dark:border-violet-900/30">
                <div class="text-xs font-medium uppercase tracking-wider text-violet-600 dark:text-violet-400">Needs Support / Attention</div>
                <div class="text-2xl font-bold {{ $data['needsAttentionCount'] > 0 ? 'text-violet-600 dark:text-violet-400' : 'text-gray-900 dark:text-white' }} mt-1">
                    {{ number_format($data['needsAttentionCount']) }}
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Flagged for attention</div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Distribution by Service Team -->
            <div class="border border-gray-200 dark:border-gray-800 rounded-xl p-4 bg-white/50 dark:bg-gray-900/50">
                <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-1.5">
                    <x-filament::icon icon="heroicon-o-academic-cap" class="h-4 w-4 text-emerald-500" />
                    Target Service Team Distribution
                </h4>
                <div class="space-y-3.5">
                    @forelse($data['byServiceTeam'] as $team)
                        <div class="space-y-1">
                            <div class="flex justify-between text-xs">
                                <span class="font-medium text-gray-700 dark:text-gray-300">{{ $team['label'] }}</span>
                                <span class="text-gray-500 dark:text-gray-400 font-semibold">{{ $team['count'] }} ({{ $team['percentage'] }}%)</span>
                            </div>
                            <div class="w-full bg-gray-100 dark:bg-gray-800 rounded-full h-2 overflow-hidden">
                                <div class="bg-emerald-500 h-2 rounded-full transition-all duration-500" style="width: {{ $team['percentage'] }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-gray-400">No service team data found.</p>
                    @endforelse
                </div>
            </div>

            <!-- Distribution by Engagement Status -->
            <div class="border border-gray-200 dark:border-gray-800 rounded-xl p-4 bg-white/50 dark:bg-gray-900/50">
                <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-1.5">
                    <x-filament::icon icon="heroicon-o-chart-pie" class="h-4 w-4 text-emerald-500" />
                    Engagement Status Breakdown
                </h4>
                <div class="space-y-3">
                    @forelse($data['byEngagementStatus'] as $status)
                        <div class="flex items-center justify-between p-3 rounded-lg bg-gray-50 dark:bg-gray-800/60 border border-gray-100 dark:border-gray-800">
                            <div class="flex items-center gap-2.5">
                                <span class="h-2.5 w-2.5 rounded-full {{ match($status['color']) {
                                    'success' => 'bg-emerald-500',
                                    'warning' => 'bg-amber-500',
                                    'danger' => 'bg-rose-500',
                                    default => 'bg-gray-400',
                                } }}"></span>
                                <span class="text-xs font-medium text-gray-800 dark:text-gray-200">{{ $status['label'] }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-bold text-gray-900 dark:text-white">{{ $status['count'] }}</span>
                                <span class="text-xs text-gray-400 font-medium">({{ $status['percentage'] }}%)</span>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-gray-400">No engagement status data found.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
