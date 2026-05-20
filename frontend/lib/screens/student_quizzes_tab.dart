import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../services/auth_service.dart';

// ─── THEME CONSTANTS ─────────────────────────────────────────────────────────
class _AppTheme {
  static const Color primary      = Color(0xFF5B2A9B);
  static const Color primaryDark  = Color(0xFF3A1A6B);
  static const Color primaryLight = Color(0xFFEDE7F2);
  static const Color softPurple   = Color(0xFFC9A8F0);
  static const Color highlight    = Color(0xFFA14BC9);
  static const Color accent       = Color(0xFFF2C94C);
  static const Color bg           = Color(0xFFFAF6EC);
  static const Color surface      = Colors.white;
  static const Color textDark     = Color(0xFF1F1235);
  static const Color textMid      = Color(0xFF7A6E8A);
  static const Color textLight    = Color(0xFFA99BC4);
  static const Color plumShadow   = Color(0xFF2A1247);
  static const Color success      = Color(0xFF22C55E);
  static const Color warning      = Color(0xFFF59E0B);
  static const Color danger       = Color(0xFFEF4444);
  static const Color review       = Color(0xFFA14BC9); // mystic magenta for under_review

  static BoxDecoration get cardDecoration => BoxDecoration(
        color: surface,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: plumShadow.withOpacity(0.10),
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
  // legacy 'completed' treated same as reviewed for backwards compat
  return status == 'reviewed' || status == 'completed';
}

class StudentQuizzesTab extends StatefulWidget {
  const StudentQuizzesTab({super.key});

  @override
  State<StudentQuizzesTab> createState() => _StudentQuizzesTabState();
}

class _StudentQuizzesTabState extends State<StudentQuizzesTab>
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

    final result = await AuthService.authGet('/student/quizzes');

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
                    style: TextStyle(fontWeight: FontWeight.bold)),
              ),
            ],
          ),
        ),
      );
    }

    return Column(
      children: [
        // ── Header with Tabs ──
        Container(
          padding: const EdgeInsets.fromLTRB(20, 50, 20, 16),
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
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Text(
                'My Quizzes',
                style: TextStyle(
                  color: Colors.white,
                  fontSize: 26,
                  fontWeight: FontWeight.bold,
                  letterSpacing: -0.5,
                ),
              ),
              const SizedBox(height: 4),
              Text(
                '${_quizzes.length} total · ${_doneQuizzes.length} completed · ${_assignedQuizzes.length} pending',
                style: TextStyle(color: Colors.white.withOpacity(0.75), fontSize: 13),
              ),
              const SizedBox(height: 16),
              Container(
                decoration: BoxDecoration(
                  color: Colors.white.withOpacity(0.15),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: TabBar(
                  controller: _tabController,
                  isScrollable: true,
                  labelColor: _AppTheme.primary,
                  unselectedLabelColor: Colors.white70,
                  indicator: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(10),
                  ),
                  indicatorSize: TabBarIndicatorSize.tab,
                  labelStyle: const TextStyle(
                    fontWeight: FontWeight.bold,
                    fontSize: 12,
                  ),
                  unselectedLabelStyle: const TextStyle(
                    fontWeight: FontWeight.normal,
                    fontSize: 12,
                  ),
                  padding: const EdgeInsets.all(4),
                  tabs: [
                    _buildTab('All', _quizzes.length),
                    _buildTab('Assigned', _assignedQuizzes.length),
                    _buildTab('Done', _doneQuizzes.length),
                    _buildTab('Missing', _missingQuizzes.length),
                  ],
                ),
              ),
            ],
          ),
        ),

        // ── Tab Content ──
        Expanded(
          child: _quizzes.isEmpty
              ? _buildEmptyState(
                  icon: Icons.quiz_outlined,
                  message: 'No quizzes available yet.',
                )
              : TabBarView(
                  controller: _tabController,
                  children: [
                    _buildQuizList(_quizzes),
                    _buildQuizList(_assignedQuizzes),
                    _buildQuizList(_doneQuizzes),
                    _buildQuizList(_missingQuizzes),
                  ],
                ),
        ),
      ],
    );
  }

  Widget _buildTab(String label, int count) {
    return Tab(
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Text(label),
          const SizedBox(width: 6),
          Container(
            padding:
                const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
            decoration: BoxDecoration(
              color: _AppTheme.primaryLight,
              borderRadius: BorderRadius.circular(6),
            ),
            child: Text(
              '$count',
              style: const TextStyle(
                fontSize: 10,
                fontWeight: FontWeight.bold,
                color: _AppTheme.primary,
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildQuizList(List<dynamic> quizzes) {
    if (quizzes.isEmpty) {
      return _buildEmptyState(
        icon: Icons.search_off_rounded,
        message: 'No quizzes in this category.',
      );
    }

    return RefreshIndicator(
      onRefresh: _loadQuizzes,
      color: _AppTheme.primary,
      child: ListView.builder(
        padding: const EdgeInsets.fromLTRB(20, 16, 20, 24),
        itemCount: quizzes.length,
        itemBuilder: (context, index) {
          return _buildQuizCard(
              Map<String, dynamic>.from(quizzes[index]));
        },
      ),
    );
  }

  Widget _buildEmptyState(
      {required IconData icon, required String message}) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(40),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              padding: const EdgeInsets.all(24),
              decoration: BoxDecoration(
                color: _AppTheme.primaryLight,
                shape: BoxShape.circle,
              ),
              child: Icon(icon,
                  size: 48,
                  color: _AppTheme.primary.withOpacity(0.5)),
            ),
            const SizedBox(height: 16),
            Text(
              message,
              textAlign: TextAlign.center,
              style: const TextStyle(
                color: _AppTheme.textMid,
                fontSize: 16,
                fontWeight: FontWeight.w500,
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
    final isPastDue =
        dueDate != null && dueDate.isBefore(DateTime.now()) && !alreadyTaken;

    // Determine icon and colors
    IconData cardIcon;
    Color iconBg;
    Color iconColor;

    if (awaitingReview) {
      final isUnderReview = attemptStatus == 'under_review';
      cardIcon = isUnderReview
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

    // Determine badge text and colors
    String badgeText;
    Color badgeTextColor;
    Color badgeBg;
    Color badgeBorder;

    if (awaitingReview) {
      final isUnderReview = attemptStatus == 'under_review';
      badgeText = isUnderReview ? '🔍 Under Review' : '⏳ Awaiting Review';
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
      badgeTextColor = _AppTheme.primary;
      badgeBg = _AppTheme.primaryLight;
      badgeBorder = _AppTheme.primary.withOpacity(0.3);
    }

    // Determine tap behavior
    VoidCallback? onTap;
    if (awaitingReview && attemptId != null) {
      // Go to pending review screen
      onTap = () => Navigator.pushNamed(
            context,
            '/pending-review',
            arguments: {
              'attempt_id': int.parse(attemptId.toString()),
              'quiz_title': quiz['title'],
            },
          );
    } else if (!alreadyTaken && !isPastDue) {
      onTap = () => Navigator.pushNamed(
            context,
            '/quiz-taking',
            arguments: {
              'quiz_id': quiz['id'],
              'quiz_title': quiz['title'],
            },
          );
    }
    // reviewed + alreadyTaken → null (no tap, score shown)

    return Container(
      margin: const EdgeInsets.only(bottom: 14),
      decoration: _AppTheme.cardDecoration,
      child: InkWell(
        borderRadius: BorderRadius.circular(16),
        onTap: onTap,
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
                    child: Icon(cardIcon, color: iconColor, size: 26),
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
                            color: _AppTheme.textDark,
                          ),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          '${quiz['class_name']} • ${quiz['teacher_name']}',
                          style: const TextStyle(
                            color: _AppTheme.textMid,
                            fontSize: 12,
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(width: 8),
                  // Status badge
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
              Divider(color: _AppTheme.primaryLight, height: 1),
              const SizedBox(height: 12),

              // Meta row
              Wrap(
                spacing: 16,
                runSpacing: 8,
                crossAxisAlignment: WrapCrossAlignment.center,
                children: [
                  _buildMeta(
                    Icons.help_outline_rounded,
                    '${quiz['questions_count']} questions',
                  ),
                  if (dueDate != null)
                    _buildMeta(
                      Icons.calendar_today_rounded,
                      _formatDueDate(dueDate),
                      color: _getDueDateColor(dueDate, alreadyTaken),
                    )
                  else
                    _buildMeta(
                      Icons.calendar_today_outlined,
                      'No deadline',
                      color: _AppTheme.textLight,
                    ),
                  // Show score only when fully reviewed
                  if (reviewed && quiz['score'] != null) ...[
                    _buildMeta(
                      Icons.star_rounded,
                      'Score: ${quiz['score']}/${quiz['total_points']}',
                      color: _getScoreColor(
                          quiz['score'], quiz['total_points']),
                    ),
                  ],
                  // Show "pending score" label when awaiting review
                  if (awaitingReview)
                    _buildMeta(
                      Icons.lock_clock_rounded,
                      'Score pending',
                      color: _AppTheme.warning,
                    ),
                ],
              ),

              const SizedBox(height: 12),

              // Action button
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
                                ? _AppTheme.danger.withOpacity(0.1)
                                : _AppTheme.accent,
                    disabledBackgroundColor: alreadyTaken
                        ? _AppTheme.primaryLight
                        : _AppTheme.danger.withOpacity(0.05),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                    elevation: 0,
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
        Text(
          label,
          style: TextStyle(
            fontSize: 12,
            color: color ?? _AppTheme.textMid,
            fontWeight: FontWeight.w500,
          ),
        ),
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