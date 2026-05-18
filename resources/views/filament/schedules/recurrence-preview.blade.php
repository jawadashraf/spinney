<div class="space-y-4">
    @if($is_valid)
        <!-- Recurrence Card -->
        <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900/30 p-4 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                    Recurrence Summary
                </span>
                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-medium bg-primary-50 text-primary-700 dark:bg-primary-950/30 dark:text-primary-400">
                    <span class="w-1.5 h-1.5 rounded-full bg-primary-600 dark:bg-primary-400 animate-pulse"></span>
                    {{ $is_recurring ? 'Recurring' : 'One-time' }}
                </span>
            </div>

            <!-- Natural Language Text -->
            <p class="text-sm font-medium text-gray-800 dark:text-gray-200 leading-relaxed">
                {{ $summary }}
            </p>

            @if($timezone)
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                    Timezone: <span class="font-medium text-gray-500 dark:text-gray-400">{{ $timezone }}</span>
                </p>
            @endif
        </div>

        <!-- Next Occurrences Section -->
        <div>
            <h4 class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-3 px-1">
                {{ $is_recurring ? 'Next Computed Occurrences' : 'Occurrence' }}
            </h4>
            
            <div class="space-y-2">
                @forelse($occurrences as $occurrence)
                    <div class="flex items-center gap-3 p-3 rounded-lg border border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900 hover:border-primary-100 dark:hover:border-primary-900/30 transition-all duration-200 shadow-sm">
                        <!-- Calendar Icon Box -->
                        <div class="flex flex-col items-center justify-center w-11 h-11 rounded-lg bg-primary-50 dark:bg-primary-950/20 text-primary-600 dark:text-primary-400 border border-primary-100/50 dark:border-primary-950/30">
                            <span class="text-[10px] font-bold uppercase tracking-tight text-primary-500">
                                {{ $occurrence->format('M') }}
                            </span>
                            <span class="text-lg font-extrabold leading-none -mt-0.5">
                                {{ $occurrence->format('d') }}
                            </span>
                        </div>

                        <!-- Date details -->
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 truncate">
                                {{ $occurrence->format('l') }}
                            </p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 flex items-center gap-1 mt-0.5">
                                <svg class="w-3 h-3 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                </svg>
                                {{ $occurrence->format('g:i A') }}
                            </p>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-4 border border-dashed border-gray-200 dark:border-gray-800 rounded-lg text-sm text-gray-400">
                        No active occurrences computed in this range.
                    </div>
                @endforelse
            </div>
        </div>
    @else
        <!-- Invalid/Initial State -->
        <div class="flex flex-col items-center justify-center p-6 text-center border border-dashed border-gray-200 dark:border-gray-800 rounded-xl bg-gray-50/20 dark:bg-gray-900/10 min-h-[160px]">
            <div class="p-3 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-400 mb-3">
                <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5m-9-6h.008v.008H12v-.008ZM12 15h.008v.008H12V15Zm0 2.25h.008v.008H12v-.008ZM9.75 15h.008v.008H9.75V15Zm0 2.25h.008v.008H9.75v-.008ZM7.5 15h.008v.008H7.5V15Zm0 2.25h.008v.008H7.5v-.008Zm6.75-4.5h.008v.008h-.008v-.008Zm0 2.25h.008v.008h-.008V15Zm0 2.25h.008v.008h-.008v-.008Zm2.25-4.5h.008v.008H16.5v-.008Zm0 2.25h.008v.008H16.5V15Z" />
                </svg>
            </div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                Provide a valid Start Date & Time to see recurrence summary and computed next occurrences.
            </p>
        </div>
    @endif
</div>
