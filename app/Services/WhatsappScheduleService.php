<?php
namespace App\Services;

class WhatsappScheduleService
{
    public function __construct(
        private ReportFactory $factory
    ) {
    }

    public function process($schedule)
    {
        $report = $this->factory->make($schedule->project);

        return $report->send($schedule);
    }
}
