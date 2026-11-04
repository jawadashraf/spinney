@php
    $card = 'rounded-3xl border border-emerald-900/10 dark:border-emerald-500/20 bg-white/95 dark:bg-slate-900/95 p-6 sm:p-8 shadow-xl';
    $stepNumber = 'flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-xl bg-emerald-600 text-sm font-bold text-white';
    $stepTitle = "text-xl font-extrabold tracking-tight text-slate-900 dark:text-white font-['Outfit',sans-serif]";
    $label = 'mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300';
    $input = 'w-full rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/80 px-4 py-3 text-sm text-slate-900 dark:text-white placeholder-slate-400 transition focus:border-emerald-500 focus:bg-white dark:focus:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-emerald-500/50';
    $checkbox = 'mt-0.5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500';
    $error = 'mt-1 block text-xs text-rose-500';
    $choice = 'flex cursor-pointer items-center justify-center rounded-xl border p-3 text-center font-semibold transition-all has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-emerald-500/60';
    $choiceOn = 'border-emerald-600 bg-emerald-600 text-white shadow-sm';
    $choiceOff = 'border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 text-slate-700 dark:text-slate-300 hover:border-emerald-400';
@endphp

<form wire:submit="submit" novalidate class="space-y-6">
    <div class="hidden" aria-hidden="true">
        <label>Leave this empty <input type="text" wire:model="website" tabindex="-1" autocomplete="off"></label>
    </div>

    {{-- 1. Your gift --}}
    <section class="{{ $card }}">
        <div class="mb-5 flex items-center gap-3">
            <span class="{{ $stepNumber }}">1</span>
            <h3 class="{{ $stepTitle }}">Your gift</h3>
        </div>

        <div role="radiogroup" aria-label="Donation frequency" class="mb-5 grid max-w-sm grid-cols-2 gap-1 rounded-2xl bg-slate-100 dark:bg-slate-800 p-1">
            @foreach (['one-off' => 'Give once', 'monthly' => 'Give monthly'] as $value => $text)
                <label wire:key="frequency-{{ $value }}" class="cursor-pointer rounded-xl px-3 py-2.5 text-center text-sm font-bold transition has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-emerald-500/60 {{ $frequency === $value ? 'bg-white dark:bg-slate-900 text-emerald-700 dark:text-emerald-400 shadow' : 'text-slate-600 dark:text-slate-300 hover:text-emerald-600' }}">
                    <input type="radio" wire:model.live="frequency" value="{{ $value }}" class="sr-only">
                    {{ $text }}
                </label>
            @endforeach
        </div>

        <div class="mb-5 grid grid-cols-2 gap-3 sm:grid-cols-4">
            @foreach (\App\Livewire\PublicDonationForm::PRESET_AMOUNTS as $amount)
                <label wire:key="preset-{{ $amount }}" class="{{ $choice }} py-4 text-xl font-['Outfit',sans-serif] {{ $preset === $amount ? $choiceOn : $choiceOff }}">
                    <input type="radio" wire:model.live="preset" value="{{ $amount }}" class="sr-only">
                    £{{ $amount }}
                </label>
            @endforeach
        </div>

        <label for="donation-other-amount" class="{{ $label }}">Or enter another amount (£)</label>
        <input id="donation-other-amount" type="number" wire:model.live.debounce.400ms="other_amount" min="1" step="0.01" inputmode="decimal" placeholder="e.g. 15" class="{{ $input }}">
        @error('other_amount') <span class="{{ $error }}">{{ $message }}</span> @enderror

        @if ($hasAmount)
            <label class="mt-5 flex cursor-pointer items-start gap-3 rounded-2xl border border-amber-200 dark:border-amber-800/60 bg-amber-50 dark:bg-amber-950/30 p-4 text-sm text-amber-900 dark:text-amber-200">
                <input type="checkbox" wire:model.live="cover_fee" class="{{ $checkbox }}">
                <span>Add <strong>{{ $fee }}</strong> to cover the card processing fee, so all of your {{ $gift }} goes to our work.</span>
            </label>

            <div class="mt-5 rounded-2xl border border-emerald-200 dark:border-emerald-800/60 bg-emerald-50 dark:bg-emerald-950/40 p-4 text-sm text-emerald-900 dark:text-emerald-200">
                You are giving <strong class="text-base">{{ $total }}</strong>{{ $frequency === 'monthly' ? ' every month' : ' as a one-off gift' }}@if ($cover_fee) (including {{ $fee }} to cover card fees)@endif.
            </div>
        @endif
    </section>

    {{-- 2. Your details --}}
    <section class="{{ $card }}">
        <div class="mb-5 flex items-center gap-3">
            <span class="{{ $stepNumber }}">2</span>
            <h3 class="{{ $stepTitle }}">Your details</h3>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="donation-title" class="{{ $label }}">Title</label>
                <select id="donation-title" wire:model="title" class="{{ $input }}">
                    <option value=""></option>
                    @foreach (\App\Livewire\PublicDonationForm::TITLES as $option)
                        <option wire:key="title-{{ $option }}" value="{{ $option }}">{{ $option }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="donation-first-name" class="{{ $label }}">First name <span class="text-rose-500">*</span></label>
                <input id="donation-first-name" type="text" wire:model="first_name" autocomplete="given-name" class="{{ $input }}">
                @error('first_name') <span class="{{ $error }}">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="donation-last-name" class="{{ $label }}">Last name <span class="text-rose-500">*</span></label>
                <input id="donation-last-name" type="text" wire:model="last_name" autocomplete="family-name" class="{{ $input }}">
                @error('last_name') <span class="{{ $error }}">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="donation-email" class="{{ $label }}">Email <span class="text-rose-500">*</span></label>
                <input id="donation-email" type="email" wire:model="email" autocomplete="email" class="{{ $input }}">
                @error('email') <span class="{{ $error }}">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="donation-phone" class="{{ $label }}">Phone</label>
                <input id="donation-phone" type="tel" wire:model="phone" autocomplete="tel" class="{{ $input }}">
                @error('phone') <span class="{{ $error }}">{{ $message }}</span> @enderror
            </div>
        </div>
    </section>

    {{-- 3. Gift Aid --}}
    <section class="{{ $card }}">
        <div class="mb-4 flex items-center gap-3">
            <span class="{{ $stepNumber }}">3</span>
            <h3 class="{{ $stepTitle }}">Boost your gift with Gift Aid</h3>
        </div>

        <p class="text-sm leading-relaxed text-slate-600 dark:text-slate-300">
            If you are a UK taxpayer, Gift Aid adds <strong class="text-emerald-700 dark:text-emerald-400">25p to every £1</strong> you give, at no extra cost to you.
            @if ($hasAmount) Your {{ $gift }} becomes <strong class="text-emerald-700 dark:text-emerald-400">{{ $giftWithGiftAid }}</strong>. @endif
        </p>

        <label class="mt-4 flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 p-4 text-sm leading-relaxed text-slate-700 dark:text-slate-300">
            <input type="checkbox" wire:model.live="gift_aid" class="{{ $checkbox }}">
            <span>Yes, I want to Gift Aid my donation @if ($hasAmount) of <strong>{{ $gift }}</strong> @endif and any donations I make in the future or have made in the past 4 years to The Recovery Network. I am a UK taxpayer and understand that if I pay less Income Tax and/or Capital Gains Tax than the amount of Gift Aid claimed on all my donations in that tax year it is my responsibility to pay any difference.</span>
        </label>

        @if ($gift_aid)
            <div class="mt-5 space-y-4">
                <p class="text-sm text-slate-600 dark:text-slate-300">We need your home address to claim Gift Aid from HMRC.</p>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="donation-house" class="{{ $label }}">House name / number <span class="text-rose-500">*</span></label>
                        <input id="donation-house" type="text" wire:model="house" autocomplete="address-line1" class="{{ $input }}">
                        @error('house') <span class="{{ $error }}">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label for="donation-street" class="{{ $label }}">Street <span class="text-rose-500">*</span></label>
                        <input id="donation-street" type="text" wire:model="street" autocomplete="address-line2" class="{{ $input }}">
                        @error('street') <span class="{{ $error }}">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label for="donation-town" class="{{ $label }}">Town / city <span class="text-rose-500">*</span></label>
                        <input id="donation-town" type="text" wire:model="town" autocomplete="address-level2" class="{{ $input }}">
                        @error('town') <span class="{{ $error }}">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label for="donation-postcode" class="{{ $label }}">Postcode <span class="text-rose-500">*</span></label>
                        <input id="donation-postcode" type="text" wire:model="postcode" autocomplete="postal-code" class="{{ $input }}">
                        @error('postcode') <span class="{{ $error }}">{{ $message }}</span> @enderror
                    </div>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400">Please let us know if you want to cancel this declaration, change your name or home address, or no longer pay enough Income Tax and/or Capital Gains Tax.</p>
            </div>
        @endif
    </section>

    {{-- 4. Confirm --}}
    <section class="{{ $card }}">
        <div class="mb-5 flex items-center gap-3">
            <span class="{{ $stepNumber }}">4</span>
            <h3 class="{{ $stepTitle }}">Confirm</h3>
        </div>

        <div class="space-y-3 text-sm text-slate-700 dark:text-slate-300">
            <label class="flex cursor-pointer items-start gap-3">
                <input type="checkbox" wire:model="contact_optin" class="{{ $checkbox }}">
                <span>I'm happy for Spinney Hill to contact me by email about its work and future appeals. <span class="text-slate-500 dark:text-slate-400">(Optional – you can unsubscribe at any time.)</span></span>
            </label>

            <label class="flex cursor-pointer items-start gap-3">
                <input type="checkbox" wire:model="consent" class="{{ $checkbox }}">
                <span>I have read the <a href="https://spinneyhill.org.uk/privacy-policy/" target="_blank" rel="noopener" class="font-semibold text-emerald-700 dark:text-emerald-400 underline">privacy policy</a> and understand how my details will be used to process my donation. <span class="text-rose-500">*</span></span>
            </label>
            @error('consent') <span class="{{ $error }}">{{ $message }}</span> @enderror
        </div>

        @error('form') <p class="mt-5 rounded-xl border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700 dark:border-rose-800/60 dark:bg-rose-950/30 dark:text-rose-300">{{ $message }}</p> @enderror

        <button type="submit" wire:loading.attr="disabled" class="mt-6 flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-emerald-600 to-emerald-700 px-6 py-4 text-sm font-bold text-white shadow-md shadow-emerald-700/20 transition hover:from-emerald-500 hover:to-emerald-600 hover:shadow-lg disabled:opacity-60">
            <span wire:loading.remove wire:target="submit">Continue to secure payment</span>
            <span wire:loading wire:target="submit">Opening secure payment…</span>
            <svg wire:loading.remove wire:target="submit" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3" />
            </svg>
        </button>

        <p class="mt-4 text-center text-xs text-slate-500 dark:text-slate-400">
            🔒 Payments are processed securely by Stripe. Card details never touch our website.
            @if ($frequency === 'monthly') Monthly gifts can be cancelled at any time by emailing info@spinneyhill.org.uk. @endif
        </p>
    </section>
</form>
