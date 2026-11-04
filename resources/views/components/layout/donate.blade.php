@props(['title', 'heading', 'intro' => null])

<x-layout.plain :title="$title" description="Give securely online to Spinney Hill Drugs, Alcohol and Addiction Support – as a one-off gift or every month.">
    @push('header')
        @include('home.partials.site-fonts')
    @endpush

    <div x-data="{ mobileMenuOpen: false }" class="min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 antialiased selection:bg-emerald-500 selection:text-white">
        @include('home.partials.site-header', ['linkBase' => url('/')])

        <section class="relative overflow-hidden bg-gradient-to-b from-emerald-950 via-slate-900 to-slate-950 pt-14 pb-16 text-white sm:pt-20 sm:pb-24">
            <div class="pointer-events-none absolute -top-40 -left-40 h-96 w-96 rounded-full bg-emerald-500/20 blur-3xl"></div>
            <div class="pointer-events-none absolute top-1/2 -right-40 h-96 w-96 rounded-full bg-amber-500/15 blur-3xl"></div>

            <div class="relative z-10 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <nav aria-label="Breadcrumb" class="mb-5 text-xs font-semibold text-emerald-300">
                    <a href="{{ url('/') }}" class="hover:text-white transition">Home</a>
                    <span class="mx-2 text-slate-500">›</span>
                    <span class="text-slate-300">{{ $heading }}</span>
                </nav>
                <h1 class="text-4xl font-black tracking-tight sm:text-5xl font-['Outfit',sans-serif]">
                    <span class="bg-gradient-to-r from-emerald-400 via-amber-300 to-amber-400 bg-clip-text text-transparent">{{ $heading }}</span>
                </h1>
                @if ($intro)
                    <p class="mt-4 max-w-2xl text-sm leading-relaxed text-slate-300 sm:text-base">{{ $intro }}</p>
                @endif
            </div>
        </section>

        <main class="mx-auto max-w-7xl px-4 py-14 sm:px-6 sm:py-20 lg:px-8">
            {{ $slot }}
        </main>

        @include('home.partials.site-footer', ['linkBase' => url('/')])
    </div>
</x-layout.plain>
