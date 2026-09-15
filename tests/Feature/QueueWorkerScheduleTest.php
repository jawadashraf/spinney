<?php

declare(strict_types=1);

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;

it('runs a short-lived queue worker every minute that a killed run cannot block for long', function () {
    $this->artisan('schedule:list')->run();

    $worker = collect(app(Schedule::class)->events())
        ->first(fn (Event $event): bool => str_contains((string) $event->command, 'queue:work'));

    expect($worker)->not->toBeNull()
        ->and($worker->command)->toContain('--stop-when-empty')
        ->and($worker->command)->toContain('--max-time=50')
        ->and($worker->expression)->toBe('* * * * *')
        ->and($worker->withoutOverlapping)->toBeTrue()
        ->and($worker->expiresAt)->toBe(5);
});
