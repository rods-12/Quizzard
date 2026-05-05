<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ClassQuizResultsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    protected $data;
    protected $className;
    protected $quizTitle;

    public function __construct($data, $className, $quizTitle)
    {
        $this->data = $data;
        $this->className = $className;
        $this->quizTitle = $quizTitle;
    }

    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            'Rank',
            'Student ID',
            'First Name',
            'Surname',
            'Score',
            'Total Points',
            'Percentage',
            'Status',
        ];
    }

    public function map($row): array
    {
        return [
            $row['rank'],
            $row['student_id'],
            $row['first_name'],
            $row['surname'],
            $row['score'],
            $row['total_points'],
            $row['percentage'] . '%',
            $row['status'],
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Style the header row (row 1)
        $sheet->getStyle('A1:H1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => 'solid',
                'startColor' => ['rgb' => '4CAF50'],
            ],
            'alignment' => [
                'horizontal' => 'center',
                'vertical' => 'center',
            ],
        ]);

        // Status color coding for data rows (starting from row 2)
        $lastRow = $sheet->getHighestRow();
        for ($row = 2; $row <= $lastRow; $row++) {
            $statusCell = $sheet->getCell("H{$row}");
            if ($statusCell->getValue() === 'Taken') {
                $sheet->getStyle("H{$row}")->getFont()->getColor()->setRGB('4CAF50');
            } else {
                $sheet->getStyle("H{$row}")->getFont()->getColor()->setRGB('9E9E9E');
            }
        }

        return [];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8,
            'B' => 15,
            'C' => 15,
            'D' => 15,
            'E' => 12,
            'F' => 14,
            'G' => 12,
            'H' => 12,
        ];
    }
}
