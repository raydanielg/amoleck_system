<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// SMS pipeline: send due scheduled messages every minute, reconcile delivery
// status from provider logs every 5 minutes, refresh credit balance hourly.
Schedule::command('messages:process')->everyMinute()->withoutOverlapping();
Schedule::command('messages:process --reconcile')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('messages:process --balance')->hourly()->withoutOverlapping();

// Drain any queued jobs — needed on shared hosting with no queue daemon
Schedule::command('queue:work --stop-when-empty --max-time=55')->everyMinute()->withoutOverlapping();

// Lead follow-up reminders to sales managers every morning (EAT)
Schedule::command('leads:remind')->dailyAt('07:00')->timezone('Africa/Dar_es_Salaam');
