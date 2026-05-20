<?php

namespace Tests\Feature\Admin;

use App\Models\ClassRoom;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AnalyticsDashboardTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('adminAnalyticsPageProvider')]
    public function test_admin_users_can_open_analytics_dashboard_pages(string $role, string $page): void
    {
        $scenario = $this->createAnalyticsScenario();
        $admin = $this->adminUser($role);

        $response = $this->actingAs($admin)->get($this->urlForPage($page, $scenario));

        $response->assertOk();
        $response->assertViewIs($this->expectedViewForPage($page));
    }

    #[DataProvider('unauthorizedAnalyticsPageProvider')]
    public function test_non_admin_users_cannot_open_analytics_dashboard_pages(string $actor, string $page): void
    {
        $scenario = $this->createAnalyticsScenario();
        $url = $this->urlForPage($page, $scenario);

        $response = match ($actor) {
            'guest' => $this->get($url),
            'student' => $this->actingAs($scenario->student)->get($url),
            'teacher' => $this->actingAs($scenario->teacher)->get($url),
        };

        $actor === 'guest'
            ? $response->assertRedirect('/login')
            : $response->assertForbidden();
    }

    #[DataProvider('filterStateProvider')]
    public function test_filterable_analytics_pages_keep_readable_filter_state(string $page, array $query, array $expected): void
    {
        $scenario = $this->createAnalyticsScenario();
        $admin = $this->adminUser();
        $query = $this->resolveQueryPlaceholders($query, $scenario);
        $expected = $this->resolveQueryPlaceholders($expected, $scenario);

        $response = $this->actingAs($admin)->get($this->urlForPage($page, $scenario, $query));

        $response->assertOk();
        $filters = $response->viewData('filters');

        foreach ($expected as $key => $value) {
            $actual = $filters[$key];

            if (str_ends_with($key, '_id')) {
                $actual = (string) $actual;
                $value = (string) $value;
            }

            $this->assertSame($value, $actual, "Expected [{$key}] to be preserved on [{$page}].");
        }
    }

    #[DataProvider('invalidFilterStateProvider')]
    public function test_invalid_analytics_filters_show_the_dashboard_error_state(string $page, array $query): void
    {
        $scenario = $this->createAnalyticsScenario();
        $admin = $this->adminUser();

        $response = $this->actingAs($admin)->get($this->urlForPage($page, $scenario, $query));

        $response->assertOk();
        $response->assertViewIs('admin.analytics.error');
    }

    #[DataProvider('expectedViewDataProvider')]
    public function test_analytics_dashboard_pages_expose_their_expected_view_data(string $page, string $key): void
    {
        $scenario = $this->createAnalyticsScenario();
        $admin = $this->adminUser();

        $response = $this->actingAs($admin)->get($this->urlForPage($page, $scenario));

        $response->assertOk();
        $response->assertViewHas($key);
    }

    public function test_student_analytics_index_calculates_core_kpis_from_attempts(): void
    {
        $scenario = $this->createAnalyticsScenario();
        $admin = $this->adminUser();

        $response = $this->actingAs($admin)->get(route('admin.analytics.students'));

        $response->assertOk();
        $this->assertSame(3, $response->viewData('totalStudents'));
        $this->assertSame(3, $response->viewData('activeStudents'));
        $this->assertEqualsWithDelta(61.67, $response->viewData('avgScore'), 0.01);
        $this->assertEqualsWithDelta(66.67, $response->viewData('passRate'), 0.01);
        $this->assertCount(3, $response->viewData('allStudents'));
        $this->assertTrue($scenario->student->is($response->viewData('topStudents')->first()));
    }

    public function test_quiz_analytics_index_calculates_core_kpis_from_attempts(): void
    {
        $scenario = $this->createAnalyticsScenario();
        $admin = $this->adminUser();

        $response = $this->actingAs($admin)->get(route('admin.analytics.quizzes'));

        $response->assertOk();
        $kpis = $response->viewData('kpis');

        $this->assertSame(2, $kpis['total_quizzes']);
        $this->assertSame(4, (int) $kpis['total_attempts']);
        $this->assertEqualsWithDelta(83.33, $kpis['avg_pass_rate'], 0.01);
        $this->assertEqualsWithDelta(71.67, $kpis['avg_score'], 0.01);
        $this->assertSame('Beta Data Quiz', $response->viewData('topPassRate')->first()->title);
    }

    public function test_class_analytics_show_calculates_class_level_kpis(): void
    {
        $scenario = $this->createAnalyticsScenario();
        $admin = $this->adminUser();

        $response = $this->actingAs($admin)->get(route('admin.analytics.classes.show', $scenario->classroom));

        $response->assertOk();
        $kpis = $response->viewData('kpis');

        $this->assertSame(3, $kpis['total_students']);
        $this->assertSame(4, $kpis['total_attempts']);
        $this->assertEqualsWithDelta(67.5, $kpis['avg_score'], 0.01);
        $this->assertEqualsWithDelta(75.0, $kpis['pass_rate'], 0.01);
    }

    public function test_student_profile_analytics_calculates_rank_and_score_summary(): void
    {
        $scenario = $this->createAnalyticsScenario();
        $admin = $this->adminUser();

        $response = $this->actingAs($admin)->get(route('admin.analytics.students.show', $scenario->student));

        $response->assertOk();
        $stats = $response->viewData('stats');

        $this->assertSame(2, $stats['total_attempts']);
        $this->assertEqualsWithDelta(85.0, $stats['avg_score'], 0.01);
        $this->assertEqualsWithDelta(100.0, $stats['pass_rate'], 0.01);
        $this->assertSame(1, $response->viewData('systemRank'));
    }

    public static function adminAnalyticsPageProvider(): array
    {
        return self::crossJoin(self::adminRoles(), self::analyticsPages());
    }

    public static function unauthorizedAnalyticsPageProvider(): array
    {
        return self::crossJoin(self::unauthorizedActors(), self::analyticsPages());
    }

    public static function filterStateProvider(): array
    {
        return self::crossJoin(self::filterablePages(), self::analyticsFilters());
    }

    public static function invalidFilterStateProvider(): array
    {
        return self::crossJoin(self::filterablePages(), self::invalidFilters());
    }

    private static function adminRoles(): array
    {
        return [
            'admin' => ['admin'],
            'superadmin' => ['superadmin'],
        ];
    }

    private static function unauthorizedActors(): array
    {
        return [
            'guest' => ['guest'],
            'student' => ['student'],
            'teacher' => ['teacher'],
        ];
    }

    private static function analyticsPages(): array
    {
        return [
            'overview' => ['overview'],
            'students index' => ['students'],
            'student profile' => ['students.show'],
            'quizzes index' => ['quizzes'],
            'quiz detail' => ['quizzes.show'],
            'classes index' => ['classes'],
            'class detail' => ['classes.show'],
            'teachers index' => ['teachers'],
            'teacher detail' => ['teachers.show'],
        ];
    }

    private static function filterablePages(): array
    {
        return [
            'overview filters' => ['overview'],
            'students filters' => ['students'],
            'quizzes filters' => ['quizzes'],
            'classes filters' => ['classes'],
            'teachers filters' => ['teachers'],
        ];
    }

    private static function analyticsFilters(): array
    {
        return [
            'all time mode' => [
                ['date_mode' => 'all'],
                ['date_mode' => 'all', 'date_from' => null, 'date_to' => null],
            ],
            'valid date range' => [
                ['date_mode' => 'range', 'date_from' => '2026-04-01', 'date_to' => '2026-04-30'],
                ['date_mode' => 'range', 'date_from' => '2026-04-01', 'date_to' => '2026-04-30'],
            ],
            'search term' => [
                ['search' => 'Alpha'],
                ['search' => 'Alpha'],
            ],
            'ascending direction' => [
                ['direction' => 'asc'],
                ['direction' => 'asc'],
            ],
            'teacher filter' => [
                ['teacher_id' => '@teacher'],
                ['teacher_id' => '@teacher'],
            ],
            'quiz and class basis filters' => [
                ['quiz_id' => '@quiz', 'class_id' => '@class'],
                ['quiz_id' => '@quiz', 'class_id' => '@class'],
            ],
        ];
    }

    private static function invalidFilters(): array
    {
        return [
            'range missing end date' => [['date_mode' => 'range', 'date_from' => '2026-04-01']],
            'end date before start date' => [['date_mode' => 'range', 'date_from' => '2026-04-30', 'date_to' => '2026-04-01']],
            'bad direction' => [['direction' => 'sideways']],
            'bad teacher id' => [['teacher_id' => 999999]],
            'too long search term' => [['search' => str_repeat('x', 101)]],
        ];
    }

    public static function expectedViewDataProvider(): array
    {
        return [
            'overview filters' => ['overview', 'filters'],
            'overview total students' => ['overview', 'totalStudents'],
            'overview attempts' => ['overview', 'totalAttempts'],
            'overview pass rate' => ['overview', 'systemPassRate'],
            'overview activity chart' => ['overview', 'activityLabels'],
            'overview class bars' => ['overview', 'classBars'],
            'overview insights' => ['overview', 'criticalInsights'],
            'students filters' => ['students', 'filters'],
            'students table' => ['students', 'allStudents'],
            'students top list' => ['students', 'topStudents'],
            'students score distribution' => ['students', 'scoreDistribution'],
            'students grade performance' => ['students', 'gradePerformance'],
            'quizzes filters' => ['quizzes', 'filters'],
            'quizzes kpis' => ['quizzes', 'kpis'],
            'quizzes table' => ['quizzes', 'allQuizzes'],
            'quizzes chart data' => ['quizzes', 'chartData'],
            'classes filters' => ['classes', 'filters'],
            'classes kpis' => ['classes', 'kpis'],
            'classes table' => ['classes', 'classes'],
            'classes chart data' => ['classes', 'chartData'],
            'teachers filters' => ['teachers', 'filters'],
            'teachers kpis' => ['teachers', 'kpis'],
            'teachers table' => ['teachers', 'teachers'],
            'student detail stats' => ['students.show', 'stats'],
            'quiz detail kpis' => ['quizzes.show', 'kpis'],
            'class detail kpis' => ['classes.show', 'kpis'],
        ];
    }

    private function adminUser(string $role = 'admin'): User
    {
        return User::factory()->create([
            'role' => $role,
            'status' => 'active',
        ]);
    }

    private function createAnalyticsScenario(): AnalyticsDashboardScenario
    {
        $teacher = User::factory()->teacher()->create([
            'first_name' => 'Alpha',
            'surname' => 'Teacher',
        ]);

        $secondTeacher = User::factory()->teacher()->create([
            'first_name' => 'Beta',
            'surname' => 'Teacher',
        ]);

        $classroom = ClassRoom::factory()->forTeacher($teacher->id)->create([
            'name' => 'Alpha Analytics',
        ]);

        $secondClassroom = ClassRoom::factory()->forTeacher($secondTeacher->id)->create([
            'name' => 'Beta Analytics',
        ]);

        $quiz = Quiz::factory()->forTeacher($teacher->id)->published()->create([
            'title' => 'Alpha Data Quiz',
        ]);

        $secondQuiz = Quiz::factory()->forTeacher($teacher->id)->published()->create([
            'title' => 'Beta Data Quiz',
        ]);

        $classroom->quizzes()->attach([$quiz->id, $secondQuiz->id]);
        $secondClassroom->quizzes()->attach($secondQuiz->id);

        $student = $this->studentWithProfile('Ada', 'Able', 'Grade 10', 'Rizal');
        $middleStudent = $this->studentWithProfile('Ben', 'Balanced', 'Grade 10', 'Rizal');
        $lowStudent = $this->studentWithProfile('Cia', 'Careful', 'Grade 11', 'Luna');

        $classroom->students()->attach([$student->id, $middleStudent->id, $lowStudent->id]);
        $secondClassroom->students()->attach($lowStudent->id);

        $this->attempt($quiz, $student, 90, '2026-04-03 10:00:00');
        $this->attempt($secondQuiz, $student, 80, '2026-04-04 10:00:00');
        $this->attempt($quiz, $middleStudent, 60, '2026-04-05 10:00:00');
        $this->attempt($quiz, $lowStudent, 40, '2026-04-06 10:00:00');

        return new AnalyticsDashboardScenario(
            teacher: $teacher,
            student: $student,
            quiz: $quiz,
            classroom: $classroom,
        );
    }

    private function studentWithProfile(string $firstName, string $surname, string $gradeLevel, string $section): User
    {
        $student = User::factory()->student()->create([
            'first_name' => $firstName,
            'surname' => $surname,
        ]);

        StudentProfile::factory()->create([
            'user_id' => $student->id,
            'grade_level' => $gradeLevel,
            'section' => $section,
        ]);

        return $student;
    }

    private function attempt(Quiz $quiz, User $student, int $score, string $completedAt): QuizAttempt
    {
        return QuizAttempt::factory()->create([
            'quiz_id' => $quiz->id,
            'student_id' => $student->id,
            'score' => $score,
            'total_points' => 100,
            'status' => 'reviewed',
            'started_at' => date('Y-m-d H:i:s', strtotime($completedAt . ' -10 minutes')),
            'completed_at' => $completedAt,
        ]);
    }

    private function urlForPage(string $page, AnalyticsDashboardScenario $scenario, array $query = []): string
    {
        $url = match ($page) {
            'overview' => route('admin.analytics.overview'),
            'students' => route('admin.analytics.students'),
            'students.show' => route('admin.analytics.students.show', $scenario->student),
            'quizzes' => route('admin.analytics.quizzes'),
            'quizzes.show' => route('admin.analytics.quizzes.show', $scenario->quiz),
            'classes' => route('admin.analytics.classes'),
            'classes.show' => route('admin.analytics.classes.show', $scenario->classroom),
            'teachers' => route('admin.analytics.teachers'),
            'teachers.show' => route('admin.analytics.teachers.show', $scenario->teacher),
        };

        return $query === [] ? $url : $url . '?' . http_build_query($query);
    }

    private function expectedViewForPage(string $page): string
    {
        return match ($page) {
            'overview' => 'admin.analytics.overview',
            'students' => 'admin.analytics.students.index',
            'students.show' => 'admin.analytics.students.show',
            'quizzes' => 'admin.analytics.quizzes.index',
            'quizzes.show' => 'admin.analytics.quizzes.show',
            'classes' => 'admin.analytics.classes.index',
            'classes.show' => 'admin.analytics.classes.show',
            'teachers' => 'admin.analytics.teachers.index',
            'teachers.show' => 'admin.analytics.teachers.show',
        };
    }

    private function resolveQueryPlaceholders(array $values, AnalyticsDashboardScenario $scenario): array
    {
        return array_map(function ($value) use ($scenario) {
            return match ($value) {
                '@teacher' => $scenario->teacher->id,
                '@quiz' => $scenario->quiz->id,
                '@class' => $scenario->classroom->id,
                default => $value,
            };
        }, $values);
    }

    private static function crossJoin(array $left, array $right): array
    {
        $joined = [];

        foreach ($left as $leftName => $leftValues) {
            foreach ($right as $rightName => $rightValues) {
                $joined["{$leftName} / {$rightName}"] = array_merge($leftValues, $rightValues);
            }
        }

        return $joined;
    }
}

final class AnalyticsDashboardScenario
{
    public function __construct(
        public readonly User $teacher,
        public readonly User $student,
        public readonly Quiz $quiz,
        public readonly ClassRoom $classroom,
    ) {
    }
}
