<?php

use Illuminate\Support\Facades\Schedule;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::command('run:post')->everyMinute()->when(function () {
    return now()->minute % 5 === 1; // ファイルの存在確実性のため、+1分遅延
});
