<x-layout.donate
    title="Donate | Recovery Network – Spinney Hill"
    heading="Help us reach further"
    intro="Give securely online – as a one-off gift or every month – or by bank transfer. Every gift, no matter how small, helps us support people in our community.">
    <div class="grid gap-10 lg:grid-cols-12">
        <div class="lg:col-span-8">
            @if ($cancelled)
                <div class="mb-6 rounded-2xl border border-amber-200 dark:border-amber-800/60 bg-amber-50 dark:bg-amber-950/30 p-4 text-sm text-amber-900 dark:text-amber-200">
                    Your payment was not completed and you have not been charged. You can try again below whenever you are ready.
                </div>
            @endif

            <div class="mb-8 space-y-4 text-sm leading-relaxed text-slate-600 dark:text-slate-300 sm:text-base">
                <span class="inline-block rounded-full border border-amber-400/40 bg-amber-100 px-3 py-1 text-xs font-bold uppercase tracking-wider text-amber-800 dark:bg-amber-500/20 dark:text-amber-300">
                    Save a Life
                </span>
                <p>Spinney Hill Drugs, Alcohol and Addiction Support is a not-for-profit organisation whose work is delivered mainly by volunteers. As such, it relies upon the donations of generous supporters in order to deliver its work. We truly appreciate any and all donations that we receive, and which help us to help those in need.</p>
                <p>If you would like to make a donation, no matter how small, you can give securely online below – as a one-off gift or every month – or by bank transfer.</p>
            </div>

            <h2 id="donate-online" class="mb-6 text-2xl font-extrabold tracking-tight text-slate-900 dark:text-white sm:text-3xl font-['Outfit',sans-serif]">Donate Online</h2>
            <livewire:public-donation-form />
        </div>

        <aside class="space-y-6 lg:col-span-4">
            <section class="rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-sm">
                <h3 class="text-lg font-extrabold text-slate-900 dark:text-white font-['Outfit',sans-serif]">Donate by Bank Transfer</h3>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">Prefer to give by bank transfer? Please use these details:</p>
                <dl class="mt-4 space-y-2 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 p-4 text-sm">
                    <div class="flex justify-between gap-3"><dt class="text-xs font-bold uppercase text-slate-500 dark:text-slate-400">Account name</dt> <dd class="font-bold text-slate-900 dark:text-white">The Recovery Network</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-xs font-bold uppercase text-slate-500 dark:text-slate-400">Sort code</dt> <dd class="font-bold text-slate-900 dark:text-white">23-05-80</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-xs font-bold uppercase text-slate-500 dark:text-slate-400">Account number</dt> <dd class="font-bold text-slate-900 dark:text-white">53589677</dd></div>
                </dl>
            </section>

            <section class="space-y-4 rounded-3xl bg-gradient-to-br from-amber-900 via-amber-950 to-slate-950 p-6 text-white shadow-xl">
                <h3 class="text-lg font-extrabold font-['Outfit',sans-serif]">Fundraise for us on JustGiving</h3>
                <p class="text-sm leading-relaxed text-slate-300">Running a marathon, holding a bake sale or setting up a fundraiser in memory of a loved one? Create your own JustGiving page in minutes and invite friends and family to support you. Every pound raised helps people in our community overcome addiction and rebuild their lives.</p>
                <a href="https://www.justgiving.com/create-page?charityId=3940364" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-xl bg-amber-400 px-5 py-3 text-sm font-bold text-slate-950 shadow transition hover:bg-amber-300">Start fundraising</a>
                <a href="https://www.justgiving.com/charity/therecoverynetwork" target="_blank" rel="noopener" class="block text-xs font-semibold text-amber-200 underline hover:text-amber-100">View our JustGiving page</a>
            </section>

            <section class="space-y-1 rounded-2xl border border-emerald-200 dark:border-emerald-800/60 bg-emerald-50 dark:bg-emerald-950/40 p-5 text-xs text-emerald-900 dark:text-emerald-200 sm:text-sm">
                <p class="font-bold">Questions about giving?</p>
                <p class="leading-relaxed text-emerald-800 dark:text-emerald-300">Call us on <a href="tel:03001240945" class="font-bold hover:underline">0300 124 0945</a> or email <a href="mailto:info@spinneyhill.org.uk" class="font-bold hover:underline">info@spinneyhill.org.uk</a>.</p>
            </section>
        </aside>
    </div>
</x-layout.donate>
