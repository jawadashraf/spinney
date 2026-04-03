<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

Schedule::command('appointments:send-reminders')
    ->dailyAt('09:00')
    ->withoutOverlapping()
    ->onOneServer();
