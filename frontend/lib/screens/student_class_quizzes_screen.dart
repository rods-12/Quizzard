import 'package:flutter/material.dart';
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

class _StudentClassQuizzesScreenState
    extends State<StudentClassQuizzesScreen>
    with SingleTickerProviderStateMixin {
  bool _isLoading = true;
  String? _errorMessage;
  List<dynamic> _quizzes = [];
  late TabController _tabController;

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

  // ─── CATEGORIZATION ──────────────────────────────────────

  List<Map<String, dynamic>> get _assignedQuizzes {
    final now = DateTime.now();
    return _quizzes
        .map((q) => Map<String, dynamic>.from(q))
        .where((q) {
          if (q['already_taken'] == true) return false;
          final due = q['due_date'] != null
              ? DateTime.tryParse(q['due_date'])
              : null;
          return due == null || due.isAfter(now);
        })
        .toList();
  }

  List<Map<String, dynamic>> get _doneQuizzes {
    return _quizzes
        .map((q) => Map<String, dynamic>.from(q))
        .where((q) => q['already_taken'] == true)
        .toList();
  }

  List<Map<String, dynamic>> get _missingQuizzes {
    final now = DateTime.now();
    return _quizzes
        .map((q) => Map<String, dynamic>.from(q))
        .where((q) {
          if (q['already_taken'] == true) return false;
          final due = q['due_date'] != null
              ? DateTime.tryParse(q['due_date'])
              : null;
          return due != null && due.isBefore(now);
        })
        .toList();
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
        bottom: TabBar(
          controller: _tabController,
          indicatorColor: Colors.white,
          labelColor: Colors.white,
          unselectedLabelColor: Colors.white60,
          isScrollable: true,
          tabAlignment: TabAlignment.start,
          tabs: [
            Tab(text: 'All (${_quizzes.length})'),
            Tab(text: 'Assigned (${_assignedQuizzes.length})'),
            Tab(text: 'Done (${_doneQuizzes.length})'),
            Tab(text: 'Missing (${_missingQuizzes.length})'),
          ],
        ),
      ),
      body: _isLoading
          ? const Center(
              child: CircularProgressIndicator(color: Color(0xFF6C63FF)),
            )
          : _errorMessage != null
              ? Center(
                  child: Padding(
                    padding: const EdgeInsets.all(20),
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        const Icon(Icons.error_outline,
                            size: 60, color: Colors.red),
                        const SizedBox(height: 16),
                        Text(_errorMessage!,
                            textAlign: TextAlign.center,
                            style: const TextStyle(color: Colors.red)),
                        const SizedBox(height: 16),
                        ElevatedButton(
                          onPressed: _loadQuizzes,
                          child: const Text('Retry'),
                        ),
                      ],
                    ),
                  ),
                )
              : TabBarView(
                  controller: _tabController,
                  children: [
                    _buildTabContent(
                        _quizzes.map((q) => Map<String, dynamic>.from(q)).toList()),
                    _buildTabContent(_assignedQuizzes),
                    _buildTabContent(_doneQuizzes),
                    _buildTabContent(_missingQuizzes),
                  ],
                ),
    );
  }

  // ─── TAB CONTENT ─────────────────────────────────────────

  Widget _buildTabContent(List<Map<String, dynamic>> quizzes) {
    if (quizzes.isEmpty) {
      return const Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.quiz_outlined, size: 80, color: Colors.grey),
            SizedBox(height: 16),
            Text(
              'No quizzes here.',
              style: TextStyle(fontSize: 16, color: Colors.grey),
            ),
          ],
        ),
      );
    }

    return RefreshIndicator(
      onRefresh: _loadQuizzes,
      color: const Color(0xFF6C63FF),
      child: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: quizzes.length,
        itemBuilder: (context, index) => _buildQuizCard(quizzes[index]),
      ),
    );
  }

  // ─── QUIZ CARD ───────────────────────────────────────────

  Widget _buildQuizCard(Map<String, dynamic> quiz) {
    final alreadyTaken = quiz['already_taken'] == true;
    final now = DateTime.now();
    final due = quiz['due_date'] != null
        ? DateTime.tryParse(quiz['due_date'])
        : null;
    final isMissing = !alreadyTaken && due != null && due.isBefore(now);

    Color statusColor;
    String statusLabel;
    if (alreadyTaken) {
      statusColor = Colors.green;
      statusLabel = '✓ Done';
    } else if (isMissing) {
      statusColor = Colors.red;
      statusLabel = 'Missing';
    } else {
      statusColor = const Color(0xFF6C63FF);
      statusLabel = 'Assigned';
    }

    Color? dueDateColor;
    if (due != null && !alreadyTaken) {
      dueDateColor = due.isBefore(now) ? Colors.red : Colors.orange;
    }

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
                    color: statusColor.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Icon(Icons.quiz, color: statusColor, size: 28),
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
                          quiz['description'].isNotEmpty)
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
                Container(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                  decoration: BoxDecoration(
                    color: statusColor.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(20),
                    border: Border.all(color: statusColor.withOpacity(0.3)),
                  ),
                  child: Text(
                    statusLabel,
                    style: TextStyle(
                      fontSize: 12,
                      color: statusColor,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),

            // Stats row
            Row(
              children: [
                Icon(Icons.help_outline,
                    size: 16, color: Colors.grey.shade500),
                const SizedBox(width: 4),
                Text(
                  '${quiz['questions_count']} questions',
                  style:
                      TextStyle(fontSize: 13, color: Colors.grey.shade600),
                ),
                if (alreadyTaken && quiz['score'] != null) ...[
                  const SizedBox(width: 16),
                  Icon(Icons.star, size: 16, color: Colors.amber.shade600),
                  const SizedBox(width: 4),
                  Text(
                    '${quiz['score']} / ${quiz['total_points']} pts',
                    style: TextStyle(
                      fontSize: 13,
                      color: Colors.grey.shade600,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ],
              ],
            ),

            // Due date row
            if (due != null) ...[
              const SizedBox(height: 6),
              Row(
                children: [
                  Icon(Icons.schedule, size: 16, color: dueDateColor),
                  const SizedBox(width: 4),
                  Text(
                    'Due: ${_formatDate(due)}',
                    style: TextStyle(
                      fontSize: 13,
                      color: dueDateColor,
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                ],
              ),
            ],

            const SizedBox(height: 12),

            // Action button
            SizedBox(
              width: double.infinity,
              height: 44,
              child: ElevatedButton(
                onPressed: (alreadyTaken || isMissing)
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
                      ? Colors.green.shade100
                      : isMissing
                          ? Colors.red.shade50
                          : const Color(0xFF6C63FF),
                  shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12)),
                ),
                child: Text(
                  alreadyTaken
                      ? 'Already Completed'
                      : isMissing
                          ? 'Past Due'
                          : 'Take Quiz',
                  style: TextStyle(
                    color: alreadyTaken
                        ? Colors.green.shade700
                        : isMissing
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

  // ─── HELPERS ─────────────────────────────────────────────

  String _formatDate(DateTime date) {
    final months = [
      'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
      'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
    ];

    final local = date.toLocal();

    int hour = local.hour;
    final minute = local.minute.toString().padLeft(2, '0');
    final period = hour >= 12 ? 'PM' : 'AM';

    hour = hour % 12;
    if (hour == 0) hour = 12;

    return '${months[local.month - 1]} ${local.day}, ${local.year} • $hour:$minute $period';
  }
}