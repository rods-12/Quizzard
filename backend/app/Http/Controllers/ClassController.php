<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\StudentAnswer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Exports\StudentPerformanceExport;
use Maatwebsite\Excel\Facades\Excel;

class ClassController extends Controller
{
    // Get all classes for the teacher
    public function index(Request $request)
    {
        $classes = ClassRoom::where('teacher_id', $request->user()->id)
            ->withCount('students')
            ->withCount('quizzes')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['classes' => $classes]);
    }

    // Create a new class
    public function store(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string',
        ], [
            'name.max' => 'Class name must not exceed 100 characters.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $class = ClassRoom::create([
            'teacher_id'  => $request->user()->id,
            'name'        => $request->name,
            'description' => $request->description,
        ]);

        return response()->json([
            'message' => 'Class created successfully.',
            'class'   => $class->loadCount(['students', 'quizzes']),
        ], 201);
    }

    // Get a single class with full details
    public function show(Request $request, $classId)
    {
        $class = ClassRoom::where('id', $classId)
            ->where('teacher_id', $request->user()->id)
            ->with(['students' => function ($q) {
                $q->select('users.id', 'users.name', 'users.email')
                ->orderBy('users.name');
            }])
            ->with(['quizzes' => function ($q) {
                $q->select('quizzes.id', 'quizzes.title', 'quizzes.is_published')
                ->withCount('questions')
                ->withPivot('due_date', 'assigned_at');
            }])
            ->firstOrFail();

        // Get student IDs in this class for class-scoped attempt counting
        $studentIds = $class->students()->pluck('users.id')->toArray();

        // Attach class_attempts_count to each quiz
        foreach ($class->quizzes as $quiz) {
            $quiz->class_attempts_count = QuizAttempt::where('quiz_id', $quiz->id)
                ->whereIn('student_id', $studentIds)
                ->count();
        }

        return response()->json(['class' => $class]);
    }
    // Update a class
    public function update(Request $request, $classId)
    {
        $class = ClassRoom::where('id', $classId)
            ->where('teacher_id', $request->user()->id)
            ->firstOrFail();

        $request->validate([
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string',
        ]);

        $class->update([
            'name'        => $request->name,
            'description' => $request->description,
        ]);

        return response()->json([
            'message' => 'Class updated successfully.',
            'class'   => $class->loadCount(['students', 'quizzes']),
        ]);
    }

    // Delete a class and cascade related student data
    public function destroy(Request $request, $classId)
    {
        $class = ClassRoom::where('id', $classId)
            ->where('teacher_id', $request->user()->id)
            ->with('quizzes')
            ->firstOrFail();

        // Get all quiz IDs assigned to this class
        $quizIds = $class->quizzes->pluck('id')->toArray();

        if (!empty($quizIds)) {
            // Get all student IDs in this class
            $studentIds = $class->students()->pluck('users.id')->toArray();

            // Get all attempt IDs for students in this class for these quizzes
            $attemptIds = QuizAttempt::whereIn('quiz_id', $quizIds)
                ->whereIn('student_id', $studentIds)
                ->pluck('id')
                ->toArray();

            // Delete student answers tied to those attempts
            if (!empty($attemptIds)) {
                StudentAnswer::whereIn('attempt_id', $attemptIds)->delete();
            }

            // Delete quiz attempts
            QuizAttempt::whereIn('quiz_id', $quizIds)
                ->whereIn('student_id', $studentIds)
                ->delete();

            // Detach all quizzes from the class
            $class->quizzes()->detach();
        }

        // Detach all students from the class
        $class->students()->detach();

        // Delete the class
        $class->delete();

        return response()->json([
            'message' => 'Class deleted successfully.',
        ]);
    }

    // Assign a quiz to a class
    public function assignQuiz(Request $request, $classId)
    {
        $class = ClassRoom::where('id', $classId)
            ->where('teacher_id', $request->user()->id)
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'quiz_id'  => 'required|integer|exists:quizzes,id',
            'due_date' => 'nullable|date|after:now',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $quiz = Quiz::where('id', $request->quiz_id)
            ->where('teacher_id', $request->user()->id)
            ->firstOrFail();

        if ($class->quizzes()->where('quiz_id', $quiz->id)->exists()) {
            return response()->json([
                'message' => 'Quiz is already assigned to this class.',
            ], 409);
        }

        $class->quizzes()->attach($quiz->id, [
            'assigned_at' => now(),
            'due_date'    => $request->due_date,
        ]);

        return response()->json([
            'message' => 'Quiz assigned to class successfully.',
        ]);
    }

    public function updateDueDate(Request $request, $classId, $quizId)
    {
        $class = ClassRoom::where('id', $classId)
            ->where('teacher_id', $request->user()->id)
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'due_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $assigned = $class->quizzes()->where('quiz_id', $quizId)->exists();

        if (!$assigned) {
            return response()->json([
                'success' => false,
                'message' => 'Quiz is not assigned to this class.',
            ], 404);
        }

        // Parse ISO 8601 and convert to UTC for consistent storage
        $dueDate = $request->due_date;
        if ($dueDate !== null) {
            $dueDate = \Carbon\Carbon::parse($dueDate)->utc()->format('Y-m-d H:i:s');
        }

        $class->quizzes()->updateExistingPivot($quizId, [
            'due_date' => $dueDate,
        ]);

        return response()->json([
            'success' => true,  // <-- Added this
            'message' => 'Due date updated successfully.',
        ]);
    }

    // Unassign a quiz from a class
    public function unassignQuiz(Request $request, $classId, $quizId)
    {
        $class = ClassRoom::where('id', $classId)
            ->where('teacher_id', $request->user()->id)
            ->firstOrFail();

        $class->quizzes()->detach($quizId);

        return response()->json([
            'message' => 'Quiz removed from class successfully.',
        ]);
    }



        /**
     * Get quiz results for all students in a specific class.
     * Returns every student with their score, or 0 if not taken.
     */
    public function classQuizResults(Request $request, $classId, $quizId)
    {
        $teacherId = $request->user()->id;

        // Verify class belongs to teacher
        $class = ClassRoom::where('id', $classId)
            ->where('teacher_id', $teacherId)
            ->firstOrFail();

        // Verify quiz is assigned to this class and belongs to teacher
        $quiz = Quiz::where('id', $quizId)
            ->where('teacher_id', $teacherId)
            ->firstOrFail();

        $isAssigned = $class->quizzes()->where('quiz_id', $quizId)->exists();

        if (!$isAssigned) {
            return response()->json([
                'success' => false,
                'message' => 'Quiz is not assigned to this class.',
            ], 404);
        }

        // Calculate total points from quiz questions
        $totalPoints = (float) $quiz->questions()->sum('points') ?: 0;

        // Get all students in the class with their profile
        $students = $class->students()
            ->with('studentProfile')
            ->get();

        // Get all completed attempts for this quiz by these students
        $attempts = QuizAttempt::where('quiz_id', $quizId)
            ->whereIn('student_id', $students->pluck('id'))
            ->where('status', 'completed')
            ->get()
            ->keyBy('student_id');

        // Build results for every student
        $studentResults = $students->map(function ($student) use ($attempts, $totalPoints) {
            $attempt = $attempts->get($student->id);
            $hasTaken = $attempt !== null;
            $score = $hasTaken ? ($attempt->score ?? 0) : 0;

            $percentage = $totalPoints > 0
                ? round(($score / $totalPoints) * 100, 1)
                : 0;

            return [
                'student_id'   => $student->studentProfile?->student_id ?? $student->id,
                'first_name'   => $student->first_name,
                'surname'      => $student->surname,
                'has_taken'    => $hasTaken,
                'score'        => $score,
                'total_points' => $totalPoints,
                'percentage'   => $percentage,
            ];
        });

        // Calculate summary stats
        $takenCount = $studentResults->where('has_taken', true)->count();
        $notTakenCount = $studentResults->count() - $takenCount;
        $averageScore = $takenCount > 0
            ? round($studentResults->where('has_taken', true)->avg('score'), 1)
            : 0;
        $averagePercentage = $takenCount > 0
            ? round($studentResults->where('has_taken', true)->avg('percentage'), 1)
            : 0;

        return response()->json([
            'success' => true,
            'quiz' => [
                'id'    => $quiz->id,
                'title' => $quiz->title,
            ],
            'total_points'       => $totalPoints,
            'total_students'     => $students->count(),
            'taken_count'        => $takenCount,
            'not_taken_count'    => $notTakenCount,
            'average_score'      => $averageScore,
            'average_percentage' => $averagePercentage,
            'students'           => $studentResults->values(),
        ]);
    }

    /**
     * Get all students in a class with their overall performance metrics.
     */
    public function studentPerformance(Request $request, $classId)
    {
        $teacherId = $request->user()->id;

        $class = ClassRoom::where('id', $classId)
            ->where('teacher_id', $teacherId)
            ->with(['students.studentProfile', 'quizzes'])
            ->firstOrFail();

        $students = $class->students;
        $totalQuizzes = $class->quizzes->count();
        $quizIds = $class->quizzes->pluck('id');

        // Get all completed attempts for this class's quizzes
        $attempts = QuizAttempt::whereIn('quiz_id', $quizIds)
            ->where('status', 'completed')
            ->get()
            ->groupBy('student_id');

        $studentData = $students->map(function ($student) use ($attempts, $totalQuizzes) {
            $studentAttempts = $attempts->get($student->id, collect());
            $quizzesTaken = $studentAttempts->unique('quiz_id')->count();

            // Calculate overall percentage across all attempts
            $overallPercentage = 0;
            if ($studentAttempts->isNotEmpty()) {
                $overallPercentage = round($studentAttempts->avg(function ($attempt) {
                    return $attempt->total_points > 0
                        ? ($attempt->score / $attempt->total_points) * 100
                        : 0;
                }), 2);
            }

            return [
                'student_id' => $student->studentProfile?->student_id ?? 'STU-' . strtoupper(substr(md5($student->id), 0, 6)),
                'first_name' => $student->first_name,
                'surname' => $student->surname,
                'email' => $student->email,
                'quizzes_taken' => $quizzesTaken,
                'total_quizzes' => $totalQuizzes,
                'overall_percentage' => $overallPercentage,
                'has_taken_any' => $quizzesTaken > 0,
            ];
        })->sortBy('surname')->values();

        // Summary stats
        $studentsWithAttempts = $studentData->where('has_taken_any', true);
        $classAverage = $studentsWithAttempts->count() > 0
            ? round($studentsWithAttempts->avg('overall_percentage'), 2)
            : 0;

        return response()->json([
        'students' => $studentData,
        'summary' => [
            'total_students' => $studentData->count(),
            'total_quizzes' => $totalQuizzes,
            'class_average_percentage' => $classAverage,
            'students_with_attempts' => $studentsWithAttempts->count(),
        ],
    ]);
    }

    /**
     * Export student performance to Excel.
     */
    public function exportStudentPerformance(Request $request, $classId)
    {
        $teacherId = $request->user()->id;

        $class = ClassRoom::where('id', $classId)
            ->where('teacher_id', $teacherId)
            ->with(['students.studentProfile', 'quizzes'])
            ->firstOrFail();

        $students = $class->students;
        $totalQuizzes = $class->quizzes->count();
        $quizIds = $class->quizzes->pluck('id');

        $attempts = QuizAttempt::whereIn('quiz_id', $quizIds)
            ->where('status', 'completed')
            ->get()
            ->groupBy('student_id');

        $exportData = $students->map(function ($student) use ($attempts, $totalQuizzes) {
            $studentAttempts = $attempts->get($student->id, collect());
            $quizzesTaken = $studentAttempts->unique('quiz_id')->count();

            $overallPercentage = 0;
            if ($studentAttempts->isNotEmpty()) {
                $overallPercentage = round($studentAttempts->avg(function ($attempt) {
                    return $attempt->total_points > 0
                        ? ($attempt->score / $attempt->total_points) * 100
                        : 0;
                }), 2);
            }

            return [
                'student_id' => $student->studentProfile?->student_id ?? 'STU-' . strtoupper(substr(md5($student->id), 0, 6)),
                'first_name' => $student->first_name,
                'surname' => $student->surname,
                'quizzes_taken' => $quizzesTaken,
                'total_quizzes' => $totalQuizzes,
                'overall_percentage' => $overallPercentage,
            ];
        })->sortBy('surname')->values();

        $filename = str_replace(' ', '_', $class->name) . '_student_performance.xlsx';

        return Excel::download(
            new StudentPerformanceExport($exportData, $class->name),
            $filename
        );
    }


}
