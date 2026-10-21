<?php

namespace App\Services\Reports;

use App\Services\WhatsappService;
use Carbon\Carbon;
use DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Spatie\Browsershot\Browsershot;
use App\Models\andon_downtimes;
use App\Models\whatsapp_groups;

class SummaryAndon
{
    protected $whatsapp;

    public function __construct(WhatsappService $whatsapp)
    {
        $this->whatsapp = $whatsapp;
    }
    public function send($location, $id_location)
    {
        $data = $this->getData($location, $id_location);
        $getCodeGroup = whatsapp_groups::where('name',$location)->first();
        $date = date('Y-m-d');
        $caption = implode("\n", [
            "Andon Report {$location}",
            $date
        ]);

        $response = null;

        try {
            // Kirim ke WhatsApp
            $response = $this->whatsapp->sendMedia(
                $getCodeGroup->code_group,
                $data['path'],
                $caption
            );
        } finally {
            // Block 'finally' PASTI dijalankan, baik sukses maupun terjadi Exception/Error
            if (File::exists($data['path'])) {
                File::delete($data['path']);
            }
        }

        return [
            'success' => true,
            'image'   => $data['path'],
            'response'   => $data['response'],
            'wa'      => $response,
        ];
    }
    function getDate() {
        // Tentukan rentang waktu berdasarkan jam eksekusi
        $currentHour = now()->hour;

        if ($currentHour < 12) {
            // Eksekusi Pagi (05:00): Dari Kemarin 17:00 s/d Hari Ini 05:00
            $startTime = now()->subDay()->setTime(17, 0, 0);
            $endTime   = now()->setTime(5, 0, 0);
        } else {
            // Eksekusi Sore (17:00): Dari Hari Ini 05:00 s/d Hari Ini 17:00
            $startTime = now()->setTime(5, 0, 0);
            $endTime   = now()->setTime(17, 0, 0);
        }
        $date = [$startTime, $endTime];
        return $date;
    }
     public function getData($location, $id_location) {
        $getDate = $this->getDate();
        $query = andon_downtimes::with(['problem', 'detail', 'device.bagian_data', 'device.location_data', 'device.area_data'])
             ->whereBetween('created_at', $getDate)
            // ->whereBetween('created_at', ['2026-10-14 00:00:00','2026-10-14 19:00:00'])
            ->whereHas('device.location_data', function ($q) use ($id_location) {
                $q->where('database___location.id', $id_location);
            });

        // Optional sorting
        $query->orderByDesc('start_at');

        $data = $query->get();
        $response = [
            'success' => true,
            'total'   => $data->count(),
            'data'    => $data,
        ];
        // return view('andon.report_summary', compact('response'));
        $html = view('andon.report_summary', compact('response'))->render();
        $folder = public_path('report');
        File::ensureDirectoryExists($folder);
        $filename = sprintf(
            'Report_Andon_%s_%s.png',
            $location,
            now()->format('YmdHis')
        );
        $path = $folder.'/'.$filename;
        // 3. Eksekusi Browsershot
        Browsershot::html($html)
                ->setOption('args', ['--no-sandbox', '--disable-setuid-sandbox'])
                ->waitUntilNetworkIdle()
                ->windowSize(1920, 1080)
                ->fullPage()
                ->save($path);
        return [
            'path'     => $path,
            'response' => $response
        ];
    }
}
