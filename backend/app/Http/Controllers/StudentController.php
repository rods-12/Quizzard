<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\StudentProfile;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    // Get dashboard data for the logged-in student
    public function dashboard(Request $request)
    {
        $student = $request->user();

        // Get only published quizzes assigned to classes the student is enrolled in
        $availableQuizzes = Quiz::where('is_published', true)
            ->whereHas('classes', function ($query) use ($student) {
                $query->whereHas('students', function ($studentQuery) use ($student) {
                    $studentQuery->where('student_id', $student->id);
                });
            })
            ->with('teacher:id,name')
            ->distinct()
            ->get()
            ->map(function ($quiz) use ($student) {
                $attempt = QuizAttempt::where('student_id', $student->id)
                    ->where('quiz_id', $quiz->id)
                    ->where('status', 'completed')
                    ->first();

                return [
                    'id'            => $quiz->id,
                    'title'         => $quiz->title,
                    'description'   => $quiz->description,
                    'teacher_name'  => $quiz->teacher->name ?? 'Unknown',
                    'already_taken' => $attempt ? true : false,
                    'score'         => $attempt ? $attempt->score : null,
                    'total_points'  => $attempt ? $attempt->total_points : null,
                ];
            });

        $recentScores = QuizAttempt::where('student_id', $student->id)
            ->where('status', 'completed')
            ->with('quiz:id,title')
            ->orderBy('completed_at', 'desc')
            ->take(5)
            ->get()
            ->map(function ($attempt) {
                return [
                    'quiz_title'   => $attempt->quiz->title ?? 'Unknown',
                    'score'        => $attempt->score,
                    'total_points' => $attempt->total_points,
                    'percentage'   => $attempt->total_points > 0
                        ? round(($attempt->score / $attempt->total_points) * 100)
                        : 0,
                    'completed_at' => $attempt->completed_at,
                ];
            });

        return response()->json([
            'student' => [
                'id'              => $student->id,
                'name'            => $student->name,
                'full_name'       => $student->name,
                'first_name'      => $student->first_name,
                'middle_initial'  => $student->middle_initial,
                'surname'         => $student->surname,
                'email'           => $student->email,
                'profile_picture' => $student->profile_picture,
            ],
            'available_quizzes'   => $availableQuizzes,
            'recent_scores'       => $recentScores,
            'total_quizzes_taken' => QuizAttempt::where('student_id', $student->id)
                ->where('status', 'completed')
                ->count(),
        ]);
    }

    // Get student profile
    public function getProfile(Request $request)
    {
        $student = $request->user();
        $profile = $student->studentProfile;

        return response()->json([
            'profile' => [
                'student_id'     => $profile?->student_id,
                'gender'         => $profile?->gender,
                'date_of_birth'  => $profile?->date_of_birth?->format('Y-m-d'),
                'contact_number' => $profile?->contact_number,
                'grade_level'    => $profile?->grade_level,
                'section'        => $profile?->section,
            ],
        ]);
    }

    // Update student profile
    public function updateProfile(Request $request)
    {
        $request->validate([
            'student_id'     => 'nullable|string|max:50',
            'gender'         => 'nullable|in:male,female,other',
            'date_of_birth'  => 'nullable|date',
            'contact_number' => 'nullable|string|max:20',
            'grade_level'    => 'nullable|in:Grade 7,Grade 8,Grade 9,Grade 10,Grade 11,Grade 12,Year 1,Year 2,Year 3,Year 4',
            'section'        => 'nullable|string|max:50',
        ]);

        $student = $request->user();

        $profile = StudentProfile::updateOrCreate(
            ['user_id' => $student->id],
            $request->only([
                'student_id',
                'gender',
                'date_of_birth',
                'contact_number',
                'grade_level',
                'section',
            ])
        );

        return response()->json([
            'message' => 'Profile updated successfully.',
            'profile' => [
                'student_id'     => $profile->student_id,
                'gender'         => $profile->gender,
                'date_of_birth'  => $profile->date_of_birth?->format('Y-m-d'),
                'contact_number' => $profile->contact_number,
                'grade_level'    => $profile->grade_level,
                'section'        => $profile->section,
            ],
        ]);
    }

    // Get all classes student is enrolled in
    public function myClasses(Request $request)
    {
        $student = $request->user();

        $classes = \App\Models\ClassRoom::whereHas('students', function ($q) use ($student) {
            $q->where('student_id', $student->id);
        })
        ->with(['teacher' => function ($q) {
            $q->select('id', 'name', 'email');
        }])
        ->withCount('students')
        ->withCount('quizzes')
        ->orderBy('created_at', 'desc')
        ->get();

        return response()->json([
            'classes' => $classes->map(function ($class) {
                return [
                    'id'                     => $class->id,
                    'name'                   => $class->name,
                    'description'            => $class->description,
                    'class_code'             => $class->class_code,
                    'teacher_name'           => $class->teacher->name,
                    'teacher_first_name'     => $class->teacher->first_name,
                    'teacher_middle_initial' => $class->teacher->middle_initial,
                    'teacher_surname'        => $class->teacher->surname,
                    'teacher_email'          => $class->teacher->email,
                    'students_count'         => $class->students_count,
                    'quizzes_count'          => $class->quizzes_count,
                ];
            }),
        ]);
    }

    // Join a class using class code
    public function joinClass(Request $request)
    {
        $request->validate([
            'class_code' => 'required|string',
        ]);

        $class = \App\Models\ClassRoom::where('class_code', strtoupper(trim($request->class_code)))
            ->first();

        if (!$class) {
            return response()->json([
                'message' => 'Invalid class code. Please check and try again.',
            ], 404);
        }

        if ($class->students()->where('student_id', $request->user()->id)->exists()) {
            return response()->json([
                'message' => 'You are already enrolled in this class.',
            ], 409);
        }

        $class->students()->attach($request->user()->id, [
            'joined_at' => now(),
        ]);

        return response()->json([
            'message' => 'Successfully joined the class!',
            'class'   => [
                'id'          => $class->id,
                'name'        => $class->name,
                'description' => $class->description,
                'class_code'  => $class->class_code,
            ],
        ]);
    }

    // Leave a class
    public function leaveClass(Request $request, $classId)
    {
        $class = \App\Models\ClassRoom::findOrFail($classId);

        if (!$class->students()->where('student_id', $request->user()->id)->exists()) {
            return response()->json([
                'message' => 'You are not enrolled in this class.',
            ], 404);
        }

        $class->students()->detach($request->user()->id);

        return response()->json([
            'message' => 'You have left the class successfully.',
        ]);
    }

    // Get all quizzes in a specific class
    public function classQuizzes(Request $request, $classId)
    {
        $student = $request->user();
        $class = \App\Models\ClassRoom::whereHas('students', function ($q) use ($student) {
            $q->where('student_id', $student->id);
        })->findOrFail($classId);

        $quizzes = $class->quizzes()
            ->where('is_published', true)
            ->withCount('questions')
            ->withPivot('due_date', 'assigned_at')
            ->get();

        $completedAttempts = \App\Models\QuizAttempt::where('student_id', $student->id)
            ->where('status', 'completed')
            ->whereIn('quiz_id', $quizzes->pluck('id'))
            ->get()
            ->keyBy('quiz_id');

        return response()->json([
            'class' => [
                'id'   => $class->id,
                'name' => $class->name,
            ],
            'quizzes' => $quizzes->map(function ($quiz) use ($completedAttempts) {
                $attempt = $completedAttempts->get($quiz->id);
                $alreadyTaken = $attempt !== null;

                return [
                    'id'              => $quiz->id,
                    'title'           => $quiz->title,
                    'description'     => $quiz->description,
                    'questions_count' => $quiz->questions_count,
                    'due_date'        => $quiz->pivot->due_date,
                    'already_taken'   => $alreadyTaken,
                    'score'           => $alreadyTaken ? $attempt->score : null,
                    'total_points'    => $alreadyTaken ? $attempt->total_points : null,
                ];
            }),
        ]);
    }



        // Get all quizzes across all enrolled classes
    public function allQuizzes(Request $request)
    {
        $student = $request->user();

        // Get all classes the student is enrolled in, with their published quizzes
        $classes = \App\Models\ClassRoom::whereHas('students', function ($q) use ($student) {
            $q->where('student_id', $student->id);
        })
        ->with(['quizzes' => function ($query) {
            $query->where('is_published', true)
                  ->withCount('questions')
                  ->withPivot('due_date', 'assigned_at');
        }, 'teacher:id,name,first_name,middle_initial,surname'])
        ->get();

        // Get all completed attempts for this student in one query
        $quizIds = $classes->pluck('quizzes.*.id')->flatten()->unique()->filter();
        $completedAttempts = \App\Models\QuizAttempt::where('student_id', $student->id)
            ->where('status', 'completed')
            ->whereIn('quiz_id', $quizIds)
            ->get()
            ->keyBy('quiz_id');

        $quizzes = [];

        foreach ($classes as $class) {
            foreach ($class->quizzes as $quiz) {
                $attempt = $completedAttempts->get($quiz->id);
                $alreadyTaken = $attempt !== null;

                $quizzes[] = [
                    'id'              => $quiz->id,
                    'title'           => $quiz->title,
                    'description'     => $quiz->description,
                    'questions_count' => $quiz->questions_count,
                    'due_date'        => $quiz->pivot->due_date,
                    'already_taken'   => $alreadyTaken,
                    'score'           => $alreadyTaken ? $attempt->score : null,
                    'total_points'    => $alreadyTaken ? $attempt->total_points : null,
                    'class_id'        => $class->id,
                    'class_name'      => $class->name,
                    'teacher_name'    => $class->teacher->name ?? 'Unknown',
                ];
            }
        }

        return response()->json([
            'quizzes' => $quizzes,
        ]);
    }


}
