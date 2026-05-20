import 'package:flutter/material.dart';
import '../services/auth_service.dart';

class QuizAnalyticsScreen extends StatefulWidget {
  final int quizId;
  final String quizTitle;

  const QuizAnalyticsScreen({
    super.key,
    required this.quizId,
    required this.quizTitle,
  });

  @override
  State<QuizAnalyticsScreen> createState() => _QuizAnalyticsScreenState();
}

class _QuizAnalyticsScreenState extends State<QuizAnalyticsScreen> {
  // ── Brand colors ──────────────────────────────────────────────
  static const Color primaryColor    = Color(0xFF5B2A9B);
  static const Color primaryDark     = Color(0xFF3A1A6B);
  static const Color primaryLight    = Color(0xFFEDE7F2);
  static const Color accentGold      = Color(0xFFF2C94C);
  static const Color softPurple      = Color(0xFFC9A8F0);
  static const Color highlightPurple = Color(0xFFA14BC9);
  static const Color background      = Color(0xFFFAF6EC);
  static const Color midnightPlum    = Color(0xFF1F1235);
  static const Color mutedText       = Color(0xFF7B6F8E);
  static const Color subtleText      = Color(0xFFA99BC4);
  static const Color plumShadow      = Color(0xFF2A1247);
  static const Color successGreen    = Color(0xFF22C55E);
  static const Color warningAmber    = Color(0xFFF59E0B);
  static const Color dangerRed       = Color(0xFFEF4444);

  bool _isLoading = true;
  String? _errorMessage;
  Map<String, dynamic>? _data;

  @override
  void initState() {
    super.initState();
    _loadAnalytics();
  }

  Future<void> _loadAnalytics() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    final result = await AuthService.authGet(
      '/teacher/quizzes/${widget.quizId}/analytics',
    );

    if (!mounted) return;

    setState(() {
      _isLoading = false;
      if (result['success']) {
        _data = Map<String, dynamic>.from(result['data']);
      } else {
        _errorMessage = result['message'];
      }
    });
  }

  num _toNum(dynamic value) {
    if (value is num) return value;
    return num.tryParse(value.toString()) ?? 0;
  }

  String _formatNumber(dynamic value, {int decimals = 1}) {
    final numValue = _toNum(value);
    if (numValue == numValue.roundToDouble()) {
      return numValue.toInt().toString();
    }
    return numValue.toStringAsFixed(decimals);
  }

  Color _difficultyColor(String difficulty) {
    switch (difficulty.toLowerCase()) {
      case 'easy':
        return successGreen;
      case 'moderate':
        return warningAmber;
      case 'hard':
        return dangerRed;
      default:
        return mutedText;
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: background,
      appBar: AppBar(
        flexibleSpace: Container(
          decoration: const BoxDecoration(
            gradient: LinearGradient(
              colors: [primaryColor, primaryDark],
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
            ),
          ),
        ),
        backgroundColor: Colors.transparent,
        foregroundColor: Colors.white,
        title: Text(
          '${widget.quizTitle} Analytics',
          overflow: TextOverflow.ellipsis,
          style: const TextStyle(fontSize: 16),
        ),
      ),
      body: _buildBody(),
    );
  }

  Widget _buildBody() {
    if (_isLoading) {
      return const Center(
        child: CircularProgressIndicator(color: primaryColor),
      );
    }

    if (_errorMessage != null) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(20),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              const Icon(Icons.analytics_outlined, size: 64, color: subtleText),
              const SizedBox(height: 16),
              Text(
                _errorMessage!,
                textAlign: TextAlign.center,
                style: const TextStyle(color: dangerRed),
              ),
              const SizedBox(height: 16),
              ElevatedButton.icon(
                onPressed: _loadAnalytics,
                icon: const Icon(Icons.refresh, color: midnightPlum),
                label: const Text(
                  'Retry',
                  style: TextStyle(color: midnightPlum),
                ),
                style: ElevatedButton.styleFrom(
                  backgroundColor: accentGold,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                ),
              ),
            ],
          ),
        ),
      );
    }

    final summary =
        Map<String, dynamic>.from(_data?['summary'] ?? <String, dynamic>{});
    final difficultyList = List<Map<String, dynamic>>.from(
      (_data?['difficulty_analysis'] ?? []).map(
        (e) => Map<String, dynamic>.from(e),
      ),
    );
    final comparisonList = List<Map<String, dynamic>>.from(
      (_data?['quiz_comparison'] ?? []).map(
        (e) => Map<String, dynamic>.from(e),
      ),
    );

    return RefreshIndicator(
      onRefresh: _loadAnalytics,
      color: primaryColor,
      child: SingleChildScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.fromLTRB(16, 16, 16, 24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            _buildHeaderCard(),
            const SizedBox(height: 16),
            _buildSectionTitle('Quiz Summary Statistics'),
            const SizedBox(height: 10),
            _buildSummaryGrid(summary),
            const SizedBox(height: 20),
            _buildSectionTitle('Question Difficulty Analysis'),
            const SizedBox(height: 10),
            _buildDifficultyCard(difficultyList),
            const SizedBox(height: 20),
            _buildSectionTitle('Quiz Comparison'),
            const SizedBox(height: 10),
            _buildComparisonCard(comparisonList),
          ],
        ),
      ),
    );
  }

  Widget _buildHeaderCard() {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [primaryColor, primaryDark],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(18),
        boxShadow: [
          BoxShadow(
            color: plumShadow.withOpacity(0.22),
            blurRadius: 14,
            offset: const Offset(0, 6),
          ),
        ],
      ),
      child: const Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(Icons.analytics_outlined, color: Colors.white, size: 30),
          SizedBox(height: 10),
          Text(
            'Quiz Analytics',
            style: TextStyle(
              color: Colors.white,
              fontSize: 20,
              fontWeight: FontWeight.bold,
            ),
          ),
          SizedBox(height: 6),
          Text(
            'View summary statistics, question difficulty, and quiz comparison insights for this assessment.',
            style: TextStyle(
              color: Colors.white70,
              fontSize: 13,
              height: 1.4,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSectionTitle(String title) {
    return Text(
      title,
      style: const TextStyle(
        fontSize: 17,
        fontWeight: FontWeight.bold,
        color: midnightPlum,
      ),
    );
  }

  Widget _buildSummaryGrid(Map<String, dynamic> summary) {
    return Column(
      children: [
        Row(
          children: [
            Expanded(
              child: _buildStatCard(
                icon: Icons.bar_chart,
                label: 'Average Score',
                value: _formatNumber(summary['average_score'] ?? 0),
                color: primaryColor,
              ),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: _buildStatCard(
                icon: Icons.emoji_events,
                label: 'Highest',
                value: _formatNumber(summary['highest_score'] ?? 0),
                color: successGreen,
              ),
            ),
          ],
        ),
        const SizedBox(height: 10),
        Row(
          children: [
            Expanded(
              child: _buildStatCard(
                icon: Icons.trending_down,
                label: 'Lowest',
                value: _formatNumber(summary['lowest_score'] ?? 0),
                color: dangerRed,
              ),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: _buildStatCard(
                icon: Icons.people,
                label: 'Attempts',
                value: _formatNumber(summary['attempt_count'] ?? 0, decimals: 0),
                color: highlightPurple,
              ),
            ),
          ],
        ),
        const SizedBox(height: 10),
        Row(
          children: [
            Expanded(
              child: _buildStatCard(
                icon: Icons.check_circle,
                label: 'Pass Rate',
                value: '${_formatNumber(summary['pass_rate'] ?? 0)}%',
                color: successGreen,
              ),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: _buildStatCard(
                icon: Icons.show_chart,
                label: 'Std. Deviation',
                value: _formatNumber(summary['standard_deviation'] ?? 0),
                color: warningAmber,
              ),
            ),
          ],
        ),
      ],
    );
  }

  Widget _buildStatCard({
    required IconData icon,
    required String label,
    required String value,
    required Color color,
  }) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: color.withOpacity(0.14)),
        boxShadow: [
          BoxShadow(
            color: plumShadow.withOpacity(0.07),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        children: [
          CircleAvatar(
            radius: 20,
            backgroundColor: color.withOpacity(0.10),
            child: Icon(icon, color: color, size: 20),
          ),
          const SizedBox(height: 10),
          Text(
            value,
            style: const TextStyle(
              fontSize: 22,
              fontWeight: FontWeight.bold,
              color: midnightPlum,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            label,
            textAlign: TextAlign.center,
            style: const TextStyle(
              fontSize: 12,
              color: mutedText,
              fontWeight: FontWeight.w500,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildMiniInfoChip({
    required IconData icon,
    required String label,
    required String value,
    required Color color,
  }) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 10),
      decoration: BoxDecoration(
        color: color.withOpacity(0.08),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Column(
        children: [
          Icon(icon, size: 16, color: color),
          const SizedBox(height: 6),
          Text(
            value,
            style: TextStyle(
              color: color,
              fontWeight: FontWeight.bold,
              fontSize: 13,
            ),
          ),
          const SizedBox(height: 2),
          Text(
            label,
            textAlign: TextAlign.center,
            style: const TextStyle(
              fontSize: 11,
              color: mutedText,
              fontWeight: FontWeight.w500,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildDifficultyCard(List<Map<String, dynamic>> difficultyList) {
    if (difficultyList.isEmpty) {
      return _buildEmptyCard(
        icon: Icons.quiz_outlined,
        title: 'No difficulty data yet',
        subtitle:
            'Question difficulty analysis will appear here once students have completed attempts.',
      );
    }

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(18),
        boxShadow: [
          BoxShadow(
            color: plumShadow.withOpacity(0.07),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        children: [
          ...difficultyList.asMap().entries.map((entry) {
            final row = entry.value;
            final difficulty = (row['difficulty'] ?? 'Unknown').toString();
            final color = _difficultyColor(difficulty);
            final questionLabel =
                (row['question_label'] ?? 'Question').toString();
            final questionText =
                (row['question_text'] ?? 'No question text').toString();
            final correctRate =
                '${_formatNumber(row['correct_rate'] ?? 0)}%';
            final correctCount = _formatNumber(
              row['correct_count'] ?? 0,
              decimals: 0,
            );
            final attemptCount = _formatNumber(
              row['attempt_count'] ?? 0,
              decimals: 0,
            );

            return Container(
              margin: EdgeInsets.only(
                bottom: entry.key == difficultyList.length - 1 ? 0 : 12,
              ),
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                color: color.withOpacity(0.06),
                borderRadius: BorderRadius.circular(14),
                border: Border.all(color: color.withOpacity(0.10)),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.symmetric(
                          horizontal: 10,
                          vertical: 5,
                        ),
                        decoration: BoxDecoration(
                          color: color.withOpacity(0.12),
                          borderRadius: BorderRadius.circular(20),
                        ),
                        child: Text(
                          questionLabel,
                          style: TextStyle(
                            color: color,
                            fontWeight: FontWeight.w700,
                            fontSize: 12,
                          ),
                        ),
                      ),
                      const Spacer(),
                      Container(
                        padding: const EdgeInsets.symmetric(
                          horizontal: 10,
                          vertical: 5,
                        ),
                        decoration: BoxDecoration(
                          color: color.withOpacity(0.12),
                          borderRadius: BorderRadius.circular(20),
                        ),
                        child: Text(
                          difficulty,
                          style: TextStyle(
                            color: color,
                            fontWeight: FontWeight.w700,
                            fontSize: 12,
                          ),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 10),
                  Text(
                    questionText,
                    style: const TextStyle(
                      fontSize: 14,
                      fontWeight: FontWeight.w600,
                      color: midnightPlum,
                      height: 1.4,
                    ),
                  ),
                  const SizedBox(height: 12),
                  Row(
                    children: [
                      Expanded(
                        child: _buildMiniInfoChip(
                          icon: Icons.percent,
                          label: 'Correct Rate',
                          value: correctRate,
                          color: color,
                        ),
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: _buildMiniInfoChip(
                          icon: Icons.check_circle,
                          label: 'Correct',
                          value: correctCount,
                          color: successGreen,
                        ),
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: _buildMiniInfoChip(
                          icon: Icons.people,
                          label: 'Attempts',
                          value: attemptCount,
                          color: highlightPurple,
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            );
          }),
        ],
      ),
    );
  }

  Widget _buildComparisonCard(List<Map<String, dynamic>> comparisonList) {
    if (comparisonList.isEmpty) {
      return _buildEmptyCard(
        icon: Icons.compare_arrows,
        title: 'No comparison data yet',
        subtitle:
            'Quiz comparison will appear here once there are teacher quizzes with completed attempts.',
      );
    }

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(18),
        boxShadow: [
          BoxShadow(
            color: plumShadow.withOpacity(0.07),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        children: comparisonList.asMap().entries.map((entry) {
          final item = entry.value;
          final quizTitle = (item['quiz_title'] ?? 'Quiz').toString();
          final averageScore = _formatNumber(item['average_score'] ?? 0);
          final attemptCount =
              _formatNumber(item['attempt_count'] ?? 0, decimals: 0);
          final passRate = _formatNumber(item['pass_rate'] ?? 0);
          final isCurrentQuiz = item['quiz_id'] == widget.quizId;

          return Container(
            margin: EdgeInsets.only(
              bottom: entry.key == comparisonList.length - 1 ? 0 : 12,
            ),
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: isCurrentQuiz
                  ? primaryColor.withOpacity(0.07)
                  : const Color(0xFFFAF6EC),
              borderRadius: BorderRadius.circular(14),
              border: Border.all(
                color: isCurrentQuiz
                    ? primaryColor.withOpacity(0.25)
                    : softPurple.withOpacity(0.18),
              ),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Expanded(
                      child: Text(
                        quizTitle,
                        style: TextStyle(
                          fontWeight: FontWeight.bold,
                          fontSize: 14,
                          color: isCurrentQuiz ? primaryColor : midnightPlum,
                        ),
                      ),
                    ),
                    if (isCurrentQuiz)
                      Container(
                        padding: const EdgeInsets.symmetric(
                          horizontal: 10,
                          vertical: 5,
                        ),
                        decoration: BoxDecoration(
                          color: primaryColor.withOpacity(0.12),
                          borderRadius: BorderRadius.circular(20),
                        ),
                        child: const Text(
                          'Current Quiz',
                          style: TextStyle(
                            color: primaryColor,
                            fontWeight: FontWeight.w700,
                            fontSize: 11,
                          ),
                        ),
                      ),
                  ],
                ),
                const SizedBox(height: 12),
                Row(
                  children: [
                    Expanded(
                      child: _buildMiniInfoChip(
                        icon: Icons.bar_chart,
                        label: 'Average',
                        value: averageScore,
                        color: primaryColor,
                      ),
                    ),
                    const SizedBox(width: 8),
                    Expanded(
                      child: _buildMiniInfoChip(
                        icon: Icons.people,
                        label: 'Attempts',
                        value: attemptCount,
                        color: highlightPurple,
                      ),
                    ),
                    const SizedBox(width: 8),
                    Expanded(
                      child: _buildMiniInfoChip(
                        icon: Icons.check_circle,
                        label: 'Pass Rate',
                        value: '$passRate%',
                        color: successGreen,
                      ),
                    ),
                  ],
                ),
              ],
            ),
          );
        }).toList(),
      ),
    );
  }

  Widget _buildEmptyCard({
    required IconData icon,
    required String title,
    required String subtitle,
  }) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(18),
        boxShadow: [
          BoxShadow(
            color: plumShadow.withOpacity(0.07),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        children: [
          Icon(icon, size: 34, color: subtleText),
          const SizedBox(height: 10),
          Text(
            title,
            style: const TextStyle(
              fontSize: 15,
              fontWeight: FontWeight.bold,
              color: midnightPlum,
            ),
          ),
          const SizedBox(height: 6),
          Text(
            subtitle,
            textAlign: TextAlign.center,
            style: const TextStyle(
              color: mutedText,
              fontSize: 13,
              height: 1.4,
            ),
          ),
        ],
      ),
    );
  }
}