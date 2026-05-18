<?php
namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class QuizResultsExport implements FromCollection, ShouldAutoSize, WithStyles, WithTitle, WithEvents
{
    protected $rows;
    protected $quizTitle;

    // Total data columns: A–K (11 columns)
    const LAST_COL   = 'K';
    const HEADER_ROW = 8; // row where column labels sit
    const DATA_START = 9; // first actual data row

    public function __construct(Collection $rows, $quizTitle = null)
    {
        $this->rows      = $rows;
        $this->quizTitle = $quizTitle ?? 'N/A';
    }

    public function title(): string
    {
        return 'Results';
    }

    public function collection()
    {
        $data = $this->rows->map(function ($row) {
            return [
                $row['rank']           ?? '',
                $row['student_id']     ?? '',
                $row['surname']        ?? '',
                $row['first_name']     ?? '',
                $row['middle_initial'] ?? '',
                $row['gender']         ?? '',
                $row['grade_level']    ?? '',
                $row['section']        ?? '',
                $row['score'],
                $row['total_points'],
                ($row['percentage'] ?? 0) . '%',
            ];
        });

        $exportedOn = now()->format('F j, Y  h:i A');

        return collect([
            // Row 1 – main title banner
            ['QUIZZARD — QUIZ RESULTS REPORT', '', '', '', '', '', '', '', '', '', ''],
            // Row 2 – spacer
            ['', '', '', '', '', '', '', '', '', '', ''],
            // Row 3 – quiz title label + value
            ['Quiz Title', $this->quizTitle, '', '', '', '', '', '', '', '', ''],
            // Row 4 – exported on
            ['Exported On', $exportedOn, '', '', '', '', '', '', '', '', ''],
            // Row 5 – total students
            ['Total Students', $this->rows->count(), '', '', '', '', '', '', '', '', ''],
            // Row 6 – spacer
            ['', '', '', '', '', '', '', '', '', '', ''],
            // Row 7 – spacer
            ['', '', '', '', '', '', '', '', '', '', ''],
            // Row 8 – column headings
            [
                'Rank', 'Student ID', 'Surname', 'First Name', 'M.I.',
                'Gender', 'Grade Level', 'Section', 'Score', 'Total', 'Percentage',
            ],
        ])->merge($data);
    }

    /**
     * styles() does NO merging here — all merges are handled exclusively
     * inside registerEvents() to avoid the duplicate-merge XML corruption.
     */
    public function styles(Worksheet $sheet)
    {
        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet     = $event->sheet->getDelegate();
                $lastRow   = $sheet->getHighestRow();
                $lastCol   = self::LAST_COL;
                $hdrRow    = self::HEADER_ROW;
                $dataStart = self::DATA_START;

                // ── Row 1: Banner title — merge all columns ───────────────
                $sheet->mergeCells("A1:{$lastCol}1");
                $sheet->getStyle('A1')->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 15, 'color' => ['rgb' => 'FFFFFF'], 'name' => 'Arial'],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F3864']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(32);

                // ── Rows 3–5: Meta info ───────────────────────────────────
                // A = label (bold), B:{lastCol} = value (merged)
                foreach ([3, 4, 5] as $r) {
                    $sheet->mergeCells("B{$r}:{$lastCol}{$r}");
                    $sheet->getStyle("A{$r}")->applyFromArray([
                        'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '1F3864'], 'name' => 'Arial'],
                    ]);
                    $sheet->getStyle("B{$r}")->applyFromArray([
                        'font' => ['size' => 10, 'name' => 'Arial'],
                    ]);
                    $sheet->getRowDimension($r)->setRowHeight(16);
                }

                // ── Row 8: Column header bar ──────────────────────────────
                $sheet->getStyle("A{$hdrRow}:{$lastCol}{$hdrRow}")->applyFromArray([
                    'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10, 'name' => 'Arial'],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2E75B6']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    'borders'   => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'FFFFFF']],
                    ],
                ]);
                $sheet->getRowDimension($hdrRow)->setRowHeight(20);

                // ── Data rows: alternating row bands ─────────────────────
                for ($row = $dataStart; $row <= $lastRow; $row++) {
                    $bg = ($row % 2 === 0) ? 'DCE6F1' : 'FFFFFF';
                    $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
                        'font'      => ['name' => 'Arial', 'size' => 10],
                        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bg]],
                        'borders'   => [
                            'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'BDD7EE']],
                        ],
                        'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                    ]);
                    $sheet->getRowDimension($row)->setRowHeight(17);
                }

                // Center-align: Rank(A), M.I.(E), Gender(F), Score(I), Total(J), Percentage(K)
                $sheet->getStyle("A{$dataStart}:A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("E{$dataStart}:F{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("I{$dataStart}:K{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // ── Outer border around the table ─────────────────────────
                $sheet->getStyle("A{$hdrRow}:{$lastCol}{$lastRow}")->applyFromArray([
                    'borders' => [
                        'outline' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '2E75B6']],
                    ],
                ]);

                // ── Freeze pane below header ──────────────────────────────
                $sheet->freezePane("A{$dataStart}");

                // ── Sheet tab color ───────────────────────────────────────
                $sheet->getTabColor()->setRGB('2E75B6');
            },
        ];
    }
}