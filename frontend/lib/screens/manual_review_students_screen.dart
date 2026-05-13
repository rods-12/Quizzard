import 'package:flutter/material.dart';
import '../services/auth_service.dart';

class _T {
  static const Color primary = Color(0xFF2ECC71);
  static const Color primaryLight = Color(0xFFE8F8F0);
  static const Color accent = Color(0xFF6C63FF);
  static const Color bg = Color(0xFFF4F7F5);
  static const Color surface = Colors.white;
  static const Color textDark = Color(0xFF1A2E22);
  static const Color textMid = Color(0xFF6B7580);
  static const Color textLight = Color(0xFFADB5BD);
  static const Color success = Color(0xFF22C55E);
  static const Color warning = Color(0xFFF59E0B);
  static const Color danger = Color(0xFFEF4444);
  static const Color orange = Color(0xFFF97316);

  static BoxDecoration get card => BoxDecoration(
        color: surface,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.05),
            blurRadius: 12,
            offset: const Offset(0, 4),
          ),
        ],
      );
}

class ManualReviewStudentsScreen extends StatefulWidget {
  final int classId;
  final String className;
  final int quizId;
  final String quizTitle;

  const ManualReviewStudentsScreen({
    super.key,
    required this.classId,
    required this.className,
    required this.quizId,
    required this.quizTitle,
  });

  @override
  State<ManualReviewStudentsScreen> createState() =>
      _ManualReviewStudentsScreenState();
}

class _ManualReviewStudentsScreenState
    extends State<ManualReviewStudentsScreen> {
  bool _isLoading = true;
  List<Map<String, dynamic>> _attempts = [];

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _isLoading = true);
    try {
      final response = await AuthService.authGet(
        '/teacher/manual-review/classes/${widget.classId}/quizzes/${widget.quizId}/attempts',
      );
      if (response['success'] == true) {
        setState(() {
          _attempts = List<Map<String, dynamic>>.from(
              response['data']['attempts'] ?? []);
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
        backgroundColor: isError ? _T.danger : _T.success,
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
        margin: const EdgeInsets.all(16),
      ),
    );
  }

  String _displayName(Map<String, dynamic> attempt) {
    final first = attempt['first_name'];
    final surname = attempt['surname'];
    if (first != null || surname != null) {
      return '${first ?? ''} ${surname ?? ''}'.trim();
    }
    return attempt['email'] ?? 'Unknown Student';
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: _T.bg,
      appBar: AppBar(
        backgroundColor: _T.surface,
        elevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back_rounded, color: _T.textDark),
          onPressed: () => Navigator.pop(context),
        ),
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              widget.quizTitle,
              style: const TextStyle(
                  color: _T.textDark,
                  fontWeight: FontWeight.bold,
                  fontSize: 16),
            ),
            Text(
              widget.className,
              style: const TextStyle(color: _T.textMid, fontSize: 12),
            ),
          ],
        ),
        bottom: PreferredSize(
          preferredSize: const Size.fromHeight(1),
          child: Divider(color: Colors.grey.shade100, height: 1),
        ),
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: _T.primary))
          : RefreshIndicator(
              onRefresh: _load,
              color: _T.primary,
              child: _attempts.isEmpty
                  ? _buildEmpty()
                  : ListView.builder(
                      padding: const EdgeInsets.fromLTRB(20, 20, 20, 40),
                      itemCount: _attempts.length,
                      itemBuilder: (context, i) =>
                          _buildAttemptCard(_attempts[i]),
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
                  child: Icon(Icons.people_outline,
                      size: 48, color: _T.primary.withOpacity(0.5)),
                ),
                const SizedBox(height: 16),
                const Text('No submissions yet',
                    style: TextStyle(
                        color: _T.textMid,
                        fontSize: 16,
                        fontWeight: FontWeight.w500)),
              ],
            ),
          ),
        ),
      ],
    );
  }


//card for seeded data without the necessary data like the attempt id
  Widget _buildSeededCard(Map<String, dynamic> attempt) {
    final studentName = _displayName(attempt);
    final email = attempt['email'] ?? '';

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: BoxDecoration(
        color: _T.surface,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: _T.warning.withOpacity(0.4), width: 1.5),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.04),
            blurRadius: 12,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // ── Seeded banner ──
            Container(
              width: double.infinity,
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
              decoration: BoxDecoration(
                color: _T.warning.withOpacity(0.1),
                borderRadius: BorderRadius.circular(8),
              ),
              child: Row(
                children: [
                  Icon(Icons.science_outlined, size: 14, color: _T.warning),
                  const SizedBox(width: 6),
                  const Text(
                    'Seeded data — not a real submission',
                    style: TextStyle(
                      fontSize: 12,
                      color: _T.warning,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 12),

            // ── Student info ──
            Row(
              children: [
                CircleAvatar(
                  radius: 22,
                  backgroundColor: Colors.grey.shade100,
                  child: Text(
                    studentName.isNotEmpty ? studentName[0].toUpperCase() : '?',
                    style: TextStyle(
                      color: _T.textLight,
                      fontWeight: FontWeight.bold,
                      fontSize: 16,
                    ),
                  ),
                ),
                const SizedBox(width: 12),
                Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      studentName.isNotEmpty ? studentName : 'Test Data',
                      style: const TextStyle(
                        fontWeight: FontWeight.bold,
                        fontSize: 15,
                        color: _T.textMid,
                      ),
                    ),
                    Text(
                      email.isNotEmpty ? email : 'No email provided',
                      style: const TextStyle(fontSize: 12, color: _T.textLight),
                    ),
                  ],
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }


  Widget _buildAttemptCard(Map<String, dynamic> attempt) {
    
    final attemptId = attempt['attempt_id'];
    // If no attempt_id, render the seeded card instead
    if (attemptId == null) return _buildSeededCard(attempt);  


    final status = attempt['status'] ?? 'submitted';
    final studentName = _displayName(attempt);
    final email = attempt['email'] ?? '';
    final submittedAt = attempt['submitted_at'];
    final reviewedAt = attempt['reviewed_at'];

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: _T.card,
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // ── Student info + status badge ──
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                CircleAvatar(
                  radius: 22,
                  backgroundColor: _T.accent.withOpacity(0.1),
                  child: Text(
                    studentName.isNotEmpty
                        ? studentName[0].toUpperCase()
                        : '?',
                    style: const TextStyle(
                        color: _T.accent,
                        fontWeight: FontWeight.bold,
                        fontSize: 16),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        studentName,
                        style: const TextStyle(
                            fontWeight: FontWeight.bold,
                            fontSize: 15,
                            color: _T.textDark),
                      ),
                      Text(
                        email,
                        style:
                            const TextStyle(fontSize: 12, color: _T.textMid),
                      ),
                    ],
                  ),
                ),
                _buildStatusBadge(status),
              ],
            ),
            const SizedBox(height: 12),
            Divider(color: Colors.grey.shade100, height: 1),
            const SizedBox(height: 12),

            // ── Timestamps ──
            if (submittedAt != null)
              _buildMeta(
                Icons.upload_rounded,
                'Submitted: ${_formatDate(submittedAt)}',
              ),
            if (reviewedAt != null) ...[
              const SizedBox(height: 4),
              _buildMeta(
                Icons.check_circle_outline_rounded,
                'Reviewed: ${_formatDate(reviewedAt)}',
                color: _T.success,
              ),
            ],
            const SizedBox(height: 12),

            // ── Action button ──
            Row(
              mainAxisAlignment: MainAxisAlignment.end,
              children: [
                _buildActionButton(attempt, status, attemptId, studentName),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildStatusBadge(String status) {
    Color color;
    Color bg;
    IconData icon;
    String label;

    switch (status) {
      case 'submitted':
        color = _T.orange;
        bg = _T.orange.withOpacity(0.1);
        icon = Icons.hourglass_top_rounded;
        label = 'Pending';
        break;
      case 'under_review':
        color = _T.accent;
        bg = _T.accent.withOpacity(0.1);
        icon = Icons.edit_note_rounded;
        label = 'In Review';
        break;
      case 'reviewed':
        color = _T.success;
        bg = _T.success.withOpacity(0.1);
        icon = Icons.check_circle_rounded;
        label = 'Reviewed';
        break;
      default:
        color = _T.textLight;
        bg = Colors.grey.shade100;
        icon = Icons.help_outline_rounded;
        label = status;
    }

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
      decoration: BoxDecoration(
        color: bg,
        borderRadius: BorderRadius.circular(20),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
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

  Widget _buildActionButton(
    Map<String, dynamic> attempt,
    String status,
    int attemptId,
    String studentName,
  ) {
    if (status == 'reviewed') {
      return GestureDetector(
        onTap: () async {
          await Navigator.pushNamed(
            context,
            '/manual-review',
            arguments: {
              'attempt_id': attemptId,
              'quiz_title': widget.quizTitle,
              'student_name': studentName,
              'read_only': true,
            },
          );
          _load();
        },
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
          decoration: BoxDecoration(
            color: _T.success.withOpacity(0.1),
            borderRadius: BorderRadius.circular(20),
            border: Border.all(color: _T.success.withOpacity(0.3)),
          ),
          child: const Row(
            children: [
              Icon(Icons.visibility_rounded, size: 14, color: _T.success),
              SizedBox(width: 6),
              Text('View Result',
                  style: TextStyle(
                      color: _T.success,
                      fontSize: 13,
                      fontWeight: FontWeight.bold)),
            ],
          ),
        ),
      );
    }

    if (status == 'under_review') {
      return GestureDetector(
        onTap: () async {
          await Navigator.pushNamed(
            context,
            '/manual-review',
            arguments: {
              'attempt_id': attemptId,
              'quiz_title': widget.quizTitle,
              'student_name': studentName,
              'read_only': false,
            },
          );
          _load();
        },
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
          decoration: BoxDecoration(
            color: _T.accent.withOpacity(0.1),
            borderRadius: BorderRadius.circular(20),
            border: Border.all(color: _T.accent.withOpacity(0.3)),
          ),
          child: const Row(
            children: [
              Icon(Icons.edit_note_rounded, size: 14, color: _T.accent),
              SizedBox(width: 6),
              Text('Continue Review',
                  style: TextStyle(
                      color: _T.accent,
                      fontSize: 13,
                      fontWeight: FontWeight.bold)),
            ],
          ),
        ),
      );
    }

    // submitted
    return GestureDetector(
      onTap: () async {
        await Navigator.pushNamed(
          context,
          '/manual-review',
          arguments: {
            'attempt_id': attemptId,
            'quiz_title': widget.quizTitle,
            'student_name': studentName,
            'read_only': false,
          },
        );
        _load();
      },
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
        decoration: BoxDecoration(
          color: _T.orange,
          borderRadius: BorderRadius.circular(20),
        ),
        child: const Row(
          children: [
            Icon(Icons.rate_review_rounded, size: 14, color: Colors.white),
            SizedBox(width: 6),
            Text('Review',
                style: TextStyle(
                    color: Colors.white,
                    fontSize: 13,
                    fontWeight: FontWeight.bold)),
          ],
        ),
      ),
    );
  }

  Widget _buildMeta(IconData icon, String label, {Color color = _T.textMid}) {
    return Row(
      children: [
        Icon(icon, size: 13, color: color),
        const SizedBox(width: 6),
        Text(label, style: TextStyle(fontSize: 12, color: color)),
      ],
    );
  }

  String _formatDate(String raw) {
    try {
      final dt = DateTime.parse(raw).toLocal();
      return '${dt.year}-${dt.month.toString().padLeft(2, '0')}-${dt.day.toString().padLeft(2, '0')} '
          '${dt.hour.toString().padLeft(2, '0')}:${dt.minute.toString().padLeft(2, '0')}';
    } catch (_) {
      return raw;
    }
  }
}