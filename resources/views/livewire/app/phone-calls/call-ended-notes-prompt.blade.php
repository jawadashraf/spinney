<div
    x-data="{
        timer: null,
        check() {
            if (! document.hidden) {
                $wire.checkForEndedCall()
            }
        },
        init() {
            this.timer = setInterval(() => this.check(), {{ \App\Livewire\App\PhoneCalls\CallEndedNotesPrompt::POLL_SECONDS * 1000 }})
        },
        destroy() {
            clearInterval(this.timer)
        },
    }"
    x-on:visibilitychange.document="check()"
>
    <x-filament-actions::modals />
</div>
