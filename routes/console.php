<?php

declare(strict_types=1);

use App\Models\PbxCallLog;
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

// Calls from unknown numbers that nobody linked are deleted after YEASTAR_UNMATCHED_RETENTION_DAYS.
Schedule::command('model:prune', ['--model' => [PbxCallLog::class]])
    ->dailyAt('03:00')
    ->timezone('Europe/London')
    ->withoutOverlapping()
    ->onOneServer();
