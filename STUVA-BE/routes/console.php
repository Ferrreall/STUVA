<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


// Jalan setiap Senin-Jumat jam 11:00 WIB
Schedule::command('attendance:generate-daily')
    ->weekdays()
    ->at('11:00');