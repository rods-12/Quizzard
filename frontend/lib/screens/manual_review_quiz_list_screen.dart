import 'package:flutter/material.dart';
import '../services/auth_service.dart';

class _T {
  static const Color primary = Color(0xFF5B2A9B);
  static const Color primaryDark = Color(0xFF3A1A6B);
  static const Color primaryLight = Color(0xFFEDE7F2);
  static const Color accent = Color(0xFFA14BC9);
  static const Color accentGold = Color(0xFFF2C94C);
  static const Color softPurple = Color(0xFFC9A8F0);
  static const Color bg = Color(0xFFFAF6EC);
  static const Color surface = Colors.white;
  static const Color textDark = Color(0xFF1F1235);
  static const Color textMid = Color(0xFF7B6F96);
  static const Color textLight = Color(0xFFA99BC4);
  static const Color warning = Color(0xFFF59E0B);
  static const Color success = Color(0xFF22C55E);
  static const Color orange = Color(0xFFF97316);
  static const Color plumShadow = Color(0xFF2A1247);

  static BoxDecoration get card => BoxDecoration(
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

class ManualReviewQuizListScreen extends StatefulWidget {
  const ManualReviewQuizListScreen({super.key});

  @override
  State<ManualReviewQuizListScreen> createState() =>
      _ManualReviewQuizListScreenState();
}

class _ManualReviewQuizListScreenState
    extends State<ManualReviewQuizListScreen> {
  bool _isLoading = true;
  List<Map<String, dynamic>> _classes = [];

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _isLoading = true);
    try {
      final response =
          await AuthService.authGet('/teacher/manual-review/quizzes');
      if (response['success'] == true) {
        setState(() {
          _classes = List<Map<String, dynamic>>.from(
              response['data']['classes'] ?? []);
        });
      } else {
        _showSnackbar(response['message'] ?? 'Failed to load', isError: true);
      }
    } catch (e) {
      _showSnackbar('Network error: $e', isError: true);
    } finally {
      setState(() => _isLoading = false);
    }
  }

  void _showSnackbar(String message, {bool isError = false}) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: isError ? const Color(0xFFEF4444) : _T.success,
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
        margin: const EdgeInsets.all(16),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: _T.bg,
      appBar: AppBar(
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
        elevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back_rounded, color: Colors.white),
          onPressed: () => Navigator.pop(context),
        ),
        title: const Text(
          'Manual Review',
          style: TextStyle(
              color: Colors.white, fontWeight: FontWeight.bold, fontSize: 18),
        ),
        bottom: PreferredSize(
          preferredSize: const Size.fromHeight(1),
          child: Divider(color: Colors.white.withOpacity(0.15), height: 1),
        ),
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: _T.primary))
          : RefreshIndicator(
              onRefresh: _load,
              color: _T.primary,
              child: _classes.isEmpty
                  ? _buildEmpty()
                  : ListView.builder(
                      padding: const EdgeInsets.fromLTRB(20, 20, 20, 40),
                      itemCount: _classes.length,
                      itemBuilder: (context, i) =>
                          _buildClassSection(_classes[i]),
                    ),
            ),
    );
  }

  Widget _buildEmpty() {
    return ListView(
      children: [
        SizedBox(
          height: MediaQuery.of(context).size.height * 0.6,
          child: Center(
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Container(
                  padding: const EdgeInsets.all(24),
                  decoration: BoxDecoration(
                      color: _T.primaryLight, shape: BoxShape.circle),
                  child: Icon(Icons.rate_review_outlined,
                      size: 48, color: _T.primary.withOpacity(0.5)),
                ),
                const SizedBox(height: 16),
                const Text('No manual review quizzes',
                    style: TextStyle(
                        color: _T.textMid,
                        fontSize: 16,
                        fontWeight: FontWeight.w500)),
                const SizedBox(height: 4),
                const Text(
                  'Quizzes assigned with manual grading\nwill appear here.',
                  textAlign: TextAlign.center,
                  style: TextStyle(color: _T.textLight, fontSize: 13),
                ),
              ],
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildClassSection(Map<String, dynamic> cls) {
    final classId = cls['class_id'];
    final className = cls['class_name'] ?? 'Unnamed Class';
    final quizzes =
        List<Map<String, dynamic>>.from(cls['quizzes'] ?? []);

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        // Class header
        Padding(
          padding: const EdgeInsets.only(bottom: 10),
          child: Row(
            children: [
              Container(
                padding:
                    const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                decoration: BoxDecoration(
                  color: _T.accent.withOpacity(0.1),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Row(
                  children: [
                    const Icon(Icons.class_rounded,
                        size: 14, color: _T.accent),
                    const SizedBox(width: 6),
                    Text(
                      className,
                      style: const TextStyle(
                          color: _T.accent,
                          fontWeight: FontWeight.bold,
                          fontSize: 13),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),

        // Quiz cards
        ...quizzes.map((quiz) => _buildQuizCard(classId, className, quiz)),

        const SizedBox(height: 16),
      ],
    );
  }

  Widget _buildQuizCard(
      int classId, String className, Map<String, dynamic> quiz) {
    final quizId = quiz['quiz_id'];
    final quizTitle = quiz['quiz_title'] ?? 'Untitled Quiz';
    final pendingCount = quiz['pending_count'] ?? 0;
    final reviewedCount = quiz['reviewed_count'] ?? 0;
    final totalAttempts = quiz['total_attempts'] ?? 0;
    final dueDate = quiz['due_date'];

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: _T.card,
      child: InkWell(
        borderRadius: BorderRadius.circular(16),
        onTap: () async {
          await Navigator.pushNamed(
            context,
            '/manual-review-students',
            arguments: {
              'class_id': classId,
              'class_name': className,
              'quiz_id': quizId,
              'quiz_title': quizTitle,
            },
          );
          _load();
        },
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Container(
                    padding: const EdgeInsets.all(10),
                    decoration: BoxDecoration(
                      color: pendingCount > 0
                          ? _T.orange.withOpacity(0.1)
                          : _T.primaryLight,
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Icon(
                      Icons.quiz_rounded,
                      color: pendingCount > 0 ? _T.orange : _T.primary,
                      size: 20,
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          quizTitle,
                          style: const TextStyle(
                              fontWeight: FontWeight.bold,
                              fontSize: 15,
                              color: _T.textDark),
                        ),
                        if (dueDate != null) ...[
                          const SizedBox(height: 2),
                          Text(
                            'Due: $dueDate',
                            style: const TextStyle(
                                fontSize: 11, color: _T.textLight),
                          ),
                        ],
                      ],
                    ),
                  ),
                  const Icon(Icons.chevron_right_rounded, color: _T.textLight),
                ],
              ),
              const SizedBox(height: 14),
              Divider(color: _T.softPurple.withOpacity(0.25), height: 1),
              const SizedBox(height: 12),
              Row(
                children: [
                  _buildCountChip(
                    icon: Icons.hourglass_top_rounded,
                    label: '$pendingCount Pending',
                    color: pendingCount > 0 ? _T.orange : _T.textLight,
                    bg: pendingCount > 0
                        ? _T.orange.withOpacity(0.1)
                        : _T.primaryLight,
                  ),
                  const SizedBox(width: 8),
                  _buildCountChip(
                    icon: Icons.check_circle_rounded,
                    label: '$reviewedCount Reviewed',
                    color: reviewedCount > 0 ? _T.success : _T.textLight,
                    bg: reviewedCount > 0
                        ? _T.success.withOpacity(0.1)
                        : _T.primaryLight,
                  ),
                  const Spacer(),
                  Text(
                    '$totalAttempts total',
                    style:
                        const TextStyle(fontSize: 11, color: _T.textLight),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildCountChip({
    required IconData icon,
    required String label,
    required Color color,
    required Color bg,
  }) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
      decoration: BoxDecoration(
        color: bg,
        borderRadius: BorderRadius.circular(20),
      ),
      child: Row(
        children: [
          Icon(icon, size: 12, color: color),
          const SizedBox(width: 4),
          Text(label,
              style: TextStyle(
                  fontSize: 11, color: color, fontWeight: FontWeight.w600)),
        ],
      ),
    );
  }
}