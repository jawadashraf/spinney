<x-guest-layout 
    :title="config('app.name') . ' - ' . __('Support, Hope and Recovery')"
    description="Spinney Hill is a place of support, hope and recovery. We provide a safe and caring environment for people who are struggling with addiction and mental health issues."
    :ogTitle="config('app.name') . ' - Support, Hope and Recovery'"
    ogDescription="Discover Spinney Hill, a place of support, hope and recovery. We provide a safe and caring environment for people who are struggling with addiction and mental health issues."
    :ogImage="url('/images/og-image.png')">
    @include('home.partials.hero')
    @include('home.partials.features')
    @include('home.partials.community')
    @include('home.partials.start-building')
</x-guest-layout>
