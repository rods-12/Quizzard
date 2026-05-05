import 'package:flutter/material.dart';
import '../services/auth_service.dart';

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
  static const Color _green = Color(0xFF4CAF50);
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
    {'value': 'lowest', 'label': 'Lowest Score'},
    {'value': 'newest', 'label': 'Newest'},
    {'value': 'oldest', 'label': 'Oldest'},
    {'value': 'passed', 'label': 'Passed Only'},
    {'value': 'failed', 'label': 'Failed Only'},
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
        final studentName = (item['student_name'] ?? '').toString().toLowerCase();
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
    if (percentage >= 80) return Colors.green;
    if (percentage >= 60) return Colors.orange;
    return Colors.red;
  }

  Color _getMedalColor(int rank) {
    switch (rank) {
      case 1:
        return const Color(0xFFFFD700);
      case 2:
        return const Color(0xFFC0C0C0);
      case 3:
        return const Color(0xFFCD7F32);
      default:
        return _green;
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
      backgroundColor: const Color(0xFFF5F5F5),
      appBar: AppBar(
        title: Text(
          widget.quizTitle,
          style: const TextStyle(fontSize: 16),
        ),
        backgroundColor: _green,
        foregroundColor: Colors.white,
      ),
      body: _buildBody(),
    );
  }

  Widget _buildBody() {
    if (_isLoading) {
      return const Center(
        child: CircularProgressIndicator(color: _green),
      );
    }

    if (_errorMessage != null) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(20),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              const Icon(Icons.error_outline, size: 60, color: Colors.red),
              const SizedBox(height: 16),
              Text(
                _errorMessage!,
                textAlign: TextAlign.center,
                style: const TextStyle(color: Colors.red),
              ),
              const SizedBox(height: 16),
              ElevatedButton(
                onPressed: _loadResults,
                child: const Text('Retry'),
              ),
            ],
          ),
        ),
      );
    }

    final totalAttempts = _toInt(_data!['total_attempts']);
    final averagePercentage = (_data!['average_percentage'] ?? 0).toString();
    final passCount = _data!['pass_count'] ?? _getPassCount();
    final failCount = _data!['fail_count'] ?? _getFailCount();

    return RefreshIndicator(
      onRefresh: _loadResults,
      color: _green,
      child: Column(
        children: [
          Container(
            width: double.infinity,
            padding: const EdgeInsets.fromLTRB(16, 20, 16, 20),
            decoration: const BoxDecoration(
              color: _green,
              borderRadius: BorderRadius.only(
                bottomLeft: Radius.circular(24),
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

          Padding(
            padding: const EdgeInsets.fromLTRB(16, 16, 16, 8),
            child: Column(
              children: [
                TextField(
                  controller: _searchController,
                  decoration: InputDecoration(
                    hintText: 'Search student name or email...',
                    prefixIcon: const Icon(Icons.search, color: _green),
                    suffixIcon: _searchController.text.isEmpty
                        ? null
                        : IconButton(
                            onPressed: () {
                              _searchController.clear();
                            },
                            icon: const Icon(Icons.clear),
                          ),
                    filled: true,
                    fillColor: Colors.white,
                    contentPadding: const EdgeInsets.symmetric(vertical: 14),
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(14),
                      borderSide: BorderSide.none,
                    ),
                    focusedBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(14),
                      borderSide: const BorderSide(color: _green, width: 1.5),
                    ),
                  ),
                ),
                const SizedBox(height: 12),
                Row(
                  children: [
                    const Icon(Icons.filter_list, color: _green, size: 20),
                    const SizedBox(width: 8),
                    Expanded(
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 12),
                        decoration: BoxDecoration(
                          color: Colors.white,
                          borderRadius: BorderRadius.circular(14),
                          border: Border.all(color: Colors.grey.shade300),
                        ),
                        child: DropdownButtonHideUnderline(
                          child: DropdownButton<String>(
                            value: _selectedFilter,
                            isExpanded: true,
                            icon: const Icon(Icons.keyboard_arrow_down),
                            items: _filterOptions.map((option) {
                              return DropdownMenuItem<String>(
                                value: option['value'],
                                child: Text(option['label']!),
                              );
                            }).toList(),
                            onChanged: (value) {
                              if (value == null) return;
                              setState(() {
                                _selectedFilter = value;
                              });
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

          Expanded(
            child: _visibleResults.isEmpty
                ? _buildEmptyState()
                : ListView.builder(
                    padding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
                    itemCount: _visibleResults.length,
                    itemBuilder: (context, index) {
                      final result = _visibleResults[index];
                      final percentage = _toInt(result['percentage']);
                      final scoreColor = _getScoreColor(percentage);
                      final rank = _toInt(result['rank']);
                      final medalColor = _getMedalColor(rank);
                      final isPassed = result['is_passed'] == true;

                      return GestureDetector(
                        onTap: () => Navigator.pushNamed(
                          context,
                          '/student-attempt-detail',
                          arguments: {
                            'quiz_id': widget.quizId,
                            'quiz_title': widget.quizTitle,
                            'attempt_id': result['attempt_id'],
                            'student_name': result['student_name'],
                          },
                        ),
                        child: Container(
                          margin: const EdgeInsets.only(bottom: 12),
                          padding: const EdgeInsets.all(16),
                          decoration: BoxDecoration(
                            color: rank <= 3 ? medalColor.withOpacity(0.08) : Colors.white,
                            borderRadius: BorderRadius.circular(16),
                            border: Border.all(
                              color: rank <= 3 ? medalColor.withOpacity(0.45) : Colors.transparent,
                              width: 1.2,
                            ),
                            boxShadow: [
                              BoxShadow(
                                color: Colors.black.withOpacity(0.05),
                                blurRadius: 8,
                                offset: const Offset(0, 2),
                              ),
                            ],
                          ),
                          child: Row(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
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
                                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
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

                              CircleAvatar(
                                backgroundColor: _green.withOpacity(0.1),
                                child: Text(
                                  _safeInitial(result['student_name']?.toString()),
                                  style: const TextStyle(
                                    color: _green,
                                    fontWeight: FontWeight.bold,
                                  ),
                                ),
                              ),
                              const SizedBox(width: 12),

                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(
                                      result['student_name']?.toString() ?? 'Unknown Student',
                                      style: const TextStyle(
                                        fontWeight: FontWeight.bold,
                                        fontSize: 15,
                                        color: Color(0xFF333333),
                                      ),
                                    ),
                                    const SizedBox(height: 2),
                                    Text(
                                      result['student_email']?.toString() ?? 'No email',
                                      style: TextStyle(
                                        fontSize: 12,
                                        color: Colors.grey.shade600,
                                      ),
                                    ),
                                    const SizedBox(height: 6),
                                    SingleChildScrollView(
                                      scrollDirection: Axis.horizontal,
                                      child: Row(
                                        children: [
                                          _buildStatusChip(
                                            label: isPassed ? 'Pass' : 'Fail',
                                            color: isPassed ? Colors.green : Colors.red,
                                            icon: isPassed ? Icons.check_circle : Icons.cancel,
                                          ),
                                          const SizedBox(width: 8),
                                          _buildStatusChip(
                                            label: 'Completed ${_formatDate(result['completed_at']?.toString())}',
                                            color: Colors.blueGrey,
                                            icon: Icons.calendar_today,
                                          ),
                                        ],
                                      ),
                                    ),
                                  ],
                                ),
                              ),

                              const SizedBox(width: 8),

                              Column(
                                crossAxisAlignment: CrossAxisAlignment.end,
                                children: [
                                  Container(
                                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
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
                                    style: TextStyle(
                                      fontSize: 11,
                                      color: Colors.grey.shade500,
                                    ),
                                  ),
                                  const SizedBox(height: 6),
                                  const Icon(Icons.chevron_right, color: Colors.grey),
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
              color: Colors.grey,
            ),
            const SizedBox(height: 12),
            Text(
              hasQuery
                  ? 'No students matched your search or filter.'
                  : 'No students have taken this quiz yet.',
              textAlign: TextAlign.center,
              style: const TextStyle(color: Colors.grey),
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
          Flexible(  // ← Changed from bare Text to Flexible
            child: Text(
              label,
              style: TextStyle(
                color: color,
                fontSize: 11,
                fontWeight: FontWeight.w600,
              ),
              overflow: TextOverflow.ellipsis,  // ← Truncate with ...
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
      final mm = date.month.toString().padLeft(2, '0');
      final dd = date.day.toString().padLeft(2, '0');
      final yyyy = date.year.toString();
      return '$mm/$dd/$yyyy';
    } catch (e) {
      return 'N/A';
    }
  }
}