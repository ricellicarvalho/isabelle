<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('doctor', function () {
    $this->call(\App\Console\Commands\DoctorCommand::class);
});

// RN08 — Alertas de vencimento de contratos (30/15/7 dias)
Schedule::command('alerts:expiring')
    ->dailyAt('08:00')
    ->timezone('America/Araguaina')
    ->withoutOverlapping();
