<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ClassShowExport implements FromCollection, ShouldAutoSize, WithStyles, WithTitle, WithEvents
{
    protected $classroom;
    protected array $kpis;
    protected Collection $students;
    protected Collection $quizPerformance;

    public function __construct($classroom, array $kpis, Collection $students, Collection $quizPerformance)
    {
        $this->classroom       = $classroom;
        $this->kpis            = $kpis;
        $this->students        = $students;
        $this->quizPerformance = $quizPerformance;
    }

    public function title(): string
    {
        return substr($this->classroom->name, 0, 31);
    }

    public function collection(): Collection
    {
        $rows = collect();

        // ── Banner ──────────────────────────────────────────────
        $rows->push(['Quizzard — Class Report: ' . $this->classroom->name]);
        $rows->push(['']);

        // ── Meta ────────────────────────────────────────────────
        $teacher = $this->classroom->teacher;
        $rows->push(['Teacher:', $teacher ? ($teacher->first_name . ' ' . $teacher->surname) : '—']);
        $rows->push(['Class Code:', $this->classroom->class_code ?? '—']);
        $rows->push(['Exported At:', now()->format('Y-m-d H:i:s')]);
        $rows->push(['']);

        // ── KPIs ────────────────────────────────────────────────
        $rows->push(['KPI Summary']);
        $rows->push(['Enrolled Students', $this->kpis['total_students']]);
        $rows->push(['Total Attempts',    $this->kpis['total_attempts']]);
        $rows->push(['Pass Rate',         number_format($this->kpis['pass_rate'], 1) . '%']);
        $rows->push(['Avg Score',         number_format($this->kpis['avg_score'], 1) . '%']);
        $rows->push(['']);

        // ── Students header ─────────────────────────────────────
        $rows->push(['Student Performance']);
        $rows->push(['Student Name', 'Avg Score (%)', 'Total Attempts', 'Pass Rate (%)']);

        foreach ($this->students as $student) {
            $rows->push([
                $student->first_name . ' ' . $student->surname,
                number_format($student->avg_score ?? 0, 1),
                $student->total_attempts ?? 0,
                number_format($student->pass_rate ?? 0, 1),
            ]);
        }

        $rows->push(['']);

        // ── Quiz performance header ──────────────────────────────
        $rows->push(['Quiz Performance']);
        $rows->push(['Quiz Title', 'Total Attempts', 'Avg Score (%)', 'Pass Rate (%)']);

        foreach ($this->quizPerformance as $quiz) {
            $rows->push([
                $quiz->title,
                $quiz->total_attempts ?? 0,
                number_format($quiz->avg_score ?? 0, 1),
                number_format($quiz->pass_rate ?? 0, 1),
            ]);
        }

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14, 'color' => ['argb' => 'FFFFFFFF']],
                  'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1E293B']]],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->getTabColor()->setARGB('FF10B981');
            },
        ];
    }
}