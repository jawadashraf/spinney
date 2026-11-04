{{-- Shared by the landing page and the donate page. $linkBase is '' on the landing page and the home URL elsewhere, so section links still work. --}}
<!-- 1. Top Urgent Helpline Announcement Bar -->
<aside aria-label="Urgent Helpline" class="bg-gradient-to-r from-emerald-950 via-slate-900 to-emerald-950 text-white text-xs sm:text-sm py-2.5 px-4 border-b border-emerald-800/40 sticky top-0 z-50 backdrop-blur-md">
    <div class="max-w-7xl mx-auto flex flex-wrap items-center justify-between gap-2">
        <div class="flex items-center gap-2">
            <span class="flex h-2.5 w-2.5 relative">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-amber-500"></span>
            </span>
            <span class="font-medium text-slate-200">
                Struggling with drugs, alcohol, or worried about a loved one?
            </span>
            <span class="hidden md:inline text-emerald-400">· 100% Free & Confidential</span>
        </div>

        <div class="flex items-center gap-4 ml-auto">
            <a href="tel:03001240945" class="flex items-center gap-1.5 font-bold text-amber-400 hover:text-amber-300 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                </svg>
                <span>0300 124 0945</span>
            </a>
            <span class="text-slate-600 hidden sm:inline">|</span>
            <a href="{{ route('login') }}" class="text-xs text-slate-300 hover:text-white transition flex items-center gap-1">
                <span>Staff Portal</span>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </a>
        </div>
    </div>
</aside>

<!-- 2. Main Navigation Bar -->
<header class="bg-white/95 dark:bg-slate-900/95 backdrop-blur-md border-b border-slate-200/80 dark:border-slate-800 sticky top-10 z-40 transition-all">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-20">
            <!-- Brand / Logo -->
            <a href="{{ url('/') }}" class="flex items-center gap-3 group">
                <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-emerald-600 via-emerald-700 to-emerald-900 flex items-center justify-center text-white shadow-md shadow-emerald-700/20 group-hover:scale-105 transition-transform duration-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                    </svg>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-xl sm:text-2xl font-black tracking-tight text-slate-900 dark:text-white font-['Outfit',sans-serif]">
                            Recovery<span class="text-emerald-600 dark:text-emerald-400">Network</span>
                        </span>
                    </div>
                    <span class="block text-[11px] font-semibold text-slate-500 dark:text-slate-400 tracking-wide uppercase">
                        Spinney Hill Addiction Support
                    </span>
                </div>
            </a>

            <!-- Desktop Nav Links -->
            <nav class="hidden lg:flex items-center space-x-7 text-sm font-semibold text-slate-700 dark:text-slate-200">
                <a href="{{ $linkBase }}#about" class="hover:text-emerald-600 dark:hover:text-emerald-400 transition">About</a>
                <a href="{{ $linkBase }}#pathway" class="hover:text-emerald-600 dark:hover:text-emerald-400 transition">Our Pathway</a>
                <a href="{{ $linkBase }}#services" class="hover:text-emerald-600 dark:hover:text-emerald-400 transition">Services</a>
                <a href="{{ $linkBase }}#testimonials" class="hover:text-emerald-600 dark:hover:text-emerald-400 transition">Stories</a>
                <a href="{{ $linkBase }}#volunteer" class="hover:text-emerald-600 dark:hover:text-emerald-400 transition">Volunteer</a>
                <a href="{{ route('donate') }}" class="hover:text-emerald-600 dark:hover:text-emerald-400 transition">Donate</a>
                <a href="{{ $linkBase }}#contact" class="hover:text-emerald-600 dark:hover:text-emerald-400 transition">Contact</a>
            </nav>

            <!-- Header CTA Buttons -->
            <div class="hidden sm:flex items-center gap-3">
                <a href="tel:03001240945" class="px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 hover:border-emerald-500 dark:hover:border-emerald-400 text-xs font-bold text-slate-700 dark:text-slate-200 hover:text-emerald-600 dark:hover:text-emerald-400 transition flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                    </svg>
                    <span>Call Us</span>
                </a>

                <a href="{{ $linkBase }}#contact" class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-emerald-700 hover:from-emerald-500 hover:to-emerald-600 text-white font-bold text-xs shadow-md shadow-emerald-700/20 hover:shadow-lg transition flex items-center gap-1.5">
                    <span>Get Support Now</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                </a>
            </div>

            <!-- Mobile Hamburger -->
            <div class="flex lg:hidden items-center">
                <button @click="mobileMenuOpen = !mobileMenuOpen" type="button" class="p-2 rounded-xl text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                    <svg x-show="!mobileMenuOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                    <svg x-show="mobileMenuOpen" x-cloak class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Menu Dropdown -->
    <div x-show="mobileMenuOpen" x-cloak @click.away="mobileMenuOpen = false" class="lg:hidden border-b border-slate-200 dark:border-slate-800 bg-white/98 dark:bg-slate-900/98 px-6 py-5 space-y-3">
        <a @click="mobileMenuOpen = false" href="{{ $linkBase }}#about" class="block py-2 text-base font-semibold text-slate-800 dark:text-slate-100 hover:text-emerald-600">About Us</a>
        <a @click="mobileMenuOpen = false" href="{{ $linkBase }}#pathway" class="block py-2 text-base font-semibold text-slate-800 dark:text-slate-100 hover:text-emerald-600">Our 3-Stage Pathway</a>
        <a @click="mobileMenuOpen = false" href="{{ $linkBase }}#services" class="block py-2 text-base font-semibold text-slate-800 dark:text-slate-100 hover:text-emerald-600">Services</a>
        <a @click="mobileMenuOpen = false" href="{{ $linkBase }}#testimonials" class="block py-2 text-base font-semibold text-slate-800 dark:text-slate-100 hover:text-emerald-600">Real Stories</a>
        <a @click="mobileMenuOpen = false" href="{{ $linkBase }}#volunteer" class="block py-2 text-base font-semibold text-slate-800 dark:text-slate-100 hover:text-emerald-600">Volunteer</a>
        <a @click="mobileMenuOpen = false" href="{{ route('donate') }}" class="block py-2 text-base font-semibold text-slate-800 dark:text-slate-100 hover:text-emerald-600">Donate</a>
        <a @click="mobileMenuOpen = false" href="{{ $linkBase }}#contact" class="block py-2 text-base font-semibold text-slate-800 dark:text-slate-100 hover:text-emerald-600">Contact Us</a>
        <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex flex-col gap-3">
            <a href="tel:03001240945" class="w-full py-3 text-center rounded-xl bg-amber-500 text-slate-950 font-bold text-sm shadow">
                📞 Call Helpline: 0300 124 0945
            </a>
            <a @click="mobileMenuOpen = false" href="{{ $linkBase }}#contact" class="w-full py-3 text-center rounded-xl bg-emerald-600 text-white font-bold text-sm shadow">
                Get Support Now
            </a>
        </div>
    </div>
</header>
