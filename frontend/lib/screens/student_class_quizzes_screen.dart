import 'dart:ui';
import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../services/auth_service.dart';

// ─── THEME CONSTANTS ─────────────────────────────────────────────────────────
class _AppTheme {
  static const Color primary = Color(0xFF5B2A9B);
  static const Color primaryDark = Color(0xFF3A1A6B);
  static const Color primaryLight = Color(0xFFEDE7F2);
  static const Color bg = Color(0xFFFAF6EC);
  static const Color surface = Colors.white;
  static const Color textDark = Color(0xFF1F1235);
  static const Color textMid = Color(0xFF7A6E99);
  static const Color textLight = Color(0xFFA99BC4);
  static const Color success = Color(0xFF22C55E);
  static const Color warning = Color(0xFFF59E0B);
  static const Color danger = Color(0xFFEF4444);
  static const Color accent = Color(0xFFF2C94C);
  static const Color accentDark = Color(0xFFE0A93B);
  static const Color softPurple = Color(0xFFC9A8F0);
  static const Color highlightPurple = Color(0xFFA14BC9);
  static const Color plumShadow = Color(0xFF2A1247);

  static BoxDecoration get cardDecoration => BoxDecoration(
        color: surface,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: plumShadow.withOpacity(0.08),
            blurRadius: 12,
            offset: const Offset(0, 4),
          ),
        ],
      );
}

// ─── Attempt status helpers ──────────────────────────────────────────────────
bool _isAwaitingReview(Map<String, dynamic> quiz) {
  final status = quiz['attempt_status'] as String?;
  return status == 'submitted' || status == 'under_review';
}

bool _isReviewed(Map<String, dynamic> quiz) {
  final status = quiz['attempt_status'] as String?;
  return status == 'reviewed' || status == 'completed';
}

class StudentClassQuizzesScreen extends StatefulWidget {
  final int classId;
  final String className;

  const StudentClassQuizzesScreen({
    super.key,
    required this.classId,
    required this.className,
  });

  @override
  State<StudentClassQuizzesScreen> createState() =>
      _StudentClassQuizzesScreenState();
}

class _StudentClassQuizzesScreenState
    extends State<StudentClassQuizzesScreen>
    with SingleTickerProviderStateMixin {
  late TabController _tabController;
  bool _isLoading = true;
  String? _errorMessage;
  List<dynamic> _quizzes = [];

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 4, vsync: this);
    _loadQuizzes();
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  Future<void> _loadQuizzes() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    final result = await AuthService.authGet(
      '/student/classes/${widget.classId}/quizzes',
    );

    setState(() {
      _isLoading = false;
      if (result['success']) {
        _quizzes = result['data']['quizzes'] as List;
      } else {
        _errorMessage = result['message'];
      }
    });
  }

  List<dynamic> get _assignedQuizzes {
    final now = DateTime.now();
    return _quizzes.where((q) {
      if (q['already_taken'] == true) return false;
      final dueDate = q['due_date'] != null
          ? DateTime.tryParse(q['due_date'])
          : null;
      return dueDate == null || dueDate.isAfter(now);
    }).toList();
  }

  List<dynamic> get _doneQuizzes {
    return _quizzes.where((q) => q['already_taken'] == true).toList();
  }

  List<dynamic> get _missingQuizzes {
    final now = DateTime.now();
    return _quizzes.where((q) {
      if (q['already_taken'] == true) return false;
      final dueDate = q['due_date'] != null
          ? DateTime.tryParse(q['due_date'])
          : null;
      return dueDate != null && dueDate.isBefore(now);
    }).toList();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: _AppTheme.bg,
      body: _buildBody(),
    );
  }

  Widget _buildBody() {
    if (_isLoading) {
      return const Center(
        child: CircularProgressIndicator(color: _AppTheme.primary),
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
                  color: _AppTheme.danger.withOpacity(0.1),
                  shape: BoxShape.circle,
                ),
                child: Icon(Icons.error_outline_rounded,
                    size: 48, color: _AppTheme.danger),
              ),
              const SizedBox(height: 16),
              Text(_errorMessage!,
                  textAlign: TextAlign.center,
                  style: const TextStyle(color: _AppTheme.textMid)),
              const SizedBox(height: 20),
              ElevatedButton(
                onPressed: _loadQuizzes,
                style: ElevatedButton.styleFrom(
                  backgroundColor: _AppTheme.accent,
                  foregroundColor: _AppTheme.textDark,
                ),
                child: const Text('Retry',
                    style: TextStyle(
                        color: _AppTheme.textDark,
                        fontWeight: FontWeight.bold)),
              ),
            ],
          ),
        ),
      );
    }

    return RefreshIndicator(
      onRefresh: _loadQuizzes,
      color: _AppTheme.accent,
      backgroundColor: _AppTheme.surface,
      child: CustomScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        slivers: [
          // ── Header ──
          SliverToBoxAdapter(
            child: Container(
              padding: const EdgeInsets.fromLTRB(20, 50, 20, 24),
              decoration: const BoxDecoration(
                gradient: LinearGradient(
                  colors: [_AppTheme.primary, _AppTheme.primaryDark],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                ),
                borderRadius: BorderRadius.only(
                  bottomLeft: Radius.circular(32),
                  bottomRight: Radius.circular(32),
                ),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      GestureDetector(
                        onTap: () => Navigator.pop(context),
                        child: Container(
                          padding: const EdgeInsets.all(8),
                          decoration: BoxDecoration(
                            color: Colors.white.withOpacity(0.2),
                            borderRadius: BorderRadius.circular(10),
                          ),
                          child: const Icon(
                              Icons.arrow_back_ios_new_rounded,
                              color: Colors.white,
                              size: 18),
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Text(
                          widget.className,
                          style: const TextStyle(
                            color: Colors.white,
                            fontSize: 20,
                            fontWeight: FontWeight.bold,
                            letterSpacing: -0.3,
                          ),
                          overflow: TextOverflow.ellipsis,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 16),
                  Row(
                    children: [
                      _buildHeaderStat('${_quizzes.length}', 'Total'),
                      const SizedBox(width: 10),
                      _buildHeaderStat(
                          '${_doneQuizzes.length}', 'Done'),
                      const SizedBox(width: 10),
                      _buildHeaderStat(
                          '${_assignedQuizzes.length}', 'Assigned'),
                      const SizedBox(width: 10),
                      _buildHeaderStat(
                          '${_missingQuizzes.length}', 'Missing'),
                    ],
                  ),
                ],
              ),
            ),
          ),

          // ── Filter chips ──
          SliverToBoxAdapter(
            child: Padding(
              padding: const EdgeInsets.fromLTRB(20, 16, 20, 0),
              child: SingleChildScrollView(
                scrollDirection: Axis.horizontal,
                child: Row(
                  children: [
                    _buildFilterChip('all', 'All', _quizzes.length),
                    const SizedBox(width: 8),
                    _buildFilterChip('assigned', 'Assigned',
                        _assignedQuizzes.length),
                    const SizedBox(width: 8),
                    _buildFilterChip(
                        'done', 'Done', _doneQuizzes.length),
                    const SizedBox(width: 8),
                    _buildFilterChip('missing', 'Missing',
                        _missingQuizzes.length),
                  ],
                ),
              ),
            ),
          ),

          // ── List or empty ──
          _quizzes.isEmpty
              ? SliverFillRemaining(
                  hasScrollBody: false,
                  child: Center(
                    child: Padding(
                      padding: const EdgeInsets.all(40),
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Container(
                            padding: const EdgeInsets.all(24),
                            decoration: BoxDecoration(
                                color: _AppTheme.primaryLight,
                                shape: BoxShape.circle),
                            child: Icon(Icons.quiz_outlined,
                                size: 48,
                                color: _AppTheme.primary
                                    .withOpacity(0.5)),
                          ),
                          const SizedBox(height: 16),
                          const Text('No quizzes yet.',
                              style: TextStyle(
                                  color: _AppTheme.textMid,
                                  fontSize: 16,
                                  fontWeight: FontWeight.w500)),
                          const SizedBox(height: 4),
                          const Text(
                              "Your teacher hasn't assigned any quizzes yet.",
                              textAlign: TextAlign.center,
                              style: TextStyle(
                                  color: _AppTheme.textLight,
                                  fontSize: 13)),
                        ],
                      ),
                    ),
                  ),
                )
              : _buildFilteredList(),
        ],
      ),
    );
  }

  Widget _buildFilteredList() {
    final filter = _tabController.index;
    List<dynamic> quizzes;
    switch (filter) {
      case 1:
        quizzes = _assignedQuizzes;
        break;
      case 2:
        quizzes = _doneQuizzes;
        break;
      case 3:
        quizzes = _missingQuizzes;
        break;
      default:
        quizzes = _quizzes;
    }

    if (quizzes.isEmpty) {
      return SliverFillRemaining(
        hasScrollBody: false,
        child: Center(
          child: Padding(
            padding: const EdgeInsets.all(40),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Container(
                  padding: const EdgeInsets.all(24),
                  decoration: BoxDecoration(
                      color: _AppTheme.primaryLight,
                      shape: BoxShape.circle),
                  child: Icon(Icons.search_off_rounded,
                      size: 48,
                      color: _AppTheme.primary.withOpacity(0.5)),
                ),
                const SizedBox(height: 16),
                const Text('No quizzes found.',
                    style: TextStyle(
                        color: _AppTheme.textMid,
                        fontSize: 16,
                        fontWeight: FontWeight.w500)),
              ],
            ),
          ),
        ),
      );
    }

    return SliverPadding(
      padding: const EdgeInsets.fromLTRB(20, 14, 20, 24),
      sliver: SliverList(
        delegate: SliverChildBuilderDelegate(
          (context, index) => _buildQuizCard(
              Map<String, dynamic>.from(quizzes[index])),
          childCount: quizzes.length,
        ),
      ),
    );
  }

  Widget _buildHeaderStat(String value, String label) {
    return Expanded(
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 10),
        decoration: BoxDecoration(
          color: Colors.white.withOpacity(0.15),
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: _AppTheme.softPurple.withOpacity(0.35)),
        ),
        child: Column(
          children: [
            Text(value,
                style: const TextStyle(
                    color: Colors.white,
                    fontWeight: FontWeight.bold,
                    fontSize: 18)),
            Text(label,
                style: TextStyle(
                    color: Colors.white.withOpacity(0.75), fontSize: 11)),
          ],
        ),
      ),
    );
  }

  Widget _buildFilterChip(String value, String label, int count) {
    final indexMap = {'all': 0, 'assigned': 1, 'done': 2, 'missing': 3};
    final index = indexMap[value] ?? 0;
    final isSelected = _tabController.index == index;

    return GestureDetector(
      onTap: () => setState(() => _tabController.index = index),
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 180),
        padding:
            const EdgeInsets.symmetric(horizontal: 14, vertical: 7),
        decoration: BoxDecoration(
          color: isSelected ? _AppTheme.primary : _AppTheme.surface,
          borderRadius: BorderRadius.circular(20),
          border: Border.all(
              color: isSelected
                  ? _AppTheme.primary
                  : _AppTheme.softPurple.withOpacity(0.5)),
          boxShadow: isSelected
              ? [
                  BoxShadow(
                      color: _AppTheme.plumShadow.withOpacity(0.22),
                      blurRadius: 8,
                      offset: const Offset(0, 2))
                ]
              : [],
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(
              label,
              style: TextStyle(
                color: isSelected ? Colors.white : _AppTheme.textMid,
                fontWeight: isSelected
                    ? FontWeight.bold
                    : FontWeight.normal,
                fontSize: 12,
              ),
            ),
            const SizedBox(width: 4),
            Container(
              padding: const EdgeInsets.symmetric(
                  horizontal: 5, vertical: 1),
              decoration: BoxDecoration(
                color: isSelected
                    ? Colors.white.withOpacity(0.22)
                    : _AppTheme.primaryLight,
                borderRadius: BorderRadius.circular(8),
              ),
              child: Text(
                '$count',
                style: TextStyle(
                  fontSize: 10,
                  fontWeight: FontWeight.bold,
                  color: isSelected
                      ? Colors.white
                      : _AppTheme.primary,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildQuizCard(Map<String, dynamic> quiz) {
    final alreadyTaken = quiz['already_taken'] == true;
    final awaitingReview = _isAwaitingReview(quiz);
    final reviewed = _isReviewed(quiz);
    final attemptStatus = quiz['attempt_status'] as String?;
    final attemptId = quiz['attempt_id'];

    final dueDate = quiz['due_date'] != null
        ? DateTime.tryParse(quiz['due_date'])
        : null;
    final isPastDue = dueDate != null &&
        dueDate.isBefore(DateTime.now()) &&
        !alreadyTaken;

    // Icon
    IconData cardIcon;
    Color iconBg;
    Color iconColor;

    if (awaitingReview) {
      cardIcon = attemptStatus == 'under_review'
          ? Icons.rate_review_rounded
          : Icons.hourglass_top_rounded;
      iconBg = _AppTheme.warning.withOpacity(0.1);
      iconColor = _AppTheme.warning;
    } else if (alreadyTaken) {
      cardIcon = Icons.check_circle_rounded;
      iconBg = _AppTheme.success.withOpacity(0.1);
      iconColor = _AppTheme.success;
    } else if (isPastDue) {
      cardIcon = Icons.warning_rounded;
      iconBg = _AppTheme.danger.withOpacity(0.1);
      iconColor = _AppTheme.danger;
    } else {
      cardIcon = Icons.quiz_rounded;
      iconBg = _AppTheme.primaryLight;
      iconColor = _AppTheme.primary;
    }

    // Badge
    String badgeText;
    Color badgeTextColor;
    Color badgeBg;
    Color badgeBorder;

    if (awaitingReview) {
      badgeText = attemptStatus == 'under_review'
          ? '🔍 Under Review'
          : '⏳ Awaiting Review';
      badgeTextColor = _AppTheme.warning;
      badgeBg = _AppTheme.warning.withOpacity(0.1);
      badgeBorder = _AppTheme.warning.withOpacity(0.3);
    } else if (alreadyTaken) {
      badgeText = '✓ Done';
      badgeTextColor = _AppTheme.success;
      badgeBg = _AppTheme.success.withOpacity(0.1);
      badgeBorder = _AppTheme.success.withOpacity(0.3);
    } else if (isPastDue) {
      badgeText = 'Past Due';
      badgeTextColor = _AppTheme.danger;
      badgeBg = _AppTheme.danger.withOpacity(0.1);
      badgeBorder = _AppTheme.danger.withOpacity(0.3);
    } else {
      badgeText = 'Pending';
      badgeTextColor = _AppTheme.highlightPurple;
      badgeBg = _AppTheme.primaryLight;
      badgeBorder = _AppTheme.softPurple.withOpacity(0.5);
    }

    // Tap behavior
    VoidCallback? onTap;
    if (awaitingReview && attemptId != null) {
      onTap = () => Navigator.pushNamed(
            context,
            '/pending-review',
            arguments: {
              'attempt_id': int.parse(attemptId.toString()),
              'quiz_title': quiz['title'],
            },
          );
    } else if (!alreadyTaken && !isPastDue) {
      onTap = () async {
        await Navigator.pushNamed(
          context,
          '/quiz-taking',
          arguments: {
            'quiz_id': quiz['id'],
            'quiz_title': quiz['title'],
          },
        );
        _loadQuizzes();
      };
    }

    return Container(
      margin: const EdgeInsets.only(bottom: 14),
      decoration: _AppTheme.cardDecoration,
      child: InkWell(
        borderRadius: BorderRadius.circular(16),
        onTap: onTap,
        splashColor: _AppTheme.primaryLight,
        highlightColor: _AppTheme.primaryLight.withOpacity(0.5),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Container(
                    width: 48,
                    height: 48,
                    decoration: BoxDecoration(
                      color: iconBg,
                      borderRadius: BorderRadius.circular(13),
                    ),
                    child:
                        Icon(cardIcon, color: iconColor, size: 26),
                  ),
                  const SizedBox(width: 14),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          quiz['title'],
                          style: const TextStyle(
                              fontWeight: FontWeight.bold,
                              fontSize: 15,
                              color: _AppTheme.textDark),
                        ),
                        if (quiz['description'] != null &&
                            (quiz['description'] as String)
                                .isNotEmpty) ...[
                          const SizedBox(height: 4),
                          Text(
                            quiz['description'],
                            maxLines: 2,
                            overflow: TextOverflow.ellipsis,
                            style: const TextStyle(
                                fontSize: 12,
                                color: _AppTheme.textMid),
                          ),
                        ],
                      ],
                    ),
                  ),
                  const SizedBox(width: 8),
                  Container(
                    padding: const EdgeInsets.symmetric(
                        horizontal: 10, vertical: 5),
                    decoration: BoxDecoration(
                      color: badgeBg,
                      borderRadius: BorderRadius.circular(20),
                      border: Border.all(color: badgeBorder),
                    ),
                    child: Text(
                      badgeText,
                      style: TextStyle(
                        color: badgeTextColor,
                        fontWeight: FontWeight.bold,
                        fontSize: 11,
                      ),
                    ),
                  ),
                ],
              ),

              const SizedBox(height: 14),
              Divider(color: _AppTheme.softPurple.withOpacity(0.2), height: 1),
              const SizedBox(height: 12),

              // Meta row
              Row(
                children: [
                  _buildMeta(Icons.help_outline_rounded,
                      '${quiz['questions_count']} questions'),
                  const Spacer(),
                  if (dueDate != null)
                    _buildMeta(
                      Icons.calendar_today_rounded,
                      _formatDueDate(dueDate),
                      color: _getDueDateColor(dueDate, alreadyTaken),
                    )
                  else
                    _buildMeta(
                        Icons.calendar_today_outlined, 'No deadline',
                        color: _AppTheme.textLight),
                ],
              ),

              // Score / pending indicator
              if (reviewed && quiz['score'] != null) ...[
                const SizedBox(height: 10),
                Container(
                  width: double.infinity,
                  padding: const EdgeInsets.symmetric(
                      vertical: 8, horizontal: 12),
                  decoration: BoxDecoration(
                    color: _getScoreColor(
                            quiz['score'], quiz['total_points'])
                        .withOpacity(0.1),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: Row(
                    children: [
                      Icon(Icons.emoji_events,
                          size: 16,
                          color: _getScoreColor(
                              quiz['score'], quiz['total_points'])),
                      const SizedBox(width: 6),
                      Text(
                        'Score: ${quiz['score']} / ${quiz['total_points']}',
                        style: TextStyle(
                          fontSize: 13,
                          fontWeight: FontWeight.bold,
                          color: _getScoreColor(
                              quiz['score'], quiz['total_points']),
                        ),
                      ),
                    ],
                  ),
                ),
              ] else if (awaitingReview) ...[
                const SizedBox(height: 10),
                Container(
                  width: double.infinity,
                  padding: const EdgeInsets.symmetric(
                      vertical: 8, horizontal: 12),
                  decoration: BoxDecoration(
                    color: _AppTheme.warning.withOpacity(0.08),
                    borderRadius: BorderRadius.circular(10),
                    border: Border.all(
                        color: _AppTheme.warning.withOpacity(0.25)),
                  ),
                  child: const Row(
                    children: [
                      Icon(Icons.lock_clock_rounded,
                          size: 16, color: _AppTheme.warning),
                      SizedBox(width: 6),
                      Text(
                        'Score hidden — pending teacher review',
                        style: TextStyle(
                          fontSize: 13,
                          fontWeight: FontWeight.bold,
                          color: _AppTheme.warning,
                        ),
                      ),
                    ],
                  ),
                ),
              ],

              const SizedBox(height: 12),
              SizedBox(
                width: double.infinity,
                height: 44,
                child: ElevatedButton(
                  onPressed: onTap,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: awaitingReview
                        ? _AppTheme.warning.withOpacity(0.15)
                        : alreadyTaken
                            ? _AppTheme.primaryLight
                            : isPastDue
                                ? _AppTheme.danger.withOpacity(0.15)
                                : _AppTheme.accent,
                    disabledBackgroundColor: alreadyTaken
                        ? _AppTheme.primaryLight
                        : _AppTheme.danger.withOpacity(0.1),
                    shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12)),
                    elevation: awaitingReview || alreadyTaken || isPastDue
                        ? 0
                        : 2,
                    shadowColor: _AppTheme.accentDark.withOpacity(0.4),
                  ),
                  child: Text(
                    awaitingReview
                        ? 'View Submission'
                        : alreadyTaken
                            ? 'Already Completed'
                            : isPastDue
                                ? 'Past Due'
                                : 'Take Quiz',
                    style: TextStyle(
                      color: awaitingReview
                          ? _AppTheme.warning
                          : alreadyTaken
                              ? _AppTheme.textLight
                              : isPastDue
                                  ? _AppTheme.danger
                                  : _AppTheme.textDark,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildMeta(IconData icon, String label, {Color? color}) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Icon(icon, size: 14, color: color ?? _AppTheme.textLight),
        const SizedBox(width: 5),
        Text(label,
            style: TextStyle(
                fontSize: 12, color: color ?? _AppTheme.textMid)),
      ],
    );
  }

  Color _getDueDateColor(DateTime dueDate, bool alreadyTaken) {
    if (alreadyTaken) return _AppTheme.textLight;
    final now = DateTime.now();
    if (dueDate.isBefore(now)) return _AppTheme.danger;
    if (dueDate.difference(now).inDays <= 2) return _AppTheme.warning;
    return _AppTheme.success;
  }

  String _formatDueDate(DateTime dueDate) {
    return DateFormat('MMM d, yyyy · h:mm a').format(dueDate);
  }

  Color _getScoreColor(int? score, int? total) {
    if (total == null || total == 0) return _AppTheme.textLight;
    final pct = ((score ?? 0) / total) * 100;
    if (pct >= 80) return _AppTheme.success;
    if (pct >= 60) return _AppTheme.warning;
    return _AppTheme.danger;
  }
}