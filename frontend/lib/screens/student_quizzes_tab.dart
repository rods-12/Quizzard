import 'package:flutter/material.dart';
import '../services/auth_service.dart';

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
    return Column(
      children: [
        Container(
          color: Colors.white,
          child: TabBar(
            controller: _tabController,
            isScrollable: true,
            labelColor: const Color(0xFF6C63FF),
            unselectedLabelColor: Colors.grey,
            indicatorColor: const Color(0xFF6C63FF),
            labelStyle: const TextStyle(
              fontWeight: FontWeight.bold,
              fontSize: 13,
            ),
            unselectedLabelStyle: const TextStyle(
              fontWeight: FontWeight.normal,
              fontSize: 12,
            ),
            tabs: [
              _buildTab('All', _quizzes.length),
              _buildTab('Assigned', _assignedQuizzes.length),
              _buildTab('Done', _doneQuizzes.length),
              _buildTab('Missing', _missingQuizzes.length),
            ],
          ),
        ),
        Expanded(
          child: _isLoading
              ? const Center(
                  child: CircularProgressIndicator(
                    color: Color(0xFF6C63FF),
                  ),
                )
              : _errorMessage != null
                  ? _buildErrorState()
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
          const SizedBox(width: 4),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 5, vertical: 1),
            decoration: BoxDecoration(
              color: const Color(0xFF6C63FF).withOpacity(0.1),
              borderRadius: BorderRadius.circular(8),
            ),
            child: Text(
              '$count',
              style: const TextStyle(
                fontSize: 10,
                fontWeight: FontWeight.bold,
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildErrorState() {
    return Center(
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
            onPressed: _loadQuizzes,
            child: const Text('Retry'),
          ),
        ],
      ),
    );
  }

  Widget _buildQuizList(List<dynamic> quizzes) {
    if (quizzes.isEmpty) {
      return _buildEmptyState();
    }

    return RefreshIndicator(
      onRefresh: _loadQuizzes,
      color: const Color(0xFF6C63FF),
      child: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: quizzes.length,
        itemBuilder: (context, index) {
          return _buildQuizCard(quizzes[index]);
        },
      ),
    );
  }

  Widget _buildEmptyState() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(Icons.quiz_outlined, size: 80, color: Colors.grey.shade300),
          const SizedBox(height: 16),
          Text(
            'No quizzes here.',
            style: TextStyle(color: Colors.grey.shade500, fontSize: 16),
          ),
        ],
      ),
    );
  }

  Widget _buildQuizCard(Map<String, dynamic> quiz) {
    final alreadyTaken = quiz['already_taken'] as bool;
    final dueDate = quiz['due_date'] != null
        ? DateTime.tryParse(quiz['due_date'])
        : null;

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      elevation: 2,
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Container(
                  width: 50,
                  height: 50,
                  decoration: BoxDecoration(
                    color: const Color(0xFF6C63FF).withOpacity(0.1),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: const Icon(
                    Icons.quiz,
                    color: Color(0xFF6C63FF),
                    size: 28,
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        quiz['title'],
                        style: const TextStyle(
                          fontWeight: FontWeight.bold,
                          fontSize: 15,
                        ),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        '${quiz['class_name']} • ${quiz['teacher_name']}',
                        style: TextStyle(
                          color: Colors.grey.shade600,
                          fontSize: 12,
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),
            Row(
              children: [
                Icon(
                  Icons.help_outline,
                  size: 14,
                  color: Colors.grey.shade500,
                ),
                const SizedBox(width: 4),
                Text(
                  '${quiz['questions_count']} questions',
                  style: TextStyle(
                    color: Colors.grey.shade500,
                    fontSize: 12,
                  ),
                ),
                if (dueDate != null) ...[
                  const SizedBox(width: 12),
                  Icon(
                    Icons.calendar_today,
                    size: 14,
                    color: _getDueDateColor(dueDate, alreadyTaken),
                  ),
                  const SizedBox(width: 4),
                  Text(
                    _formatDueDate(dueDate),
                    style: TextStyle(
                      color: _getDueDateColor(dueDate, alreadyTaken),
                      fontSize: 12,
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                ] else ...[
                  const SizedBox(width: 12),
                  Icon(
                    Icons.calendar_today_outlined,
                    size: 14,
                    color: Colors.grey.shade400,
                  ),
                  const SizedBox(width: 4),
                  Text(
                    'No deadline',
                    style: TextStyle(
                      color: Colors.grey.shade400,
                      fontSize: 12,
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                ],
              ],
            ),
            if (alreadyTaken) ...[
              const SizedBox(height: 8),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                decoration: BoxDecoration(
                  color: _getScoreColor(quiz['score'], quiz['total_points'])
                      .withOpacity(0.1),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Text(
                  'Score: ${quiz['score']}/${quiz['total_points']}',
                  style: TextStyle(
                    color: _getScoreColor(quiz['score'], quiz['total_points']),
                    fontSize: 12,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ),
            ],
            const SizedBox(height: 12),
            SizedBox(
              width: double.infinity,
              child: ElevatedButton(
                onPressed: alreadyTaken
                    ? null
                    : () => Navigator.pushNamed(
                          context,
                          '/quiz-taking',
                          arguments: {
                            'quiz_id': quiz['id'],
                            'quiz_title': quiz['title'],
                          },
                        ),
                style: ElevatedButton.styleFrom(
                  backgroundColor: alreadyTaken ? Colors.grey.shade300 : const Color(0xFF6C63FF),
                  foregroundColor: alreadyTaken ? Colors.grey.shade600 : Colors.white,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                ),
                child: Text(alreadyTaken ? 'Completed' : 'Take Quiz'),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Color _getDueDateColor(DateTime dueDate, bool alreadyTaken) {
    if (alreadyTaken) return Colors.grey;
    final now = DateTime.now();
    if (dueDate.isBefore(now)) return Colors.red;
    if (dueDate.difference(now).inDays <= 2) return Colors.orange;
    return Colors.green;
  }

  String _formatDueDate(DateTime dueDate) {
    final now = DateTime.now();
    final difference = dueDate.difference(now).inDays;

    if (difference < 0) return 'Overdue';
    if (difference == 0) return 'Due today';
    if (difference == 1) return 'Due tomorrow';
    return 'Due in $difference days';
  }

  Color _getScoreColor(int? score, int? total) {
    if (total == null || total == 0) return Colors.grey;
    final pct = ((score ?? 0) / total) * 100;
    if (pct >= 80) return Colors.green;
    if (pct >= 60) return Colors.orange;
    return Colors.red;
  }
}