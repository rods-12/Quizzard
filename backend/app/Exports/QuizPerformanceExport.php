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

class QuizPerformanceExport implements FromCollection, ShouldAutoSize, WithStyles, WithTitle, WithEvents
{
    public function __construct(
        protected Collection $quizzes,
        protected array $filters = [],
        protected array $kpis = []
    ) {
    }

    public function title(): string
    {
        return 'Quiz Analytics';
    }

    public function collection(): Collection
    {
        $from = $this->filters['date_from'] ?: 'All time';
        $to = $this->filters['date_to'] ?: 'All time';

        $rows = collect([
            ['Quizzard - Quiz Analytics Report'],
            ['Generated At', now()->format('Y-m-d H:i:s')],
            ['Date Range', "{$from} to {$to}"],
            ['Teacher Filter', $this->filters['teacher_id'] ?? 'All Teachers'],
            [''],
            ['KPI Summary'],
            ['Total Attempts', $this->kpis['total_attempts'] ?? 0],
            ['Average Pass Rate', number_format(min(100, max(0, $this->kpis['avg_pass_rate'] ?? 0)), 1) . '%'],
            ['Average Score', number_format($this->kpis['avg_score'] ?? 0, 1) . '%'],
            [''],
            [
                'Quiz Title',
                'Teacher',
                'Latest Attempt Date',
                'Questions',
                'Total Attempts',
                'Average Score (%)',
                'Pass Rate (%)',
                'Highest Score (%)',
                'Lowest Score (%)',
                'Status',
            ],
        ]);

        foreach ($this->quizzes as $quiz) {
            $rows->push([
                $quiz->title,
                $quiz->teacher_name ?? '-',
                $quiz->latest_attempt_at ? \Carbon\Carbon::parse($quiz->latest_attempt_at)->format('Y-m-d') : 'No attempts',
                $quiz->questions_count ?? 0,
                $quiz->total_attempts ?? 0,
                number_format($quiz->avg_score ?? 0, 1),
                number_format(min(100, max(0, $quiz->pass_rate ?? 0)), 1),
                number_format($quiz->highest_score ?? 0, 1),
                number_format($quiz->lowest_score ?? 0, 1),
                $quiz->is_published ? 'Published' : 'Draft',
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
            11 => [
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

                $sheet->mergeCells('A1:J1');
                $sheet->getRowDimension(1)->setRowHeight(30);
                $sheet->freezePane('A12');
                $sheet->getTabColor()->setARGB('FF2563EB');

                $sheet->getStyle("A11:J{$lastRow}")->getBorders()
                    ->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

                for ($row = 12; $row <= $lastRow; $row++) {
                    if ($row % 2 === 0) {
                        $sheet->getStyle("A{$row}:J{$row}")->getFill()
                            ->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()->setRGB('F8FAFC');
                    }
                    $sheet->getRowDimension($row)->setRowHeight(18);
                }
            },
        ];
    }
}
