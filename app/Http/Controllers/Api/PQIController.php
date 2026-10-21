<?php

namespace App\Http\Controllers\Api;

use App\Exports\PQIExport;
use App\Http\Controllers\Controller;
use App\Models\area___db_pqi;
use App\Models\stop_line___log;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Facades\Excel;

class PQIController extends Controller
{
    public function updateStatus($id_device)
    {
        // Menggunakan DB Direct Query tanpa membuat objek Model Eloquent yang berat di RAM
        DB::table('stop_line___logs') // sesuaikan nama tabel
            ->where('id_device', $id_device)
            ->whereBetween('created_at', [now()->startOfHour(), now()->endOfHour()])
            ->where('status', 'NOTHING')
            ->update(['status' => 'DONE']);

        return response()->json([
            'status'  => 'success',
            'message' => 'Status log berhasil diubah menjadi DONE'
        ]);
    }
    public function export_data(Request $request)
    {
        // 1. Validasi Input Form (Menggunakan Date Range)
        $request->validate([
            'start_date'       => 'required|date',
            'end_date'         => 'required|date|after_or_equal:start_date',
            'data_type'        => 'required|in:summary,detail,maximum',
            'summary_interval' => 'required_if:data_type,summary|nullable|in:minute,hour',
        ]);

        $startDate       = $request->input('start_date');
        $endDate         = $request->input('end_date');
        $dataType        = $request->input('data_type');
        $summaryInterval = $request->input('summary_interval');

        // 2. Format Tanggal ke YYYYMMDD untuk SQL Server (PROD_DATE)
        $formattedStartDate = date('Ymd', strtotime($startDate));
        $formattedEndDate   = date('Ymd', strtotime($endDate));

        $dateCondition = "AND A.PROD_DATE BETWEEN ? AND ?";
        $queryParams   = [$formattedStartDate, $formattedEndDate];

        // 3. Konfigurasi Dinamis Berdasarkan Data Type
        $selectColumns = "";
        $groupByClause = "";
        $orderByClause = "";
        $topLimit      = ($dataType === 'detail' || $dataType === 'maximum') ? "TOP 1000" : "";

        if ($dataType === 'summary') {
            $intervalFormat = $summaryInterval === 'minute'
                ? "FORMAT(CREATE_DATE, 'yyyy-MM-dd HH:mm')"
                : "FORMAT(CREATE_DATE, 'yyyy-MM-dd HH:00')";

            $selectColumns = "
                GOOD_TYPE,
                PROD_DATE,
                LINE_GRP,
                LINE_NM,
                LINE_CAPA_HOUR,
                ARTICLEDESC,
                ARTNO,
                O_NO,
                DFT_CATG,
                DFT_NM,
                TIME_CD,
                {$intervalFormat} AS time_interval,
                SUM(QTY) AS total_qty,
                COUNT(*) AS total_frequency
            ";

            $groupByClause = "
                GROUP BY
                    GOOD_TYPE, PROD_DATE, LINE_GRP, LINE_NM, LINE_CAPA_HOUR,
                    ARTICLEDESC, ARTNO, O_NO, DFT_CATG, DFT_NM, TIME_CD,
                    {$intervalFormat}
            ";

            $orderByClause = "ORDER BY time_interval ASC";

        } else {
            $selectColumns = "
                GOOD_TYPE, PROD_DATE, LINE_GRP, LINE_NM, LINE_CAPA_HOUR,
                ARTICLEDESC, ARTNO, O_NO, DFT_CATG, DFT_NM, TIME_CD,
                QTY, CREATE_DATE
            ";

            $orderByClause = ($dataType === 'maximum') ? "ORDER BY QTY DESC" : "ORDER BY CREATE_DATE ASC";
        }

        // 4. Eksekusi Query SQL Server (Clean & Single Source)
        $sql = "
            WITH DEFECT_DATA AS (
                SELECT
                    A.GOOD_TYPE,
                    A.PROD_DATE,
                    D.LINE_GRP5 AS LINE_GRP,
                    D.LINE_NM,
                    D.CAPA_HOUR AS LINE_CAPA_HOUR,
                    C.ARTICLEDESC,
                    B.ARTNO,
                    B.O_NO + ' - ' + CONVERT(NVARCHAR(1), B.O_SEQ) AS O_NO,
                    F.COM_DESC AS DFT_CATG,
                    E.DFT_NM,
                    CASE
                        WHEN A.TIME_CD = 'A012' THEN 'A011'
                        ELSE A.TIME_CD
                    END AS TIME_CD,
                    A.QTY,
                    A.CREATE_DATE
                FROM dbo.VIEW_MrpQipDftDefectPop AS A WITH (NOLOCK)
                INNER JOIN SAL_ORDER_MST AS B WITH (NOLOCK)
                    ON A.PWI_NO = B.PWI_NO
                INNER JOIN MST_ARTICLE AS C WITH (NOLOCK)
                    ON B.ARTNO = C.ARTNO
                INNER JOIN MST_LINE AS D WITH (NOLOCK)
                    ON A.LINE_CD = D.LINE_CD
                    --AND D.LINE_GRP1 = '02'
                    --AND D.LINE_GRP2 = '0002'
                LEFT OUTER JOIN MST_QIP_DEFECT AS E WITH (NOLOCK)
                    ON A.DFT_CATG = E.DFT_CATG
                    AND A.DFT_CD = E.DFT_CD
                    AND B.BUYER = E.BUYER
                    AND A.FAC_CD = E.FAC_CD
                LEFT OUTER JOIN MST_COMM_DET AS F WITH (NOLOCK)
                    ON F.COM_CD = 'DFT_CATG'
                    AND A.DFT_CATG = F.COM_SEQ
                WHERE A.FAC_CD = '51'
                {$dateCondition}
                AND E.DFT_NM IS NOT NULL
            )
            SELECT {$topLimit} {$selectColumns}
            FROM DEFECT_DATA
            {$groupByClause}
            {$orderByClause}
        ";

        $data = DB::connection('sqlsrv_erp')->select($sql, $queryParams);

        // 5. Generate Nama File & Download Excel
        $fileName = 'PQI_Defect_Report_' . $dataType . '_' . ($summaryInterval ?? 'raw') . '_' . $startDate . '_to_' . $endDate . '.xlsx';
        return Excel::download(new PQIExport($data, $dataType), $fileName);
    }

    public function getDataPQI($id_device)
    {
        $key = "key-" . $id_device;
        $get_cell = area___db_pqi::with('area_data:id,area')->where('id_device', $id_device)->first(['id', 'id_device', 'id_area']);

        if (!$get_cell) {
            return [
                'alert'  => 'false',
                'status' => 'Data cell tidak ada! cek id device sudah di daftarin belum!'
            ];
        }

        $cell = $get_cell->area_data->area;
        // query database
        $data = DB::connection('sqlsrv_erp')->select("
                WITH DEFECT_DATA AS
                (
                    SELECT
                        A.GOOD_TYPE,
                        A.PROD_DATE,
                        D.LINE_GRP5 AS LINE_GRP,
                        D.LINE_NM,
                        D.CAPA_HOUR AS LINE_CAPA_HOUR,
                        C.ARTICLEDESC,
                        B.ARTNO,
                        B.O_NO + ' - ' + CONVERT(NVARCHAR(1), B.O_SEQ) AS O_NO,
                        F.COM_DESC AS DFT_CATG,
                        E.DFT_NM,
                        CASE
                            WHEN A.TIME_CD = 'A012' THEN 'A011'
                            ELSE A.TIME_CD
                        END AS TIME_CD,
                        A.QTY,
                        A.CREATE_DATE
                    FROM dbo.VIEW_MrpQipDftDefectPop AS A WITH (NOLOCK)
                    INNER JOIN SAL_ORDER_MST AS B WITH (NOLOCK)
                        ON A.PWI_NO = B.PWI_NO
                    INNER JOIN MST_ARTICLE AS C WITH (NOLOCK)
                        ON B.ARTNO = C.ARTNO
                    INNER JOIN MST_LINE AS D WITH (NOLOCK)
                        ON A.LINE_CD = D.LINE_CD
                        --AND D.LINE_GRP1 = '02'
                        --AND D.LINE_GRP2 = '0002'
                    LEFT OUTER JOIN MST_QIP_DEFECT AS E WITH (NOLOCK)
                        ON A.DFT_CATG = E.DFT_CATG
                        AND A.DFT_CD = E.DFT_CD
                        AND B.BUYER = E.BUYER
                        AND A.FAC_CD = E.FAC_CD
                    LEFT OUTER JOIN MST_COMM_DET AS F WITH (NOLOCK)
                        ON F.COM_CD = 'DFT_CATG'
                        AND A.DFT_CATG = F.COM_SEQ
                    WHERE A.FAC_CD = '51'
                    AND LINE_GRP5 = 'F-C'
                    AND LINE_NM = 'C-03'
                    --AND D.LINE_NM = ?

                    -- Filter tanggal hari ini (Format YYYYMMDD)
                    --AND A.PROD_DATE = CONVERT(VARCHAR(8), DATEADD(DAY, -1, GETDATE()), 112)
                    AND A.PROD_DATE = CONVERT(VARCHAR(8), GETDATE(), 112)

                    AND A.CREATE_DATE >= DATEADD(HOUR, DATEDIFF(HOUR, 0, GETDATE()), 0)
                    AND A.CREATE_DATE <= GETDATE()

                    AND E.DFT_NM IS NOT NULL
                )
                SELECT
                    GOOD_TYPE,
                    PROD_DATE,
                    LINE_GRP,
                    LINE_NM,
                    LINE_CAPA_HOUR,
                    ARTICLEDESC,
                    ARTNO,
                    O_NO,
                    DFT_CATG,
                    DFT_NM,
                    TIME_CD,
                    QTY,
                    CREATE_DATE
                FROM DEFECT_DATA
                ORDER BY
                    CREATE_DATE ASC;
            ", [$cell]);
            // --- JSON DATA / DB QUERY ---
        // $jsonString = '[
        //     {"GOOD_TYPE":"R","PROD_DATE":"20260904","LINE_GRP":"B1","LINE_NM":"B1-01","ARTICLEDESC":"M603V1_LI","ARTNO":"M6032OD-D","O_NO":"7880498 - 1","DFT_CATG":"General","DFT_NM":"Cleanliness","TIME_CD":"A007","QTY":"1.00","CREATE_DATE":"2026-09-04 07:00:00.000"},
        //     {"GOOD_TYPE":"R","PROD_DATE":"20260904","LINE_GRP":"B1","LINE_NM":"B1-01","ARTICLEDESC":"M603V1_LI","ARTNO":"M6032OD-D","O_NO":"7880498 - 1","DFT_CATG":"General","DFT_NM":"Cleanliness","TIME_CD":"A007","QTY":"1.00","CREATE_DATE":"2026-09-04 07:01:00.000"},
        //     {"GOOD_TYPE":"R","PROD_DATE":"20260904","LINE_GRP":"B1","LINE_NM":"B1-01","ARTICLEDESC":"M603V1_LI","ARTNO":"M6032OD-D","O_NO":"7880498 - 1","DFT_CATG":"General","DFT_NM":"Cleanliness","TIME_CD":"A007","QTY":"1.00","CREATE_DATE":"2026-09-04 07:02:00.000"},
        //     {"GOOD_TYPE":"R","PROD_DATE":"20260904","LINE_GRP":"B1","LINE_NM":"B1-01","ARTICLEDESC":"M603V1_LI","ARTNO":"M6032OD-D","O_NO":"7880498 - 1","DFT_CATG":"General","DFT_NM":"Cleanliness","TIME_CD":"A007","QTY":"1.00","CREATE_DATE":"2026-09-04 07:03:00.000"},
        //     {"GOOD_TYPE":"R","PROD_DATE":"20260904","LINE_GRP":"B1","LINE_NM":"B1-01","ARTICLEDESC":"M603V1_LI","ARTNO":"M6032OD-D","O_NO":"7880498 - 1","DFT_CATG":"General","DFT_NM":"Cleanliness","TIME_CD":"A007","QTY":"1.00","CREATE_DATE":"2026-09-04 07:04:00.000"},
        //     {"GOOD_TYPE":"R","PROD_DATE":"20260904","LINE_GRP":"B1","LINE_NM":"B1-01","ARTICLEDESC":"M603V1_LI","ARTNO":"M6032OD-D","O_NO":"7880498 - 1","DFT_CATG":"General","DFT_NM":"Cleanliness","TIME_CD":"A007","QTY":"1.00","CREATE_DATE":"2026-09-04 07:05:00.000"},
        //     {"GOOD_TYPE":"R","PROD_DATE":"20260904","LINE_GRP":"B1","LINE_NM":"B1-01","ARTICLEDESC":"M603V1_LI","ARTNO":"M6032OD-D","O_NO":"7880498 - 1","DFT_CATG":"General","DFT_NM":"Cleanliness","TIME_CD":"A007","QTY":"1.00","CREATE_DATE":"2026-09-04 07:06:00.000"},
        //     {"GOOD_TYPE":"R","PROD_DATE":"20260904","LINE_GRP":"B1","LINE_NM":"B1-01","ARTICLEDESC":"M603V1_LI","ARTNO":"M6032OD-D","O_NO":"7880498 - 1","DFT_CATG":"General","DFT_NM":"Cleanliness","TIME_CD":"A007","QTY":"1.00","CREATE_DATE":"2026-09-04 07:07:00.000"},
        //     {"GOOD_TYPE":"R","PROD_DATE":"20260904","LINE_GRP":"B1","LINE_NM":"B1-01","ARTICLEDESC":"M603V1_LI","ARTNO":"M6032OD-D","O_NO":"7880498 - 1","DFT_CATG":"General","DFT_NM":"Cleanliness","TIME_CD":"A007","QTY":"1.00","CREATE_DATE":"2026-09-04 07:08:00.000"},
        //     {"GOOD_TYPE":"R","PROD_DATE":"20260904","LINE_GRP":"B1","LINE_NM":"B1-01","ARTICLEDESC":"M603V1_LI","ARTNO":"M6032OD-D","O_NO":"7880498 - 1","DFT_CATG":"Sewing","DFT_NM":"Stitching","TIME_CD":"A007","QTY":"1.00","CREATE_DATE":"2026-09-04 07:09:00.000"},
        //     {"GOOD_TYPE":"R","PROD_DATE":"20260904","LINE_GRP":"B1","LINE_NM":"B1-01","ARTICLEDESC":"M603V1_LI","ARTNO":"M6032OD-D","O_NO":"7880498 - 1","DFT_CATG":"Sewing","DFT_NM":"Stitching","TIME_CD":"A007","QTY":"1.00","CREATE_DATE":"2026-09-04 07:10:00.000"},
        //     {"GOOD_TYPE":"R","PROD_DATE":"20260904","LINE_GRP":"B1","LINE_NM":"B1-01","ARTICLEDESC":"M603V1_LI","ARTNO":"M6032OD-D","O_NO":"7880498 - 1","DFT_CATG":"Sewing","DFT_NM":"Stitching","TIME_CD":"A007","QTY":"1.00","CREATE_DATE":"2026-09-04 07:11:00.000"},
        //     {"GOOD_TYPE":"R","PROD_DATE":"20260904","LINE_GRP":"B1","LINE_NM":"B1-01","ARTICLEDESC":"M603V1_LI","ARTNO":"M6032OD-D","O_NO":"7880498 - 1","DFT_CATG":"Sewing","DFT_NM":"Stitching","TIME_CD":"A007","QTY":"1.00","CREATE_DATE":"2026-09-04 07:12:00.000"},
        //     {"GOOD_TYPE":"R","PROD_DATE":"20260904","LINE_GRP":"B1","LINE_NM":"B1-01","ARTICLEDESC":"M603V1_LI","ARTNO":"M6032OD-D","O_NO":"7880498 - 1","DFT_CATG":"Sewing","DFT_NM":"Stitching","TIME_CD":"A007","QTY":"1.00","CREATE_DATE":"2026-09-04 07:13:00.000"},
        //     {"GOOD_TYPE":"R","PROD_DATE":"20260904","LINE_GRP":"B1","LINE_NM":"B1-01","ARTICLEDESC":"M603V1_LI","ARTNO":"M6032OD-D","O_NO":"7880498 - 1","DFT_CATG":"General","DFT_NM":"Cleanliness","TIME_CD":"A007","QTY":"1.00","CREATE_DATE":"2026-09-04 07:14:00.000"},
        //     {"GOOD_TYPE":"R","PROD_DATE":"20260904","LINE_GRP":"B1","LINE_NM":"B1-01","ARTICLEDESC":"M603V1_LI","ARTNO":"M6032OD-D","O_NO":"7880498 - 1","DFT_CATG":"General","DFT_NM":"Cleanliness","TIME_CD":"A007","QTY":"1.00","CREATE_DATE":"2026-09-04 07:15:00.000"},
        //     {"GOOD_TYPE":"R","PROD_DATE":"20260904","LINE_GRP":"B1","LINE_NM":"B1-01","ARTICLEDESC":"M603V1_LI","ARTNO":"M6032OD-D","O_NO":"7880498 - 1","DFT_CATG":"General","DFT_NM":"Cleanliness","TIME_CD":"A007","QTY":"1.00","CREATE_DATE":"2026-09-04 07:16:00.000"}
        // ]';

        // $data = json_decode($jsonString);
        if (!$data) {
            return [
                'alert'  => 'false',
                'status' => 'normal',
                'data'   => []
            ];
        }

        // --- HITUNG DEFECT (Single Pass Optimization) ---
        $totalCounts = [];
        $consecutiveCounts = [];
        $currentConsecutive = 0;
        $lastDefect = null;
        $line_capa = 0;

        foreach ($data as $item) {
            $defect = $item->DFT_NM;
            $line_capa = $item->LINE_CAPA_HOUR;

            // Total count
            $totalCounts[$defect] = ($totalCounts[$defect] ?? 0) + 1;

            // Consecutive count
            if ($defect === $lastDefect) {
                $currentConsecutive++;
            } else {
                $currentConsecutive = 1;
                $lastDefect = $defect;
            }

            if ($currentConsecutive > ($consecutiveCounts[$defect] ?? 0)) {
                $consecutiveCounts[$defect] = $currentConsecutive;
            }
        }
        if ($line_capa > 120) {
            $target = 12;
        }else{
            $target = round($line_capa*0.1);
        }

        // Filter Alert
        $alert12Defects = [];
        foreach ($totalCounts as $dft => $count) {
            if ($count >= $target) $alert12Defects[] = $dft;
        }

        $alert5ConsecutiveDefects = [];
        foreach ($consecutiveCounts as $dft => $maxConsec) {
            if ($maxConsec >= 5) $alert5ConsecutiveDefects[] = $dft;
        }

        $data_alert = [];
        if (!empty($alert12Defects)) $data_alert['alert_12_Defects'] = $alert12Defects;
        if (!empty($alert5ConsecutiveDefects)) $data_alert['alert_5_berturut_turut'] = $alert5ConsecutiveDefects;

        // Jika tidak ada defect yang memenuhi syarat alert, langsung return (save DB call)
        if (empty($data_alert)) {
            return [
                'alert'  => 'false',
                'status' => 'normal',
                'data'   => []
            ];
        }

        // --- LOG DATABASE & ALARM CHECK (Hemat Memori) ---
        $start = now()->startOfHour();
        $end   = now()->endOfHour();

        // Ambil log yang sudah ada di jam ini (hanya butuh array simpel dari DB)
        $existingLogs = DB::table('stop_line___logs')
            ->where('id_device', $id_device)
            ->whereBetween('created_at', [$start, $end])
            ->get(['DFT_NM', 'alert_option', 'status']);

        // Batch insert data baru jika belum ada di DB
        $newLogs = [];
        $nowFormatted = now()->toDateTimeString();

        if (!empty($alert12Defects)) {
            foreach ($alert12Defects as $a) {
                $exists = $existingLogs->contains(fn($i) => $i->DFT_NM === $a && $i->alert_option === 'alert_12_Defects');
                if (!$exists) {
                    $newLogs[] = [
                        'id_device'    => $id_device,
                        'DFT_NM'       => $a,
                        'alert_option' => 'alert_12_Defects',
                        'status'       => 'NOTHING',
                        'created_at'   => $nowFormatted,
                        'updated_at'   => $nowFormatted,
                    ];
                }
            }
        }

        if (!empty($alert5ConsecutiveDefects)) {
            foreach ($alert5ConsecutiveDefects as $a) {
                $exists = $existingLogs->contains(fn($i) => $i->DFT_NM === $a && $i->alert_option === 'alert_5_berturut_turut');
                if (!$exists) {
                    $newLogs[] = [
                        'id_device'    => $id_device,
                        'DFT_NM'       => $a,
                        'alert_option' => 'alert_5_berturut_turut',
                        'status'       => 'NOTHING',
                        'created_at'   => $nowFormatted,
                        'updated_at'   => $nowFormatted,
                    ];
                }
            }
        }

        // Insert sekaligus (Bulk Insert) jika ada log baru
        if (!empty($newLogs)) {
            DB::table('stop_line___logs')->insert($newLogs);
        }

        // Pengecekan status alarm aktif tanpa re-fetch heavy models
        $hasActiveAlarm = DB::table('stop_line___logs')
            ->where('id_device', $id_device)
            ->whereBetween('created_at', [$start, $end])
            ->where('status', 'NOTHING')
            ->exists();

        if ($hasActiveAlarm) {
            return [
                'alert'  => 'true',
                'status' => 'error',
                'data'   => $data_alert
            ];
        }

        return [
            'alert'  => 'false',
            'status' => 'normal',
            'data'   => []
        ];
    }

    public function data($date) {
        $data = DB::connection('sqlsrv_erp')->select("
                WITH DEFECT_DATA AS
                (
                    SELECT
                        A.GOOD_TYPE,
                        A.PROD_DATE,
                        D.LINE_GRP5 AS LINE_GRP,
                        D.LINE_NM,
                        D.CAPA_HOUR AS LINE_CAPA_HOUR,
                        C.ARTICLEDESC,
                        B.ARTNO,
                        B.O_NO + ' - ' + CONVERT(NVARCHAR(1), B.O_SEQ) AS O_NO,
                        F.COM_DESC AS DFT_CATG,
                        E.DFT_NM,
                        CASE
                            WHEN A.TIME_CD = 'A012' THEN 'A011'
                            ELSE A.TIME_CD
                        END AS TIME_CD,
                        A.QTY,
                        A.CREATE_DATE
                    FROM dbo.VIEW_MrpQipDftDefectPop AS A WITH (NOLOCK)
                    INNER JOIN SAL_ORDER_MST AS B WITH (NOLOCK)
                        ON A.PWI_NO = B.PWI_NO
                    INNER JOIN MST_ARTICLE AS C WITH (NOLOCK)
                        ON B.ARTNO = C.ARTNO
                    INNER JOIN MST_LINE AS D WITH (NOLOCK)
                        ON A.LINE_CD = D.LINE_CD
                        --AND D.LINE_GRP1 = '02'
                        --AND D.LINE_GRP2 = '0002'
                    LEFT OUTER JOIN MST_QIP_DEFECT AS E WITH (NOLOCK)
                        ON A.DFT_CATG = E.DFT_CATG
                        AND A.DFT_CD = E.DFT_CD
                        AND B.BUYER = E.BUYER
                        AND A.FAC_CD = E.FAC_CD
                    LEFT OUTER JOIN MST_COMM_DET AS F WITH (NOLOCK)
                        ON F.COM_CD = 'DFT_CATG'
                        AND A.DFT_CATG = F.COM_SEQ
                    WHERE A.FAC_CD = '51'
                    --AND LINE_GRP5 = 'F-C'
                    --AND LINE_NM = 'C-03'
                    --AND D.LINE_NM = 'Line_GRP1'

                    -- Filter tanggal hari ini (Format YYYYMMDD)
                    --AND A.PROD_DATE = CONVERT(VARCHAR(8), DATEADD(DAY, -1, GETDATE()), 112)
                    AND A.PROD_DATE = ?

                    --AND A.CREATE_DATE >= DATEADD(HOUR, DATEDIFF(HOUR, 0, GETDATE()), 0)
                    --AND A.CREATE_DATE <= GETDATE()

                    AND E.DFT_NM IS NOT NULL
                )
                SELECT
                    GOOD_TYPE,
                    PROD_DATE,
                    LINE_GRP,
                    LINE_NM,
                    LINE_CAPA_HOUR,
                    ARTICLEDESC,
                    ARTNO,
                    O_NO,
                    DFT_CATG,
                    DFT_NM,
                    TIME_CD,
                    QTY,
                    CREATE_DATE
                FROM DEFECT_DATA
                ORDER BY
                    CREATE_DATE ASC;
            ", [$date]);
        return json_encode($data);
    }
}
