<?php

namespace App\Services\Reports;

use App\Services\WhatsappService;
use Carbon\Carbon;
use DB;
use Illuminate\Support\Facades\File;
use Spatie\Browsershot\Browsershot;

class HumidityReportService
{
    protected $whatsapp;

    public function __construct(WhatsappService $whatsapp)
    {
        $this->whatsapp = $whatsapp;
    }
    public function send($data)
    {
        $image = $this->reportHumidity($data->building_id, $data->area_id, $data->time_range);
        $date = date('Y-m-d '.$data->time_range);
        $caption = implode("\n", [
            "Humidity Report {$data->area} - {$data->building}",
            $date
        ]);

        $response = null;

        try {
            // Kirim ke WhatsApp
            $response = $this->whatsapp->sendMedia(
                $data->code_group,
                $image,
                $caption
            );
        } finally {
            // Block 'finally' PASTI dijalankan, baik sukses maupun terjadi Exception/Error
            if (File::exists($image)) {
                File::delete($image);
            }
        }

        return [
            'success' => true,
            'image'   => $image,
            'wa'      => $response,
        ];
    }
    public function reportHumidity($building_id, $area_id, $time)
    {
        [$startHour, $endHour] = array_map('intval', explode('-', $time));

        // Tentukan periode
        $currentEnd = today()->setTime($endHour, 0, 0);

        if ($endHour <= $startHour) {
            // Contoh: 18-00, 22-06
            $currentStart = today()->subDay()->setTime($startHour, 0, 0);
        } else {
            // Contoh: 06-18
            $currentStart = today()->setTime($startHour, 0, 0);
        }

        // Ambil device
        $get_devices = DB::connection('sqlsrv2')
            ->table('database___devices')
            ->where([
                'id_building' => $building_id,
                'area'        => $area_id
            ])
            ->get(['device_id', 'coordinates'])
            ->keyBy('device_id');

        $deviceIds = $get_devices->keys();

        // Ambil data sesuai periode
        $allData = DB::connection('sqlsrv2')
            ->table('dht22___data as a')
            ->join('database___devices as b', 'a.id_device', '=', 'b.device_id')
            ->leftJoin('database___location as c', 'b.location', '=', 'c.id')
            ->select(
                'a.id_device',
                'a.temperature',
                'a.humidity',
                'a.created_at',
                DB::raw("CONVERT(VARCHAR(8), a.created_at, 108) as created_time"),
                'c.location as location_name'
            )
            ->whereIn('a.id_device', $deviceIds)
            ->whereBetween('a.created_at', [$currentStart, $currentEnd])
            ->orderBy('location_name')
            ->orderBy('created_at')
            ->get();

        // Bentuk data chart
        $result_data = $allData
            ->groupBy(function ($item) {
                return "{$item->location_name}|{$item->id_device}";
            })
            ->map(function ($items) use ($get_devices) {

                return [
                    'coordinates' => $get_devices[$items->first()->id_device]->coordinates ?? null,

                    'temperature' => $items->pluck('temperature')
                        ->map(fn($v) => round((float)$v, 1))
                        ->values()
                        ->toArray(),

                    'humidity' => $items->pluck('humidity')
                        ->map(fn($v) => round((float)$v, 1))
                        ->values()
                        ->toArray(),

                    'created_at' => $items->pluck('created_time')->toArray(),
                ];
            })
            ->toArray();

        $data = [
            'result_data' => $result_data,
        ];
        // Render View ke String HTML (Tambahkan ->render())
        // return view('th.report', [
        //     'time'         => $time,
        //     'data'         => $data
        // ]);
        $html = view('th.report', [
            'time'         => $time,
            'data'         => $data
        ])->render();
        $folder = public_path('report');
        File::ensureDirectoryExists($folder);
        $filename = sprintf(
            'humidity_%s_%s_%s.png',
            $building_id,
            $area_id,
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
        return $path;
    }
}
