<?php
namespace App\Services;

use App\Services\Reports\HumidityReportService;
use App\Services\Reports\TemperatureReportService;

class ReportFactory
{
    public function make(string $project)
    {
        return match ($project) {

            'Humidity' => app(HumidityReportService::class),

            'Temperature' => app(TemperatureReportService::class),

            default => throw new \Exception("Project {$project} tidak ditemukan"),

        };
    }
}
