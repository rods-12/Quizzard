<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\StudentAnswer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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
                ->withCount('attempts')
                ->withPivot('due_date', 'assigned_at');
            }])
            ->firstOrFail();

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
}
