<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\AnswerOption;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class AiQuizController extends Controller
{
    private string $apiKey;
    private string $apiUrl = 'https://api.groq.com/openai/v1/chat/completions';

    public function __construct()
    {
        $this->apiKey = env('GROQ_API_KEY');
    }

    // ─── POST /api/ai/generate-questions ─────────────────────────
    public function generate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'topic'            => 'nullable|string|max:500',
            'passage'          => 'nullable|string|max:10000',
            'num_questions'    => 'integer|min:1|max:30',
            'difficulty'       => 'required|in:easy,medium,hard',
            'question_types'   => 'required|array|min:1',
            'question_types.*' => 'in:multiple_choice,true_false,identification,matching',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        if (!$request->topic && !$request->passage) {
            return response()->json([
                'success' => false,
                'message' => 'Please provide a topic or passage.',
            ], 422);
        }

        $numQuestions   = $request->num_questions ?? 15;
        $difficulty     = $request->difficulty;
        $types          = $request->question_types;
        $typesFormatted = implode(', ', $types);

        $context = '';
        if ($request->topic)   $context .= "Topic: {$request->topic}\n";
        if ($request->passage) $context .= "Passage:\n{$request->passage}\n";

        $prompt = <<<PROMPT
            Generate exactly {$numQuestions} quiz questions based on the content below.

            Difficulty: {$difficulty}
            Question types to include (distribute evenly): {$typesFormatted}

            {$context}

            STRICT OUTPUT RULES:
            - Respond ONLY with a valid JSON object containing a "questions" array.
            - Each object in the "questions" array must follow this structure depending on type:

            For "multiple_choice":
            {
            "type": "multiple_choice",
            "question_text": "...",
            "points": 1,
            "options": [
                {"option_text": "...", "is_correct": false},
                {"option_text": "...", "is_correct": false},
                {"option_text": "...", "is_correct": false},
                {"option_text": "...", "is_correct": true}
            ]
            }

            For "true_false":
            {
            "type": "true_false",
            "question_text": "...",
            "points": 1,
            "correct_answer": true
            }

            For "identification":
            {
            "type": "identification",
            "question_text": "...",
            "points": 1,
            "answer": "..."
            }

            For "matching":
            {
            "type": "matching",
            "question_text": "Match the following:",
            "points": 2,
            "pairs": [
                {"left": "...", "right": "..."},
                {"left": "...", "right": "..."},
                {"left": "...", "right": "..."},
                {"left": "...", "right": "..."}
            ]
            }
        PROMPT;

        $payload = [
            'model'    => 'llama-3.3-70b-versatile',
            'messages' => [
                ['role' => 'system', 'content' => 'You are an expert quiz generator API. You output strictly in JSON format.'],
                ['role' => 'user',   'content' => $prompt],
            ],
            'temperature'     => 0.7,
            'response_format' => ['type' => 'json_object'],
        ];

        $response = Http::withHeaders([
            'Content-Type'  => 'application/json',
            'Authorization' => "Bearer {$this->apiKey}",
        ])->post($this->apiUrl, $payload);

        if (!$response->successful()) {
            $status = $response->status();
            return response()->json([
                'success' => false,
                'message' => $status === 429 ? 'AI service is busy. Please try again.' : 'Failed to connect to AI.',
                'error'   => $response->json(),
            ], $status === 429 ? 429 : 502);
        }

        $responseData = $response->json();
        $rawText      = $responseData['choices'][0]['message']['content'] ?? null;

        if (!$rawText) {
            return response()->json(['success' => false, 'message' => 'AI returned an empty response.'], 502);
        }

        $parsed = json_decode($rawText, true);

        if (json_last_error() !== JSON_ERROR_NONE || !isset($parsed['questions'])) {
            return response()->json([
                'success' => false,
                'message' => 'AI returned invalid JSON. Please try again.',
                'raw'     => $rawText,
            ], 502);
        }

        return response()->json([
            'success'   => true,
            'questions' => $parsed['questions'],
        ]);
    }

    // ─── POST /api/ai/quizzes/{quizId}/save-questions ─────────────
    public function saveQuestions(Request $request, $quizId)
    {
        $quiz = Quiz::findOrFail($quizId);

        if ($quiz->teacher_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        if (QuizAttempt::where('quiz_id', $quizId)->exists()) {
            return response()->json([
                'message' => 'This quiz cannot be modified because students have already taken it.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'questions'                => 'required|array|min:1',
            'questions.*.type'         => 'required|in:multiple_choice,true_false,identification,matching',
            'questions.*.question_text'=> 'required|string',
            'questions.*.points'       => 'required|integer|min:1',

            // multiple_choice
            'questions.*.options'               => 'sometimes|array|min:2',
            'questions.*.options.*.option_text' => 'sometimes|required|string',
            'questions.*.options.*.is_correct'  => 'sometimes|required|boolean',

            // true_false
            'questions.*.correct_answer' => 'sometimes|boolean',

            // identification
            'questions.*.answer' => 'sometimes|string',

            // matching
            'questions.*.pairs'        => 'sometimes|array|min:2',
            'questions.*.pairs.*.left' => 'sometimes|required|string',
            'questions.*.pairs.*.right'=> 'sometimes|required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $savedQuestions = [];
        $order = Question::where('quiz_id', $quizId)->count() + 1;

        foreach ($request->questions as $q) {
            $question = Question::create([
                'quiz_id'       => $quizId,
                'question_text' => $q['question_text'],
                'question_type' => $q['type'],
                'points'        => $q['points'],
                'order'         => $order++,
            ]);

            switch ($q['type']) {

                case 'multiple_choice':
                    foreach ($q['options'] as $i => $opt) {
                        AnswerOption::create([
                            'question_id' => $question->id,
                            'option_text' => $opt['option_text'],
                            'is_correct'  => $opt['is_correct'],
                            'order'       => $i + 1,
                        ]);
                    }
                    break;

                case 'true_false':
                    $correct = $q['correct_answer'];
                    AnswerOption::create(['question_id' => $question->id, 'option_text' => 'True',  'is_correct' => $correct === true,  'order' => 1]);
                    AnswerOption::create(['question_id' => $question->id, 'option_text' => 'False', 'is_correct' => $correct === false, 'order' => 2]);
                    break;

                case 'identification':
                    AnswerOption::create([
                        'question_id' => $question->id,
                        'option_text' => $q['answer'],
                        'is_correct'  => true,
                        'order'       => 1,
                    ]);
                    break;

                case 'matching':
                    foreach ($q['pairs'] as $i => $pair) {
                        AnswerOption::create([
                            'question_id' => $question->id,
                            'option_text' => $pair['left'],
                            'match_pair'  => $pair['right'],
                            'is_correct'  => true,
                            'order'       => $i + 1,
                        ]);
                    }
                    break;
            }

            $savedQuestions[] = $question->load('answerOptions');
        }

        return response()->json([
            'success'   => true,
            'message'   => count($savedQuestions) . ' question(s) saved to quiz successfully.',
            'questions' => $savedQuestions,
        ], 201);
    }
}
