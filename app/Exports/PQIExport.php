<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

use Maatwebsite\Excel\Concerns\FromCollection;

class PQIExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $data;
    protected $dataType;

    public function __construct($data, $dataType)
    {
        $this->data = collect($data);
        $this->dataType = $dataType;
    }

    public function collection()
    {
        return $this->data;
    }

    // Header kolom dinamis menyesuaikan data type (Summary vs Detail/Maximum)
    public function headings(): array
    {
        if ($this->dataType === 'summary') {
            return [
                'Good Type', 'Prod Date', 'Line Group', 'Line Name', 'Capa Hour',
                'Article Desc', 'Article No', 'Order No', 'Defect Category', 
                'Defect Name', 'Time Code', 'Time Interval', 'Total Qty', 'Total Frequency'
            ];
        }

        return [
            'Good Type', 'Prod Date', 'Line Group', 'Line Name', 'Capa Hour',
            'Article Desc', 'Article No', 'Order No', 'Defect Category', 
            'Defect Name', 'Time Code', 'Qty', 'Create Date'
        ];
    }

    // Mapping baris data agar sesuai urutan header
    public function map($row): array
    {
        if ($this->dataType === 'summary') {
            return [
                $row->GOOD_TYPE,
                $row->PROD_DATE,
                $row->LINE_GRP,
                $row->LINE_NM,
                $row->LINE_CAPA_HOUR,
                $row->ARTICLEDESC,
                $row->ARTNO,
                $row->O_NO,
                $row->DFT_CATG,
                $row->DFT_NM,
                $row->TIME_CD,
                $row->time_interval,
                $row->total_qty,
                $row->total_frequency,
            ];
        }

        return [
            $row->GOOD_TYPE,
            $row->PROD_DATE,
            $row->LINE_GRP,
            $row->LINE_NM,
            $row->LINE_CAPA_HOUR,
            $row->ARTICLEDESC,
            $row->ARTNO,
            $row->O_NO,
            $row->DFT_CATG,
            $row->DFT_NM,
            $row->TIME_CD,
            $row->QTY,
            $row->CREATE_DATE,
        ];
    }

    // Styling Header Excel (Bold, Warna Background, Border)
    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => '198754'] // Warna hijau Bootstrap (btn-success)
                ],
            ],
        ];
    }
}
