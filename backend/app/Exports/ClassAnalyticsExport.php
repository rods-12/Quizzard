<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ClassAnalyticsExport implements FromCollection, ShouldAutoSize, WithStyles, WithTitle, WithEvents
{
    public function __construct(
        protected Collection $classes,
        protected array $filters,
        protected array $kpis
    ) {
    }

    public function title(): string
    {
        return 'Class Analytics';
    }

    public function collection(): Collection
    {
        $from = $this->filters['date_from'] ?: 'All time';
        $to = $this->filters['date_to'] ?: 'All time';

        $rows = collect([
            ['Quizzard - Class Analytics Report'],
            ['Generated At', now()->format('Y-m-d H:i:s')],
            ['Date Range', "{$from} to {$to}"],
            ['Teacher Filter', $this->filters['teacher_id'] ?? 'All Teachers'],
            [''],
            ['KPI Summary'],
            ['Total Classes', $this->kpis['total_classes'] ?? 0],
            ['Total Students', $this->kpis['total_students'] ?? 0],
            ['Average Pass Rate', number_format($this->kpis['avg_pass_rate'] ?? 0, 1) . '%'],
            ['Average Score', number_format($this->kpis['avg_score'] ?? 0, 1) . '%'],
            [''],
            [
                'Class Name',
                'Class Code',
                'Teacher',
                'Latest Attempt Date',
                'Students',
                'Quizzes Assigned',
                'Total Attempts',
                'Average Score (%)',
                'Pass Rate (%)',
            ],
        ]);

        foreach ($this->classes as $class) {
            $rows->push([
                $class->name,
                $class->class_code ?? '-',
                $class->teacher_name ?? '-',
                $class->latest_attempt_at ? \Carbon\Carbon::parse($class->latest_attempt_at)->format('Y-m-d') : 'No attempts',
                $class->students_count ?? 0,
                $class->quizzes_count ?? 0,
                $class->total_attempts ?? 0,
                number_format($class->avg_score ?? 0, 1),
                number_format(min(100, max(0, $class->pass_rate ?? 0)), 1),
            ]);
        }

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E293B']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            6 => ['font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '1E293B']]],
            12 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2563EB']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = $sheet->getHighestRow();

                $sheet->mergeCells('A1:I1');
                $sheet->getRowDimension(1)->setRowHeight(30);
                $sheet->freezePane('A13');
                $sheet->getTabColor()->setARGB('FF2563EB');

                $sheet->getStyle("A12:I{$lastRow}")->getBorders()
                    ->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

                for ($row = 13; $row <= $lastRow; $row++) {
                    if ($row % 2 === 0) {
                        $sheet->getStyle("A{$row}:I{$row}")->getFill()
                            ->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()->setRGB('F8FAFC');
                    }
                    $sheet->getRowDimension($row)->setRowHeight(18);
                }
            },
        ];
    }
}
