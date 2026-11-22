<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('subscriptions:apply-scheduled-changes')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('support:close-resolved')
    ->hourly()
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('subscriptions:expire-lapsed')
    ->hourly()
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('signatures:replay-webhooks')
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->onOneServer();
