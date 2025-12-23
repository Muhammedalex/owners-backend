<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule invoice generation daily at 2:00 AM (Riyadh timezone)
Schedule::command('invoices:generate')
    ->dailyAt('02:00')
    ->timezone('Asia/Riyadh')
    ->withoutOverlapping()
    ->runInBackground();

// Schedule overdue invoices check daily at 3:00 AM (Riyadh timezone)
Schedule::command('invoices:check-overdue')
    ->dailyAt('03:00')
    ->timezone('Asia/Riyadh')
    ->withoutOverlapping()
    ->runInBackground();
