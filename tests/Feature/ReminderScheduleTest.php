<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;

it('runs the daily reminders at UK local time all year', function (string $command, string $summerUtcTime, string $ukTime) {
    $this->artisan('schedule:list')->run();

    $reminder = collect(app(Schedule::class)->events())
        ->first(fn (Event $event): bool => str_contains((string) $event->command, $command));

    $this->travelTo(CarbonImmutable::parse("2026-07-01 {$summerUtcTime}:00", 'UTC'));
    $dueInSummerAtUkTime = $reminder->isDue(app());

    $this->travelTo(CarbonImmutable::parse("2026-07-01 {$ukTime}:00", 'UTC'));
    $dueInSummerAtUtcTime = $reminder->isDue(app());

    $this->travelTo(CarbonImmutable::parse("2026-01-15 {$ukTime}:00", 'UTC'));
    $dueInWinter = $reminder->isDue(app());

    expect($dueInSummerAtUkTime)->toBeTrue()
        ->and($dueInSummerAtUtcTime)->toBeFalse()
        ->and($dueInWinter)->toBeTrue();
})->with([
    'call reminders at 07:45' => ['calls:send-reminders', '06:45', '07:45'],
    'task reminders at 08:00' => ['tasks:send-due-reminders', '07:00', '08:00'],
    'appointment reminders at 09:00' => ['appointments:send-reminders', '08:00', '09:00'],
]);
