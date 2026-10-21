<?php

namespace App\Jobs;

use App\Services\ManageReportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $location;

    public function __construct(string $location)
    {
        $this->location = $location;
    }

    public function handle(ManageReportService $waService): void
    {
        $waResult = $waService->handleAndonReport($this->location);

        if (!isset($waResult['success']) || !$waResult['success']) {
            $errorMsg   = $waResult['error'] ?? 'Unknown Error';
            $statusCode = $waResult['status_code'] ?? '500';

            Log::error("SendNotificationJob Failed [{$statusCode}]: {$errorMsg}");
            throw new \Exception("WA Report Failed [{$statusCode}]: {$errorMsg}");
        }

        Log::info("WA Notification Processed Successfully for location: {$this->location}", $waResult);
    }
}
