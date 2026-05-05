<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StudentPerformanceExport implements FromCollection, WithHeadings, WithStyles
{
    protected $data;
    protected $className;

    public function __construct($data, $className)
    {
        $this->data = $data;
        $this->className = $className;
    }

    public function collection()
    {
        return $this->data->map(function ($item) {
            return [
                'Student ID' => $item['student_id'],
                'First Name' => $item['first_name'],
                'Last Name' => $item['surname'],
                'Quizzes Taken' => $item['quizzes_taken'] . ' / ' . $item['total_quizzes'],
                'Overall Grade' => $item['overall_percentage'] . '%',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Student ID',
            'First Name',
            'Last Name',
            'Quizzes Taken',
            'Overall Grade',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Header styling
        $sheet->getStyle('A1:E1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '4CAF50']],
        ]);

        // Auto-width columns
        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return [];
    }
}
