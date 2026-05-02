<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ClassRoom;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\StudentAnswer;
use App\Exports\QuizResultsExport;
use App\Exports\QuizAnalyticsExport;
use Maatwebsite\Excel\Facades\Excel;


class AdminQuizController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->search;
        $sort = $request->sort ?? 'latest';
        $filterBy = $request->get('filter_by', 'all'); // all, first_name, middle_initial, surname

        $query = ClassRoom::with(['teacher', 'students']);

        if ($search) {
    $query->where(function ($q) use ($search, $filterBy) {
        $q->where('name', 'like', "%{$search}%")
          ->orWhereHas('teacher', function ($teacherQuery) use ($search, $filterBy) {
              if ($filterBy === 'first_name') {
                  $teacherQuery->where('first_name', 'like', "%{$search}%");
              } elseif ($filterBy === 'middle_initial') {
                  $teacherQuery->where('middle_initial', 'like', "%{$search}%");
              } elseif ($filterBy === 'surname') {
                  $teacherQuery->where('surname', 'like', "%{$search}%");
              } else {
                  $teacherQuery->where('first_name', 'like', "%{$search}%")
                      ->orWhere('middle_initial', 'like', "%{$search}%")
                      ->orWhere('surname', 'like', "%{$search}%")
                      ->orWhere('name', 'like', "%{$search}%");
              }
          });
    });
}

        if ($sort === 'oldest') {
            $query->orderBy('created_at', 'asc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $classes = $query->paginate(10);

        $activeTeachers = User::where('role', 'teacher')
            ->where('status', 'active')
            ->count();

        $teachers = User::where('role', 'teacher')
            ->where('status', 'active')
            ->orderBy('first_name')
            ->orderBy('surname')
            ->orderBy('name')
            ->get();

        $studentsCount = User::where('role', 'student')->count();

        $classesCount = ClassRoom::count();
        $totalEnrollments = DB::table('class_students')->count();

        if ($request->ajax()) {
            return view('admin.classes.partials.table', compact('classes'))->render();
        }

        return view('admin.classes.index', compact(
            'classes',
            'activeTeachers',
            'teachers',
            'studentsCount',
            'classesCount',
            'totalEnrollments'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'teacher_id' => 'required|exists:users,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $teacher = User::where('id', $request->teacher_id)
            ->where('role', 'teacher')
            ->where('status', 'active')
            ->first();

        if (!$teacher) {
            return response()->json([
                'success' => false,
                'message' => 'Selected teacher is invalid or inactive.'
            ], 422);
        }

        $class = ClassRoom::create([
            'teacher_id' => $teacher->id,
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Class created successfully.',
            'class' => $class,
        ]);
    }

    public function update(Request $request, $id)
    {
        $class = ClassRoom::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string'
        ]);

        $class->update([
            'name' => $request->name,
            'description' => $request->description
        ]);

        return response()->json(['success' => true]);
    }

    public function destroy($id)
    {
        $class = ClassRoom::findOrFail($id);
        $class->delete();

        return response()->json(['success' => true]);
    }

    public function details($id)
    {
        $class = ClassRoom::with([
            'teacher',
            'students',
            'quizzes' => function ($query) {
    $query->withCount(['questions', 'attempts'])
        ->orderBy('created_at', 'desc');
}
        ])->findOrFail($id);

        $studentsEnrolledCount = $class->students->count();
        $quizzesCount = $class->quizzes->count();

        return view('admin.classes.show', [
            'class' => $class,
            'studentsEnrolledCount' => $studentsEnrolledCount,
            'quizzesCount' => $quizzesCount,
        ]);
    }

    public function quizDetails(Request $request, $classId, $quizId)
    {
        $tab = $request->get('tab', 'results');

        $class = ClassRoom::with('teacher')->findOrFail($classId);

        $quiz = Quiz::with([
            'teacher',
            'questions' => function ($query) {
                $query->orderBy('order');
            },
            'attempts' => function ($query) {
                $query->with('student')->orderByDesc('completed_at')->orderByDesc('created_at');
            }
        ])->findOrFail($quizId);

        $isAssignedToClass = $class->quizzes()->where('quizzes.id', $quizId)->exists();

        abort_unless($isAssignedToClass, 404);

        $completedAttempts = $quiz->attempts->where('status', 'completed')->values();

        $totalQuestions = $quiz->questions->count();
        $totalAttempts = $completedAttempts->count();
        $highestScore = $totalAttempts > 0 ? $completedAttempts->max('score') : 0;
        $lowestScore = $totalAttempts > 0 ? $completedAttempts->min('score') : 0;
        $averageScore = $totalAttempts > 0 ? round($completedAttempts->avg('score'), 2) : 0;
        $averagePercentage = $totalAttempts > 0
            ? round($completedAttempts->avg(function ($attempt) {
                return $attempt->total_points > 0
                    ? ($attempt->score / $attempt->total_points) * 100
                    : 0;
            }), 2)
            : 0;

        $passingPercentage = 60;
        $passCount = $completedAttempts->filter(function ($attempt) use ($passingPercentage) {
            return $attempt->total_points > 0
                && (($attempt->score / $attempt->total_points) * 100) >= $passingPercentage;
        })->count();

        $failCount = $totalAttempts - $passCount;
        $passRate = $totalAttempts > 0 ? round(($passCount / $totalAttempts) * 100, 2) : 0;

        $resultsRows = $completedAttempts->map(function ($attempt) use ($passingPercentage) {
    $percentage = $attempt->total_points > 0
        ? round(($attempt->score / $attempt->total_points) * 100, 2)
        : 0;

    $student = $attempt->student;
    $profile = $student?->studentProfile;

    return [
        'student_id' => $profile->student_id ?? '-',
        'surname' => $student->surname ?? '-',
        'first_name' => $student->first_name ?? '-',
        'middle_initial' => $student->middle_initial ?? '-',
        'gender' => $profile->gender ?? '-',
        'grade_level' => $profile->grade_level ?? '-',
        'section' => $profile->section ?? '-',

        'student_name' => $student->name ?? 'Unknown Student',
        'score' => $attempt->score,
        'total_points' => $attempt->total_points,
        'percentage' => $percentage,
        'status' => $percentage >= $passingPercentage ? 'Passed' : 'Failed',
        'completed_at' => $attempt->completed_at,
    ];
})
->sortByDesc('completed_at')
->values();

if ($request->get('export') === 'excel' && $tab === 'results') {
    $exportRows = $resultsRows->values()->map(function ($row, $index) {
        $row['rank'] = $index + 1;
        return $row;
    });

    return Excel::download(
        new QuizResultsExport($exportRows),
        'quiz-results-report.xlsx'
    );
}

if ($request->get('export') === 'excel' && $tab === 'results') {
    $exportRows = $resultsRows->values()->map(function ($row, $index) {
        $row['rank'] = $index + 1;
        return $row;
    });

    return Excel::download(
        new QuizResultsExport($exportRows),
        'quiz-results-report.xlsx'
    );
}

        $attemptIds = $completedAttempts->pluck('id');

        $answers = $attemptIds->isNotEmpty()
            ? StudentAnswer::with('question')
                ->whereIn('attempt_id', $attemptIds)
                ->get()
            : collect();

        $questionAnalytics = $quiz->questions->map(function ($question) use ($answers, $totalAttempts) {
            $questionAnswers = $answers->where('question_id', $question->id);
            $correctCount = $questionAnswers->where('is_correct', true)->count();
            $attemptedCount = $questionAnswers->count();
            $avgPoints = $attemptedCount > 0 ? round($questionAnswers->avg('points_earned'), 2) : 0;
            $correctRate = $totalAttempts > 0 ? round(($correctCount / $totalAttempts) * 100, 2) : 0;

            if ($correctRate >= 80) {
                $difficulty = 'Easy';
            } elseif ($correctRate >= 50) {
                $difficulty = 'Moderate';
            } else {
                $difficulty = 'Difficult';
            }

            return [
                'order' => $question->order,
                'question_text' => $question->question_text,
                'question_type' => $question->question_type,
                'points' => $question->points,
                'attempted_count' => $attemptedCount,
                'correct_count' => $correctCount,
                'correct_rate' => $correctRate,
                'average_points' => $avgPoints,
                'difficulty' => $difficulty,
            ];
        })->values();

        if ($request->get('export') === 'excel' && $tab === 'analytics') {
    return Excel::download(
        new QuizAnalyticsExport($questionAnalytics),
        'quiz-analytics-report.xlsx'
    );
}

        return view('admin.quizzes.show', [
            'class' => $class,
            'quiz' => $quiz,
            'tab' => $tab,
            'totalQuestions' => $totalQuestions,
            'totalAttempts' => $totalAttempts,
            'highestScore' => $highestScore,
            'lowestScore' => $lowestScore,
            'averageScore' => $averageScore,
            'averagePercentage' => $averagePercentage,
            'passCount' => $passCount,
            'failCount' => $failCount,
            'passRate' => $passRate,
            'resultsRows' => $resultsRows,
            'questionAnalytics' => $questionAnalytics,
        ]);
    }

    public function show($id)
    {
        $class = ClassRoom::with(['teacher', 'students'])->findOrFail($id);
        return response()->json($class);
    }

    public function exportResults($classId, $quizId)
{
    $quiz = Quiz::with(['attempts.student.studentProfile', 'questions'])
        ->findOrFail($quizId);

    $completedAttempts = $quiz->attempts->where('status', 'completed')->values();

    $passingPercentage = 60;

    $rows = $completedAttempts->map(function ($attempt, $index) use ($passingPercentage) {

        $percentage = $attempt->total_points > 0
            ? round(($attempt->score / $attempt->total_points) * 100, 2)
            : 0;

        $student = $attempt->student;
        $profile = $student?->studentProfile;

        return [
            'rank' => $index + 1,
            'student_id' => $profile->student_id ?? '-',
            'surname' => $student->surname ?? '-',
            'first_name' => $student->first_name ?? '-',
            'middle_initial' => $student->middle_initial ?? '-',
            'gender' => $profile->gender ?? '-',
            'grade_level' => $profile->grade_level ?? '-',
            'section' => $profile->section ?? '-',
            'score' => $attempt->score,
            'total_points' => $attempt->total_points,
            'percentage' => $percentage,
        ];
    });

    return Excel::download(
        new QuizResultsExport($rows, $quiz->title),
        'quiz-results.xlsx'
    );
}

public function exportAnalytics($classId, $quizId)
{
    $quiz = Quiz::with(['questions', 'attempts.student'])
        ->findOrFail($quizId);

    $attemptIds = $quiz->attempts->pluck('id');

    $answers = StudentAnswer::whereIn('attempt_id', $attemptIds)->get();

    $totalAttempts = $quiz->attempts->where('status', 'completed')->count();

    $questionAnalytics = $quiz->questions->map(function ($question) use ($answers, $totalAttempts) {

        $questionAnswers = $answers->where('question_id', $question->id);

        $correctCount = $questionAnswers->where('is_correct', true)->count();
        $attemptedCount = $questionAnswers->count();

        $avgPoints = $attemptedCount > 0
            ? round($questionAnswers->avg('points_earned'), 2)
            : 0;

        $correctRate = $totalAttempts > 0
            ? round(($correctCount / $totalAttempts) * 100, 2)
            : 0;

        return [
            'order' => $question->order,
            'question_text' => $question->question_text,
            'question_type' => $question->question_type,
            'points' => $question->points,
            'attempted_count' => $attemptedCount,
            'correct_count' => $correctCount,
            'correct_rate' => $correctRate,
            'average_points' => $avgPoints,
            'difficulty' => $correctRate >= 80 ? 'Easy' : ($correctRate >= 50 ? 'Moderate' : 'Difficult'),
        ];
    });

    return Excel::download(
        new QuizAnalyticsExport($questionAnalytics),
        'quiz-analytics.xlsx'
    );
}
}

