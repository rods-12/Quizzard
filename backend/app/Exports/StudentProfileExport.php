<?php
// app/Exports/StudentProfileExport.php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use App\Models\User;

class StudentProfileExport implements WithMultipleSheets
{
    protected User $student;
    protected array $stats;
    protected $attempts;
    protected $weakAreas;

    public function __construct(User $student, array $stats, $attempts, $weakAreas)
    {
        $this->student   = $student;
        $this->stats     = $stats;
        $this->attempts  = collect($attempts);
        $this->weakAreas = collect($weakAreas);
    }

    public function sheets(): array
    {
        return [
            new StudentProfileSummarySheet($this->student, $this->stats),
            new StudentProfileAttemptsSheet($this->student, $this->attempts),
            new StudentProfileWeakAreasSheet($this->student, $this->weakAreas),
        ];
    }
}

// ─────────────────────────────────────────────────────────────
// Sheet 1: Summary
// ─────────────────────────────────────────────────────────────
class StudentProfileSummarySheet implements
    FromCollection, WithTitle, WithHeadings, WithStyles, ShouldAutoSize
{
    protected User $student;
    protected array $stats;

    public function __construct(User $student, array $stats)
    {
        $this->student = $student;
        $this->stats   = $stats;
    }

    public function title(): string { return 'Summary'; }

    public function collection(): Collection
    {
        $profile = $this->student->studentProfile;
        return collect([
            ['Student Name',    $this->student->full_name],
            ['Email',           $this->student->email],
            ['Grade Level',     $profile->grade_level ?? '—'],
            ['Section',         $profile->section     ?? '—'],
            ['Gender',          $profile->gender      ?? '—'],
            [''],
            ['PERFORMANCE SUMMARY', ''],
            ['Total Attempts',  $this->stats['total_attempts']],
            ['Average Score',   number_format($this->stats['avg_score'], 2) . '%'],
            ['Pass Rate',       number_format($this->stats['pass_rate'], 2) . '%'],
            ['Best Score',      number_format($this->stats['best_score'], 2) . '%'],
            ['Worst Score',     number_format($this->stats['worst_score'] ?? 0, 2) . '%'],
            ['Quizzes Passed',  $this->stats['passed']],
            ['Quizzes Failed',  $this->stats['total_attempts'] - $this->stats['passed']],
            [''],
            ['Report Generated', now()->format('F d, Y h:i A')],
        ]);
    }

    public function headings(): array
    {
        return [
            ['QUIZZARD — INDIVIDUAL STUDENT REPORT'],
            [''],
            ['Field', 'Value'],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->mergeCells('A1:B1');
        $sheet->mergeCells('A2:B2');
        return [
            1 => [
                'font'      => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1e3a5f']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            3 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '3b82f6']],
            ],
        ];
    }

}

// ─────────────────────────────────────────────────────────────
// Sheet 2: All Attempts
// ─────────────────────────────────────────────────────────────
class StudentProfileAttemptsSheet implements
    FromCollection, WithTitle, WithHeadings, WithStyles, ShouldAutoSize
{
    protected User $student;
    protected Collection $attempts;

    public function __construct(User $student, Collection $attempts)
    {
        $this->student  = $student;
        $this->attempts = $attempts;
    }

    public function title(): string { return 'All Attempts'; }

    public function collection(): Collection
    {
        return $this->attempts->map(function ($a) {
            $pct = $a->total_points > 0 ? round(($a->score / $a->total_points) * 100, 2) : 0;
            $duration = ($a->started_at && $a->completed_at)
                ? self::formatDuration(\Carbon\Carbon::parse($a->started_at)->diffInSeconds($a->completed_at))
                : '—';
            return [
                $a->quiz->title ?? 'Deleted Quiz',
                $a->quiz?->classes->first()?->name ?? '—',
                $a->score . ' / ' . $a->total_points,
                $pct . '%',
                ucfirst($a->status),
                $a->completed_at ? \Carbon\Carbon::parse($a->completed_at)->format('M d, Y') : '—',
                $duration,
            ];
        });
    }

    public function headings(): array
    {
        return [
            ['QUIZ ATTEMPT HISTORY - ' . strtoupper($this->student->full_name ?: $this->student->name ?: $this->student->email)],
            ['Generated: ' . now()->format('F d, Y h:i A')],
            [''],
            ['Quiz', 'Class', 'Score', 'Percentage', 'Status', 'Date', 'Duration'],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->mergeCells('A1:G1');
        $sheet->mergeCells('A2:G2');
        $sheet->mergeCells('A3:G3');
        return [
            1 => [
                'font'      => ['bold' => true, 'size' => 13, 'color' => ['rgb' => 'FFFFFF']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1e3a5f']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            2 => [
                'font' => ['italic' => true, 'color' => ['rgb' => '64748b']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F1F5F9']],
            ],
            4 => [
                'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '3b82f6']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }

    private static function formatDuration(int $seconds): string
    {
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $remainingSeconds = $seconds % 60;

        if ($hours > 0) {
            return "{$hours}h {$minutes}m";
        }

        return $minutes > 0 ? "{$minutes}m {$remainingSeconds}s" : "{$remainingSeconds}s";
    }
}

// ─────────────────────────────────────────────────────────────
// Sheet 3: Weak Areas
// ─────────────────────────────────────────────────────────────
class StudentProfileWeakAreasSheet implements
    FromCollection, WithTitle, WithHeadings, WithStyles, ShouldAutoSize
{
    protected User $student;
    protected Collection $weakAreas;

    public function __construct(User $student, Collection $weakAreas)
    {
        $this->student   = $student;
        $this->weakAreas = $weakAreas;
    }

    public function title(): string { return 'Weak Areas'; }

    public function collection(): Collection
    {
        return $this->weakAreas->map(fn($item) => [
            $item->question_text,
            $item->quiz_title,
            $item->wrong_count,
            $item->total_seen,
            $item->total_seen > 0 ? number_format(($item->wrong_count / $item->total_seen) * 100, 1) . '%' : '—',
        ]);
    }

    public function headings(): array
    {
        return [
            ['WEAK AREAS - ' . strtoupper($this->student->full_name ?: $this->student->name ?: $this->student->email)],
            ['Questions this student answers incorrectly most often'],
            [''],
            ['Question', 'Quiz', 'Times Wrong', 'Times Seen', 'Error Rate'],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->mergeCells('A1:E1');
        $sheet->mergeCells('A2:E2');
        $sheet->mergeCells('A3:E3');
        return [
            1 => [
                'font'      => ['bold' => true, 'size' => 13, 'color' => ['rgb' => 'FFFFFF']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'dc2626']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            2 => [
                'font' => ['italic' => true, 'color' => ['rgb' => '64748b']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FEF2F2']],
            ],
            4 => [
                'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'ef4444']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }
}
