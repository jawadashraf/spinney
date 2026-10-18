<div x-data="{ expanded: false }">
    @php
        $record = $getRecord();
    @endphp

    <div class="flex justify-between items-start mb-2">
        <div>
            <div class="flex items-center gap-1.5 text-sm font-semibold text-gray-900 dark:text-white">
                @if ($record->isRestricted())
                    <x-filament::icon icon="heroicon-o-lock-closed" class="h-4 w-4 text-danger-600 dark:text-danger-400" />
                @endif
                {{ $record->title }}
            </div>
            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                {{ $record->creator?->name ?? 'System' }} • {{ $record->created_at->diffForHumans() }}
            </p>
        </div>
    </div>

    @if ($record->isRestricted())
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Restricted note. Use <span class="font-medium">Open restricted note</span> to read it; opening it is recorded.
        </p>
    @else
    <div class="text-sm text-gray-700 dark:text-gray-300">
        <div
            class="fi-prose prose-sm max-w-none dark:prose-invert"
            :class="expanded ? '' : 'line-clamp-3'"
        >
            {!! \Filament\Forms\Components\RichEditor\RichContentRenderer::make($record->body)
                ->mentions([
                    \Filament\Forms\Components\RichEditor\MentionProvider::make('@')
                        ->getLabelsUsing(fn (array $ids): array => \App\Models\User::query()
                            ->whereIn('id', $ids)
                            ->pluck('name', 'id')
                            ->all())
                        ->url(fn (string $id, string $label): string => '#'),
                ])
                ->toHtml() !!}
        </div>

        @if(strlen(strip_tags($record->body)) > 150)
            <button
                type="button"
                @click="expanded = !expanded"
                class="mt-2 text-xs font-medium text-primary-600 dark:text-primary-400 hover:text-primary-500 transition-colors focus:outline-none"
            >
                <span x-show="!expanded">Read more</span>
                <span x-show="expanded">Show less</span>
            </button>
        @endif
    </div>
    @endif
</div>
