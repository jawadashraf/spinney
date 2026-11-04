<x-dynamic-component :component="$getEntryWrapperView()" :entry="$entry">
    @include('filament.components.call-recording-player', ['url' => $getState()])
</x-dynamic-component>
