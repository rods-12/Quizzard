import 'package:flutter/material.dart';
import '../services/auth_service.dart';

class PendingReviewScreen extends StatefulWidget {
  final int attemptId;
  final String quizTitle;

  const PendingReviewScreen({
    super.key,
    required this.attemptId,
    required this.quizTitle,
  });

  @override
  State<PendingReviewScreen> createState() => _PendingReviewScreenState();
}

class _PendingReviewScreenState extends State<PendingReviewScreen> {
  bool _isLoading = true;
  String? _errorMessage;
  Map<String, dynamic>? _attemptData;

  static const String _statusSubmitted = 'submitted';
  static const String _statusUnderReview = 'under_review';
  static const String _statusReviewed = 'reviewed';

  @override
  void initState() {
    super.initState();
    _loadAttempt();
  }

  Future<void> _loadAttempt() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    final result = await AuthService.authGet(
      '/student/attempts/${widget.attemptId}',
    );

    setState(() {
      _isLoading = false;
      if (result['success']) {
        _attemptData = result['data'] as Map<String, dynamic>;

        final status = _attemptData?['attempt']?['status'] as String?;
        if (status == _statusReviewed && mounted) {
          _redirectToResult();
        }
      } else {
        _errorMessage = result['message'];
      }
    });
  }

  void _redirectToResult() {
    if (!mounted) return;
    Navigator.pushReplacementNamed(
      context,
      '/quiz-result',
      arguments: _attemptData,
    );
  }

  String _statusLabel(String status) {
    switch (status) {
      case _statusSubmitted:
        return 'Submitted — Awaiting Review';
      case _statusUnderReview:
        return 'Under Review';
      default:
        return status;
    }
  }

  Color _statusColor(String status) {
    switch (status) {
      case _statusSubmitted:
        return const Color(0xFFF59E0B);
      case _statusUnderReview:
        return const Color(0xFFC9A8F0);
      default:
        return const Color(0xFFA99BC4);
    }
  }

  IconData _statusIcon(String status) {
    switch (status) {
      case _statusSubmitted:
        return Icons.hourglass_top_rounded;
      case _statusUnderReview:
        return Icons.rate_review_rounded;
      default:
        return Icons.info_outline_rounded;
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFFAF6EC),
      appBar: AppBar(
        title: Text(
          widget.quizTitle,
          style: const TextStyle(fontSize: 16),
          overflow: TextOverflow.ellipsis,
        ),
        flexibleSpace: Container(
          decoration: const BoxDecoration(
            gradient: LinearGradient(
              colors: [Color(0xFF5B2A9B), Color(0xFF3A1A6B)],
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
            ),
          ),
        ),
        backgroundColor: Colors.transparent,
        foregroundColor: Colors.white,
        automaticallyImplyLeading: false,
      ),
      body: _buildBody(),
    );
  }

  Widget _buildBody() {
    if (_isLoading) {
      return const Center(
        child: CircularProgressIndicator(color: Color(0xFF5B2A9B)),
      );
    }

    if (_errorMessage != null) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(32),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Container(
                padding: const EdgeInsets.all(20),
                decoration: BoxDecoration(
                  color: const Color(0xFFEF4444).withOpacity(0.1),
                  shape: BoxShape.circle,
                ),
                child: const Icon(Icons.error_outline_rounded,
                    size: 48, color: Color(0xFFEF4444)),
              ),
              const SizedBox(height: 16),
              Text(
                _errorMessage!,
                textAlign: TextAlign.center,
                style: const TextStyle(color: Color(0xFFA99BC4)),
              ),
              const SizedBox(height: 20),
              ElevatedButton(
                onPressed: _loadAttempt,
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFFF2C94C),
                  foregroundColor: const Color(0xFF1F1235),
                  elevation: 0,
                ),
                child: const Text(
                  'Retry',
                  style: TextStyle(
                    color: Color(0xFF1F1235),
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ),
            ],
          ),
        ),
      );
    }

    final attempt = _attemptData!['attempt'] != null
        ? Map<String, dynamic>.from(_attemptData!['attempt'])
        : <String, dynamic>{};
    final answers = _attemptData!['answers'] as List? ?? [];
    final status = attempt['status'] as String? ?? 'submitted';

    return RefreshIndicator(
      onRefresh: _loadAttempt,
      color: const Color(0xFF5B2A9B),
      child: SingleChildScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        child: Column(
          children: [
            // ── Status Banner ──
            Container(
              width: double.infinity,
              padding: const EdgeInsets.fromLTRB(24, 32, 24, 32),
              decoration: const BoxDecoration(
                gradient: LinearGradient(
                  colors: [Color(0xFF5B2A9B), Color(0xFF3A1A6B)],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                ),
                borderRadius: BorderRadius.only(
                  bottomLeft: Radius.circular(32),
                  bottomRight: Radius.circular(32),
                ),
              ),
              child: Column(
                children: [
                  Container(
                    padding: const EdgeInsets.all(20),
                    decoration: BoxDecoration(
                      color: Colors.white.withOpacity(0.15),
                      shape: BoxShape.circle,
                    ),
                    child: Icon(
                      _statusIcon(status),
                      size: 52,
                      color: const Color(0xFFF2C94C),
                    ),
                  ),
                  const SizedBox(height: 16),
                  const Text(
                    'Quiz Submitted!',
                    style: TextStyle(
                      color: Colors.white,
                      fontSize: 22,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  const SizedBox(height: 8),
                  Container(
                    padding: const EdgeInsets.symmetric(
                        horizontal: 14, vertical: 6),
                    decoration: BoxDecoration(
                      color: _statusColor(status).withOpacity(0.25),
                      borderRadius: BorderRadius.circular(20),
                      border: Border.all(
                        color: Colors.white.withOpacity(0.4),
                      ),
                    ),
                    child: Text(
                      _statusLabel(status),
                      style: const TextStyle(
                        color: Colors.white,
                        fontWeight: FontWeight.bold,
                        fontSize: 13,
                      ),
                    ),
                  ),
                  const SizedBox(height: 16),
                  Text(
                    'Your teacher will review and grade your answers.\nYou\'ll be able to see your results once grading is complete.',
                    textAlign: TextAlign.center,
                    style: TextStyle(
                      color: Colors.white.withOpacity(0.85),
                      fontSize: 13,
                      height: 1.5,
                    ),
                  ),
                ],
              ),
            ),

            const SizedBox(height: 20),

            // ── Info card ──
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 20),
              child: Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(16),
                  boxShadow: [
                    BoxShadow(
                      color: const Color(0xFF2A1247).withOpacity(0.08),
                      blurRadius: 12,
                      offset: const Offset(0, 4),
                    ),
                  ],
                ),
                child: Column(
                  children: [
                    _buildInfoRow(
                      Icons.assignment_rounded,
                      'Quiz',
                      widget.quizTitle,
                    ),
                    const Divider(height: 20, color: Color(0xFFEDE7F2)),
                    _buildInfoRow(
                      Icons.help_outline_rounded,
                      'Questions Answered',
                      '${answers.length} question${answers.length == 1 ? '' : 's'}',
                    ),
                    const Divider(height: 20, color: Color(0xFFEDE7F2)),
                    _buildInfoRow(
                      Icons.lock_clock_rounded,
                      'Score',
                      'Hidden until reviewed',
                      valueColor: const Color(0xFFA99BC4),
                    ),
                  ],
                ),
              ),
            ),

            const SizedBox(height: 20),

            // ── Submitted Answers ──
            if (answers.isNotEmpty) ...[
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 20),
                child: Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.all(6),
                      decoration: BoxDecoration(
                        color: const Color(0xFFEDE7F2),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: const Icon(Icons.list_alt_rounded,
                          color: Color(0xFF5B2A9B), size: 16),
                    ),
                    const SizedBox(width: 10),
                    const Text(
                      'Your Submitted Answers',
                      style: TextStyle(
                        fontSize: 17,
                        fontWeight: FontWeight.bold,
                        color: Color(0xFF1F1235),
                        letterSpacing: -0.3,
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 12),
              ListView.builder(
                shrinkWrap: true,
                physics: const NeverScrollableScrollPhysics(),
                padding: const EdgeInsets.symmetric(horizontal: 20),
                itemCount: answers.length,
                itemBuilder: (context, index) {
                  final answer =
                      Map<String, dynamic>.from(answers[index]);
                  return _buildAnswerCard(index + 1, answer);
                },
              ),
              const SizedBox(height: 20),
            ],

            // ── Note about hidden results ──
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 20),
              child: Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: const Color(0xFFF59E0B).withOpacity(0.08),
                  borderRadius: BorderRadius.circular(14),
                  border: Border.all(
                    color: const Color(0xFFF59E0B).withOpacity(0.3),
                  ),
                ),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Icon(Icons.info_outline_rounded,
                        color: Color(0xFFF59E0B), size: 20),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Text(
                        'Scores, correct answers, and feedback are hidden until your teacher completes the review. Pull down to refresh and check if your results are ready.',
                        style: TextStyle(
                          color: const Color(0xFFE0A93B).withOpacity(0.9),
                          fontSize: 12,
                          height: 1.5,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ),

            const SizedBox(height: 24),

            // ── Back to Dashboard button ──
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 20),
              child: SizedBox(
                width: double.infinity,
                height: 54,
                child: ElevatedButton.icon(
                  onPressed: () => Navigator.pushNamedAndRemoveUntil(
                    context,
                    '/student-dashboard',
                    (route) => false,
                  ),
                  icon: const Icon(Icons.home_rounded,
                      color: Color(0xFF1F1235)),
                  label: const Text(
                    'Back to Dashboard',
                    style: TextStyle(
                      color: Color(0xFF1F1235),
                      fontSize: 16,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFFF2C94C),
                    elevation: 0,
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(14),
                    ),
                  ),
                ),
              ),
            ),
            const SizedBox(height: 32),
          ],
        ),
      ),
    );
  }

  Widget _buildInfoRow(
    IconData icon,
    String label,
    String value, {
    Color? valueColor,
  }) {
    return Row(
      children: [
        Container(
          padding: const EdgeInsets.all(8),
          decoration: BoxDecoration(
            color: const Color(0xFFEDE7F2),
            borderRadius: BorderRadius.circular(10),
          ),
          child: Icon(icon, color: const Color(0xFF5B2A9B), size: 18),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                label,
                style: const TextStyle(
                  fontSize: 11,
                  color: Color(0xFFA99BC4),
                  fontWeight: FontWeight.w500,
                ),
              ),
              const SizedBox(height: 2),
              Text(
                value,
                style: TextStyle(
                  fontSize: 14,
                  fontWeight: FontWeight.w600,
                  color: valueColor ?? const Color(0xFF1F1235),
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }

  Widget _buildAnswerCard(int number, Map<String, dynamic> answer) {
    final questionText =
        answer['question_text'] as String? ?? 'Question $number';
    final questionType =
        answer['question_type'] as String? ?? '';
    final answerGiven = answer['answer_given'] as String? ?? '';

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        boxShadow: [
          BoxShadow(
            color: const Color(0xFF2A1247).withOpacity(0.06),
            blurRadius: 8,
            offset: const Offset(0, 3),
          ),
        ],
      ),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Container(
                  width: 28,
                  height: 28,
                  decoration: BoxDecoration(
                    color: const Color(0xFFEDE7F2),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Center(
                    child: Text(
                      '$number',
                      style: const TextStyle(
                        color: Color(0xFF5B2A9B),
                        fontWeight: FontWeight.bold,
                        fontSize: 12,
                      ),
                    ),
                  ),
                ),
                const SizedBox(width: 8),
                Container(
                  padding: const EdgeInsets.symmetric(
                      horizontal: 8, vertical: 3),
                  decoration: BoxDecoration(
                    color: const Color(0xFFFAF6EC),
                    borderRadius: BorderRadius.circular(6),
                  ),
                  child: Text(
                    _formatQuestionType(questionType),
                    style: const TextStyle(
                      fontSize: 10,
                      color: Color(0xFFA99BC4),
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                ),
                const Spacer(),
                Container(
                  padding: const EdgeInsets.symmetric(
                      horizontal: 8, vertical: 3),
                  decoration: BoxDecoration(
                    color: const Color(0xFFEDE7F2),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: const Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Icon(Icons.send_rounded,
                          size: 10, color: Color(0xFF5B2A9B)),
                      SizedBox(width: 4),
                      Text(
                        'Submitted',
                        style: TextStyle(
                          fontSize: 10,
                          color: Color(0xFF5B2A9B),
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),

            Text(
              questionText,
              style: const TextStyle(
                fontWeight: FontWeight.w600,
                fontSize: 14,
                color: Color(0xFF1F1235),
                height: 1.4,
              ),
            ),
            const SizedBox(height: 10),
            const Divider(height: 1, color: Color(0xFFEDE7F2)),
            const SizedBox(height: 10),

            _buildAnswerDisplay(questionType, answerGiven, answer),
          ],
        ),
      ),
    );
  }

  Widget _buildAnswerDisplay(
    String questionType,
    String answerGiven,
    Map<String, dynamic> answer,
  ) {
    switch (questionType) {
      case 'multiple_choice':
      case 'true_false':
        return _buildOptionAnswerDisplay(answerGiven, answer);
      case 'identification':
        return _buildTextAnswerDisplay(answerGiven);
      case 'matching':
        return _buildMatchingAnswerDisplay(answerGiven, answer);
      default:
        return _buildTextAnswerDisplay(answerGiven);
    }
  }

  Widget _buildOptionAnswerDisplay(
      String answerGiven, Map<String, dynamic> answer) {
    final options = answer['answer_options'] as List? ?? [];
    String displayText = answerGiven;

    if (answerGiven.isNotEmpty) {
      final selectedId = int.tryParse(answerGiven);
      for (var opt in options) {
        if (opt['id'] == selectedId) {
          displayText = opt['option_text'] as String? ?? answerGiven;
          break;
        }
      }
    }

    return _buildAnswerChip(
      displayText.isNotEmpty ? displayText : 'No answer given',
      isEmpty: displayText.isEmpty || answerGiven.isEmpty,
    );
  }

  Widget _buildTextAnswerDisplay(String answerGiven) {
    final isEmpty = answerGiven.trim().isEmpty;
    return _buildAnswerChip(
      isEmpty ? 'No answer given' : answerGiven,
      isEmpty: isEmpty,
    );
  }

  Widget _buildMatchingAnswerDisplay(
      String answerGiven, Map<String, dynamic> answer) {
    if (answerGiven.trim().isEmpty) {
      return _buildAnswerChip('No answer given', isEmpty: true);
    }

    Map<String, String> matches = {};
    try {
      final clean = answerGiven
          .trim()
          .replaceAll('{', '')
          .replaceAll('}', '');
      final pairs = clean.split(',"');
      for (var pair in pairs) {
        final parts = pair.replaceAll('"', '').split(':');
        if (parts.length >= 2) {
          final key = parts[0].trim();
          final value = parts.sublist(1).join(':').trim();
          matches[key] = value;
        }
      }
    } catch (_) {
      matches = {};
    }

    if (matches.isEmpty) {
      return _buildAnswerChip('No answer given', isEmpty: true);
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text(
          'Your matches:',
          style: TextStyle(
            fontSize: 11,
            color: Color(0xFFA99BC4),
            fontWeight: FontWeight.w500,
          ),
        ),
        const SizedBox(height: 6),
        ...matches.entries.map(
          (e) => Padding(
            padding: const EdgeInsets.only(bottom: 6),
            child: Row(
              children: [
                Expanded(
                  child: Container(
                    padding: const EdgeInsets.symmetric(
                        horizontal: 10, vertical: 6),
                    decoration: BoxDecoration(
                      color: const Color(0xFFEDE7F2),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Text(
                      e.key,
                      style: const TextStyle(
                        fontSize: 12,
                        color: Color(0xFF1F1235),
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                  ),
                ),
                const Padding(
                  padding: EdgeInsets.symmetric(horizontal: 8),
                  child: Icon(Icons.arrow_forward_rounded,
                      size: 14, color: Color(0xFF5B2A9B)),
                ),
                Expanded(
                  child: Container(
                    padding: const EdgeInsets.symmetric(
                        horizontal: 10, vertical: 6),
                    decoration: BoxDecoration(
                      color: const Color(0xFFFAF6EC),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Text(
                      e.value,
                      style: const TextStyle(
                        fontSize: 12,
                        color: Color(0xFF1F1235),
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildAnswerChip(String text, {bool isEmpty = false}) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
      decoration: BoxDecoration(
        color: isEmpty
            ? const Color(0xFFFAF6EC)
            : const Color(0xFFEDE7F2),
        borderRadius: BorderRadius.circular(10),
        border: Border.all(
          color: isEmpty
              ? const Color(0xFFA99BC4).withOpacity(0.3)
              : const Color(0xFF5B2A9B).withOpacity(0.2),
        ),
      ),
      child: Row(
        children: [
          Icon(
            isEmpty
                ? Icons.remove_circle_outline_rounded
                : Icons.check_box_outline_blank_rounded,
            size: 14,
            color: isEmpty
                ? const Color(0xFFA99BC4)
                : const Color(0xFF5B2A9B),
          ),
          const SizedBox(width: 8),
          Expanded(
            child: Text(
              text,
              style: TextStyle(
                fontSize: 13,
                color: isEmpty
                    ? const Color(0xFFA99BC4)
                    : const Color(0xFF1F1235),
                fontStyle:
                    isEmpty ? FontStyle.italic : FontStyle.normal,
                fontWeight: FontWeight.w500,
              ),
            ),
          ),
        ],
      ),
    );
  }

  String _formatQuestionType(String type) {
    switch (type) {
      case 'multiple_choice':
        return 'Multiple Choice';
      case 'true_false':
        return 'True / False';
      case 'identification':
        return 'Identification';
      case 'matching':
        return 'Matching';
      default:
        return type;
    }
  }
}