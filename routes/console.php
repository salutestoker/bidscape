<?php

use App\Services\LateEstimateProcedureService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('bidscape:late-estimates', function (LateEstimateProcedureService $lateEstimates): int {
    $sent = $lateEstimates->run();

    $this->info("Late estimate notifications queued: {$sent}");

    return 0;
})->purpose('Queue late estimate procedure notification emails.');

Schedule::command('bidscape:late-estimates')->daily();
