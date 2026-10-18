<div class="bg-white/95 dark:bg-slate-900/95 backdrop-blur-md rounded-3xl p-6 sm:p-10 shadow-2xl border border-emerald-900/10 dark:border-emerald-500/20 transition-all duration-300">
    @if ($submitted)
        <div class="text-center py-8 space-y-6 animate-fadeIn">
            <div class="w-20 h-20 bg-emerald-100 dark:bg-emerald-950/80 text-emerald-600 dark:text-emerald-400 rounded-full flex items-center justify-center mx-auto shadow-inner">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                </svg>
            </div>

            <div class="space-y-2">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-700">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    Enquiry Logged Securely
                </span>
                <h3 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white font-['Outfit',sans-serif]">
                    We Have Received Your Message
                </h3>
                <p class="text-slate-600 dark:text-slate-300 max-w-lg mx-auto text-sm sm:text-base leading-relaxed">
                    Thank you for taking this brave first step. One of our trained, compassionate team members will contact you in <strong class="text-emerald-700 dark:text-emerald-400 font-semibold">strict confidence</strong> via your preferred method.
                </p>
            </div>

            <div class="bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800/60 rounded-2xl p-5 max-w-md mx-auto text-left flex items-start gap-4">
                <div class="p-2 bg-amber-100 dark:bg-amber-900/60 text-amber-700 dark:text-amber-300 rounded-xl flex-shrink-0 mt-0.5">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                    </svg>
                </div>
                <div class="space-y-1">
                    <p class="text-xs uppercase tracking-wider font-bold text-amber-800 dark:text-amber-300">Need Immediate Advice?</p>
                    <p class="text-sm text-amber-900 dark:text-amber-200">
                        If you or someone else is in immediate distress or needs advice right now, please call our direct confidential line:
                    </p>
                    <a href="tel:03001240945" class="inline-block pt-1 font-bold text-lg text-emerald-800 dark:text-emerald-400 hover:underline">
                        0300 124 0945
                    </a>
                </div>
            </div>

            <div class="pt-4">
                <button type="button" wire:click="resetForm" class="text-sm font-semibold text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white transition">
                    ← Send another message or enquiry
                </button>
            </div>
        </div>
    @else
        <form wire:submit="submit" class="space-y-6">
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100/80 text-emerald-800 dark:bg-emerald-950/80 dark:text-emerald-300">
                        <svg class="w-3.5 h-3.5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 1.944A11.954 11.954 0 012.166 5C2.056 5.649 2 6.319 2 7c0 5.225 3.34 9.67 8 11.317C14.66 16.67 18 12.225 18 7c0-.682-.057-1.35-.166-2.001A11.954 11.954 0 0110 1.944zM11 14a1 1 0 11-2 0 1 1 0 012 0zm0-7a1 1 0 10-2 0v3a1 1 0 102 0V7z" clip-rule="evenodd" />
                        </svg>
                        100% Confidential & Free
                    </span>
                    <span class="text-xs text-slate-500 dark:text-slate-400">Response within 24h</span>
                </div>
                <h3 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight font-['Outfit',sans-serif]">
                    Request a Confidential Callback
                </h3>
                <p class="text-sm text-slate-600 dark:text-slate-300">
                    Reach out for yourself, a family member, or a friend. We do not judge, and your details never leave our team.
                </p>
            </div>

            <!-- Support For Radio Buttons -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-2">
                    Who needs support? <span class="text-rose-500">*</span>
                </label>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                    @foreach ([
                        'myself' => 'Myself',
                        'family' => 'Family Member',
                        'friend' => 'Friend',
                        'professional' => 'Referral'
                    ] as $key => $label)
                        <label class="cursor-pointer border rounded-xl p-2.5 text-center text-xs sm:text-sm font-semibold transition-all flex items-center justify-center
                            {{ $support_for === $key ? 'bg-emerald-600 text-white border-emerald-600 shadow-sm' : 'bg-slate-50 dark:bg-slate-800/60 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700 hover:border-emerald-400' }}">
                            <input type="radio" wire:model.live="support_for" value="{{ $key }}" class="sr-only">
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
            </div>

            <!-- Substance / Focus Area -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-2">
                    Primary Area of Concern <span class="text-rose-500">*</span>
                </label>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                    @foreach ([
                        'drugs' => 'Drugs / Substances',
                        'alcohol' => 'Alcohol Addiction',
                        'both' => 'Drugs & Alcohol',
                        'gambling' => 'Gambling',
                        'other' => 'Other / Multiple'
                    ] as $subKey => $subLabel)
                        <label class="cursor-pointer border rounded-xl p-2.5 text-center text-xs sm:text-sm font-semibold transition-all flex items-center justify-center
                            {{ $substance === $subKey ? 'bg-amber-500 text-white border-amber-500 shadow-sm' : 'bg-slate-50 dark:bg-slate-800/60 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700 hover:border-amber-400' }}">
                            <input type="radio" wire:model.live="substance" value="{{ $subKey }}" class="sr-only">
                            {{ $subLabel }}
                        </label>
                    @endforeach
                </div>
            </div>

            <!-- Contact Fields Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Name -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label for="enquiry-name" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                            Your Name (or First Name)
                        </label>
                        <label class="inline-flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400 cursor-pointer">
                            <input type="checkbox" wire:model.live="is_anonymous" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                            Anonymous
                        </label>
                    </div>
                    <input
                        type="text"
                        id="enquiry-name"
                        wire:model="name"
                        placeholder="{{ $is_anonymous ? 'Remaining Anonymous' : 'e.g. Tariq or Wasim' }}"
                        @disabled($is_anonymous)
                        class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/80 text-slate-900 dark:text-white placeholder-slate-400 focus:bg-white dark:focus:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500 transition text-sm disabled:opacity-50">
                    @error('name') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- Phone -->
                <div>
                    <label for="enquiry-phone" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                        Phone Number <span class="text-rose-500">*</span>
                    </label>
                    <input
                        type="tel"
                        id="enquiry-phone"
                        wire:model="phone"
                        placeholder="e.g. 07123 456789"
                        required
                        class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/80 text-slate-900 dark:text-white placeholder-slate-400 focus:bg-white dark:focus:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500 transition text-sm">
                    @error('phone') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                </div>
            </div>

            <!-- Email & Preferred Contact -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Email -->
                <div>
                    <label for="enquiry-email" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                        Email Address <span class="text-slate-400 font-normal">(Optional)</span>
                    </label>
                    <input
                        type="email"
                        id="enquiry-email"
                        wire:model="email"
                        placeholder="you@example.com"
                        class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/80 text-slate-900 dark:text-white placeholder-slate-400 focus:bg-white dark:focus:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500 transition text-sm">
                    @error('email') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- Preferred Method -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                        How should we contact you?
                    </label>
                    <select wire:model.live="preferred_contact" class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/80 text-slate-900 dark:text-white focus:bg-white dark:focus:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500 transition text-sm">
                        <option value="phone">Discreet Phone Call</option>
                        <option value="text">Discreet SMS / Text Message</option>
                        <option value="whatsapp">WhatsApp Message</option>
                        <option value="email">Email</option>
                    </select>
                </div>
            </div>

            <!-- Message / Notes -->
            <div>
                <label for="enquiry-message" class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                    How can we help? <span class="text-slate-400 font-normal">(Optional brief message)</span>
                </label>
                <textarea
                    id="enquiry-message"
                    wire:model="message"
                    rows="3"
                    placeholder="Tell us whatever you are comfortable sharing: what you're facing, best time to reach you, etc."
                    class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/80 text-slate-900 dark:text-white placeholder-slate-400 focus:bg-white dark:focus:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500 transition text-sm"></textarea>
                @error('message') <span class="text-xs text-rose-500 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <!-- Urgent checkbox & Reassurance -->
            <div class="flex items-start gap-3 bg-emerald-50/60 dark:bg-emerald-950/20 p-3.5 rounded-xl border border-emerald-100 dark:border-emerald-900/40">
                <input
                    type="checkbox"
                    id="enquiry-urgent"
                    wire:model="urgent"
                    class="mt-1 rounded border-slate-300 text-amber-500 focus:ring-amber-400">
                <label for="enquiry-urgent" class="text-xs sm:text-sm text-slate-700 dark:text-slate-300 cursor-pointer">
                    <strong class="font-bold text-slate-900 dark:text-white">This situation is urgent.</strong> Check this box if you or your loved one need priority outreach or are in crisis.
                </label>
            </div>

            <!-- Submit Button -->
            <div>
                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    class="w-full py-4 px-6 rounded-2xl bg-gradient-to-r from-emerald-600 via-emerald-700 to-emerald-800 hover:from-emerald-500 hover:to-emerald-700 text-white font-bold text-base shadow-lg shadow-emerald-700/25 hover:shadow-xl hover:shadow-emerald-700/35 transition-all transform active:scale-[0.99] flex items-center justify-center gap-2 cursor-pointer disabled:opacity-70">
                    <span wire:loading.remove class="flex items-center gap-2">
                        <span>Send Confidential Request</span>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </span>
                    <span wire:loading class="flex items-center gap-2">
                        <svg class="animate-spin -ml-1 mr-2 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Submitting Securely...
                    </span>
                </button>
            </div>

            <p class="text-center text-xs text-slate-500 dark:text-slate-400">
                🔒 Protected by strict data protection & confidentiality laws. We will never share your information with anyone outside our support staff.
            </p>
        </form>
    @endif
</div>
