<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('torex:heartbeat-sweep')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();

Schedule::command('torex:weekly-digest')
    ->weeklyOn(1, '08:00')
    ->withoutOverlapping()
    ->runInBackground();

Schedule::command('backup:clean')
    ->daily()
    ->at('01:00')
    ->withoutOverlapping()
    ->runInBackground();

Schedule::command('backup:run --only-db')
    ->daily()
    ->at('01:30')
    ->withoutOverlapping()
    ->runInBackground();

Schedule::command('backup:run')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->runInBackground();

Schedule::command('backup:monitor')
    ->dailyAt('07:00')
    ->withoutOverlapping()
    ->runInBackground();

Schedule::command('activitylog:clean')
    ->daily()
    ->at('03:30')
    ->runInBackground();
