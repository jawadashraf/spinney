<x-layout.donate title="Thank you | Recovery Network – Spinney Hill" heading="Thank you">
    <div class="mx-auto max-w-2xl space-y-6 rounded-3xl border border-emerald-900/10 dark:border-emerald-500/20 bg-white/95 dark:bg-slate-900/95 p-8 text-center shadow-2xl sm:p-12">
        <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 shadow-inner dark:bg-emerald-950/80 dark:text-emerald-400">
            <svg class="h-10 w-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
            </svg>
        </div>
        <h2 class="text-2xl font-extrabold text-slate-900 dark:text-white sm:text-3xl font-['Outfit',sans-serif]">
            Thank you{{ $firstName ? ', '.$firstName : '' }}!
        </h2>
        <p class="text-sm leading-relaxed text-slate-600 dark:text-slate-300 sm:text-base">
            Your {{ $isMonthly ? 'monthly gift' : 'gift' }} helps people in our community overcome addiction and rebuild their lives.
            Stripe will email you a receipt for your payment.
        </p>
        @if ($isMonthly)
            <p class="text-sm text-slate-500 dark:text-slate-400">You can cancel your monthly gift at any time by emailing info@spinneyhill.org.uk.</p>
        @endif
        <a href="{{ url('/') }}" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-emerald-600 to-emerald-700 px-6 py-3 text-sm font-bold text-white shadow-md shadow-emerald-700/20 transition hover:from-emerald-500 hover:to-emerald-600">Back to home</a>
    </div>
</x-layout.donate>
