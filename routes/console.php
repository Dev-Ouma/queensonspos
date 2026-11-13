<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;

Schedule::call(fn () => app(\App\Services\InventoryService::class)->releaseExpired())
    ->everyFiveMinutes()
    ->name('release-expired-reservations');

Schedule::command('model:prune', ['--model' => [\App\Models\SearchLog::class]])
    ->daily()
    ->name('prune-search-logs');
