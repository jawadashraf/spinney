@php
    $data = $this->getReportData();
@endphp

<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <x-filament::icon
                        icon="heroicon-o-phone-arrow-down-left"
                        class="h-5 w-5 text-primary-500"
                    />
                    <span class="text-base font-semibold">Enquiry Volume & Category Report</span>
                </div>
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-800 px-2.5 py-1 rounded-full">
                    {{ $data['dateRangeLabel'] }}
                </span>
            </div>
        </x-slot>

        <!-- KPI Metrics Grid -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <a href="{{ \App\Filament\Resources\Enquiries\EnquiryResource::getUrl('index') }}" class="group block rounded-xl p-4 bg-gradient-to-br from-blue-50 to-indigo-50/50 dark:from-blue-950/20 dark:to-indigo-950/10 border border-blue-100 dark:border-blue-900/30 hover:border-blue-300 dark:hover:border-blue-700/50 hover:shadow-sm transition-all duration-200">
                <div class="flex items-center justify-between text-xs font-medium uppercase tracking-wider text-blue-600 dark:text-blue-400">
                    <span>Total Enquiries</span>
                    <x-filament::icon icon="heroicon-m-arrow-top-right-on-square" class="h-3.5 w-3.5 opacity-0 group-hover:opacity-100 transition-opacity" />
                </div>
                <div class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($data['totalEnquiries']) }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Logged calls & contact</div>
            </a>

            <a href="{{ \App\Filament\Resources\Enquiries\EnquiryResource::getUrl('index', ['tableFilters' => ['status' => ['value' => \App\Enums\EnquiryStatus::OPEN->value]]]) }}" class="group block rounded-xl p-4 bg-gradient-to-br from-amber-50 to-yellow-50/50 dark:from-amber-950/20 dark:to-yellow-950/10 border border-amber-100 dark:border-amber-900/30 hover:border-amber-300 dark:hover:border-amber-700/50 hover:shadow-sm transition-all duration-200">
                <div class="flex items-center justify-between text-xs font-medium uppercase tracking-wider text-amber-600 dark:text-amber-400">
                    <span>Open Enquiries</span>
                    <x-filament::icon icon="heroicon-m-arrow-top-right-on-square" class="h-3.5 w-3.5 opacity-0 group-hover:opacity-100 transition-opacity" />
                </div>
                <div class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($data['openCount']) }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Requiring action</div>
            </a>

            <a href="{{ \App\Filament\Resources\Enquiries\EnquiryResource::getUrl('index', ['tableFilters' => ['status' => ['value' => \App\Enums\EnquiryStatus::CLOSED->value]]]) }}" class="group block rounded-xl p-4 bg-gradient-to-br from-emerald-50 to-teal-50/50 dark:from-emerald-950/20 dark:to-teal-950/10 border border-emerald-100 dark:border-emerald-900/30 hover:border-emerald-300 dark:hover:border-emerald-700/50 hover:shadow-sm transition-all duration-200">
                <div class="flex items-center justify-between text-xs font-medium uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
                    <span>Resolved / Converted</span>
                    <x-filament::icon icon="heroicon-m-arrow-top-right-on-square" class="h-3.5 w-3.5 opacity-0 group-hover:opacity-100 transition-opacity" />
                </div>
                <div class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ number_format($data['closedCount']) }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Closed or promoted</div>
            </a>

            <a href="{{ \App\Filament\Resources\Enquiries\EnquiryResource::getUrl('index', ['tableFilters' => ['safeguarding_flags' => ['value' => true]]]) }}" class="group block rounded-xl p-4 bg-gradient-to-br from-rose-50 to-red-50/50 dark:from-rose-950/20 dark:to-red-950/10 border border-rose-100 dark:border-rose-900/30 hover:border-rose-300 dark:hover:border-rose-700/50 hover:shadow-sm transition-all duration-200">
                <div class="flex items-center justify-between text-xs font-medium uppercase tracking-wider text-rose-600 dark:text-rose-400">
                    <span>Safeguarding Flags</span>
                    <x-filament::icon icon="heroicon-m-arrow-top-right-on-square" class="h-3.5 w-3.5 opacity-0 group-hover:opacity-100 transition-opacity" />
                </div>
                <div class="text-2xl font-bold {{ $data['safeguardingCount'] > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-gray-900 dark:text-white' }} mt-1">
                    {{ number_format($data['safeguardingCount']) }}
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Safety alerts flagged</div>
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- By Category Table & Progress -->
            <div class="border border-gray-200 dark:border-gray-800 rounded-xl p-4 bg-white/50 dark:bg-gray-900/50">
                <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-1.5">
                    <x-filament::icon icon="heroicon-o-tag" class="h-4 w-4 text-primary-500" />
                    Enquiries by Category
                </h4>
                <div class="space-y-3">
                    @forelse($data['byCategory'] as $item)
                        <a href="{{ \App\Filament\Resources\Enquiries\EnquiryResource::getUrl('index', ['tableFilters' => ['category' => ['value' => $item['case']->value]]]) }}" class="group block space-y-1 rounded-md p-1 -m-1 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                            <div class="flex justify-between text-xs">
                                <span class="font-medium text-gray-700 dark:text-gray-300 group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">{{ $item['label'] }}</span>
                                <span class="text-gray-500 dark:text-gray-400 font-semibold group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">{{ $item['count'] }} ({{ $item['percentage'] }}%)</span>
                            </div>
                            <div class="w-full bg-gray-100 dark:bg-gray-800 rounded-full h-2 overflow-hidden">
                                <div class="bg-primary-500 h-2 rounded-full transition-all duration-500" style="width: {{ $item['percentage'] }}%"></div>
                            </div>
                        </a>
                    @empty
                        <p class="text-xs text-gray-400">No category data found.</p>
                    @endforelse
                </div>
            </div>

            <!-- By Status Table -->
            <div class="border border-gray-200 dark:border-gray-800 rounded-xl p-4 bg-white/50 dark:bg-gray-900/50">
                <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-1.5">
                    <x-filament::icon icon="heroicon-o-arrow-path" class="h-4 w-4 text-primary-500" />
                    Enquiries by Status
                </h4>
                <div class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($data['byStatus'] as $item)
                        <a href="{{ \App\Filament\Resources\Enquiries\EnquiryResource::getUrl('index', ['tableFilters' => ['status' => ['value' => $item['case']->value]]]) }}" class="group py-2.5 flex items-center justify-between text-xs hover:bg-gray-50/50 dark:hover:bg-gray-800/40 rounded px-1 -mx-1 transition-colors">
                            <span class="inline-flex items-center gap-1.5">
                                <span class="h-2 w-2 rounded-full bg-primary-500"></span>
                                <span class="font-medium text-gray-700 dark:text-gray-300 group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">{{ $item['label'] }}</span>
                            </span>
                            <div class="flex items-center gap-2">
                                <span class="font-semibold text-gray-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">{{ $item['count'] }}</span>
                                <span class="text-gray-400 text-[11px]">({{ $item['percentage'] }}%)</span>
                            </div>
                        </a>
                    @empty
                        <p class="text-xs text-gray-400">No status data found.</p>
                    @endforelse
                </div>
            </div>

            <!-- Handling Team Members -->
            <div class="border border-gray-200 dark:border-gray-800 rounded-xl p-4 bg-white/50 dark:bg-gray-900/50">
                <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-1.5">
                    <x-filament::icon icon="heroicon-o-user-group" class="h-4 w-4 text-primary-500" />
                    Handling Team Members
                </h4>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-gray-100 dark:border-gray-800 text-gray-500 dark:text-gray-400">
                                <th class="pb-2 font-medium">Staff</th>
                                <th class="pb-2 font-medium text-center">Total</th>
                                <th class="pb-2 font-medium text-center">Open</th>
                                <th class="pb-2 font-medium text-right">Resolved</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @forelse($data['byStaff'] as $staff)
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/40 transition-colors">
                                    <td class="py-2 font-medium text-gray-900 dark:text-white truncate max-w-[100px]">
                                        <a href="{{ $staff['user_id'] ? \App\Filament\Resources\Enquiries\EnquiryResource::getUrl('index', ['tableFilters' => ['user_id' => ['value' => $staff['user_id']]]]) : \App\Filament\Resources\Enquiries\EnquiryResource::getUrl('index') }}" class="hover:text-primary-600 dark:hover:text-primary-400 hover:underline">
                                            {{ $staff['staff'] }}
                                        </a>
                                    </td>
                                    <td class="py-2 text-center font-semibold text-gray-700 dark:text-gray-300">
                                        <a href="{{ $staff['user_id'] ? \App\Filament\Resources\Enquiries\EnquiryResource::getUrl('index', ['tableFilters' => ['user_id' => ['value' => $staff['user_id']]]]) : \App\Filament\Resources\Enquiries\EnquiryResource::getUrl('index') }}" class="hover:underline">
                                            {{ $staff['total'] }}
                                        </a>
                                    </td>
                                    <td class="py-2 text-center text-amber-600 dark:text-amber-400">
                                        <a href="{{ $staff['user_id'] ? \App\Filament\Resources\Enquiries\EnquiryResource::getUrl('index', ['tableFilters' => ['user_id' => ['value' => $staff['user_id']], 'status' => ['value' => \App\Enums\EnquiryStatus::OPEN->value]]]) : \App\Filament\Resources\Enquiries\EnquiryResource::getUrl('index', ['tableFilters' => ['status' => ['value' => \App\Enums\EnquiryStatus::OPEN->value]]]) }}" class="hover:underline">
                                            {{ $staff['open'] }}
                                        </a>
                                    </td>
                                    <td class="py-2 text-right text-emerald-600 dark:text-emerald-400">
                                        <a href="{{ $staff['user_id'] ? \App\Filament\Resources\Enquiries\EnquiryResource::getUrl('index', ['tableFilters' => ['user_id' => ['value' => $staff['user_id']], 'status' => ['value' => \App\Enums\EnquiryStatus::CLOSED->value]]]) : \App\Filament\Resources\Enquiries\EnquiryResource::getUrl('index', ['tableFilters' => ['status' => ['value' => \App\Enums\EnquiryStatus::CLOSED->value]]]) }}" class="hover:underline">
                                            {{ $staff['resolved'] }}
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-3 text-center text-gray-400 text-xs">
                                        No team allocations in selected range.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
