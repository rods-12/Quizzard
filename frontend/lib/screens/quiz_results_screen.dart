import 'package:flutter/material.dart';
import '../services/auth_service.dart';

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

class QuizResultsScreen extends StatefulWidget {
  final int quizId;
  final String quizTitle;

  const QuizResultsScreen({
    super.key,
    required this.quizId,
    required this.quizTitle,
  });

  @override
  State<QuizResultsScreen> createState() => _QuizResultsScreenState();
}

class _QuizResultsScreenState extends State<QuizResultsScreen> {
  static const double _passPercentage = 60;

  bool _isLoading = true;
  String? _errorMessage;
  Map<String, dynamic>? _data;

  final TextEditingController _searchController = TextEditingController();

  String _selectedFilter = 'highest';
  List<Map<String, dynamic>> _allResults = [];
  List<Map<String, dynamic>> _visibleResults = [];

  final List<Map<String, String>> _filterOptions = const [
    {'value': 'highest', 'label': 'Highest Score'},
    {'value': 'lowest',  'label': 'Lowest Score'},
    {'value': 'newest',  'label': 'Newest'},
    {'value': 'oldest',  'label': 'Oldest'},
    {'value': 'passed',  'label': 'Passed Only'},
    {'value': 'failed',  'label': 'Failed Only'},
  ];

  @override
  void initState() {
    super.initState();
    _searchController.addListener(_applySearchAndFilter);
    _loadResults();
  }

  @override
  void dispose() {
    _searchController.removeListener(_applySearchAndFilter);
    _searchController.dispose();
    super.dispose();
  }

  Future<void> _loadResults() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    final result = await AuthService.authGet(
      '/teacher/quizzes/${widget.quizId}/results',
    );

    if (!mounted) return;

    setState(() {
      _isLoading = false;

      if (result['success']) {
        _data = Map<String, dynamic>.from(result['data']);
        _allResults = List<Map<String, dynamic>>.from(
          (_data!['results'] as List).map((e) => Map<String, dynamic>.from(e)),
        );
        _applySearchAndFilter();
      } else {
        _errorMessage = result['message'];
      }
    });
  }

  void _applySearchAndFilter() {
    if (_data == null) return;

    final query = _searchController.text.trim().toLowerCase();

    List<Map<String, dynamic>> working = _allResults.map((item) {
      final percentage = _toInt(item['percentage']);
      final score = _toNum(item['score']);
      final totalPoints = _toNum(item['total_points']);
      final completedAt = DateTime.tryParse(item['completed_at']?.toString() ?? '');

      return {
        ...item,
        'percentage': percentage,
        'score': score,
        'total_points': totalPoints,
        'is_passed': percentage >= _passPercentage,
        'completed_at_dt': completedAt,
      };
    }).toList();

    if (query.isNotEmpty) {
      working = working.where((item) {
        final studentName  = (item['student_name']  ?? '').toString().toLowerCase();
        final studentEmail = (item['student_email'] ?? '').toString().toLowerCase();
        return studentName.contains(query) || studentEmail.contains(query);
      }).toList();
    }

    switch (_selectedFilter) {
      case 'lowest':
        working.sort((a, b) => _compareResultsAscending(a, b));
        break;
      case 'newest':
        working.sort((a, b) => _compareDatesDesc(a, b));
        break;
      case 'oldest':
        working.sort((a, b) => _compareDatesAsc(a, b));
        break;
      case 'passed':
        working = working.where((item) => item['is_passed'] == true).toList();
        working.sort((a, b) => _compareResultsDescending(a, b));
        break;
      case 'failed':
        working = working.where((item) => item['is_passed'] == false).toList();
        working.sort((a, b) => _compareResultsDescending(a, b));
        break;
      case 'highest':
      default:
        working.sort((a, b) => _compareResultsDescending(a, b));
        break;
    }

    for (int i = 0; i < working.length; i++) {
      working[i]['rank'] = i + 1;
    }

    if (!mounted) return;
    setState(() {
      _visibleResults = working;
    });
  }

  int _compareResultsDescending(Map<String, dynamic> a, Map<String, dynamic> b) {
    final percentageCompare = _toInt(b['percentage']).compareTo(_toInt(a['percentage']));
    if (percentageCompare != 0) return percentageCompare;

    final scoreCompare = _toNum(b['score']).compareTo(_toNum(a['score']));
    if (scoreCompare != 0) return scoreCompare;

    final totalPointsCompare = _toNum(b['total_points']).compareTo(_toNum(a['total_points']));
    if (totalPointsCompare != 0) return totalPointsCompare;

    return _compareDatesAsc(a, b);
  }

  int _compareResultsAscending(Map<String, dynamic> a, Map<String, dynamic> b) {
    final percentageCompare = _toInt(a['percentage']).compareTo(_toInt(b['percentage']));
    if (percentageCompare != 0) return percentageCompare;

    final scoreCompare = _toNum(a['score']).compareTo(_toNum(b['score']));
    if (scoreCompare != 0) return scoreCompare;

    final totalPointsCompare = _toNum(a['total_points']).compareTo(_toNum(b['total_points']));
    if (totalPointsCompare != 0) return totalPointsCompare;

    return _compareDatesAsc(a, b);
  }

  int _compareDatesDesc(Map<String, dynamic> a, Map<String, dynamic> b) {
    final aDate = a['completed_at_dt'] as DateTime?;
    final bDate = b['completed_at_dt'] as DateTime?;
    if (aDate == null && bDate == null) return 0;
    if (aDate == null) return 1;
    if (bDate == null) return -1;
    return bDate.compareTo(aDate);
  }

  int _compareDatesAsc(Map<String, dynamic> a, Map<String, dynamic> b) {
    final aDate = a['completed_at_dt'] as DateTime?;
    final bDate = b['completed_at_dt'] as DateTime?;
    if (aDate == null && bDate == null) return 0;
    if (aDate == null) return 1;
    if (bDate == null) return -1;
    return aDate.compareTo(bDate);
  }

  int _toInt(dynamic value) {
    if (value is int) return value;
    if (value is double) return value.round();
    return int.tryParse(value.toString()) ?? 0;
  }

  num _toNum(dynamic value) {
    if (value is num) return value;
    return num.tryParse(value.toString()) ?? 0;
  }

  int _getPassCount() {
    return _visibleResults.where((e) => e['is_passed'] == true).length;
  }

  int _getFailCount() {
    return _visibleResults.where((e) => e['is_passed'] == false).length;
  }

  Color _getScoreColor(int percentage) {
    if (percentage >= 80) return _Q.success;
    if (percentage >= 60) return _Q.warning;
    return _Q.danger;
  }

  Color _getMedalColor(int rank) {
    switch (rank) {
      case 1:
        return const Color(0xFFFFD700); // Gold
      case 2:
        return const Color(0xFFC0C0C0); // Silver
      case 3:
        return const Color(0xFFCD7F32); // Bronze
      default:
        return _Q.magenta;
    }
  }

  IconData _getMedalIcon(int rank) {
    switch (rank) {
      case 1:
      case 2:
      case 3:
        return Icons.workspace_premium;
      default:
        return Icons.emoji_events_outlined;
    }
  }

  String _getRankLabel(int rank) {
    switch (rank) {
      case 1:
        return '1st';
      case 2:
        return '2nd';
      case 3:
        return '3rd';
      default:
        return '#$rank';
    }
  }

  String _safeInitial(String? name) {
    if (name == null || name.trim().isEmpty) return '?';
    return name.trim()[0].toUpperCase();
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
                onPressed: _loadResults,
                style: ElevatedButton.styleFrom(
                  backgroundColor: _Q.primary,
                  foregroundColor: Colors.white,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                ),
                child: const Text('Retry'),
              ),
            ],
          ),
        ),
      );
    }

    final totalAttempts      = _toInt(_data!['total_attempts']);
    final averagePercentage  = (_data!['average_percentage'] ?? 0).toString();
    final passCount          = _data!['pass_count'] ?? _getPassCount();
    final failCount          = _data!['fail_count'] ?? _getFailCount();

    return RefreshIndicator(
      onRefresh: _loadResults,
      color: _Q.primary,
      child: Column(
        children: [
          // ── Hero stats banner ──────────────────────────────────────────
          Container(
            width: double.infinity,
            padding: const EdgeInsets.fromLTRB(16, 20, 16, 20),
            decoration: const BoxDecoration(
              gradient: LinearGradient(
                colors: [_Q.primary, _Q.primaryDark],
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
              borderRadius: BorderRadius.only(
                bottomLeft:  Radius.circular(24),
                bottomRight: Radius.circular(24),
              ),
            ),
            child: Column(
              children: [
                Row(
                  children: [
                    Expanded(
                      child: _buildStat(
                        label: 'Total Students',
                        value: totalAttempts.toString(),
                        icon: Icons.people,
                      ),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: _buildStat(
                        label: 'Average',
                        value: '$averagePercentage%',
                        icon: Icons.bar_chart,
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 10),
                Row(
                  children: [
                    Expanded(
                      child: _buildStat(
                        label: 'Passed',
                        value: passCount.toString(),
                        icon: Icons.check_circle,
                      ),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: _buildStat(
                        label: 'Failed',
                        value: failCount.toString(),
                        icon: Icons.cancel,
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),

          // ── Search & filter ────────────────────────────────────────────
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 16, 16, 8),
            child: Column(
              children: [
                TextField(
                  controller: _searchController,
                  style: const TextStyle(color: _Q.textPrimary),
                  decoration: InputDecoration(
                    hintText: 'Search student name or email...',
                    hintStyle: const TextStyle(color: _Q.textSubtle),
                    prefixIcon: const Icon(Icons.search, color: _Q.primary),
                    suffixIcon: _searchController.text.isEmpty
                        ? null
                        : IconButton(
                            onPressed: () => _searchController.clear(),
                            icon: const Icon(Icons.clear, color: _Q.textMuted),
                          ),
                    filled: true,
                    fillColor: _Q.surface,
                    contentPadding: const EdgeInsets.symmetric(vertical: 14),
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(14),
                      borderSide: BorderSide.none,
                    ),
                    focusedBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(14),
                      borderSide: const BorderSide(color: _Q.primary, width: 1.5),
                    ),
                  ),
                ),
                const SizedBox(height: 12),
                Row(
                  children: [
                    const Icon(Icons.filter_list, color: _Q.primary, size: 20),
                    const SizedBox(width: 8),
                    Expanded(
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 12),
                        decoration: BoxDecoration(
                          color: _Q.surface,
                          borderRadius: BorderRadius.circular(14),
                          border: Border.all(color: _Q.softPurple.withOpacity(0.5)),
                        ),
                        child: DropdownButtonHideUnderline(
                          child: DropdownButton<String>(
                            value: _selectedFilter,
                            isExpanded: true,
                            icon: const Icon(Icons.keyboard_arrow_down, color: _Q.primary),
                            style: const TextStyle(color: _Q.textPrimary, fontSize: 14),
                            dropdownColor: _Q.surface,
                            items: _filterOptions.map((option) {
                              return DropdownMenuItem<String>(
                                value: option['value'],
                                child: Text(option['label']!),
                              );
                            }).toList(),
                            onChanged: (value) {
                              if (value == null) return;
                              setState(() => _selectedFilter = value);
                              _applySearchAndFilter();
                            },
                          ),
                        ),
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),

          // ── Results list ───────────────────────────────────────────────
          Expanded(
            child: _visibleResults.isEmpty
                ? _buildEmptyState()
                : ListView.builder(
                    padding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
                    itemCount: _visibleResults.length,
                    itemBuilder: (context, index) {
                      final result     = _visibleResults[index];
                      final percentage = _toInt(result['percentage']);
                      final scoreColor = _getScoreColor(percentage);
                      final rank       = _toInt(result['rank']);
                      final medalColor = _getMedalColor(rank);
                      final isPassed   = result['is_passed'] == true;

                      return GestureDetector(
                        onTap: () => Navigator.pushNamed(
                          context,
                          '/student-attempt-detail',
                          arguments: {
                            'quiz_id':      widget.quizId,
                            'quiz_title':   widget.quizTitle,
                            'attempt_id':   result['attempt_id'],
                            'student_name': result['student_name'],
                          },
                        ),
                        child: Container(
                          margin: const EdgeInsets.only(bottom: 12),
                          padding: const EdgeInsets.all(16),
                          decoration: BoxDecoration(
                            color: rank <= 3
                                ? medalColor.withOpacity(0.08)
                                : _Q.surface,
                            borderRadius: BorderRadius.circular(16),
                            border: Border.all(
                              color: rank <= 3
                                  ? medalColor.withOpacity(0.45)
                                  : _Q.softPurple.withOpacity(0.25),
                              width: 1.2,
                            ),
                            boxShadow: [
                              BoxShadow(
                                color: _Q.plumShadow.withOpacity(0.07),
                                blurRadius: 10,
                                offset: const Offset(0, 3),
                              ),
                            ],
                          ),
                          child: Row(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              // Rank medal column
                              Column(
                                children: [
                                  Container(
                                    width: 52,
                                    height: 52,
                                    decoration: BoxDecoration(
                                      color: medalColor.withOpacity(0.14),
                                      shape: BoxShape.circle,
                                      border: Border.all(
                                        color: medalColor.withOpacity(0.45),
                                      ),
                                    ),
                                    child: Icon(
                                      _getMedalIcon(rank),
                                      color: medalColor,
                                      size: 26,
                                    ),
                                  ),
                                  const SizedBox(height: 6),
                                  Container(
                                    padding: const EdgeInsets.symmetric(
                                        horizontal: 8, vertical: 4),
                                    decoration: BoxDecoration(
                                      color: medalColor,
                                      borderRadius: BorderRadius.circular(20),
                                    ),
                                    child: Text(
                                      _getRankLabel(rank),
                                      style: const TextStyle(
                                        color: Colors.white,
                                        fontSize: 11,
                                        fontWeight: FontWeight.bold,
                                      ),
                                    ),
                                  ),
                                ],
                              ),
                              const SizedBox(width: 12),

                              // Avatar
                              CircleAvatar(
                                backgroundColor: _Q.primary.withOpacity(0.12),
                                child: Text(
                                  _safeInitial(result['student_name']?.toString()),
                                  style: const TextStyle(
                                    color: _Q.primary,
                                    fontWeight: FontWeight.bold,
                                  ),
                                ),
                              ),
                              const SizedBox(width: 12),

                              // Student info
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(
                                      result['student_name']?.toString() ?? 'Unknown Student',
                                      style: const TextStyle(
                                        fontWeight: FontWeight.bold,
                                        fontSize: 15,
                                        color: _Q.textPrimary,
                                      ),
                                    ),
                                    const SizedBox(height: 2),
                                    Text(
                                      result['student_email']?.toString() ?? 'No email',
                                      style: const TextStyle(
                                        fontSize: 12,
                                        color: _Q.textSubtle,
                                      ),
                                    ),
                                    const SizedBox(height: 6),
                                    SingleChildScrollView(
                                      scrollDirection: Axis.horizontal,
                                      child: Row(
                                        children: [
                                          _buildStatusChip(
                                            label: isPassed ? 'Pass' : 'Fail',
                                            color: isPassed ? _Q.success : _Q.danger,
                                            icon: isPassed
                                                ? Icons.check_circle
                                                : Icons.cancel,
                                          ),
                                          const SizedBox(width: 8),
                                          _buildStatusChip(
                                            label:
                                                'Completed ${_formatDate(result['completed_at']?.toString())}',
                                            color: _Q.textMuted,
                                            icon: Icons.calendar_today,
                                          ),
                                        ],
                                      ),
                                    ),
                                  ],
                                ),
                              ),

                              const SizedBox(width: 8),

                              // Score badge
                              Column(
                                crossAxisAlignment: CrossAxisAlignment.end,
                                children: [
                                  Container(
                                    padding: const EdgeInsets.symmetric(
                                        horizontal: 12, vertical: 6),
                                    decoration: BoxDecoration(
                                      color: scoreColor.withOpacity(0.1),
                                      borderRadius: BorderRadius.circular(20),
                                      border: Border.all(
                                        color: scoreColor.withOpacity(0.3),
                                      ),
                                    ),
                                    child: Text(
                                      '$percentage%',
                                      style: TextStyle(
                                        color: scoreColor,
                                        fontWeight: FontWeight.bold,
                                        fontSize: 16,
                                      ),
                                    ),
                                  ),
                                  const SizedBox(height: 6),
                                  Text(
                                    '${result['score']}/${result['total_points']} pts',
                                    style: const TextStyle(
                                      fontSize: 11,
                                      color: _Q.textSubtle,
                                    ),
                                  ),
                                  const SizedBox(height: 6),
                                  const Icon(Icons.chevron_right,
                                      color: _Q.textSubtle),
                                ],
                              ),
                            ],
                          ),
                        ),
                      );
                    },
                  ),
          ),
        ],
      ),
    );
  }

  Widget _buildEmptyState() {
    final hasQuery = _searchController.text.trim().isNotEmpty;

    return Center(
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(
              hasQuery ? Icons.search_off : Icons.inbox,
              size: 60,
              color: _Q.textSubtle,
            ),
            const SizedBox(height: 12),
            Text(
              hasQuery
                  ? 'No students matched your search or filter.'
                  : 'No students have taken this quiz yet.',
              textAlign: TextAlign.center,
              style: const TextStyle(color: _Q.textMuted),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildStatusChip({
    required String label,
    required Color color,
    required IconData icon,
  }) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
      decoration: BoxDecoration(
        color: color.withOpacity(0.10),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: color.withOpacity(0.20)),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 13, color: color),
          const SizedBox(width: 4),
          Flexible(
            child: Text(
              label,
              style: TextStyle(
                color: color,
                fontSize: 11,
                fontWeight: FontWeight.w600,
              ),
              overflow: TextOverflow.ellipsis,
              maxLines: 1,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildStat({
    required String label,
    required String value,
    required IconData icon,
  }) {
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 14, horizontal: 10),
      decoration: BoxDecoration(
        color: Colors.white.withOpacity(0.15),
        borderRadius: BorderRadius.circular(16),
      ),
      child: Column(
        children: [
          Icon(icon, color: Colors.white70, size: 20),
          const SizedBox(height: 6),
          Text(
            value,
            style: const TextStyle(
              color: Colors.white,
              fontSize: 22,
              fontWeight: FontWeight.bold,
            ),
          ),
          Text(
            label,
            style: const TextStyle(color: Colors.white70, fontSize: 12),
            textAlign: TextAlign.center,
          ),
        ],
      ),
    );
  }

  String _formatDate(String? dateString) {
    if (dateString == null) return 'N/A';
    try {
      final date = DateTime.parse(dateString).toLocal();
      final mm   = date.month.toString().padLeft(2, '0');
      final dd   = date.day.toString().padLeft(2, '0');
      final yyyy = date.year.toString();
      return '$mm/$dd/$yyyy';
    } catch (e) {
      return 'N/A';
    }
  }
}