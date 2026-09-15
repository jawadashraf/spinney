<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

Schedule::command('appointments:send-reminders')
    ->dailyAt('09:00')
    ->timezone('Europe/London')
    ->withoutOverlapping()
    ->onOneServer();
Schedule::command('calls:send-reminders')
    ->dailyAt('07:45')
    ->timezone('Europe/London')
    ->withoutOverlapping()
    ->onOneServer();
Schedule::command('tasks:send-due-reminders')
    ->dailyAt('08:00')
    ->timezone('Europe/London')
    ->withoutOverlapping()
    ->onOneServer();
