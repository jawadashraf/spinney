{{-- Streams the recording straight from the phone system; nothing is copied to this server. --}}
<div class="space-y-2">
    <audio
        controls
        preload="{{ ($autoplay ?? false) ? 'auto' : 'none' }}"
        @if ($autoplay ?? false) autoplay @endif
        src="{{ $url }}"
        class="w-full"
    >
        Your browser cannot play this recording.
    </audio>

    <a
        href="{{ $url }}"
        target="_blank"
        rel="noopener noreferrer"
        class="text-xs text-gray-500 underline hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
    >
        Download recording
    </a>
</div>
