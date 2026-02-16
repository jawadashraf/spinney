<x-layout.plain :title="config('app.name') . ' - ' . __('The Next-Generation Open-Source CRM Platform')"
    description="Spinney Hill Support Centre is an CRM platform designed for modern businesses."
    :ogTitle="config('app.name') . ' - CRM Platform'"
    ogDescription="Discover Spinney Hill Support Centre, CRM platform." :ogImage="url('/images/og-image.jpg')">
    <div class="flex flex-col items-center justify-end min-h-screen p-4 pb-20 bg-cover bg-center bg-no-repeat"
        style="background-image: url('{{ asset('images/spinney_bg.png') }}')">
        <div class="w-full max-w-2xl bg-white/90 backdrop-blur-sm p-8 rounded-2xl shadow-2xl border border-white/20">
            <h1 class="text-3xl font-bold text-gray-900 mb-6 text-center">
                {{-- {{ config('app.name') }} --}}
                Spinney Hill Support Centre
            </h1>
            <p class="text-lg text-gray-600 mb-8 text-center leading-relaxed">
                Spinney Hill Support Centre Slogan Here.
            </p>
            <div class="flex justify-center gap-4">
                <a href="{{ route('login') }}"
                    class="px-6 py-3 bg-amber-400 text-white font-semibold rounded-lg hover:bg-amber-500 transition">
                    Login to Portal
                </a>
                {{-- <a href="{{ route('register') }}"
                    class="px-6 py-3 bg-white text-gray-700 font-semibold rounded-lg border border-gray-200 hover:bg-gray-50 transition">
                    Create Account
                </a> --}}
            </div>
        </div>
    </div>
</x-layout.plain>