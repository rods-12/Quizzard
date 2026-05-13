<?php

namespace Database\Seeders;

use App\Models\ClassRoom;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class QuizzardDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🌱 Starting Quizzard Demo Seeder...');

        $now = now();

        // ── 0. DEMO TEACHER ─────────────────────────────────────────────
        $demoTeacher = User::firstOrCreate(
            ['email' => 'demoteacher@quizzard.com'],
            [
                'name'                  => 'Demo Teacher',
                'first_name'            => 'Demo',
                'middle_initial'        => 'D',
                'surname'               => 'Teacher',
                'password'              => Hash::make('DemoTeacher@1234'),
                'role'                  => 'teacher',
                'status'                => 'active',
                'failed_login_attempts' => 0,
                'locked_until'          => null,
                'profile_picture'       => null,
                'profile_image'         => null,
                'bio'                   => null,
            ]
        );

        // ── 0B. DEMO STUDENT ─────────────────────────────────────────────
        $demoStudent = User::firstOrCreate(
            ['email' => 'demostudent@quizzard.com'],
            [
                'name'                  => 'Demo Student',
                'first_name'            => 'Demo',
                'middle_initial'        => 'D',
                'surname'               => 'Student',
                'password'              => Hash::make('DemoStudent@1234'),
                'role'                  => 'student',
                'status'                => 'active',
                'failed_login_attempts' => 0,
                'locked_until'          => null,
                'profile_picture'       => null,
                'profile_image'         => null,
                'bio'                   => null,
            ]
        );

        \App\Models\StudentProfile::firstOrCreate(
            ['user_id' => $demoStudent->id],
            [
                'student_id'     => 'STU-DEMO1',
                'gender'         => 'male',
                'date_of_birth'  => '2000-01-01',
                'contact_number' => '09000000000',
                'grade_level'    => 'Grade 10',
                'section'        => 'Rizal',
            ]
        );

        // ── 1. TEACHERS ──────────────────────────────────────────────────
        $this->command->info('Creating 500 teachers...');
        $teachers   = User::factory()->teacher()->count(500)->create();
        $teacherIds = $teachers->pluck('id')->toArray();
        $teacherIds[] = $demoTeacher->id;

        // ── 2. STUDENTS ──────────────────────────────────────────────────
        $this->command->info('Creating 1000 students...');
        $students   = User::factory()->student()->count(1000)->create();
        $studentIds = $students->pluck('id')->toArray();
        $studentIds[] = $demoStudent->id;

        // ── 3. STUDENT PROFILES ──────────────────────────────────────────
        $this->command->info('Creating student profiles...');
        $genders    = ['male', 'female', 'other'];
        $grades     = ['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'];
        $sections   = ['Rizal', 'Bonifacio', 'Luna', 'Mabini', 'Aquino'];
        $profileRows = [];

        foreach ($studentIds as $i => $sid) {
            $profileRows[] = [
                'user_id'        => $sid,
                'student_id'     => 'STU-' . strtoupper(substr(md5($sid . $i), 0, 5)),
                'gender'         => $genders[array_rand($genders)],
                'date_of_birth'  => fake()->dateTimeBetween('-25 years', '-15 years')->format('Y-m-d'),
                'contact_number' => '09' . fake()->numerify('#########'),
                'grade_level'    => $grades[array_rand($grades)],
                'section'        => $sections[array_rand($sections)],
                'created_at'     => $now,
                'updated_at'     => $now,
            ];
        }
        // Skip demo student — already created above
        $profileRows = array_filter($profileRows, fn($r) => $r['user_id'] !== $demoStudent->id);
        foreach (array_chunk($profileRows, 500) as $chunk) {
            DB::table('student_profiles')->insertOrIgnore($chunk);
        }

        // ── 4. QUIZZES ───────────────────────────────────────────────────
        $this->command->info('Creating 1000 quizzes (2 per teacher)...');
        $quizIds = [];
        foreach ($teacherIds as $tid) {
            $quizzes = Quiz::factory()->forTeacher($tid)->count(2)->create();
            foreach ($quizzes->pluck('id') as $qid) {
                $quizIds[] = $qid;
            }
        }

        // ── 5. QUESTIONS + ANSWER OPTIONS ───────────────────────────────
        $this->command->info('Creating questions and answer options...');
        $questionRows = [];
        $types = [
            ['type' => 'multiple_choice', 'count' => 5],
            ['type' => 'true_false',      'count' => 5],
            ['type' => 'identification',  'count' => 5],
            ['type' => 'matching',        'count' => 5],
        ];

        foreach ($quizIds as $quizId) {
            $order = 1;
            foreach ($types as $tg) {
                for ($q = 0; $q < $tg['count']; $q++) {
                    $questionRows[] = [
                        'quiz_id'       => $quizId,
                        'question_type' => $tg['type'],
                        'question_text' => fake()->sentence() . '?',
                        'media_path'    => null,
                        'media_type'    => null,
                        'image_path'    => null,
                        'video_path'    => null,
                        'audio_path'    => null,
                        'points'        => fake()->numberBetween(1, 5),
                        'order'         => $order++,
                        'created_at'    => $now,
                        'updated_at'    => $now,
                    ];
                }
            }
        }

        foreach (array_chunk($questionRows, 500) as $chunk) {
            DB::table('questions')->insert($chunk);
        }

        // Fetch inserted questions
        $questions = DB::table('questions')
            ->whereIn('quiz_id', $quizIds)
            ->select('id', 'quiz_id', 'question_type', 'points')
            ->get();

        $answerRows = [];
        foreach ($questions as $q) {
            switch ($q->question_type) {
                case 'multiple_choice':
                    $correctIdx = rand(0, 3);
                    for ($o = 0; $o < 4; $o++) {
                        $answerRows[] = $this->answerRow($q->id, fake()->sentence(), $o === $correctIdx, null, $o + 1, $now);
                    }
                    break;

                case 'true_false':
                    $correctIdx = rand(0, 1);
                    foreach (['True', 'False'] as $o => $label) {
                        $answerRows[] = $this->answerRow($q->id, $label, $o === $correctIdx, null, $o + 1, $now);
                    }
                    break;

                case 'identification':
                    $answerRows[] = $this->answerRow($q->id, fake()->word(), true, null, 1, $now);
                    break;

                case 'matching':
                    for ($p = 0; $p < 4; $p++) {
                        $answerRows[] = $this->answerRow($q->id, fake()->word(), true, fake()->word(), $p + 1, $now);
                    }
                    break;
            }
        }

        foreach (array_chunk($answerRows, 1000) as $chunk) {
            DB::table('answer_options')->insert($chunk);
        }

        // ── 6. CLASSROOMS ────────────────────────────────────────────────
        $this->command->info('Creating 2500 classrooms (5 per teacher)...');
        $classroomIds = [];
        foreach ($teacherIds as $tid) {
            $classes = ClassRoom::factory()->forTeacher($tid)->count(5)->create();
            foreach ($classes->pluck('id') as $cid) {
                $classroomIds[] = $cid;
            }
        }

        // ── 7. ASSIGN STUDENTS TO CLASSES ───────────────────────────────
        $this->command->info('Assigning students to classrooms (20 per class)...');
        $classStudentRows = [];
        foreach ($classroomIds as $classId) {
            $shuffled = $studentIds;
            shuffle($shuffled);
            foreach (array_slice($shuffled, 0, 20) as $sid) {
                $classStudentRows[] = [
                    'class_id'   => $classId,
                    'student_id' => $sid,
                    'joined_at'  => $now,
                ];
            }
        }
        foreach (array_chunk($classStudentRows, 1000) as $chunk) {
            DB::table('class_students')->insertOrIgnore($chunk);
        }

        // ── 8. ASSIGN QUIZZES TO CLASSES ────────────────────────────────
        $this->command->info('Assigning quizzes to classrooms (up to 10 per class)...');
        $classQuizRows = [];

        $classroomTeacherMap = DB::table('classes')
            ->whereIn('id', $classroomIds)
            ->pluck('teacher_id', 'id')
            ->toArray();

        $quizzesByTeacher = DB::table('quizzes')
            ->whereIn('id', $quizIds)
            ->select('id', 'teacher_id')
            ->get()
            ->groupBy('teacher_id')
            ->map(fn($rows) => $rows->pluck('id')->toArray())
            ->toArray();

        foreach ($classroomIds as $classId) {
            $tid            = $classroomTeacherMap[$classId] ?? null;
            $teacherQuizIds = $quizzesByTeacher[$tid] ?? [];
            if (empty($teacherQuizIds)) continue;

            shuffle($teacherQuizIds);
            foreach (array_slice($teacherQuizIds, 0, min(10, count($teacherQuizIds))) as $quizId) {
                $classQuizRows[] = [
                    'class_id'     => $classId,
                    'quiz_id'      => $quizId,
                    'grading_mode' => rand(0, 2) === 0 ? 'manual' : 'automatic',
                    'assigned_at'  => $now,
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ];
            }
        }
        foreach (array_chunk($classQuizRows, 1000) as $chunk) {
            DB::table('class_quizzes')->insertOrIgnore($chunk);
        }

        // ── 9. QUIZ ATTEMPTS ─────────────────────────────────────────────
        $this->command->info('Creating quiz attempts (50% chance per student per quiz)...');
        $attemptRows = [];

        $quizTotalPoints = DB::table('questions')
            ->whereIn('quiz_id', $quizIds)
            ->selectRaw('quiz_id, SUM(points) as total_points')
            ->groupBy('quiz_id')
            ->pluck('total_points', 'quiz_id')
            ->toArray();

        $classStudentsMap  = DB::table('class_students')->get()->groupBy('class_id');
        $classQuizzesMap   = DB::table('class_quizzes')->get()->groupBy('class_id');
        $gradingModeMap    = DB::table('class_quizzes')
            ->get()
            ->groupBy('class_id')
            ->map(fn($rows) => $rows->pluck('grading_mode', 'quiz_id')->toArray())
            ->toArray();

        foreach ($classroomIds as $classId) {
            $studentsInClass = isset($classStudentsMap[$classId])
                ? $classStudentsMap[$classId]->pluck('student_id')->toArray()
                : [];
            $quizzesInClass  = isset($classQuizzesMap[$classId])
                ? $classQuizzesMap[$classId]->pluck('quiz_id')->toArray()
                : [];

            foreach ($quizzesInClass as $quizId) {
                $totalPoints = $quizTotalPoints[$quizId] ?? 20;
                $gradingMode = $gradingModeMap[$classId][$quizId] ?? 'automatic';

                foreach ($studentsInClass as $sid) {
                    if (rand(0, 1) !== 1) continue;

                    if ($gradingMode === 'manual') {
                        $status = ['submitted', 'under_review', 'reviewed'][rand(0, 2)];
                    } else {
                        $status = 'reviewed';
                    }

                    $attemptRows[] = [
                        'quiz_id'          => $quizId,
                        'student_id'       => $sid,
                        'score'            => $status === 'reviewed' ? rand(0, $totalPoints) : 0,
                        'total_points'     => $totalPoints,
                        'status'           => $status,
                        'reviewed_at'      => $status === 'reviewed' ? $now : null,
                        'reviewed_by'      => null,
                        'teacher_feedback' => $status === 'reviewed' ? 'Great job on this quiz!' : null,
                        'started_at'       => $now,
                        'completed_at'     => $now,
                        'created_at'       => $now,
                        'updated_at'       => $now,
                    ];
                }
            }
        }

        foreach (array_chunk($attemptRows, 1000) as $chunk) {
            DB::table('quiz_attempts')->insert($chunk);
        }

        $totalAttempts = count($attemptRows);
        $this->command->info("Created {$totalAttempts} quiz attempts.");

        // ── 10. STUDENT ANSWERS (20% of attempts) ───────────────────────
        $this->command->info('Creating student answers for 20% of attempts...');

        // Fetch inserted attempt IDs
        $allAttemptIds = DB::table('quiz_attempts')
            ->whereIn('quiz_id', $quizIds)
            ->pluck('id', 'id') // id => id
            ->toArray();

        // Sample 20%
        shuffle($allAttemptIds);
        $sampledAttemptIds = array_slice($allAttemptIds, 0, (int) ceil(count($allAttemptIds) * 0.20));

        // Load attempt -> quiz mapping for sampled attempts
        $attemptQuizMap = DB::table('quiz_attempts')
            ->whereIn('id', $sampledAttemptIds)
            ->pluck('quiz_id', 'id')
            ->toArray();

        // Load attempt -> status mapping
        $attemptStatusMap = DB::table('quiz_attempts')
            ->whereIn('id', $sampledAttemptIds)
            ->pluck('status', 'id')
            ->toArray();

        // Load questions grouped by quiz_id
        $questionsByQuiz = DB::table('questions')
            ->whereIn('quiz_id', array_unique(array_values($attemptQuizMap)))
            ->select('id', 'quiz_id', 'question_type', 'points')
            ->get()
            ->groupBy('quiz_id');

        // Load correct answers grouped by question_id (for identification/MC/TF)
        $correctAnswersByQuestion = DB::table('answer_options')
            ->where('is_correct', true)
            ->whereIn('question_id', DB::table('questions')->whereIn('quiz_id', array_unique(array_values($attemptQuizMap)))->pluck('id'))
            ->select('question_id', 'option_text')
            ->get()
            ->groupBy('question_id')
            ->map(fn($opts) => $opts->pluck('option_text')->toArray())
            ->toArray();

        $studentAnswerRows = [];
        $reviewableAnswers = []; // [attempt_id => [student_answer index,...]] for identification/matching in reviewed attempts

        foreach ($sampledAttemptIds as $attemptId) {
            $quizId  = $attemptQuizMap[$attemptId] ?? null;
            $status  = $attemptStatusMap[$attemptId] ?? 'reviewed';
            if (!$quizId || !isset($questionsByQuiz[$quizId])) continue;

            foreach ($questionsByQuiz[$quizId] as $q) {
                $isCorrect    = (bool) rand(0, 1);
                $correctOpts  = $correctAnswersByQuestion[$q->id] ?? [];
                $answerGiven  = null;
                $pointsEarned = 0;
                $justification = null;

                switch ($q->question_type) {
                    case 'multiple_choice':
                    case 'true_false':
                        if ($isCorrect && !empty($correctOpts)) {
                            $answerGiven  = $correctOpts[0];
                            $pointsEarned = $q->points;
                        } else {
                            $answerGiven  = fake()->word();
                            $pointsEarned = 0;
                            $isCorrect    = false;
                        }
                        break;

                    case 'identification':
                        $answerGiven   = $isCorrect && !empty($correctOpts)
                            ? $correctOpts[0]
                            : fake()->word();
                        $pointsEarned  = 0; // teacher grades manually or auto
                        $justification = fake()->boolean(30) ? fake()->sentence() : null;
                        break;

                    case 'matching':
                        // Store as JSON pairs
                        $pairs = [];
                        $matchOpts = DB::table('answer_options')
                            ->where('question_id', $q->id)
                            ->select('option_text', 'match_pair')
                            ->get();
                        foreach ($matchOpts as $pair) {
                            $pairs[$pair->option_text] = $isCorrect
                                ? $pair->match_pair
                                : fake()->word();
                        }
                        $answerGiven  = json_encode($pairs);
                        $pointsEarned = 0;
                        break;
                }

                $rowIndex = count($studentAnswerRows);
                $studentAnswerRows[] = [
                    'attempt_id'    => $attemptId,
                    'question_id'   => $q->id,
                    'answer_given'  => $answerGiven,
                    'is_correct'    => $isCorrect,
                    'points_earned' => $pointsEarned,
                    'justification' => $justification,
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ];

                // Track identification/matching rows in reviewed attempts for review seeding
                if (
                    $status === 'reviewed' &&
                    in_array($q->question_type, ['identification', 'matching'])
                ) {
                    $reviewableAnswers[$attemptId][] = $rowIndex;
                }
            }
        }

        $this->command->info('Inserting ' . count($studentAnswerRows) . ' student answer rows...');
        foreach (array_chunk($studentAnswerRows, 1000) as $chunk) {
            DB::table('student_answers')->insert($chunk);
        }

        // ── 11. STUDENT ANSWER REVIEWS ───────────────────────────────────
        $this->command->info('Creating student answer reviews for identification/matching in reviewed attempts...');

        // Fetch the real IDs of the inserted student answers we need to review.
        // We match on attempt_id + question_id since we don't have the IDs yet.
        $reviewAttemptIds = array_keys($reviewableAnswers);

        if (!empty($reviewAttemptIds)) {
            $answersNeedingReview = DB::table('student_answers')
                ->whereIn('attempt_id', $reviewAttemptIds)
                ->whereIn(
                    'question_id',
                    DB::table('questions')
                        ->whereIn('question_type', ['identification', 'matching'])
                        ->pluck('id')
                )
                ->select('id', 'attempt_id')
                ->get();

            // Map attempt_id -> teacher_id via quiz_attempts -> quiz -> teacher
            $attemptTeacherMap = DB::table('quiz_attempts as qa')
                ->join('quizzes as qz', 'qa.quiz_id', '=', 'qz.id')
                ->whereIn('qa.id', $reviewAttemptIds)
                ->pluck('qz.teacher_id', 'qa.id')
                ->toArray();

            $reviewRows = [];
            foreach ($answersNeedingReview as $sa) {
                $teacherId = $attemptTeacherMap[$sa->attempt_id] ?? null;
                $reviewRows[] = [
                    'student_answer_id' => $sa->id,
                    'teacher_id'        => $teacherId,
                    'points_awarded'    => fake()->randomFloat(2, 0, 5),
                    'feedback'          => fake()->boolean(60) ? fake()->sentence() : null,
                    'reviewed_at'       => $now,
                    'created_at'        => $now,
                    'updated_at'        => $now,
                ];
            }

            $this->command->info('Inserting ' . count($reviewRows) . ' review rows...');
            foreach (array_chunk($reviewRows, 1000) as $chunk) {
                DB::table('student_answer_reviews')->insert($chunk);
            }
        }

        $this->command->info('✅ Quizzard Demo Seeder complete!');
        $this->command->info('Teachers: 500 | Students: 1000 | Classes: 2500 | Quizzes: ~1000');
    }

    // ── HELPERS ──────────────────────────────────────────────────────────

    private function answerRow(
        int $questionId,
        string $optionText,
        bool $isCorrect,
        ?string $matchPair,
        int $order,
        $now
    ): array {
        return [
            'question_id' => $questionId,
            'option_text' => $optionText,
            'is_correct'  => $isCorrect,
            'match_pair'  => $matchPair,
            'order'       => $order,
            'image_path'  => null,
            'video_path'  => null,
            'audio_path'  => null,
            'created_at'  => $now,
            'updated_at'  => $now,
        ];
    }
}
