<?php

namespace Database\Seeders;

use App\Models\AnswerOption;
use App\Models\ClassRoom;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QuizzardDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🌱 Starting Quizzard Demo Seeder...');

        // ── 0. CREATE FALLBACK DEMO TEACHER ────────────────────────────
        User::firstOrCreate(
            ['email' => 'demoteacher@quizzard.com'],
            [
                'name'                  => 'Demo Teacher',
                'first_name'            => 'Demo',
                'middle_initial'        => 'D',
                'surname'               => 'Teacher',
                'password'              => \Illuminate\Support\Facades\Hash::make('DemoTeacher@1234'),
                'role'                  => 'teacher',
                'status'               => 'active',
                'failed_login_attempts' => 0,
                'locked_until'          => null,
                'profile_picture'       => null,
                'profile_image'         => null,
                'bio'                   => null,
            ]
        );


        // ── 0B. CREATE FALLBACK DEMO STUDENT ───────────────────────────
        $demoStudent = User::firstOrCreate(
            ['email' => 'demostudent@quizzard.com'],
            [
                'name'                  => 'Demo Student',
                'first_name'            => 'Demo',
                'middle_initial'        => 'D',
                'surname'               => 'Student',
                'password'              => \Illuminate\Support\Facades\Hash::make('DemoStudent@1234'),
                'role'                  => 'student',
                'status'               => 'active',
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


        // ── 1. CREATE TEACHERS ──────────────────────────────────────────
        $this->command->info('Creating 500 teachers...');
        $teachers = User::factory()->teacher()->count(500)->create();
        $demoTeacher = User::where('email', 'demoteacher@quizzard.com')->first();
        $teacherIds = $teachers->pluck('id')->toArray();
        if ($demoTeacher) {
            $teacherIds[] = $demoTeacher->id;
        }

        // ── 2. CREATE STUDENTS ──────────────────────────────────────────
        $this->command->info('Creating 1000 students...');
        $students = User::factory()->student()->count(1000)->create();
        $demoStudent = User::where('email', 'demostudent@quizzard.com')->first();
        $studentIds = $students->pluck('id')->toArray();
        if ($demoStudent) {
            $studentIds[] = $demoStudent->id;
        }

        // ── 3. CREATE STUDENT PROFILES ──────────────────────────────────
        $this->command->info('Creating student profiles...');
        $profileRows = [];
        $now = now();
        $genders = ['male', 'female', 'other'];
        $gradeLevels = ['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'];
        $sections = ['Rizal', 'Bonifacio', 'Luna', 'Mabini', 'Aquino'];

        foreach ($studentIds as $i => $studentId) {
            $profileRows[] = [
                'user_id'        => $studentId,
                'student_id'     => 'STU-' . strtoupper(substr(md5($studentId . $i), 0, 5)),
                'gender'         => $genders[array_rand($genders)],
                'date_of_birth'  => fake()->dateTimeBetween('-25 years', '-15 years')->format('Y-m-d'),
                'contact_number' => '09' . fake()->numerify('#########'),
                'grade_level'    => $gradeLevels[array_rand($gradeLevels)],
                'section'        => $sections[array_rand($sections)],
                'created_at'     => $now,
                'updated_at'     => $now,
            ];
        }
        DB::table('student_profiles')->insert($profileRows);

        // ── 4. CREATE QUIZZES ───────────────────────────────────────────
        $this->command->info('Creating 1000 quizzes...');
        $quizIds = [];
        $quizzesPerTeacher = intdiv(1000, 500); // 2 per teacher
        foreach ($teacherIds as $teacherId) {
            $quizzes = Quiz::factory()->forTeacher($teacherId)->count($quizzesPerTeacher)->create();
            foreach ($quizzes->pluck('id')->toArray() as $qid) {
                $quizIds[] = $qid;
            }
        }

        // ── 5. CREATE QUESTIONS + ANSWER OPTIONS ────────────────────────
        $this->command->info('Creating questions and answer options for all quizzes...');
        $questionRows = [];
        $answerRows = [];

        foreach ($quizIds as $quizId) {
            $order = 1;
            $types = [
                ['type' => 'multiple_choice',  'count' => 5],
                ['type' => 'true_false',        'count' => 5],
                ['type' => 'identification',    'count' => 5],
                ['type' => 'matching',          'count' => 5],
            ];

            foreach ($types as $typeGroup) {
                for ($q = 0; $q < $typeGroup['count']; $q++) {
                    $questionRows[] = [
                        'quiz_id'       => $quizId,
                        'question_type' => $typeGroup['type'],
                        'question_text' => fake()->sentence() . '?',
                        'points'        => fake()->numberBetween(1, 5),
                        'order'         => $order++,
                        'image_path'    => null,
                        'video_path'    => null,
                        'audio_path'    => null,
                        'created_at'    => $now,
                        'updated_at'    => $now,
                    ];
                }
            }
        }

        // Bulk insert questions
        foreach (array_chunk($questionRows, 500) as $chunk) {
            DB::table('questions')->insert($chunk);
        }

        // Fetch inserted question IDs with their types
        $questions = DB::table('questions')
            ->whereIn('quiz_id', $quizIds)
            ->select('id', 'question_type')
            ->get();

        foreach ($questions as $question) {
            switch ($question->question_type) {
                case 'multiple_choice':
                    $correctIndex = rand(0, 3);
                    for ($o = 0; $o < 4; $o++) {
                        $answerRows[] = [
                            'question_id' => $question->id,
                            'option_text' => fake()->sentence(),
                            'is_correct'  => $o === $correctIndex,
                            'match_pair'  => null,
                            'order'       => $o + 1,
                            'image_path'  => null,
                            'video_path'  => null,
                            'audio_path'  => null,
                            'created_at'  => $now,
                            'updated_at'  => $now,
                        ];
                    }
                    break;

                case 'true_false':
                    $correctIndex = rand(0, 1);
                    foreach (['True', 'False'] as $o => $label) {
                        $answerRows[] = [
                            'question_id' => $question->id,
                            'option_text' => $label,
                            'is_correct'  => $o === $correctIndex,
                            'match_pair'  => null,
                            'order'       => $o + 1,
                            'image_path'  => null,
                            'video_path'  => null,
                            'audio_path'  => null,
                            'created_at'  => $now,
                            'updated_at'  => $now,
                        ];
                    }
                    break;

                case 'identification':
                    $answerRows[] = [
                        'question_id' => $question->id,
                        'option_text' => fake()->word(),
                        'is_correct'  => true,
                        'match_pair'  => null,
                        'order'       => 1,
                        'image_path'  => null,
                        'video_path'  => null,
                        'audio_path'  => null,
                        'created_at'  => $now,
                        'updated_at'  => $now,
                    ];
                    break;

                case 'matching':
                    for ($p = 0; $p < 4; $p++) {
                        $answerRows[] = [
                            'question_id' => $question->id,
                            'option_text' => fake()->word(),
                            'is_correct'  => true,
                            'match_pair'  => fake()->word(),
                            'order'       => $p + 1,
                            'image_path'  => null,
                            'video_path'  => null,
                            'audio_path'  => null,
                            'created_at'  => $now,
                            'updated_at'  => $now,
                        ];
                    }
                    break;
            }
        }

        // Bulk insert answer options
        foreach (array_chunk($answerRows, 1000) as $chunk) {
            DB::table('answer_options')->insert($chunk);
        }

        // ── 6. CREATE CLASSROOMS ────────────────────────────────────────
        $this->command->info('Creating 2500 classrooms (5 per teacher)...');
        $classroomIds = [];
        foreach ($teacherIds as $teacherId) {
            $classes = ClassRoom::factory()->forTeacher($teacherId)->count(5)->create();
            foreach ($classes->pluck('id')->toArray() as $cid) {
                $classroomIds[] = $cid;
            }
        }

        // ── 7. ASSIGN STUDENTS TO CLASSES (min 20 per class) ───────────
        $this->command->info('Assigning students to classrooms...');
        $classStudentRows = [];
        foreach ($classroomIds as $classId) {
            $assigned = (array) array_slice(
                array_keys(array_flip($studentIds)),
                0,
                0
            );
            $picked = array_slice(
                array_unique(
                    array_merge(
                        (array) array_rand(array_flip($studentIds), 20),
                        []
                    )
                ),
                0,
                20
            );

            // Safer approach: shuffle and pick 20
            $shuffled = $studentIds;
            shuffle($shuffled);
            $picked = array_slice($shuffled, 0, 20);

            foreach ($picked as $studentId) {
                $classStudentRows[] = [
                    'class_id'   => $classId,
                    'student_id' => $studentId,
                    'joined_at'  => $now,
                ];
            }
        }

        foreach (array_chunk($classStudentRows, 1000) as $chunk) {
            DB::table('class_students')->insert($chunk);
        }

        // ── 8. ASSIGN QUIZZES TO CLASSES (min 10 per class) ────────────
        $this->command->info('Assigning quizzes to classrooms...');
        $classQuizRows = [];

        // Preload teacher_id per classroom
        $classroomTeacherMap = DB::table('classes')
            ->whereIn('id', $classroomIds)
            ->pluck('teacher_id', 'id')
            ->toArray();

        // Preload quizzes grouped by teacher_id
        $quizzesByTeacher = DB::table('quizzes')
            ->whereIn('id', $quizIds)
            ->select('id', 'teacher_id')
            ->get()
            ->groupBy('teacher_id')
            ->map(fn($q) => $q->pluck('id')->toArray())
            ->toArray();

        foreach ($classroomIds as $classId) {
            $teacherId = $classroomTeacherMap[$classId] ?? null;
            $teacherQuizIds = $quizzesByTeacher[$teacherId] ?? [];

            if (empty($teacherQuizIds)) continue;

            $shuffled = $teacherQuizIds;
            shuffle($shuffled);
            $picked = array_slice($shuffled, 0, min(10, count($shuffled)));

            foreach ($picked as $quizId) {
                $classQuizRows[] = [
                    'class_id'     => $classId,
                    'quiz_id'      => $quizId,
                    'grading_mode' => rand(0, 2) === 0 ? 'manual' : 'automatic', // ~33% manual
                    'assigned_at'  => $now,
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ];
            }
        }

        foreach (array_chunk($classQuizRows, 1000) as $chunk) {
            DB::table('class_quizzes')->insert($chunk);
        }

        // ── 9. CREATE QUIZ ATTEMPTS (50% chance per student per quiz) ───
        $this->command->info('Creating quiz attempts (50% chance)...');
        $attemptRows = [];

        // Preload real total_points per quiz
        $quizTotalPoints = DB::table('questions')
            ->whereIn('quiz_id', $quizIds)
            ->selectRaw('quiz_id, SUM(points) as total_points')
            ->groupBy('quiz_id')
            ->pluck('total_points', 'quiz_id')
            ->toArray();

        $classStudents = DB::table('class_students')->get()->groupBy('class_id');
        $classQuizzes  = DB::table('class_quizzes')->get()->groupBy('class_id');
        $classQuizGradingMode = DB::table('class_quizzes')->get()->groupBy('class_id')->map(fn($rows) => $rows->pluck('grading_mode', 'quiz_id')->toArray())->toArray();

        foreach ($classroomIds as $classId) {
            $studentsInClass = isset($classStudents[$classId])
                ? $classStudents[$classId]->pluck('student_id')->toArray()
                : [];
            $quizzesInClass = isset($classQuizzes[$classId])
                ? $classQuizzes[$classId]->pluck('quiz_id')->toArray()
                : [];

            foreach ($quizzesInClass as $quizId) {
                $totalPoints = $quizTotalPoints[$quizId] ?? 20;

                foreach ($studentsInClass as $studentId) {
                    if (rand(0, 1) === 1) {
                        $status = rand(0, 1) === 1 ? 'reviewed' : 'submitted';
                        $gradingMode = $classQuizGradingMode[$classId][$quizId] ?? 'automatic';
                        if ($gradingMode === 'manual') {
                            // Manual: random between submitted, under_review, reviewed
                            $status = ['submitted', 'under_review', 'reviewed'][rand(0, 2)];
                        } else {
                            // Automatic: always reviewed
                            $status = 'reviewed';
                        }
                        $attemptRows[] = [
                            'quiz_id'          => $quizId,
                            'student_id'       => $studentId,
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
        }

        foreach (array_chunk($attemptRows, 1000) as $chunk) {
            DB::table('quiz_attempts')->insert($chunk);
        }

        $this->command->info('✅ Quizzard Demo Seeder complete!');
        $this->command->info('Teachers: 500 | Students: 1000 | Classes: 2500 | Quizzes: 1000');
    }
}
