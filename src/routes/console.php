<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// RF-13 / RF-18: archiva reportes resueltos (>2h) y reportes pendientes/verificados sin interacción (>24h)
Schedule::command('reports:archive-stale')->everyFiveMinutes();
