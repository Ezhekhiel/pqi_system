<?php

namespace App\Services;

use App\Models\whatsapp_groups;
use App\Models\database___location;
use App\Models\area___db;
use App\Models\WaMessageLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ManageReportService
{
    protected WhatsAppService $waService;

    public function __construct(WhatsAppService $waService)
    {
        $this->waService = $waService;
    }

    public function handleAndonReport(string $location = 'MAIN'): array
    {
        Log::info("[Andon Process START] Starting processing for Location: '{$location}'");

        $group = whatsapp_groups::where("name", $location)->first();
        $getLocation = database___location::where('location', $location)->first();

        if (!$group) {
            Log::error("[Andon Process FAIL] WhatsApp group not found for location: {$location}");
            return [
                'success' => false,
                'status_code' => 404,
                'error' => "Group WA untuk lokasi '{$location}' tidak ditemukan."
            ];
        }

        $target = $group->code_group;

        // QUERY FIX: Kunci MAX(id) murni per device_id
        $getLogs = DB::table('andon_logs as a')
                ->join(DB::raw('(
                    SELECT device_id, problem_type_id, detail_problem, MAX(id) AS max_id
                    FROM andon_logs
                    GROUP BY device_id, problem_type_id, detail_problem
                ) latest'), function ($join) {
                    $join->on('a.id', '=', 'latest.max_id');
                })
            ->leftJoin('area___db as area_db', 'a.device_id', '=', 'area_db.id_device')
            ->leftJoin('database___bagian as c', 'area_db.bagian', '=', 'c.id')
            ->leftJoin('database___location as d', DB::raw('LTRIM(RTRIM(area_db.location))'), '=', 'd.id')
            ->leftJoin('problem___db as e', 'a.problem_type_id', '=', 'e.id')
            ->leftJoin('problem_detail___db as f', 'a.detail_problem', '=', 'f.id')
            ->leftJoin('database___area as g', 'area_db.area', '=', 'g.id')
            ->orderBy('a.triggered_at')
            ->select(
                'a.device_id',
                'a.id as andon_log_id',
                'a.problem_type_id',
                'a.detail_problem',
                'a.status',
                DB::raw("
                    CASE
                        WHEN a.status = 0 THEN 'off'
                        WHEN a.status = 1 THEN 'on'
                        WHEN a.status = 2 THEN 'standby'
                    END AS status_text
                "),
                'a.triggered_at',
                'a.created_at',
                'c.bagian',
                'd.location',
                'e.problem',
                'f.detail',
                'g.area'
            );

        if ($getLocation) {
            $getid_device = area___db::select('id_device')->where('location', $getLocation->id)->get();
            $arr_id_device = $getid_device->pluck('id_device')->toArray();
            $getLogs = $getLogs->whereIn('a.device_id', $arr_id_device);
        } else {
            if (in_array($location, ['MAIN', 'MESIN', 'QUALITY', 'MATERIAL'])) {
                $getLogs = $getLogs->where('a.created_at', '<=', now()->subMinutes(5));
            }

            if ($location == 'QUALITY') {
                $getLogs = $getLogs->where('a.problem_type_id', '1');
            } elseif ($location == 'MESIN') {
                $getLogs = $getLogs->where('a.problem_type_id', '2');
            } elseif ($location == 'MATERIAL') {
                $getLogs = $getLogs->where('a.problem_type_id', '3');
            }
        }

        $logs = $getLogs->get();
        $processedCount = 0;
        $failedCount = 0;

        foreach ($logs as $log) {
            $statusResult = false;

            if ($log->status == 1) {
                // STATUS 1: Menunggu Penanganan (❌)
                $statusResult = $this->processStatusWaiting($target, $log);
            } elseif ($log->status == 2) {
                // STATUS 2: Dalam Proses (⚠️)
                $statusResult = $this->processStatusInProcess($target, $log);
            } elseif ($log->status == 0) {
                // STATUS 0: Selesai (✅)
                $statusResult = $this->processStatusFinished($log);
            }

            if ($statusResult) {
                $processedCount++;
            } else {
                $failedCount++;
            }
        }

        Log::info("[Andon Process FINISHED] Location '{$location}' | Processed: {$processedCount} | Failed/Skipped: {$failedCount}");

        return [
            'success'   => $failedCount === 0,
            'processed' => $processedCount,
            'failed'    => $failedCount,
        ];
    }

    /**
     * STATUS 1: Kirim Pesan Alert Pertama (Siklus Baru)
     */
    private function processStatusWaiting(string $target, object $log): bool
    {
        $topic = "Andon Device {$log->device_id} P{$log->problem_type_id} D{$log->detail_problem}";

        // Cari pesan WA aktif khusus untuk kombinasi device + problem ini
        $existingLog = WaMessageLog::where('topic', $topic)
            ->whereIn('status', ['sent_status_1', 'sent_status_2'])
            ->first();

        if ($existingLog) {
            Log::info("[Andon Status 1 SKIP] Alert message already active for Topic: {$topic}");
            return true;
        }

        $message = $this->buildMessageText($log, 'Menunggu Penanganan', '❌');
        $res     = $this->waService->sendText($target, $message);

        if ($res['success'] && !empty($res['message_id'])) {
            WaMessageLog::create([
                'andon_log_id' => $log->andon_log_id,
                'target'       => $target,
                'message_id'   => $res['message_id'],
                'topic'        => $topic, // Topic unik per problem
                'payload'      => json_encode(['text' => $message]),
                'status'       => 'sent_status_1',
            ]);

            Log::info("[Andon Status 1 SUCCESS] Alert created for Log ID: {$log->andon_log_id} | Topic: {$topic}");
            return true;
        }

        return false;
    }

    /**
     * STATUS 2: Hapus Pesan Pertama -> Kirim Pesan Kedua
     */
    private function processStatusInProcess(string $target, object $log): bool
    {
        $topic = "Andon Device {$log->device_id} P{$log->problem_type_id} D{$log->detail_problem}";

        // Cari pesan WA aktif khusus untuk kombinasi device + problem ini
        $existingLog = WaMessageLog::where('topic', $topic)
            ->whereIn('status', ['sent_status_1', 'sent_status_2'])
            ->first();

        if (!$existingLog) {
            Log::error("[ANDON PROCESS FAIL] Status 2 cannot find active WA message for Topic: {$topic}");
            return false;
        }

        if ($existingLog->status === 'sent_status_2') {
            return true;
        }

        if ($existingLog->message_id) {
            $this->waService->deleteText($existingLog->message_id);
        }

        $message = $this->buildMessageText($log, 'Dalam Proses', '⚠️');
        $res     = $this->waService->sendText($target, $message);

        if ($res['success'] && !empty($res['message_id'])) {
            $existingLog->update([
                'andon_log_id' => $log->andon_log_id,
                'message_id'   => $res['message_id'],
                'payload'      => json_encode(['text' => $message]),
                'status'       => 'sent_status_2',
            ]);

            Log::info("[Andon Status 2 SUCCESS] Updated to Status 2 for Topic: {$topic}");
            return true;
        }

        return false;
    }

    /**
     * STATUS 0: Hapus Pesan -> Finish
     */
    private function processStatusFinished(object $log): bool
    {
        $topic = "Andon Device {$log->device_id} P{$log->problem_type_id} D{$log->detail_problem}";

        // Cari pesan WA aktif khusus untuk kombinasi device + problem ini
        $existingLog = WaMessageLog::where('topic', $topic)
            ->whereIn('status', ['sent_status_1', 'sent_status_2'])
            ->first();

        if (!$existingLog) {
            Log::info("[Andon Status 0 SKIP] No active WA message found for Topic: {$topic}");
            return true;
        }

        if ($existingLog->message_id) {
            $delRes = $this->waService->deleteText($existingLog->message_id);
            if (!$delRes['success']) {
                return false;
            }
        }

        $existingLog->update([
            'andon_log_id' => $log->andon_log_id,
            'status'       => 'deleted_finished',
        ]);

        Log::info("[Andon Status 0 SUCCESS] Message deleted and cycle finished for Topic: {$topic}");
        return true;
    }

    private function buildMessageText(object $log, string $statusText, string $icon): string
    {
        $area    = $log->area ?? '-';
        $bagian  = $log->bagian ?? '-';
        $lokasi  = $log->location ?? '-';
        $problem = $log->problem ?? '-';
        $detail  = $log->detail ? "📝 *DETAIL:* {$log->detail}\n" : "";
        $waktu   = date('H:i');

        return "🚨 *ANDON ALERT*\n" .
               "━━━━━━━━━━━━━━━━━━\n" .
               "📍 *LOKASI:* {$area} ({$bagian}) {$lokasi}\n" .
               "🔧 *JENIS:* {$problem}\n" .
               $detail .
               "🕒 *WAKTU:* {$waktu} WIB\n" .
               "━━━━━━━━━━━━━━━━━━\n" .
               "{$icon} *Status:* {$statusText}";
    }
}
