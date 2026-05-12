import 'package:flutter/material.dart';
import '../widgets/multiple_choice_result_widget.dart';
import '../widgets/true_false_result_widget.dart';
import '../widgets/identification_result_widget.dart';
import '../widgets/matching_result_widget.dart';

class QuizResultScreen extends StatelessWidget {
  const QuizResultScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final data = ModalRoute.of(context)!.settings.arguments
        as Map<String, dynamic>;

    // ── Guard: if status is submitted or under_review, redirect to pending review ──
    final status = data['status'] as String?;
    if (status == 'submitted' || status == 'under_review') {
      // Schedule redirect after build
      WidgetsBinding.instance.addPostFrameCallback((_) {
        final attemptId = data['attempt_id'];
        final quizTitle = data['quiz_title'] as String? ?? 'Quiz';
        if (attemptId != null) {
          Navigator.pushReplacementNamed(
            context,
            '/pending-review',
            arguments: {
              'attempt_id': int.parse(attemptId.toString()),
              'quiz_title': quizTitle,
            },
          );
        } else {
          // Fallback: show not available message on dashboard
          Navigator.pushNamedAndRemoveUntil(
            context,
            '/student-dashboard',
            (route) => false,
          );
        }
      });

      // Show a brief loading screen while redirect happens
      return const Scaffold(
        backgroundColor: Color(0xFFF5F5F5),
        body: Center(
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              CircularProgressIndicator(color: Color(0xFF6C63FF)),
              SizedBox(height: 16),
              Text(
                'Results not yet available.',
                style: TextStyle(
                  color: Color(0xFF6B7080),
                  fontSize: 14,
                ),
              ),
            ],
          ),
        ),
      );
    }

    // ── Normal reviewed result ──
    final score = int.parse(data['score'].toString());
    final totalPoints = int.parse(data['total_points'].toString());
    final percentage = int.parse(data['percentage'].toString());
    final quizTitle = data['quiz_title'] as String;
    final questionResults = data['question_results'] as List;

    Color scoreColor;
    String scoreLabel;
    IconData scoreIcon;
    if (percentage >= 80) {
      scoreColor = Colors.green;
      scoreLabel = 'Excellent!';
      scoreIcon = Icons.emoji_events;
    } else if (percentage >= 60) {
      scoreColor = Colors.orange;
      scoreLabel = 'Good Job!';
      scoreIcon = Icons.thumb_up;
    } else {
      scoreColor = Colors.red;
      scoreLabel = 'Keep Practicing!';
      scoreIcon = Icons.refresh;
    }

    return Scaffold(
      backgroundColor: const Color(0xFFF5F5F5),
      appBar: AppBar(
        title: const Text('Quiz Results'),
        backgroundColor: const Color(0xFF6C63FF),
        foregroundColor: Colors.white,
        automaticallyImplyLeading: false,
      ),
      body: SingleChildScrollView(
        child: Column(
          children: [
            // Score header
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(30),
              decoration: const BoxDecoration(
                color: Color(0xFF6C63FF),
                borderRadius: BorderRadius.only(
                  bottomLeft: Radius.circular(30),
                  bottomRight: Radius.circular(30),
                ),
              ),
              child: Column(
                children: [
                  Icon(scoreIcon, color: Colors.white, size: 60),
                  const SizedBox(height: 12),
                  Text(
                    scoreLabel,
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 24,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    quizTitle,
                    style: const TextStyle(
                      color: Colors.white70,
                      fontSize: 14,
                    ),
                  ),
                  const SizedBox(height: 24),

                  // Score circle
                  Container(
                    width: 140,
                    height: 140,
                    decoration: BoxDecoration(
                      color: Colors.white,
                      shape: BoxShape.circle,
                      boxShadow: [
                        BoxShadow(
                          color: Colors.black.withOpacity(0.1),
                          blurRadius: 20,
                          offset: const Offset(0, 10),
                        ),
                      ],
                    ),
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Text(
                          '$percentage%',
                          style: TextStyle(
                            fontSize: 36,
                            fontWeight: FontWeight.bold,
                            color: scoreColor,
                          ),
                        ),
                        Text(
                          '$score / $totalPoints pts',
                          style: const TextStyle(
                            fontSize: 13,
                            color: Colors.grey,
                            fontWeight: FontWeight.w500,
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 24),

                  // Stats row
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                    children: [
                      _buildStat(
                        label: 'Correct',
                        value: questionResults
                            .where((q) => q['is_correct'] == true)
                            .length
                            .toString(),
                        color: Colors.green,
                      ),
                      _buildStat(
                        label: 'Wrong',
                        value: questionResults
                            .where((q) => q['is_correct'] == false)
                            .length
                            .toString(),
                        color: Colors.red,
                      ),
                      _buildStat(
                        label: 'Total',
                        value: questionResults.length.toString(),
                        color: Colors.white,
                      ),
                    ],
                  ),
                ],
              ),
            ),

            // Question results
            Padding(
              padding: const EdgeInsets.all(20),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'Question Review',
                    style: TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                      color: Color(0xFF333333),
                    ),
                  ),
                  const SizedBox(height: 16),
                  ...questionResults.asMap().entries.map((entry) {
                    final index = entry.key;
                    final q = Map<String, dynamic>.from(entry.value);
                    return Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            Container(
                              width: 28,
                              height: 28,
                              decoration: BoxDecoration(
                                color: q['is_correct'] == true
                                    ? Colors.green
                                    : Colors.red,
                                shape: BoxShape.circle,
                              ),
                              child: Center(
                                child: Text(
                                  '${index + 1}',
                                  style: const TextStyle(
                                    color: Colors.white,
                                    fontWeight: FontWeight.bold,
                                    fontSize: 12,
                                  ),
                                ),
                              ),
                            ),
                            const SizedBox(width: 8),
                            Text(
                              '${q['points_earned']}/${q['points']} pts',
                              style: TextStyle(
                                color: q['is_correct'] == true
                                    ? Colors.green
                                    : Colors.red,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 10),
                        _buildResultWidget(q),
                        const SizedBox(height: 20),
                        if (index < questionResults.length - 1)
                          const Divider(height: 20),
                      ],
                    );
                  }),
                  const SizedBox(height: 20),

                  // Back to dashboard button
                  SizedBox(
                    width: double.infinity,
                    height: 54,
                    child: ElevatedButton.icon(
                      onPressed: () =>
                          Navigator.pushNamedAndRemoveUntil(
                        context,
                        '/student-dashboard',
                        (route) => false,
                      ),
                      icon: const Icon(Icons.home, color: Colors.white),
                      label: const Text(
                        'Back to Dashboard',
                        style: TextStyle(
                          color: Colors.white,
                          fontSize: 16,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFF6C63FF),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(12),
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(height: 20),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildStat({
    required String label,
    required String value,
    required Color color,
  }) {
    return Column(
      children: [
        Text(
          value,
          style: TextStyle(
            color: color,
            fontSize: 28,
            fontWeight: FontWeight.bold,
          ),
        ),
        Text(
          label,
          style: const TextStyle(
            color: Colors.white70,
            fontSize: 13,
          ),
        ),
      ],
    );
  }

  Widget _buildResultWidget(Map<String, dynamic> question) {
    final qType = question['question_type'] as String;
    final answerGiven = question['answer_given'] as String;

    switch (qType) {
      case 'multiple_choice':
        return MultipleChoiceResultWidget(
          question: question,
          selectedAnswerId: int.tryParse(answerGiven),
        );
      case 'true_false':
        return TrueFalseResultWidget(
          question: question,
          selectedAnswerId: int.tryParse(answerGiven),
        );
      case 'identification':
        return IdentificationResultWidget(
          question: question,
          studentAnswer: answerGiven,
        );
      case 'matching':
        Map<String, String> matches = {};
        try {
          final rawMap = Map<String, dynamic>.from(
            (answerGiven.isNotEmpty ? _parseJsonMap(answerGiven) : {}),
          );
          matches =
              rawMap.map((key, value) => MapEntry(key, value.toString()));
        } catch (e) {
          matches = {};
        }
        return MatchingResultWidget(
          question: question,
          studentAnswers: matches,
        );
      default:
        return const SizedBox();
    }
  }

  Map<String, dynamic> _parseJsonMap(String jsonString) {
    try {
      final clean = jsonString
          .trim()
          .replaceAll('{', '')
          .replaceAll('}', '');
      final pairs = clean.split('","');
      final result = <String, dynamic>{};
      for (var pair in pairs) {
        final parts = pair.replaceAll('"', '').split(':');
        if (parts.length >= 2) {
          final key = parts[0].trim();
          final value = parts.sublist(1).join(':').trim();
          result[key] = value;
        }
      }
      return result;
    } catch (e) {
      return {};
    }
  }
}