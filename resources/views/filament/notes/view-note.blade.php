<div class="space-y-3">
    <p class="text-xs text-gray-500 dark:text-gray-400">
        {{ $note->creator?->name ?? 'System' }} • {{ $note->created_at?->format('j M Y, H:i') }}
        @if ($note->isRestricted())
            • <span class="font-medium text-danger-600 dark:text-danger-400">Restricted: only managers and admins can read this note. Opening it has been recorded.</span>
        @endif
    </p>

    <div class="fi-prose prose-sm max-w-none dark:prose-invert">
        {!! \Filament\Forms\Components\RichEditor\RichContentRenderer::make($note->body)
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
</div>
