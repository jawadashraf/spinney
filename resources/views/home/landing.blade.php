<x-layout.plain
    title="Recovery Network | Spinney Hill Drugs, Alcohol & Addiction Support"
    description="Community-based, culturally sensitive, and confidential addiction support in Leicester. Holistic recovery pathway addressing the Body, Mind, and Soul."
    ogTitle="Recovery Network - Support, Hope and Long-Term Recovery"
    ogDescription="Free, confidential, and culturally sensitive drug and alcohol addiction support for individuals and families in Leicester. Call 0300 124 0945."
    :ogImage="url('/images/og-image.png')">

    @push('header')
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
        <style>
            html {
                scroll-behavior: smooth;
                font-family: 'Plus Jakarta Sans', sans-serif;
            }
            h1, h2, h3, h4, .font-heading {
                font-family: 'Outfit', sans-serif;
            }
        </style>
    @endpush

    <div x-data="{ mobileMenuOpen: false, activePillar: 'body' }" class="min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 antialiased selection:bg-emerald-500 selection:text-white">

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
                        <a href="#about" class="hover:text-emerald-600 dark:hover:text-emerald-400 transition">About</a>
                        <a href="#pathway" class="hover:text-emerald-600 dark:hover:text-emerald-400 transition">Our Pathway</a>
                        <a href="#services" class="hover:text-emerald-600 dark:hover:text-emerald-400 transition">Services</a>
                        <a href="#testimonials" class="hover:text-emerald-600 dark:hover:text-emerald-400 transition">Stories</a>
                        <a href="#volunteer" class="hover:text-emerald-600 dark:hover:text-emerald-400 transition">Volunteer</a>
                        <a href="#donate" class="hover:text-emerald-600 dark:hover:text-emerald-400 transition">Donate</a>
                        <a href="#contact" class="hover:text-emerald-600 dark:hover:text-emerald-400 transition">Contact</a>
                    </nav>

                    <!-- Header CTA Buttons -->
                    <div class="hidden sm:flex items-center gap-3">
                        <a href="tel:03001240945" class="px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 hover:border-emerald-500 dark:hover:border-emerald-400 text-xs font-bold text-slate-700 dark:text-slate-200 hover:text-emerald-600 dark:hover:text-emerald-400 transition flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                            <span>Call Us</span>
                        </a>

                        <a href="#contact" class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-emerald-700 hover:from-emerald-500 hover:to-emerald-600 text-white font-bold text-xs shadow-md shadow-emerald-700/20 hover:shadow-lg transition flex items-center gap-1.5">
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
                <a @click="mobileMenuOpen = false" href="#about" class="block py-2 text-base font-semibold text-slate-800 dark:text-slate-100 hover:text-emerald-600">About Us</a>
                <a @click="mobileMenuOpen = false" href="#pathway" class="block py-2 text-base font-semibold text-slate-800 dark:text-slate-100 hover:text-emerald-600">Our 3-Stage Pathway</a>
                <a @click="mobileMenuOpen = false" href="#services" class="block py-2 text-base font-semibold text-slate-800 dark:text-slate-100 hover:text-emerald-600">Services</a>
                <a @click="mobileMenuOpen = false" href="#testimonials" class="block py-2 text-base font-semibold text-slate-800 dark:text-slate-100 hover:text-emerald-600">Real Stories</a>
                <a @click="mobileMenuOpen = false" href="#volunteer" class="block py-2 text-base font-semibold text-slate-800 dark:text-slate-100 hover:text-emerald-600">Volunteer</a>
                <a @click="mobileMenuOpen = false" href="#donate" class="block py-2 text-base font-semibold text-slate-800 dark:text-slate-100 hover:text-emerald-600">Donate</a>
                <a @click="mobileMenuOpen = false" href="#contact" class="block py-2 text-base font-semibold text-slate-800 dark:text-slate-100 hover:text-emerald-600">Contact Us</a>
                <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex flex-col gap-3">
                    <a href="tel:03001240945" class="w-full py-3 text-center rounded-xl bg-amber-500 text-slate-950 font-bold text-sm shadow">
                        📞 Call Helpline: 0300 124 0945
                    </a>
                    <a @click="mobileMenuOpen = false" href="#contact" class="w-full py-3 text-center rounded-xl bg-emerald-600 text-white font-bold text-sm shadow">
                        Get Support Now
                    </a>
                </div>
            </div>
        </header>

        <!-- 3. Hero Section -->
        <section class="relative overflow-hidden pt-12 pb-20 sm:pt-20 sm:pb-32 bg-gradient-to-b from-emerald-950 via-slate-900 to-slate-950 text-white">
            <!-- Decorative Glow Elements -->
            <div class="absolute -top-40 -left-40 w-96 h-96 bg-emerald-500/20 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute top-1/2 -right-40 w-96 h-96 bg-amber-500/15 rounded-full blur-3xl pointer-events-none"></div>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-8 items-center">
                    <!-- Left Hero Copy -->
                    <div class="lg:col-span-7 space-y-6 text-center lg:text-left">
                        <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-emerald-500/15 border border-emerald-400/30 text-emerald-300 text-xs sm:text-sm font-semibold backdrop-blur-sm">
                            <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                            Community-Based · Culturally Sensitive · Confidential
                        </div>

                        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black tracking-tight leading-[1.1] font-['Outfit',sans-serif]">
                            Support, Hope & <br class="hidden sm:inline" />
                            <span class="text-transparent bg-clip-text bg-gradient-to-r from-emerald-400 via-amber-300 to-amber-400">
                                Long-Term Recovery
                            </span>
                        </h1>

                        <p class="text-base sm:text-lg lg:text-xl text-slate-300 max-w-2xl mx-auto lg:mx-0 leading-relaxed font-normal">
                            Spinney Hill Recovery Network is a community-rooted charity set up to help men, women, and families overcome alcohol, drug, and substance addiction. If you or a loved one are ready to take the first step, we are here for you.
                        </p>

                        <!-- Hero CTA Buttons -->
                        <div class="flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-4 pt-2">
                            <a href="#contact" class="w-full sm:w-auto px-8 py-4 rounded-2xl bg-gradient-to-r from-amber-400 to-amber-500 hover:from-amber-300 hover:to-amber-400 text-slate-950 font-extrabold text-base shadow-xl shadow-amber-500/20 hover:shadow-amber-500/30 transition transform hover:-translate-y-0.5 text-center flex items-center justify-center gap-2">
                                <span>Get Confidential Help</span>
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </a>

                            <a href="tel:03001240945" class="w-full sm:w-auto px-7 py-4 rounded-2xl bg-white/10 hover:bg-white/15 border border-white/20 text-white font-bold text-base backdrop-blur-md transition text-center flex items-center justify-center gap-2">
                                <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                                <span>Call: 0300 124 0945</span>
                            </a>
                        </div>

                        <!-- Trust Checkmarks -->
                        <div class="pt-6 border-t border-slate-800/80 grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs sm:text-sm text-slate-400">
                            <div class="flex items-center gap-2 justify-center lg:justify-start">
                                <svg class="w-4 h-4 text-emerald-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                                <span>100% Free Service</span>
                            </div>
                            <div class="flex items-center gap-2 justify-center lg:justify-start">
                                <svg class="w-4 h-4 text-emerald-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                                <span>Strictly Confidential</span>
                            </div>
                            <div class="flex items-center gap-2 justify-center lg:justify-start">
                                <svg class="w-4 h-4 text-emerald-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                                <span>Faith & Culturally Sensitive</span>
                            </div>
                            <div class="flex items-center gap-2 justify-center lg:justify-start">
                                <svg class="w-4 h-4 text-emerald-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                                <span>Inpatient Rehab Placements</span>
                            </div>
                        </div>
                    </div>

                    <!-- Right Hero Interactive Triage Card -->
                    <div class="lg:col-span-5">
                        <div class="bg-gradient-to-b from-white/10 to-white/5 backdrop-blur-xl border border-white/15 p-6 sm:p-8 rounded-3xl shadow-2xl space-y-6">
                            <div class="flex items-center justify-between border-b border-white/10 pb-4">
                                <div>
                                    <span class="text-xs font-bold uppercase tracking-wider text-amber-400">Immediate Triage</span>
                                    <h3 class="text-xl font-bold text-white">How Can We Help You Today?</h3>
                                </div>
                                <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-400/30">
                                    Open 7 Days
                                </span>
                            </div>

                            <p class="text-sm text-slate-300 leading-relaxed">
                                Whether you are personally fighting an addiction or deeply worried about your son, husband, parent, or friend — we provide honest advice and clear options.
                            </p>

                            <!-- Pathways quick picker -->
                            <div class="space-y-3">
                                <a href="#contact" class="group block p-4 rounded-2xl bg-white/5 hover:bg-emerald-600/20 border border-white/10 hover:border-emerald-500/50 transition">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center font-bold">1</div>
                                            <div>
                                                <h4 class="text-sm font-bold text-white group-hover:text-emerald-300 transition">I Need Help for Myself</h4>
                                                <p class="text-xs text-slate-400">Confidential 1-to-1 assessment & rehab pathway</p>
                                            </div>
                                        </div>
                                        <svg class="w-5 h-5 text-slate-400 group-hover:text-emerald-400 group-hover:translate-x-1 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </div>
                                </a>

                                <a href="#contact" class="group block p-4 rounded-2xl bg-white/5 hover:bg-amber-500/20 border border-white/10 hover:border-amber-500/50 transition">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-xl bg-amber-500/20 text-amber-400 flex items-center justify-center font-bold">2</div>
                                            <div>
                                                <h4 class="text-sm font-bold text-white group-hover:text-amber-300 transition">I Am Worried About a Loved One</h4>
                                                <p class="text-xs text-slate-400">Family mediation, home visits & intervention advice</p>
                                            </div>
                                        </div>
                                        <svg class="w-5 h-5 text-slate-400 group-hover:text-amber-400 group-hover:translate-x-1 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </div>
                                </a>

                                <a href="#volunteer" class="group block p-4 rounded-2xl bg-white/5 hover:bg-slate-700/40 border border-white/10 hover:border-white/30 transition">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-xl bg-slate-700/50 text-slate-300 flex items-center justify-center font-bold">3</div>
                                            <div>
                                                <h4 class="text-sm font-bold text-white">I Want to Volunteer or Support</h4>
                                                <p class="text-xs text-slate-400">Peer mentorship, driving, practical aid, or donate</p>
                                            </div>
                                        </div>
                                        <svg class="w-5 h-5 text-slate-400 group-hover:translate-x-1 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </div>
                                </a>
                            </div>

                            <!-- Emergency banner inside card -->
                            <div class="p-3.5 rounded-2xl bg-amber-500/20 border border-amber-400/30 flex items-center justify-between">
                                <div class="flex items-center gap-2.5">
                                    <span class="w-2.5 h-2.5 rounded-full bg-amber-400 animate-pulse"></span>
                                    <span class="text-xs font-semibold text-amber-200">Confidential Helpline:</span>
                                </div>
                                <a href="tel:03001240945" class="text-sm font-black text-amber-300 hover:text-white underline">
                                    0300 124 0945
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 4. About Us Section -->
        <section id="about" class="py-20 sm:py-28 bg-white dark:bg-slate-900 border-b border-slate-200/80 dark:border-slate-800">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                    <!-- Left Column Text -->
                    <div class="lg:col-span-7 space-y-6">
                        <span class="text-xs font-extrabold uppercase tracking-widest text-emerald-600 dark:text-emerald-400">
                            Our Mission & Origins
                        </span>
                        <h2 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-slate-900 dark:text-white tracking-tight leading-tight">
                            Breaking the Stigma, Restoring Lives & Families
                        </h2>
                        <div class="space-y-4 text-slate-600 dark:text-slate-300 text-base sm:text-lg leading-relaxed">
                            <p>
                                <strong>Spinney Hill Recovery Network</strong> was established in response to the growing number of individuals from within our communities who have developed an addiction to drugs, alcohol, and prescription substances.
                            </p>
                            <p>
                                Substance use has affected local families for decades, but the landscape has changed dramatically. While historically cannabis was the primary drug, Class A substances such as <strong class="text-slate-900 dark:text-white">Cocaine and Heroin</strong>, as well as destructive alcohol misuse, have devastated households.
                            </p>
                            <p>
                                Stigma, cultural barriers, and fear of judgment often stop people from seeking conventional help until it is almost too late. We bridge that gap by offering a <em>safe, culturally sensitive, non-judgmental community environment</em> where true healing can take root.
                            </p>
                        </div>

                        <!-- 4 Pillars of Hope Grid -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-4">
                            <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/60 flex items-start gap-3">
                                <div class="w-8 h-8 rounded-lg bg-emerald-100 dark:bg-emerald-900/60 text-emerald-700 dark:text-emerald-300 flex items-center justify-center flex-shrink-0 mt-0.5">
                                    ✓
                                </div>
                                <div>
                                    <h4 class="font-bold text-slate-900 dark:text-white text-sm">Non-Judgmental Approach</h4>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">No condemnation, ever. We meet you exactly where you are.</p>
                                </div>
                            </div>

                            <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/60 flex items-start gap-3">
                                <div class="w-8 h-8 rounded-lg bg-emerald-100 dark:bg-emerald-900/60 text-emerald-700 dark:text-emerald-300 flex items-center justify-center flex-shrink-0 mt-0.5">
                                    ✓
                                </div>
                                <div>
                                    <h4 class="font-bold text-slate-900 dark:text-white text-sm">Family Mediation</h4>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">We work directly with parents and spouses to rebuild trust.</p>
                                </div>
                            </div>

                            <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/60 flex items-start gap-3">
                                <div class="w-8 h-8 rounded-lg bg-emerald-100 dark:bg-emerald-900/60 text-emerald-700 dark:text-emerald-300 flex items-center justify-center flex-shrink-0 mt-0.5">
                                    ✓
                                </div>
                                <div>
                                    <h4 class="font-bold text-slate-900 dark:text-white text-sm">Practical Relocation & Aid</h4>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">Food vouchers, clothing, rent arrears negotiation, safe shelter.</p>
                                </div>
                            </div>

                            <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/60 flex items-start gap-3">
                                <div class="w-8 h-8 rounded-lg bg-emerald-100 dark:bg-emerald-900/60 text-emerald-700 dark:text-emerald-300 flex items-center justify-center flex-shrink-0 mt-0.5">
                                    ✓
                                </div>
                                <div>
                                    <h4 class="font-bold text-slate-900 dark:text-white text-sm">Full Inpatient Placement</h4>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">Structured admission into 3–6 month specialist residential rehabs.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column Impact Card -->
                    <div class="lg:col-span-5">
                        <div class="bg-gradient-to-br from-emerald-900 via-slate-900 to-slate-950 p-8 sm:p-10 rounded-3xl text-white shadow-2xl space-y-8 border border-emerald-800/30">
                            <div>
                                <span class="text-amber-400 text-xs font-bold uppercase tracking-wider">Community Impact</span>
                                <h3 class="text-2xl sm:text-3xl font-extrabold text-white mt-1">
                                    Rooted in Compassion, Proven by Action
                                </h3>
                            </div>

                            <!-- Stat Counters -->
                            <div class="space-y-6">
                                <div class="flex items-center gap-4">
                                    <div class="w-14 h-14 rounded-2xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center text-2xl font-black flex-shrink-0">
                                        3
                                    </div>
                                    <div>
                                        <div class="text-xl font-bold text-white">Three-Pillar Healing</div>
                                        <div class="text-xs text-slate-300">Synchronized care for the Body, the Mind, and the Soul</div>
                                    </div>
                                </div>

                                <div class="flex items-center gap-4">
                                    <div class="w-14 h-14 rounded-2xl bg-amber-500/20 text-amber-400 flex items-center justify-center text-2xl font-black flex-shrink-0">
                                        25+
                                    </div>
                                    <div>
                                        <div class="text-xl font-bold text-white">Years Community Trust</div>
                                        <div class="text-xs text-slate-300">Deep grassroots credibility across Leicester and regional partners</div>
                                    </div>
                                </div>

                                <div class="flex items-center gap-4">
                                    <div class="w-14 h-14 rounded-2xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center text-2xl font-black flex-shrink-0">
                                        100%
                                    </div>
                                    <div>
                                        <div class="text-xl font-bold text-white">Volunteer & Community Led</div>
                                        <div class="text-xs text-slate-300">Run by compassionate volunteers, peer mentors, and dedicated professionals</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Quote Callout -->
                            <div class="pt-6 border-t border-white/10 text-xs sm:text-sm text-slate-300 italic">
                                "If there are deficiencies in any one of the three component parts — body, mind, or soul — it is almost impossible for an individual to maintain sustained recovery."
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 5. The 3-Component Pathway Section -->
        <section id="pathway" class="py-20 sm:py-28 bg-slate-50 dark:bg-slate-950 border-b border-slate-200/80 dark:border-slate-800">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-3xl mx-auto space-y-4 mb-16">
                    <span class="text-xs font-extrabold uppercase tracking-widest text-emerald-600 dark:text-emerald-400">
                        Our Treatment Philosophy
                    </span>
                    <h2 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                        The Three Components: Body, Mind & Soul
                    </h2>
                    <p class="text-slate-600 dark:text-slate-300 text-base sm:text-lg">
                        At Spinney Hill, we recognize that true human recovery cannot occur by treating symptoms alone. We have developed a synchronized pathway that rebuilds every dimension of the human being.
                    </p>
                </div>

                <!-- 3 Pillars Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <!-- Pillar 1: Body -->
                    <div class="bg-white dark:bg-slate-900 rounded-3xl p-8 border border-slate-200 dark:border-slate-800 shadow-xl hover:shadow-2xl transition-all duration-300 flex flex-col justify-between group hover:-translate-y-1">
                        <div class="space-y-5">
                            <div class="w-14 h-14 rounded-2xl bg-emerald-100 dark:bg-emerald-950/80 text-emerald-700 dark:text-emerald-400 flex items-center justify-center font-bold text-2xl group-hover:scale-110 transition-transform">
                                🏃
                            </div>
                            <div>
                                <span class="text-xs font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Pillar 1</span>
                                <h3 class="text-2xl font-bold text-slate-900 dark:text-white">The Body</h3>
                            </div>
                            <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
                                Addressing physical health, withdrawal safety, nutrition, and immediate survival needs. An exhausted, malnourished body cannot sustain recovery.
                            </p>
                            <ul class="space-y-2.5 text-xs sm:text-sm text-slate-600 dark:text-slate-400 pt-2 border-t border-slate-100 dark:border-slate-800">
                                <li class="flex items-center gap-2">
                                    <span class="text-emerald-500 font-bold">•</span>
                                    <span>Medical detox coordination & GP liaisons</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <span class="text-emerald-500 font-bold">•</span>
                                    <span>Emergency food vouchers & clean clothing</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <span class="text-emerald-500 font-bold">•</span>
                                    <span>Safe housing relocation & landlord negotiation</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <span class="text-emerald-500 font-bold">•</span>
                                    <span>Physical fitness, routine & sleep hygiene</span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Pillar 2: Mind -->
                    <div class="bg-white dark:bg-slate-900 rounded-3xl p-8 border-2 border-emerald-600/30 dark:border-emerald-500/40 shadow-xl hover:shadow-2xl transition-all duration-300 flex flex-col justify-between group hover:-translate-y-1 relative">
                        <div class="absolute -top-3 left-1/2 -translate-x-1/2 px-3 py-1 rounded-full text-[11px] font-extrabold uppercase tracking-wider bg-emerald-600 text-white shadow">
                            Core Transformation
                        </div>
                        <div class="space-y-5">
                            <div class="w-14 h-14 rounded-2xl bg-amber-100 dark:bg-amber-950/80 text-amber-700 dark:text-amber-400 flex items-center justify-center font-bold text-2xl group-hover:scale-110 transition-transform">
                                🧠
                            </div>
                            <div>
                                <span class="text-xs font-bold uppercase tracking-wider text-amber-600 dark:text-amber-400">Pillar 2</span>
                                <h3 class="text-2xl font-bold text-slate-900 dark:text-white">The Mind</h3>
                            </div>
                            <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
                                Unpacking the trauma, cognitive patterns, emotional triggers, and coping mechanisms behind substance dependence.
                            </p>
                            <ul class="space-y-2.5 text-xs sm:text-sm text-slate-600 dark:text-slate-400 pt-2 border-t border-slate-100 dark:border-slate-800">
                                <li class="flex items-center gap-2">
                                    <span class="text-amber-500 font-bold">•</span>
                                    <span>1-to-1 psychological counseling & therapy</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <span class="text-amber-500 font-bold">•</span>
                                    <span>Relapse prevention & trigger identification</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <span class="text-amber-500 font-bold">•</span>
                                    <span>Emotional regulation & stress management</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <span class="text-amber-500 font-bold">•</span>
                                    <span>Experienced peer mentorship from former addicts</span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Pillar 3: Soul -->
                    <div class="bg-white dark:bg-slate-900 rounded-3xl p-8 border border-slate-200 dark:border-slate-800 shadow-xl hover:shadow-2xl transition-all duration-300 flex flex-col justify-between group hover:-translate-y-1">
                        <div class="space-y-5">
                            <div class="w-14 h-14 rounded-2xl bg-emerald-100 dark:bg-emerald-950/80 text-emerald-700 dark:text-emerald-400 flex items-center justify-center font-bold text-2xl group-hover:scale-110 transition-transform">
                                ✨
                            </div>
                            <div>
                                <span class="text-xs font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Pillar 3</span>
                                <h3 class="text-2xl font-bold text-slate-900 dark:text-white">The Soul</h3>
                            </div>
                            <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
                                Rekindling purpose, human dignity, moral values, and reconnecting with family and faith. Without a reason to live, relapse remains a constant risk.
                            </p>
                            <ul class="space-y-2.5 text-xs sm:text-sm text-slate-600 dark:text-slate-400 pt-2 border-t border-slate-100 dark:border-slate-800">
                                <li class="flex items-center gap-2">
                                    <span class="text-emerald-500 font-bold">•</span>
                                    <span>Spiritual and moral counseling (all backgrounds)</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <span class="text-emerald-500 font-bold">•</span>
                                    <span>Restoring self-worth & overcoming guilt/shame</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <span class="text-emerald-500 font-bold">•</span>
                                    <span>Reconciling with parents, spouses, and children</span>
                                </li>
                                <li class="flex items-center gap-2">
                                    <span class="text-emerald-500 font-bold">•</span>
                                    <span>Giving back to society as a volunteer mentor</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- 3 Stages Timeline Bar -->
                <div class="mt-16 bg-white dark:bg-slate-900 rounded-3xl p-8 border border-slate-200 dark:border-slate-800 shadow-lg">
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-6 text-center">
                        Our 3-Stage Pathway to Long-Term Sobriety
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="p-5 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700/60">
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300">
                                Stage 1: Stabilize
                            </span>
                            <h4 class="font-bold text-base text-slate-900 dark:text-white mt-3">Intake & Family Mediation</h4>
                            <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 mt-1">
                                Confidential meeting, assessing safety and medical needs, engaging the family, and establishing mutual trust.
                            </p>
                        </div>

                        <div class="p-5 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700/60">
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 dark:bg-amber-950 text-amber-800 dark:text-amber-300">
                                Stage 2: Intensive Rehab
                            </span>
                            <h4 class="font-bold text-base text-slate-900 dark:text-white mt-3">Specialist Residential Care</h4>
                            <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 mt-1">
                                Placement in specialist 3–6 month inpatient rehab centres with round-the-clock therapy and medical detox supervision.
                            </p>
                        </div>

                        <div class="p-5 rounded-2xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700/60">
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300">
                                Stage 3: Reintegrate
                            </span>
                            <h4 class="font-bold text-base text-slate-900 dark:text-white mt-3">Aftercare & Peer Mentorship</h4>
                            <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-400 mt-1">
                                Structured aftercare calls, housing stability, employment guidance, and opportunities to mentor new service users.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 6. Services & Support Section -->
        <section id="services" class="py-20 sm:py-28 bg-white dark:bg-slate-900 border-b border-slate-200/80 dark:border-slate-800">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-3xl mx-auto space-y-4 mb-16">
                    <span class="text-xs font-extrabold uppercase tracking-widest text-emerald-600 dark:text-emerald-400">
                        What We Offer
                    </span>
                    <h2 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                        Comprehensive, Hands-On Community Support
                    </h2>
                    <p class="text-slate-600 dark:text-slate-300 text-base sm:text-lg">
                        We do not simply hand out pamphlets. Our volunteer caseworkers go directly to where the need is to provide real, tangible relief.
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
                    <!-- Service 1 -->
                    <div class="p-7 rounded-3xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200/80 dark:border-slate-700/60 space-y-4 hover:shadow-lg transition">
                        <div class="w-12 h-12 rounded-2xl bg-emerald-600/10 dark:bg-emerald-400/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-bold text-xl">
                            📋
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 dark:text-white">Confidential Assessment</h3>
                        <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
                            A private, respectful one-to-one consultation to understand your unique circumstances, drug or alcohol history, and what goals you want to achieve.
                        </p>
                    </div>

                    <!-- Service 2 -->
                    <div class="p-7 rounded-3xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200/80 dark:border-slate-700/60 space-y-4 hover:shadow-lg transition">
                        <div class="w-12 h-12 rounded-2xl bg-amber-500/10 dark:bg-amber-400/10 text-amber-600 dark:text-amber-400 flex items-center justify-center font-bold text-xl">
                            🏥
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 dark:text-white">Inpatient Rehab Placement</h3>
                        <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
                            Direct referrals to trusted residential rehabilitation clinics across the UK. We handle the paperwork, travel arrangements, and ongoing pastoral contact.
                        </p>
                    </div>

                    <!-- Service 3 -->
                    <div class="p-7 rounded-3xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200/80 dark:border-slate-700/60 space-y-4 hover:shadow-lg transition">
                        <div class="w-12 h-12 rounded-2xl bg-emerald-600/10 dark:bg-emerald-400/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-bold text-xl">
                            🤝
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 dark:text-white">Family Mediation & Home Visits</h3>
                        <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
                            Addiction affects the whole household. We visit homes, counsel distraught parents and partners, and facilitate honest dialogue to restore relationships.
                        </p>
                    </div>

                    <!-- Service 4 -->
                    <div class="p-7 rounded-3xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200/80 dark:border-slate-700/60 space-y-4 hover:shadow-lg transition">
                        <div class="w-12 h-12 rounded-2xl bg-emerald-600/10 dark:bg-emerald-400/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-bold text-xl">
                            🍲
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 dark:text-white">Emergency Practical Relief</h3>
                        <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
                            Nobody can focus on recovery while starving or facing eviction. We provide food bank vouchers, clean clothing, rent negotiations, and temporary shelter.
                        </p>
                    </div>

                    <!-- Service 5 -->
                    <div class="p-7 rounded-3xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200/80 dark:border-slate-700/60 space-y-4 hover:shadow-lg transition">
                        <div class="w-12 h-12 rounded-2xl bg-amber-500/10 dark:bg-amber-400/10 text-amber-600 dark:text-amber-400 flex items-center justify-center font-bold text-xl">
                            📚
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 dark:text-white">Drugs & Alcohol A to Z Directory</h3>
                        <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
                            Clear, factual education for families on cannabis, cocaine, heroin, prescription pills, alcohol, withdrawal risks, and legal implications.
                        </p>
                    </div>

                    <!-- Service 6 -->
                    <div class="p-7 rounded-3xl bg-slate-50 dark:bg-slate-800/50 border border-slate-200/80 dark:border-slate-700/60 space-y-4 hover:shadow-lg transition">
                        <div class="w-12 h-12 rounded-2xl bg-emerald-600/10 dark:bg-emerald-400/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-bold text-xl">
                            📞
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 dark:text-white">Aftercare & Regular Check-In Calls</h3>
                        <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
                            Recovery is a lifetime commitment. Our Volunteer Liaisons provide structured weekly calls, ensuring you always have a listening ear when life gets difficult.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- 7. Real Testimonials Section -->
        <section id="testimonials" class="py-20 sm:py-28 bg-gradient-to-b from-slate-900 to-slate-950 text-white border-b border-slate-800">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-3xl mx-auto space-y-4 mb-16">
                    <span class="text-xs font-extrabold uppercase tracking-widest text-amber-400">
                        Real Stories of Hope
                    </span>
                    <h2 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold tracking-tight">
                        From Brokenness to Rebirth
                    </h2>
                    <p class="text-slate-300 text-base sm:text-lg">
                        These are real words from men and women who thought all hope was lost before finding Spinney Hill Recovery Network.
                    </p>
                </div>

                <!-- Testimonial Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Wasim -->
                    <div class="p-8 rounded-3xl bg-white/5 border border-white/10 backdrop-blur-md flex flex-col justify-between space-y-6">
                        <div class="space-y-4">
                            <div class="flex text-amber-400 gap-1 text-sm">
                                ★★★★★
                            </div>
                            <blockquote class="text-slate-200 text-base sm:text-lg leading-relaxed italic">
                                "I just want to say Jazakallah to the Spinney Hill Recovery Team for being understanding and not judging me for being an addict. You help us broken brothers to be fixed again. I am now married and have a job — none of this would have been imaginable before I met you. As a recovering addict, you still support me at the end of the phone, and I now have the privilege to work as a Peer Mentor to give back."
                            </blockquote>
                        </div>
                        <div class="flex items-center gap-3 pt-4 border-t border-white/10">
                            <div class="w-10 h-10 rounded-full bg-emerald-600/30 text-emerald-400 flex items-center justify-center font-bold">W</div>
                            <div>
                                <h4 class="font-bold text-white">Wasim</h4>
                                <p class="text-xs text-emerald-400">Recovering Addict & Peer Mentor</p>
                            </div>
                        </div>
                    </div>

                    <!-- Rizwan -->
                    <div class="p-8 rounded-3xl bg-white/5 border border-white/10 backdrop-blur-md flex flex-col justify-between space-y-6">
                        <div class="space-y-4">
                            <div class="flex text-amber-400 gap-1 text-sm">
                                ★★★★★
                            </div>
                            <blockquote class="text-slate-200 text-base sm:text-lg leading-relaxed italic">
                                "Being an addict for 25 years, all hope in myself was lost until Spinney Hill reached out. Other organisations gave food vouchers and housing so I was not sleeping on the streets, and the spiritual support gave me strength where no therapy in the past had worked. Today I have my family, I speak to my parents, and through mercy have a relationship with my daughter after 16 years."
                            </blockquote>
                        </div>
                        <div class="flex items-center gap-3 pt-4 border-t border-white/10">
                            <div class="w-10 h-10 rounded-full bg-emerald-600/30 text-emerald-400 flex items-center justify-center font-bold">R</div>
                            <div>
                                <h4 class="font-bold text-white">Rizwan</h4>
                                <p class="text-xs text-emerald-400">Clean After 25 Years of Addiction</p>
                            </div>
                        </div>
                    </div>

                    <!-- Abdul Abz Alibhai -->
                    <div class="p-8 rounded-3xl bg-white/5 border border-white/10 backdrop-blur-md flex flex-col justify-between space-y-6">
                        <div class="space-y-4">
                            <div class="flex text-amber-400 gap-1 text-sm">
                                ★★★★★
                            </div>
                            <blockquote class="text-slate-200 text-base sm:text-lg leading-relaxed italic">
                                "They were very professional in giving me advice. They worked with my family and encouraged them to support me, visited my house, and encouraged my extended family to believe in me. They supported me into a 3-month rehab programme — it was the best decision of my life, and for the first time in 20 years I am completely drug free. Don't delay, ring the brothers!"
                            </blockquote>
                        </div>
                        <div class="flex items-center gap-3 pt-4 border-t border-white/10">
                            <div class="w-10 h-10 rounded-full bg-emerald-600/30 text-emerald-400 flex items-center justify-center font-bold">A</div>
                            <div>
                                <h4 class="font-bold text-white">Abdul (Abz) Alibhai</h4>
                                <p class="text-xs text-emerald-400">Drug-Free After 20 Years</p>
                            </div>
                        </div>
                    </div>

                    <!-- Suleman -->
                    <div class="p-8 rounded-3xl bg-white/5 border border-white/10 backdrop-blur-md flex flex-col justify-between space-y-6">
                        <div class="space-y-4">
                            <div class="flex text-amber-400 gap-1 text-sm">
                                ★★★★★
                            </div>
                            <blockquote class="text-slate-200 text-base sm:text-lg leading-relaxed italic">
                                "I have been an alcoholic and drug addict for over 25 years. I was living in a filthy flat with lots of unsavoury people coming and going. The brothers visited at night, washed my clothes so I had clean attire, brought me food, negotiated outstanding rent with my landlord, and moved me into temporary housing. When ready, they referred me to specialist rehab. They did all this in their own time as volunteers."
                            </blockquote>
                        </div>
                        <div class="flex items-center gap-3 pt-4 border-t border-white/10">
                            <div class="w-10 h-10 rounded-full bg-emerald-600/30 text-emerald-400 flex items-center justify-center font-bold">S</div>
                            <div>
                                <h4 class="font-bold text-white">Suleman</h4>
                                <p class="text-xs text-emerald-400">Recovering Alcoholic & Substance Free</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 8. Volunteer & Donate Dual Section -->
        <section class="py-20 sm:py-28 bg-white dark:bg-slate-900 border-b border-slate-200/80 dark:border-slate-800">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Volunteer Card -->
                    <div id="volunteer" class="rounded-3xl p-8 sm:p-12 bg-gradient-to-br from-emerald-900 via-emerald-950 to-slate-950 text-white shadow-xl flex flex-col justify-between space-y-6">
                        <div class="space-y-4">
                            <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-emerald-500/20 text-emerald-300 border border-emerald-400/30 inline-block">
                                Join the Team
                            </span>
                            <h3 class="text-2xl sm:text-4xl font-extrabold text-white">
                                Volunteer With Us
                            </h3>
                            <p class="text-slate-300 text-sm sm:text-base leading-relaxed">
                                If you would like to do something truly amazing and support someone in overcoming their addiction to live a happier, healthier, and more fulfilled life, get involved today.
                            </p>
                            <div class="space-y-2 text-xs sm:text-sm text-emerald-200">
                                <p>✓ Peer Mentorship (Lived Experience welcome)</p>
                                <p>✓ Community Liaisons & Family Mediators</p>
                                <p>✓ Transport & Practical Welfare Volunteers</p>
                            </div>
                        </div>

                        <div>
                            <a href="#contact" class="inline-flex items-center gap-2 px-6 py-3.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-sm shadow transition">
                                <span>Express Interest in Volunteering</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </a>
                        </div>
                    </div>

                    <!-- Donate Card -->
                    <div id="donate" class="rounded-3xl p-8 sm:p-12 bg-gradient-to-br from-amber-900 via-amber-950 to-slate-950 text-white shadow-xl flex flex-col justify-between space-y-6">
                        <div class="space-y-4">
                            <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-amber-500/20 text-amber-300 border border-amber-400/30 inline-block">
                                Save a Life
                            </span>
                            <h3 class="text-2xl sm:text-4xl font-extrabold text-white">
                                Support Our Mission
                            </h3>
                            <p class="text-slate-300 text-sm sm:text-base leading-relaxed">
                                Spinney Hill Drugs, Alcohol & Addiction Support is a non-profit organisation whose frontline work is delivered by devoted volunteers. Every contribution directly funds emergency food, clothing, and specialist rehab placements.
                            </p>
                            <div class="space-y-2 text-xs sm:text-sm text-amber-200">
                                <p>✓ 100% of public donations fund direct community aid</p>
                                <p>✓ Eligible for UK Gift Aid (+25% at no cost to you)</p>
                                <p>✓ Bank transfers & general charity donations welcome</p>
                            </div>
                        </div>

                        <div>
                            <a href="tel:03001240945" class="inline-flex items-center gap-2 px-6 py-3.5 rounded-xl bg-amber-400 hover:bg-amber-300 text-slate-950 font-bold text-sm shadow transition">
                                <span>Donate or Inquire: 0300 124 0945</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 9. Contact & Intake Form Section -->
        <section id="contact" class="py-20 sm:py-28 bg-slate-50 dark:bg-slate-950 relative">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-start">
                    <!-- Left: Contact Details & Guarantee -->
                    <div class="lg:col-span-5 space-y-8">
                        <div>
                            <span class="text-xs font-extrabold uppercase tracking-widest text-emerald-600 dark:text-emerald-400">
                                Get In Touch
                            </span>
                            <h2 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-slate-900 dark:text-white tracking-tight mt-1">
                                Take the First Step in Confidence
                            </h2>
                            <p class="text-slate-600 dark:text-slate-300 text-base mt-4 leading-relaxed">
                                You do not have to know all the answers today. Just reach out, and one of our caring caseworkers will walk you through your options.
                            </p>
                        </div>

                        <!-- Direct Contact List -->
                        <div class="space-y-4">
                            <!-- Phone -->
                            <a href="tel:03001240945" class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 flex items-center gap-4 hover:border-emerald-500 transition shadow-sm">
                                <div class="w-12 h-12 rounded-xl bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-400 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-xs uppercase font-bold text-slate-400">Confidential Telephone</p>
                                    <p class="text-lg font-bold text-slate-900 dark:text-white">0300 124 0945</p>
                                </div>
                            </a>

                            <!-- Email -->
                            <a href="mailto:info@spinneyhill.org.uk" class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 flex items-center gap-4 hover:border-emerald-500 transition shadow-sm">
                                <div class="w-12 h-12 rounded-xl bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-400 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-xs uppercase font-bold text-slate-400">Email Address</p>
                                    <p class="text-sm sm:text-base font-bold text-slate-900 dark:text-white">info@spinneyhill.org.uk</p>
                                </div>
                            </a>

                            <!-- Address -->
                            <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 flex items-center gap-4 shadow-sm">
                                <div class="w-12 h-12 rounded-xl bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-400 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-xs uppercase font-bold text-slate-400">Centre Address</p>
                                    <p class="text-sm sm:text-base font-bold text-slate-900 dark:text-white">451 St Saviours Road, Leicester, LE5 4HJ</p>
                                </div>
                            </div>
                        </div>

                        <!-- Strict Confidentiality Card -->
                        <div class="p-5 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 text-xs sm:text-sm text-emerald-900 dark:text-emerald-200 space-y-1">
                            <p class="font-bold">🛡️ Our Strict Confidentiality Pledge</p>
                            <p class="text-emerald-800 dark:text-emerald-300 leading-relaxed">
                                We are not connected to the police, immigration, or local statutory registers. What you discuss with us is completely private and protected under UK GDPR.
                            </p>
                        </div>
                    </div>

                    <!-- Right: Embedded Livewire Form -->
                    <div class="lg:col-span-7">
                        <livewire:public-enquiry-form />
                    </div>
                </div>
            </div>
        </section>

        <!-- 10. Footer -->
        <footer class="bg-slate-950 text-slate-400 border-t border-slate-800 text-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-10">
                    <!-- Brand Column -->
                    <div class="space-y-4 md:col-span-2">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-emerald-600 text-white flex items-center justify-center font-bold">
                                RN
                            </div>
                            <div>
                                <span class="text-xl font-bold text-white font-['Outfit',sans-serif]">Recovery Network</span>
                                <span class="block text-xs text-slate-500">Spinney Hill Drugs, Alcohol & Addiction Support</span>
                            </div>
                        </div>
                        <p class="text-xs sm:text-sm text-slate-400 max-w-md leading-relaxed">
                            Established to address the growing addiction crisis in our communities. Delivering culturally sensitive, confidential, holistic care across the Body, Mind, and Soul.
                        </p>
                        <p class="text-xs text-slate-500">
                            Registered non-profit community organisation · 451 St Saviours Road, Leicester, LE5 4HJ
                        </p>
                    </div>

                    <!-- Quick Navigation -->
                    <div>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-200 mb-4">Quick Navigation</h4>
                        <ul class="space-y-2 text-xs sm:text-sm">
                            <li><a href="#about" class="hover:text-emerald-400 transition">About Us</a></li>
                            <li><a href="#pathway" class="hover:text-emerald-400 transition">Our 3-Stage Pathway</a></li>
                            <li><a href="#services" class="hover:text-emerald-400 transition">Support & Services</a></li>
                            <li><a href="#testimonials" class="hover:text-emerald-400 transition">Community Stories</a></li>
                            <li><a href="#volunteer" class="hover:text-emerald-400 transition">Volunteer</a></li>
                            <li><a href="#donate" class="hover:text-emerald-400 transition">Donate</a></li>
                            <li><a href="#contact" class="hover:text-emerald-400 transition">Contact & Helpline</a></li>
                        </ul>
                    </div>

                    <!-- Support & Portal -->
                    <div>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-200 mb-4">Support & Portal</h4>
                        <ul class="space-y-2 text-xs sm:text-sm">
                            <li><a href="tel:03001240945" class="font-bold text-amber-400 hover:text-amber-300">Helpline: 0300 124 0945</a></li>
                            <li><a href="mailto:info@spinneyhill.org.uk" class="hover:text-emerald-400 transition">info@spinneyhill.org.uk</a></li>
                            <li><a href="{{ route('policy.show') }}" class="hover:text-emerald-400 transition">Privacy Policy</a></li>
                            <li><a href="{{ route('terms.show') }}" class="hover:text-emerald-400 transition">Terms of Service</a></li>
                            <li class="pt-3">
                                <a href="{{ route('login') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-white text-xs font-semibold transition">
                                    <span>Staff & Volunteer CRM</span>
                                    <svg class="w-3 h-3 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                    </svg>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="mt-12 pt-8 border-t border-slate-900 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-500">
                    <p>© {{ date('Y') }} Recovery Network / Spinney Hill Addiction Support. All rights reserved.</p>
                    <p>Designed with care for the Leicester community and beyond.</p>
                </div>
            </div>
        </footer>

    </div>

</x-layout.plain>
