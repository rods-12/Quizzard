<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\StudentProfile;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function dashboard(Request $request)
    {
        $student = $request->user();

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
                    ->whereIn('status', [
                        QuizAttempt::STATUS_SUBMITTED,
                        QuizAttempt::STATUS_UNDER_REVIEW,
                        QuizAttempt::STATUS_REVIEWED,
                    ])
                    ->orderByDesc('created_at')
                    ->first();

                $reviewed = $attempt && $attempt->status === QuizAttempt::STATUS_REVIEWED;

                return [
                    'id'            => $quiz->id,
                    'title'         => $quiz->title,
                    'description'   => $quiz->description,
                    'teacher_name'  => $quiz->teacher->name ?? 'Unknown',
                    'already_taken' => $attempt ? true : false,
                    'status'        => $attempt ? $attempt->status : null,
                    'score'         => $reviewed ? $attempt->score : null,
                    'total_points'  => $reviewed ? $attempt->total_points : null,
                ];
            });

        $recentScores = QuizAttempt::where('student_id', $student->id)
            ->where('status', QuizAttempt::STATUS_REVIEWED)
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
                ->where('status', QuizAttempt::STATUS_REVIEWED)
                ->count(),
        ]);
    }

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

        $attempts = QuizAttempt::where('student_id', $student->id)
            ->whereIn('status', [
                QuizAttempt::STATUS_SUBMITTED,
                QuizAttempt::STATUS_UNDER_REVIEW,
                QuizAttempt::STATUS_REVIEWED,
            ])
            ->whereIn('quiz_id', $quizzes->pluck('id'))
            ->get()
            ->keyBy('quiz_id');

        return response()->json([
            'class' => [
                'id'   => $class->id,
                'name' => $class->name,
            ],
            'quizzes' => $quizzes->map(function ($quiz) use ($attempts) {
                $attempt  = $attempts->get($quiz->id);
                $reviewed = $attempt && $attempt->status === QuizAttempt::STATUS_REVIEWED;

                return [
                    'id'              => $quiz->id,
                    'title'           => $quiz->title,
                    'description'     => $quiz->description,
                    'questions_count' => $quiz->questions_count,
                    'due_date'        => $quiz->pivot->due_date,
                    'already_taken'   => $attempt ? true : false,
                    'status'          => $attempt ? $attempt->status : null,
                    'score'           => $reviewed ? $attempt->score : null,
                    'total_points'    => $reviewed ? $attempt->total_points : null,
                ];
            }),
        ]);
    }

    public function allQuizzes(Request $request)
    {
        $student = $request->user();

        $classes = \App\Models\ClassRoom::whereHas('students', function ($q) use ($student) {
            $q->where('student_id', $student->id);
        })
        ->with(['quizzes' => function ($query) {
            $query->where('is_published', true)
                  ->withCount('questions')
                  ->withPivot('due_date', 'assigned_at');
        }, 'teacher:id,name,first_name,middle_initial,surname'])
        ->get();

        $quizIds = $classes->pluck('quizzes.*.id')->flatten()->unique()->filter();

        $attempts = QuizAttempt::where('student_id', $student->id)
            ->whereIn('status', [
                QuizAttempt::STATUS_SUBMITTED,
                QuizAttempt::STATUS_UNDER_REVIEW,
                QuizAttempt::STATUS_REVIEWED,
            ])
            ->whereIn('quiz_id', $quizIds)
            ->get()
            ->keyBy('quiz_id');

        $quizzes = [];

        foreach ($classes as $class) {
            foreach ($class->quizzes as $quiz) {
                $attempt  = $attempts->get($quiz->id);
                $reviewed = $attempt && $attempt->status === QuizAttempt::STATUS_REVIEWED;

                $quizzes[] = [
                    'id'              => $quiz->id,
                    'title'           => $quiz->title,
                    'description'     => $quiz->description,
                    'questions_count' => $quiz->questions_count,
                    'due_date'        => $quiz->pivot->due_date,
                    'already_taken'   => $attempt ? true : false,
                    'status'          => $attempt ? $attempt->status : null,
                    'score'           => $reviewed ? $attempt->score : null,
                    'total_points'    => $reviewed ? $attempt->total_points : null,
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

    public function getAttempt(Request $request, $attemptId)
    {
        $student = $request->user();

        $attempt = QuizAttempt::where('id', $attemptId)
            ->where('student_id', $student->id)
            ->whereIn('status', [
                QuizAttempt::STATUS_SUBMITTED,
                QuizAttempt::STATUS_UNDER_REVIEW,
                QuizAttempt::STATUS_REVIEWED,
            ])
            ->with([
                'quiz:id,title,description',
                'answers.question',
                'answers.review',
            ])
            ->first();

        if (!$attempt) {
            return response()->json(['message' => 'Attempt not found.'], 404);
        }

        $isReviewed = $attempt->status === QuizAttempt::STATUS_REVIEWED;

        $answers = $attempt->answers->map(function ($answer) use ($isReviewed) {
            $base = [
                'question_id'   => $answer->question_id,
                'question_text' => $answer->question->question_text ?? null,
                'question_type' => $answer->question->question_type ?? null,
                'answer_given'  => $answer->answer_given,
                'justification' => $answer->justification,
            ];

            if ($isReviewed) {
                $base['is_correct']     = $answer->is_correct;
                $base['points_earned']  = $answer->review
                    ? $answer->review->points_awarded
                    : $answer->points_earned;
                $base['feedback']       = $answer->review->feedback ?? null;
            }

            return $base;
        });

        $response = [
            'attempt_id'       => $attempt->id,
            'quiz'             => [
                'id'          => $attempt->quiz->id,
                'title'       => $attempt->quiz->title,
                'description' => $attempt->quiz->description,
            ],
            'status'           => $attempt->status,
            'started_at'       => $attempt->started_at,
            'submitted_at'     => $attempt->completed_at,
            'answers'          => $answers,
        ];

        if ($isReviewed) {
            $response['score']            = $attempt->score;
            $response['total_points']     = $attempt->total_points;
            $response['percentage']       = $attempt->total_points > 0
                ? round(($attempt->score / $attempt->total_points) * 100, 2)
                : 0;
            $response['teacher_feedback'] = $attempt->teacher_feedback;
            $response['reviewed_at']      = $attempt->reviewed_at;
        } else {
            $response['message'] = 'Your attempt is pending review by your teacher.';
        }

        return response()->json($response);
    }
}
