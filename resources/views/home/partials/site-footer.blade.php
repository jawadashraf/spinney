{{-- Shared by the landing page and the donate page. See site-header for $linkBase. --}}
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
                    <li><a href="{{ $linkBase }}#about" class="hover:text-emerald-400 transition">About Us</a></li>
                    <li><a href="{{ $linkBase }}#pathway" class="hover:text-emerald-400 transition">Our 3-Stage Pathway</a></li>
                    <li><a href="{{ $linkBase }}#services" class="hover:text-emerald-400 transition">Support & Services</a></li>
                    <li><a href="{{ $linkBase }}#testimonials" class="hover:text-emerald-400 transition">Community Stories</a></li>
                    <li><a href="{{ $linkBase }}#volunteer" class="hover:text-emerald-400 transition">Volunteer</a></li>
                    <li><a href="{{ route('donate') }}" class="hover:text-emerald-400 transition">Donate</a></li>
                    <li><a href="{{ $linkBase }}#contact" class="hover:text-emerald-400 transition">Contact & Helpline</a></li>
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
