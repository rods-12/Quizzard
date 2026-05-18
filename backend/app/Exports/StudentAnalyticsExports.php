<?php
// app/Exports/StudentAnalyticsExport.php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Sheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class StudentAnalyticsExport implements WithMultipleSheets
{
    protected Collection $students;
    protected array $filters;

    public function __construct(Collection $students, array $filters = [])
    {
        $this->students = $students;
        $this->filters  = $filters;
    }

    public function sheets(): array
    {
        return [
            new StudentAnalyticsMainSheet($this->students, $this->filters),
            new StudentAnalyticsTopSheet($this->students),
            new StudentAnalyticsAtRiskSheet($this->students),
        ];
    }
}

// ─────────────────────────────────────────────────────────────
// Sheet 1: All Students
// ─────────────────────────────────────────────────────────────
class StudentAnalyticsMainSheet implements
    FromCollection, WithTitle, WithHeadings,
    WithStyles, ShouldAutoSize
{
    protected Collection $students;
    protected array $filters;
    protected int $dataStartRow = 7;

    public function __construct(Collection $students, array $filters)
    {
        $this->students = $students;
        $this->filters  = $filters;
    }

    public function title(): string { return 'All Students'; }

    public function collection(): Collection
    {
        return $this->students->map(fn($s) => [
            $s->full_name,
            $s->email,
            $s->grade_level  ?? '—',
            $s->section      ?? '—',
            $s->gender       ?? '—',
            $s->attempt_count,
            $s->attempt_count > 0 ? number_format($s->avg_score, 2) : '—',
            $s->attempt_count > 0 ? number_format($s->pass_rate, 2) : '—',
            $s->attempt_count > 0 ? ($s->avg_score >= 75 ? 'Passing' : ($s->avg_score >= 50 ? 'Developing' : 'At Risk')) : 'Inactive',
        ]);
    }

    public function headings(): array
    {
        return [
            ['QUIZZARD — STUDENT ANALYTICS REPORT'],
            ['Generated: ' . now()->format('F d, Y h:i A')],
            ['Period: ' . ($this->filters['date_from'] ?? 'All time') . ' — ' . ($this->filters['date_to'] ?? now()->toDateString())],
            ['Grade Filter: ' . ($this->filters['grade_level'] ?? 'All Grades')],
            [''],
            ['Full Name', 'Email', 'Grade', 'Section', 'Gender', 'Attempts', 'Avg Score %', 'Pass Rate %', 'Status'],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = $this->dataStartRow + $this->students->count();

        // Banner
        $sheet->mergeCells('A1:I1');
        $sheet->mergeCells('A2:I2');
        $sheet->mergeCells('A3:I3');
        $sheet->mergeCells('A4:I4');
        $sheet->mergeCells('A5:I5');

        // Alternating rows
        for ($row = $this->dataStartRow; $row <= $lastRow; $row++) {
            if ($row % 2 === 0) {
                $sheet->getStyle("A{$row}:I{$row}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F8FAFC');
            }
        }

        return [
            1 => [
                'font'      => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1e3a5f']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            2 => [
                'font' => ['italic' => true, 'color' => ['rgb' => '64748b']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F1F5F9']],
            ],
            3 => [
                'font' => ['italic' => true, 'color' => ['rgb' => '64748b']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F1F5F9']],
            ],
            4 => [
                'font' => ['italic' => true, 'color' => ['rgb' => '64748b']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F1F5F9']],
            ],
            6 => [
                'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '3b82f6']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'borders'   => ['bottom' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '2563eb']]],
            ],
        ];
    }
}

// ─────────────────────────────────────────────────────────────
// Sheet 2: Top 10
// ─────────────────────────────────────────────────────────────
class StudentAnalyticsTopSheet implements
    FromCollection, WithTitle, WithHeadings,
    WithStyles, ShouldAutoSize
{
    protected Collection $students;

    public function __construct(Collection $students)
    {
        $this->students = $students->filter(fn($s) => $s->attempt_count > 0)
                                   ->sortByDesc('avg_score')->take(10)->values();
    }

    public function title(): string { return 'Top 10 Students'; }

    public function collection(): Collection
    {
        return $this->students->map(fn($s, $i) => [
            $i + 1,
            $s->full_name,
            $s->email,
            $s->grade_level ?? '—',
            $s->section     ?? '—',
            $s->attempt_count,
            number_format($s->avg_score, 2),
            number_format($s->pass_rate, 2),
        ]);
    }

    public function headings(): array
    {
        return [
            ['TOP 10 PERFORMING STUDENTS'],
            ['Generated: ' . now()->format('F d, Y h:i A')],
            [''],
            ['Rank', 'Full Name', 'Email', 'Grade', 'Section', 'Attempts', 'Avg Score %', 'Pass Rate %'],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->mergeCells('A1:H1');
        $sheet->mergeCells('A2:H2');
        $sheet->mergeCells('A3:H3');
        return [
            1 => [
                'font'      => ['bold' => true, 'size' => 13, 'color' => ['rgb' => 'FFFFFF']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '059669']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            2 => [
                'font' => ['italic' => true, 'color' => ['rgb' => '64748b']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F1F5F9']],
            ],
            4 => [
                'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '10b981']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }
}

// ─────────────────────────────────────────────────────────────
// Sheet 3: Bottom 10 / At-Risk
// ─────────────────────────────────────────────────────────────
class StudentAnalyticsAtRiskSheet implements
    FromCollection, WithTitle, WithHeadings,
    WithStyles, ShouldAutoSize
{
    protected Collection $students;

    public function __construct(Collection $students)
    {
        $this->students = $students->filter(fn($s) => $s->attempt_count > 0)
                                   ->sortBy('avg_score')->take(10)->values();
    }

    public function title(): string { return 'At-Risk Students'; }

    public function collection(): Collection
    {
        return $this->students->map(fn($s, $i) => [
            $i + 1,
            $s->full_name,
            $s->email,
            $s->grade_level ?? '—',
            $s->section     ?? '—',
            $s->attempt_count,
            number_format($s->avg_score, 2),
            number_format($s->pass_rate, 2),
        ]);
    }

    public function headings(): array
    {
        return [
            ['BOTTOM 10 AT-RISK STUDENTS'],
            ['Generated: ' . now()->format('F d, Y h:i A')],
            [''],
            ['Rank', 'Full Name', 'Email', 'Grade', 'Section', 'Attempts', 'Avg Score %', 'Pass Rate %'],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->mergeCells('A1:H1');
        $sheet->mergeCells('A2:H2');
        $sheet->mergeCells('A3:H3');
        return [
            1 => [
                'font'      => ['bold' => true, 'size' => 13, 'color' => ['rgb' => 'FFFFFF']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'dc2626']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            2 => [
                'font' => ['italic' => true, 'color' => ['rgb' => '64748b']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F1F5F9']],
            ],
            4 => [
                'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'ef4444']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }
}