<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Auto-carry de equipos: genera equipo_dia para el día siguiente cada medianoche
Schedule::command('equipo:auto-carry')->dailyAt('00:00');
