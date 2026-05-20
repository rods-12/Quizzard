import 'package:flutter/material.dart';
import '../services/auth_service.dart';
import '../widgets/multiple_choice_widget.dart';
import '../widgets/true_false_widget.dart';
import '../widgets/identification_widget.dart';
import '../widgets/matching_widget.dart';

// ── Quizzard Brand Palette ──────────────────────────────────────────────────
class _Q {
  static const primary        = Color(0xFF5B2A9B); // Royal Purple
  static const primaryDark    = Color(0xFF3A1A6B); // Deep Violet
  static const primaryLight   = Color(0xFFEDE7F2); // Wizard Beard White
  static const gold           = Color(0xFFF2C94C); // Enchanted Gold
  static const goldDark       = Color(0xFFE0A93B); // Warm Amber
  static const softPurple     = Color(0xFFC9A8F0); // Light Lilac
  static const magenta        = Color(0xFFA14BC9); // Mystic Magenta
  static const background     = Color(0xFFFAF6EC); // Parchment Cream
  static const surface        = Color(0xFFFFFFFF); // Card / Surface
  static const textPrimary    = Color(0xFF1F1235); // Midnight Plum
  static const textMuted      = Color(0xFF7B6F96); // Muted Purple-Gray
  static const textSubtle     = Color(0xFFA99BC4); // Muted Lavender
  static const plumShadow     = Color(0xFF2A1247); // Deep dark shadow
  static const success        = Color(0xFF22C55E);
  static const warning        = Color(0xFFF59E0B);
  static const danger         = Color(0xFFEF4444);
}
// ───────────────────────────────────────────────────────────────────────────

class QuizTakingScreen extends StatefulWidget {
  final int quizId;
  final String quizTitle;

  const QuizTakingScreen({
    super.key,
    required this.quizId,
    required this.quizTitle,
  });

  @override
  State<QuizTakingScreen> createState() => _QuizTakingScreenState();
}

class _QuizTakingScreenState extends State<QuizTakingScreen> {
  bool _isLoading = true;
  bool _isSubmitting = false;
  String? _errorMessage;
  List<dynamic> _questions = [];
  int _currentIndex = 0;
  int? _attemptId;

  // Store answers for each question
  // key = question id, value = answer
  final Map<int, dynamic> _answers = {};
  final ScrollController _dotsScrollController = ScrollController();

  @override
  void initState() {
    super.initState();
    _loadQuiz();
  }

  Future<void> _loadQuiz() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    // Start attempt
    final attemptResult = await AuthService.authPost(
      '/quizzes/${widget.quizId}/start',
      {},
    );

    if (!attemptResult['success'] &&
        attemptResult['message'] != 'Resuming existing attempt.') {
      setState(() {
        _isLoading = false;
        _errorMessage = attemptResult['message'];
      });
      return;
    }

    _attemptId = int.parse(attemptResult['data']['attempt']['id'].toString());

    // Load quiz questions
    final quizResult = await AuthService.authGet('/quizzes/${widget.quizId}');

    setState(() {
      _isLoading = false;
      if (quizResult['success']) {
        _questions = quizResult['data']['data']['questions'] as List;
      } else {
        _errorMessage = quizResult['message'];
      }
    });
  }

  @override
  void dispose() {
    _dotsScrollController.dispose();
    super.dispose();
  }

  void _goToNext() {
    if (_currentIndex < _questions.length - 1) {
      setState(() => _currentIndex++);
      _scrollToCurrentDot();
    }
  }

  void _goToPrevious() {
    if (_currentIndex > 0) {
      setState(() => _currentIndex--);
      _scrollToCurrentDot();
    }
  }

  void _scrollToCurrentDot() {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!_dotsScrollController.hasClients) return;

      final viewportWidth = _dotsScrollController.position.viewportDimension;
      final maxScroll = _dotsScrollController.position.maxScrollExtent;

      const double estimatedDotWidth = 44.0;

      final double targetOffset = (_currentIndex * estimatedDotWidth) -
          (viewportWidth / 2) +
          (estimatedDotWidth / 2);

      _dotsScrollController.animateTo(
        targetOffset.clamp(0.0, maxScroll),
        duration: const Duration(milliseconds: 300),
        curve: Curves.easeInOut,
      );
    });
  }

  bool _isAnswered(int questionId) {
    final answer = _answers[questionId];
    if (answer == null) return false;
    if (answer is String) return answer.trim().isNotEmpty;
    if (answer is Map) return answer.isNotEmpty;
    return true;
  }

  Future<void> _confirmSubmit() async {
    final unanswered = _questions.where((q) => !_isAnswered(q['id'])).length;

    final confirm = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        backgroundColor: _Q.surface,
        title: const Text(
          'Submit Quiz?',
          style: TextStyle(
            color: _Q.textPrimary,
            fontWeight: FontWeight.bold,
          ),
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'You have answered ${_questions.length - unanswered} out of ${_questions.length} questions.',
              style: const TextStyle(color: _Q.textMuted),
            ),
            if (unanswered > 0) ...[
              const SizedBox(height: 8),
              Text(
                '$unanswered question(s) unanswered.',
                style: const TextStyle(
                  color: _Q.warning,
                  fontWeight: FontWeight.bold,
                ),
              ),
            ],
            const SizedBox(height: 8),
            const Text(
              'Are you sure you want to submit?',
              style: TextStyle(color: _Q.textMuted),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            style: TextButton.styleFrom(
              foregroundColor: _Q.textMuted,
            ),
            child: const Text('Cancel'),
          ),
          ElevatedButton(
            onPressed: () => Navigator.pop(context, true),
            style: ElevatedButton.styleFrom(
              backgroundColor: _Q.gold,
              foregroundColor: _Q.textPrimary,
              elevation: 0,
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(10),
              ),
            ),
            child: const Text(
              'Submit',
              style: TextStyle(fontWeight: FontWeight.bold),
            ),
          ),
        ],
      ),
    );

    if (confirm == true) {
      await _submitQuiz();
    }
  }

  Future<void> _submitQuiz() async {
    setState(() => _isSubmitting = true);
    // Build answers list for API
    final answersList = [];
    for (var question in _questions) {
      final qId = question['id'];
      final qType = question['question_type'];
      final answer = _answers[qId];

      if (qType == 'multiple_choice' || qType == 'true_false') {
        answersList.add({
          'question_id': qId,
          'answer_type': qType,
          'selected_option_id': answer,
        });
      } else if (qType == 'identification') {
        answersList.add({
          'question_id': qId,
          'answer_type': qType,
          'answer_text': answer ?? '',
        });
      } else if (qType == 'matching') {
        answersList.add({
          'question_id': qId,
          'answer_type': qType,
          'matches': answer ?? {},
        });
      }
    }

    final result = await AuthService.authPost(
      '/quizzes/${widget.quizId}/submit',
      {
        'attempt_id': _attemptId,
        'answers': answersList,
      },
    );

    setState(() => _isSubmitting = false);
    print('DEBUG full result: $result');
    print('DEBUG submit result: ${result['data']}');

    if (!mounted) return;

    final isManualPending = result['success'] == false &&
        (result['message'] as String? ?? '').toLowerCase().contains('pending');

    if (result['success'] == true || isManualPending) {
      final data = result['data'] != null
          ? Map<String, dynamic>.from(result['data'])
          : null;
      final status = data?['status'] as String? ?? '';

      if (isManualPending || status == 'submitted' || status == 'under_review') {
        Navigator.pushReplacementNamed(
          context,
          '/pending-review',
          arguments: {
            'attempt_id': _attemptId,
            'quiz_title': widget.quizTitle,
          },
        );
      } else {
        Navigator.pushReplacementNamed(
          context,
          '/quiz-result',
          arguments: data,
        );
      }
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(result['message']),
          backgroundColor: _Q.danger,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: _Q.background,
      appBar: AppBar(
        title: Text(
          widget.quizTitle,
          style: const TextStyle(
            fontSize: 16,
            fontWeight: FontWeight.bold,
            color: Colors.white,
          ),
        ),
        flexibleSpace: Container(
          decoration: const BoxDecoration(
            gradient: LinearGradient(
              colors: [_Q.primary, _Q.primaryDark],
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
            ),
          ),
        ),
        backgroundColor: Colors.transparent,
        foregroundColor: Colors.white,
        elevation: 0,
        shadowColor: _Q.plumShadow.withOpacity(0.4),
        actions: [
          if (!_isLoading && _questions.isNotEmpty)
            Padding(
              padding: const EdgeInsets.symmetric(vertical: 10, horizontal: 8),
              child: TextButton(
                onPressed: _isSubmitting ? null : _confirmSubmit,
                style: TextButton.styleFrom(
                  backgroundColor: _Q.gold,
                  foregroundColor: _Q.textPrimary,
                  padding: const EdgeInsets.symmetric(horizontal: 14),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(8),
                  ),
                ),
                child: const Text(
                  'Submit',
                  style: TextStyle(
                    fontWeight: FontWeight.bold,
                    fontSize: 14,
                  ),
                ),
              ),
            ),
        ],
      ),
      body: _buildBody(),
    );
  }

  Widget _buildBody() {
    if (_isLoading) {
      return const Center(
        child: CircularProgressIndicator(color: _Q.primary),
      );
    }

    if (_errorMessage != null) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(20),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              const Icon(Icons.error_outline, size: 60, color: _Q.danger),
              const SizedBox(height: 16),
              Text(
                _errorMessage!,
                textAlign: TextAlign.center,
                style: const TextStyle(color: _Q.danger),
              ),
              const SizedBox(height: 16),
              ElevatedButton(
                onPressed: () => Navigator.pop(context),
                style: ElevatedButton.styleFrom(
                  backgroundColor: _Q.primary,
                  foregroundColor: Colors.white,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                ),
                child: const Text('Go Back'),
              ),
            ],
          ),
        ),
      );
    }

    if (_questions.isEmpty) {
      return const Center(
        child: Text(
          'This quiz has no questions yet.',
          style: TextStyle(color: _Q.textMuted),
        ),
      );
    }

    final question = Map<String, dynamic>.from(_questions[_currentIndex]);
    final questionId = question['id'] as int;
    final totalQuestions = _questions.length;

    return Column(
      children: [
        // Progress bar
        Container(
          color: _Q.surface,
          padding: const EdgeInsets.all(16),
          child: Column(
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(
                    'Question ${_currentIndex + 1} of $totalQuestions',
                    style: const TextStyle(
                      fontWeight: FontWeight.bold,
                      color: _Q.textPrimary,
                    ),
                  ),
                  Text(
                    '${_questions.where((q) => _isAnswered(q['id'])).length} answered',
                    style: const TextStyle(color: _Q.textSubtle, fontSize: 13),
                  ),
                ],
              ),
              const SizedBox(height: 8),
              ClipRRect(
                borderRadius: BorderRadius.circular(4),
                child: LinearProgressIndicator(
                  value: (_currentIndex + 1) / totalQuestions,
                  backgroundColor: _Q.primaryLight,
                  valueColor: const AlwaysStoppedAnimation<Color>(_Q.primary),
                  minHeight: 8,
                ),
              ),
              const SizedBox(height: 8),
              // Question dots
              SingleChildScrollView(
                controller: _dotsScrollController,
                scrollDirection: Axis.horizontal,
                child: Row(
                  children: List.generate(totalQuestions, (index) {
                    final q = Map<String, dynamic>.from(_questions[index]);
                    final isAnswered = _isAnswered(q['id']);
                    final isCurrent = index == _currentIndex;

                    return GestureDetector(
                      onTap: () {
                        setState(() => _currentIndex = index);
                        _scrollToCurrentDot();
                      },
                      child: AnimatedContainer(
                        duration: const Duration(milliseconds: 200),
                        margin: const EdgeInsets.symmetric(horizontal: 4),
                        width: isCurrent ? 36 : 32,
                        height: 32,
                        decoration: BoxDecoration(
                          color: isCurrent
                              ? _Q.primary
                              : isAnswered
                                  ? _Q.magenta
                                  : _Q.primaryLight,
                          borderRadius: BorderRadius.circular(8),
                          boxShadow: isCurrent
                              ? [
                                  BoxShadow(
                                    color: _Q.primary.withOpacity(0.35),
                                    blurRadius: 6,
                                    offset: const Offset(0, 2),
                                  ),
                                ]
                              : null,
                        ),
                        child: Center(
                          child: Text(
                            '${index + 1}',
                            style: TextStyle(
                              color: isCurrent || isAnswered
                                  ? Colors.white
                                  : _Q.textMuted,
                              fontWeight: isCurrent
                                  ? FontWeight.bold
                                  : FontWeight.w500,
                              fontSize: isCurrent ? 14 : 12,
                            ),
                          ),
                        ),
                      ),
                    );
                  }),
                ),
              ),
            ],
          ),
        ),

        // Question content
        Expanded(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(20),
            child: _buildQuestion(question, questionId),
          ),
        ),

        // Navigation buttons
        Container(
          color: _Q.surface,
          padding: const EdgeInsets.all(16),
          child: Row(
            children: [
              if (_currentIndex > 0)
                Expanded(
                  child: OutlinedButton.icon(
                    onPressed: _goToPrevious,
                    icon: const Icon(Icons.arrow_back),
                    label: const Text('Previous'),
                    style: OutlinedButton.styleFrom(
                      foregroundColor: _Q.primary,
                      side: const BorderSide(color: _Q.softPurple, width: 1.5),
                      backgroundColor: _Q.primaryLight,
                      padding: const EdgeInsets.symmetric(vertical: 14),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12),
                      ),
                    ),
                  ),
                ),
              if (_currentIndex > 0) const SizedBox(width: 12),

              Expanded(
                child: ElevatedButton.icon(
                  onPressed: _isSubmitting
                      ? null
                      : _currentIndex < totalQuestions - 1
                          ? _goToNext
                          : _confirmSubmit,
                  icon: Icon(
                    _currentIndex < totalQuestions - 1
                        ? Icons.arrow_forward
                        : Icons.check_circle,
                  ),
                  label: Text(
                    _currentIndex < totalQuestions - 1
                        ? 'Next'
                        : 'Submit Quiz',
                  ),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: (_currentIndex < totalQuestions - 1)
                        ? _Q.primary
                        : _Q.gold,
                    foregroundColor: (_currentIndex < totalQuestions - 1)
                        ? Colors.white
                        : _Q.textPrimary,
                    elevation: 2,
                    shadowColor: _Q.plumShadow.withOpacity(0.3),
                    padding: const EdgeInsets.symmetric(vertical: 14),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                  ),
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }

  Widget _buildQuestion(Map<String, dynamic> question, int questionId) {
    final qType = question['question_type'] as String;

    switch (qType) {
      case 'multiple_choice':
        return MultipleChoiceWidget(
          key: ValueKey(questionId),
          question: question,
          selectedAnswerId: _answers[questionId] as int?,
          onAnswerSelected: (id) => setState(() => _answers[questionId] = id),
        );
      case 'true_false':
        return TrueFalseWidget(
          key: ValueKey(questionId),
          question: question,
          selectedAnswerId: _answers[questionId] as int?,
          onAnswerSelected: (id) => setState(() => _answers[questionId] = id),
        );
      case 'identification':
        return IdentificationWidget(
          key: ValueKey(questionId),
          question: question,
          currentAnswer: _answers[questionId] as String? ?? '',
          onAnswerChanged: (val) => setState(() => _answers[questionId] = val),
        );
      case 'matching':
        return MatchingWidget(
          key: ValueKey(questionId),
          question: question,
          currentAnswers: _answers[questionId] as Map<String, String>?,
          onAnswerChanged: (matches) =>
              setState(() => _answers[questionId] = matches),
        );
      default:
        return const Text('Unknown question type');
    }
  }
}