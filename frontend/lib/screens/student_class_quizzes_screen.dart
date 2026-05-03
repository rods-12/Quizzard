import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../services/auth_service.dart';

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

class _StudentClassQuizzesScreenState extends State<StudentClassQuizzesScreen>
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
      backgroundColor: const Color(0xFFF5F5F5),
      appBar: AppBar(
        title: Text(
          widget.className,
          style: const TextStyle(fontSize: 16),
        ),
        backgroundColor: const Color(0xFF6C63FF),
        foregroundColor: Colors.white,
        bottom: _isLoading || _errorMessage != null
            ? null
            : TabBar(
                controller: _tabController,
                isScrollable: true,
                labelColor: Colors.white,
                unselectedLabelColor: Colors.white70,
                indicatorColor: Colors.white,
                indicatorWeight: 3,
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
      body: _buildBody(),
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
              color: Colors.white.withOpacity(0.25),
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

  Widget _buildBody() {
    if (_isLoading) {
      return const Center(
        child: CircularProgressIndicator(color: Color(0xFF6C63FF)),
      );
    }

    return RefreshIndicator(
      onRefresh: _loadQuizzes,
      color: const Color(0xFF6C63FF),
      child: _errorMessage != null
          ? _buildErrorState()
          : _quizzes.isEmpty
              ? _buildEmptyState()
              : TabBarView(
                  controller: _tabController,
                  children: [
                    _buildQuizList(_quizzes),
                    _buildQuizList(_assignedQuizzes),
                    _buildQuizList(_doneQuizzes),
                    _buildQuizList(_missingQuizzes),
                  ],
                ),
    );
  }

  Widget _buildErrorState() {
    return LayoutBuilder(
      builder: (context, constraints) => SingleChildScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        child: ConstrainedBox(
          constraints: BoxConstraints(minHeight: constraints.maxHeight),
          child: Center(
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
                    onPressed: _loadQuizzes,
                    child: const Text('Retry'),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildEmptyState() {
    return LayoutBuilder(
      builder: (context, constraints) => SingleChildScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        child: ConstrainedBox(
          constraints: BoxConstraints(minHeight: constraints.maxHeight),
          child: const Center(
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Icon(Icons.quiz_outlined, size: 80, color: Colors.grey),
                SizedBox(height: 16),
                Text(
                  'No quizzes available yet.',
                  style: TextStyle(fontSize: 18, color: Colors.grey),
                ),
                SizedBox(height: 8),
                Text(
                  'Your teacher hasn\'t assigned any quizzes yet.',
                  textAlign: TextAlign.center,
                  style: TextStyle(color: Colors.grey),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildQuizList(List<dynamic> quizzes) {
    if (quizzes.isEmpty) {
      return _buildEmptyState();
    }

    return ListView.builder(
      padding: const EdgeInsets.all(16),
      itemCount: quizzes.length,
      itemBuilder: (context, index) {
        return _buildQuizCard(quizzes[index]);
      },
    );
  }

  Widget _buildQuizCard(Map<String, dynamic> quiz) {
    final alreadyTaken = quiz['already_taken'] == true;
    final dueDate = quiz['due_date'] != null
        ? DateTime.tryParse(quiz['due_date'])
        : null;
    final isPastDue = dueDate != null && dueDate.isBefore(DateTime.now()) && !alreadyTaken;

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      elevation: 3,
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Container(
                  width: 48,
                  height: 48,
                  decoration: BoxDecoration(
                    color: const Color(0xFF6C63FF).withOpacity(0.1),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: const Icon(Icons.quiz,
                      color: Color(0xFF6C63FF), size: 28),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        quiz['title'],
                        style: const TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.bold,
                          color: Color(0xFF333333),
                        ),
                      ),
                      if (quiz['description'] != null &&
                          quiz['description'].toString().isNotEmpty)
                        Text(
                          quiz['description'],
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: TextStyle(
                              fontSize: 13, color: Colors.grey.shade600),
                        ),
                    ],
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),

            // Stats row
            Wrap(
              spacing: 12,
              runSpacing: 6,
              crossAxisAlignment: WrapCrossAlignment.center,
              children: [
                Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Icon(Icons.help_outline,
                        size: 16, color: Colors.grey.shade500),
                    const SizedBox(width: 4),
                    Text(
                      '${quiz['questions_count']} questions',
                      style: TextStyle(
                          fontSize: 13, color: Colors.grey.shade600),
                    ),
                  ],
                ),
                if (dueDate != null)
                  Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Icon(
                        Icons.calendar_today,
                        size: 14,
                        color: _getDueDateColor(dueDate, alreadyTaken),
                      ),
                      const SizedBox(width: 4),
                      Text(
                        _formatDueDate(dueDate),
                        style: TextStyle(
                          fontSize: 12,
                          fontWeight: FontWeight.w500,
                          color: _getDueDateColor(dueDate, alreadyTaken),
                        ),
                      ),
                    ],
                  )
                else
                  Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Icon(
                        Icons.calendar_today_outlined,
                        size: 14,
                        color: Colors.grey.shade400,
                      ),
                      const SizedBox(width: 4),
                      Text(
                        'No deadline',
                        style: TextStyle(
                          fontSize: 12,
                          fontWeight: FontWeight.w500,
                          color: Colors.grey.shade400,
                        ),
                      ),
                    ],
                  ),
                Container(
                  padding: const EdgeInsets.symmetric(
                      horizontal: 10, vertical: 4),
                  decoration: BoxDecoration(
                    color: alreadyTaken
                        ? Colors.green.withOpacity(0.1)
                        : isPastDue
                            ? Colors.red.withOpacity(0.1)
                            : const Color(0xFF6C63FF).withOpacity(0.1),
                    borderRadius: BorderRadius.circular(20),
                    border: Border.all(
                      color: alreadyTaken
                          ? Colors.green.withOpacity(0.3)
                          : isPastDue
                              ? Colors.red.withOpacity(0.3)
                              : const Color(0xFF6C63FF).withOpacity(0.3),
                    ),
                  ),
                  child: Text(
                    alreadyTaken
                        ? '✓ Done'
                        : isPastDue
                            ? 'Past Due'
                            : 'Not taken',
                    style: TextStyle(
                      fontSize: 12,
                      color: alreadyTaken
                          ? Colors.green
                          : isPastDue
                              ? Colors.red
                              : const Color(0xFF6C63FF),
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ),
              ],
            ),

            // Score badge for completed quizzes
            if (alreadyTaken) ...[
              const SizedBox(height: 10),
              Container(
                width: double.infinity,
                padding: const EdgeInsets.symmetric(vertical: 8, horizontal: 12),
                decoration: BoxDecoration(
                  color: _getScoreColor(quiz['score'], quiz['total_points'])
                      .withOpacity(0.1),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Icon(
                      Icons.emoji_events,
                      size: 16,
                      color: _getScoreColor(quiz['score'], quiz['total_points']),
                    ),
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
            ],

            const SizedBox(height: 12),
            SizedBox(
              width: double.infinity,
              height: 44,
              child: ElevatedButton(
                onPressed: (alreadyTaken || isPastDue)
                    ? null
                    : () async {
                        await Navigator.pushNamed(
                          context,
                          '/quiz-taking',
                          arguments: {
                            'quiz_id': quiz['id'],
                            'quiz_title': quiz['title'],
                          },
                        );
                        _loadQuizzes();
                      },
                style: ElevatedButton.styleFrom(
                  backgroundColor: alreadyTaken
                      ? Colors.grey.shade300
                      : isPastDue
                          ? Colors.red.shade100
                          : const Color(0xFF6C63FF),
                  shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12)),
                ),
                child: Text(
                  alreadyTaken
                      ? 'Already Completed'
                      : isPastDue
                          ? 'Past Due'
                          : 'Take Quiz',
                  style: TextStyle(
                    color: alreadyTaken
                        ? Colors.grey.shade600
                        : isPastDue
                            ? Colors.red.shade700
                            : Colors.white,
                    fontWeight: FontWeight.bold,
                  ),
                ),
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
    return DateFormat('MMM d, yyyy · h:mm a').format(dueDate);
  }

  Color _getScoreColor(int? score, int? total) {
    if (total == null || total == 0) return Colors.grey;
    final pct = ((score ?? 0) / total) * 100;
    if (pct >= 80) return Colors.green;
    if (pct >= 60) return Colors.orange;
    return Colors.red;
  }
}