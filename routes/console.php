<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Pengingat dokumen akan kadaluarsa — butuh cron `* * * * * php artisan schedule:run` di server.
Schedule::command('dms:notify-expiring')->dailyAt('07:00');
