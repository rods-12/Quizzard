<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class AiQuizController extends Controller
{
    private string $apiKey;
    private string $apiUrl  = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent';

    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY');
    }

    // POST /api/ai/generate-questions
    public function generate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'topic'          => 'nullable|string|max:500',
            'passage'        => 'nullable|string|max:10000',
            'file'           => 'nullable|file|mimes:pdf,png,jpg,jpeg|max:10240',
            'num_questions'  => 'integer|min:1|max:30',
            'difficulty'     => 'required|in:easy,medium,hard',
            'question_types' => 'required|array|min:1',
            'question_types.*' => 'in:multiple_choice,true_false,identification,matching',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        // At least one input source is required
        if (!$request->topic && !$request->passage && !$request->hasFile('file')) {
            return response()->json([
                'success' => false,
                'message' => 'Please provide a topic, passage, or file.',
            ], 422);
        }

        $numQuestions  = $request->num_questions ?? 15;
        $difficulty    = $request->difficulty;
        $types         = $request->question_types;

        // Build context string
        $context = '';
        if ($request->topic)   $context .= "Topic: {$request->topic}\n";
        if ($request->passage) $context .= "Passage:\n{$request->passage}\n";

        // Handle file upload — extract base64 for Gemini
        $filePart = null;
        if ($request->hasFile('file')) {
            $file      = $request->file('file');
            $mimeType  = $file->getMimeType();
            $base64    = base64_encode(file_get_contents($file->getRealPath()));
            $filePart  = [
                'inline_data' => [
                    'mime_type' => $mimeType,
                    'data'      => $base64,
                ]
            ];
        }

        $typesFormatted = implode(', ', $types);

        $prompt = <<<PROMPT
You are a quiz generator. Generate exactly {$numQuestions} quiz questions based on the content below.

Difficulty: {$difficulty}
Question types to include (distribute evenly): {$typesFormatted}

{$context}

STRICT OUTPUT RULES:
- Respond ONLY with a valid JSON array. No explanation, no markdown, no code fences.
- Each object in the array must have exactly this structure depending on type:

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

Rules:
- multiple_choice must have exactly 4 options and exactly 1 correct answer
- true_false correct_answer must be a boolean (true or false)
- identification answer must be a short specific word or phrase
- matching must have exactly 4 pairs
- Distribute question types as evenly as possible
- Do NOT include any text outside the JSON array
PROMPT;

        // Build Gemini request parts
        $parts = [];
        if ($filePart)        $parts[] = $filePart;
        $parts[] = ['text' => $prompt];

        $payload = [
            'contents' => [
                ['parts' => $parts]
            ],
            'generationConfig' => [
                'temperature'     => 0.7,
                'maxOutputTokens' => 8192,
            ]
        ];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post("{$this->apiUrl}?key={$this->apiKey}", $payload);

        if (!$response->successful()) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to connect to AI service.',
                'error'   => $response->body(),
            ], 502);
        }

        $responseData = $response->json();
        $rawText = $responseData['candidates'][0]['content']['parts'][0]['text'] ?? null;

        if (!$rawText) {
            return response()->json([
                'success' => false,
                'message' => 'AI returned an empty response.',
            ], 502);
        }

        // Strip markdown code fences if present
        $cleaned = preg_replace('/^```(?:json)?\s*/i', '', trim($rawText));
        $cleaned = preg_replace('/\s*```$/', '', $cleaned);

        $questions = json_decode(trim($cleaned), true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($questions)) {
            return response()->json([
                'success' => false,
                'message' => 'AI returned invalid JSON. Please try again.',
                'raw'     => $rawText,
            ], 502);
        }

        return response()->json([
            'success'   => true,
            'questions' => $questions,
        ]);
    }
}
