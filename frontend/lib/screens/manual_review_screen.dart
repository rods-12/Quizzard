import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import '../services/auth_service.dart';

class _T {
  static const Color primary = Color(0xFF2ECC71);
  static const Color primaryLight = Color(0xFFE8F8F0);
  static const Color primaryDark = Color(0xFF1BA35A);
  static const Color accent = Color(0xFF6C63FF);
  static const Color accentLight = Color(0xFFEEEDFF);
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

class ManualReviewScreen extends StatefulWidget {
  final int attemptId;
  final String quizTitle;
  final String studentName;
  final bool readOnly;

  const ManualReviewScreen({
    super.key,
    required this.attemptId,
    required this.quizTitle,
    required this.studentName,
    required this.readOnly,
  });

  @override
  State<ManualReviewScreen> createState() => _ManualReviewScreenState();
}

class _ManualReviewScreenState extends State<ManualReviewScreen> {
  bool _isLoading = true;
  bool _isSaving = false;
  bool _isFinalizing = false;
  bool _isReopening = false;
  bool _readOnly = false;

  Map<String, dynamic> _attempt = {};
  Map<String, dynamic> _student = {};
  List<Map<String, dynamic>> _questions = [];

  // Per-question controllers keyed by student_answer_id
  final Map<int, TextEditingController> _pointsControllers = {};
  final Map<int, TextEditingController> _feedbackControllers = {};

  // Ticket 10.5 — per-question inline error messages (null = no error)
  final Map<int, String?> _pointsErrors = {};

  // Ticket 10.5 — true only when every question has a valid points value
  bool _canFinalize = false;

  @override
  void initState() {
    super.initState();
    _readOnly = widget.readOnly;
    _load();
  }

  @override
  void dispose() {
    for (final c in _pointsControllers.values) c.dispose();
    for (final c in _feedbackControllers.values) c.dispose();
    super.dispose();
  }

  // ─── Ticket 10.5: validate a single points field ───────────────────────────
  // Returns an error string if invalid, null if valid.
  String? _validatePoints(String text, double maxPoints) {
    if (text.trim().isEmpty) return 'Required';
    final value = double.tryParse(text.trim());
    if (value == null) return 'Must be a number';
    if (value < 0) return 'Cannot be negative';
    if (value > maxPoints) {
      return 'Max is ${maxPoints.toStringAsFixed(maxPoints.truncateToDouble() == maxPoints ? 0 : 1)}';
    }
    return null;
  }

  // ─── Ticket 10.5: recompute _canFinalize across all questions ─────────────
  void _revalidateAll() {
    if (_readOnly) return;
    bool allValid = true;
    for (final q in _questions) {
      final id = q['student_answer_id'] as int;
      final maxPoints = (q['max_points'] as num).toDouble();
      final text = _pointsControllers[id]?.text ?? '';
      final error = _validatePoints(text, maxPoints);
      _pointsErrors[id] = error;
      if (error != null) allValid = false;
    }
    setState(() => _canFinalize = allValid);
  }

  Future<void> _load() async {
    setState(() => _isLoading = true);
    try {
      final response = await AuthService.authGet(
          '/teacher/manual-review/attempts/${widget.attemptId}');
      if (response['success'] == true) {
        final data = response['data'];
        final questions =
            List<Map<String, dynamic>>.from(data['questions'] ?? []);

        // Build controllers from existing review data
        for (final q in questions) {
          final id = q['student_answer_id'] as int;
          final review = q['review'] as Map<String, dynamic>?;
          final existingPoints = review?['points_awarded'];
          final existingFeedback = review?['feedback'] ?? '';

          // Points controller
          if (!_pointsControllers.containsKey(id)) {
            _pointsControllers[id] = TextEditingController(
              text: existingPoints != null ? existingPoints.toString() : '',
            );
            // Ticket 10.5 — attach live validation listener
            _pointsControllers[id]!.addListener(_revalidateAll);
          } else {
            _pointsControllers[id]!.text =
                existingPoints != null ? existingPoints.toString() : '';
          }

          // Feedback controller
          if (!_feedbackControllers.containsKey(id)) {
            _feedbackControllers[id] =
                TextEditingController(text: existingFeedback);
          } else {
            _feedbackControllers[id]!.text = existingFeedback;
          }

          // Initialise error state
          _pointsErrors[id] = null;
        }

        setState(() {
          _attempt = Map<String, dynamic>.from(data['attempt'] ?? {});
          _student = Map<String, dynamic>.from(data['student'] ?? {});
          _questions = questions;
        });

        // Run initial validation after state is set
        _revalidateAll();
      } else {
        _showSnackbar(response['message'] ?? 'Failed to load', isError: true);
      }
    } catch (e) {
      _showSnackbar('Network error: $e', isError: true);
    } finally {
      setState(() => _isLoading = false);
    }
  }

  // ─── Build review payload from controllers ─────────────────────────────────

  List<Map<String, dynamic>>? _buildReviewPayload({bool requireAll = false}) {
    final List<Map<String, dynamic>> reviews = [];
    for (final q in _questions) {
      final id = q['student_answer_id'] as int;
      final maxPoints = (q['max_points'] as num).toDouble();
      final pointsText = _pointsControllers[id]?.text.trim() ?? '';
      final feedback = _feedbackControllers[id]?.text.trim() ?? '';

      if (requireAll && pointsText.isEmpty) {
        _showSnackbar(
            'Please enter points for all questions before finalizing.',
            isError: true);
        return null;
      }

      double? points;
      if (pointsText.isNotEmpty) {
        points = double.tryParse(pointsText);
        if (points == null) {
          _showSnackbar('Invalid points value for one of the questions.',
              isError: true);
          return null;
        }
        if (points < 0 || points > maxPoints) {
          _showSnackbar(
              'Points must be between 0 and $maxPoints for each question.',
              isError: true);
          return null;
        }
      }

      reviews.add({
        'student_answer_id': id,
        if (points != null) 'points_awarded': points,
        'feedback': feedback,
      });
    }
    return reviews;
  }

  Future<void> _saveDraft() async {
    final reviews = _buildReviewPayload(requireAll: false);
    if (reviews == null) return;

    setState(() => _isSaving = true);
    try {
      final response = await AuthService.authPatch(
        '/teacher/manual-review/attempts/${widget.attemptId}/save-draft',
        {'reviews': reviews},
      );
      if (response['success'] == true) {
        _showSnackbar('Draft saved.');
        await _load();
      } else {
        _showSnackbar(response['message'] ?? 'Failed to save draft.',
            isError: true);
      }
    } catch (e) {
      _showSnackbar('Network error: $e', isError: true);
    } finally {
      setState(() => _isSaving = false);
    }
  }

  Future<void> _finalize() async {
    // Ticket 10.5 — run full validation before showing dialog
    _revalidateAll();
    if (!_canFinalize) {
      _showSnackbar(
          'Fix all points errors before finalizing.',
          isError: true);
      return;
    }

    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: const Text('Finalize Review',
            style: TextStyle(fontWeight: FontWeight.bold)),
        content: const Text(
            'This will mark the attempt as reviewed and release results to the student. Continue?'),
        actions: [
          TextButton(
              onPressed: () => Navigator.pop(ctx, false),
              child: const Text('Cancel')),
          ElevatedButton(
            style: ElevatedButton.styleFrom(
                backgroundColor: _T.primary,
                shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(10))),
            onPressed: () => Navigator.pop(ctx, true),
            child:
                const Text('Finalize', style: TextStyle(color: Colors.white)),
          ),
        ],
      ),
    );
    if (confirmed != true) return;

    final reviews = _buildReviewPayload(requireAll: true);
    if (reviews == null) return;

    setState(() => _isFinalizing = true);
    try {
      // ── First persist all points via save-draft ──
      final reviews = _buildReviewPayload(requireAll: true);
      if (reviews == null) {
        setState(() => _isFinalizing = false);
        return;
      }

      final draftResponse = await AuthService.authPatch(
        '/teacher/manual-review/attempts/${widget.attemptId}/save-draft',
        {'reviews': reviews},
      );

      if (draftResponse['success'] != true) {
        _showSnackbar(draftResponse['message'] ?? 'Failed to save points.',
            isError: true);
        return;
      }

      // ── Then finalize ──
      final response = await AuthService.authPost(
        '/teacher/manual-review/attempts/${widget.attemptId}/finalize',
        {},
      );
      if (response['success'] == true) {
        _showSnackbar('Review finalized. Results released to student.');
        setState(() => _readOnly = true);
        await _load();
      } else {
        _showSnackbar(response['message'] ?? 'Failed to finalize.',
            isError: true);
      }
    } catch (e) {
      _showSnackbar('Network error: $e', isError: true);
    } finally {
      setState(() => _isFinalizing = false);
    }
  }

  Future<void> _reopen() async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: const Text('Reopen Review',
            style: TextStyle(fontWeight: FontWeight.bold)),
        content: const Text(
            'This will reopen the attempt for editing. The student\'s results will be hidden until you finalize again.'),
        actions: [
          TextButton(
              onPressed: () => Navigator.pop(ctx, false),
              child: const Text('Cancel')),
          ElevatedButton(
            style: ElevatedButton.styleFrom(
                backgroundColor: _T.orange,
                shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(10))),
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('Reopen', style: TextStyle(color: Colors.white)),
          ),
        ],
      ),
    );
    if (confirmed != true) return;

    setState(() => _isReopening = true);
    try {
      final response = await AuthService.authPost(
        '/teacher/manual-review/attempts/${widget.attemptId}/reopen',
        {},
      );
      if (response['success'] == true) {
        _showSnackbar('Attempt reopened for editing.');
        setState(() => _readOnly = false);
        await _load();
      } else {
        _showSnackbar(response['message'] ?? 'Failed to reopen.',
            isError: true);
      }
    } catch (e) {
      _showSnackbar('Network error: $e', isError: true);
    } finally {
      setState(() => _isReopening = false);
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

  String _displayStudentName() {
    final first = _student['first_name'];
    final surname = _student['surname'];
    if (first != null || surname != null) {
      return '${first ?? ''} ${surname ?? ''}'.trim();
    }
    return _student['email'] ?? widget.studentName;
  }

  String _statusLabel(String status) {
    switch (status) {
      case 'submitted':
        return 'Pending Review';
      case 'under_review':
        return 'In Review';
      case 'reviewed':
        return 'Reviewed';
      default:
        return status;
    }
  }

  Color _statusColor(String status) {
    switch (status) {
      case 'submitted':
        return _T.orange;
      case 'under_review':
        return _T.accent;
      case 'reviewed':
        return _T.success;
      default:
        return _T.textLight;
    }
  }

  // ─── BUILD ─────────────────────────────────────────────────────────────────

  @override
  Widget build(BuildContext context) {
    final isBusy = _isSaving || _isFinalizing || _isReopening;

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
              _readOnly ? 'View Review' : 'Reviewing',
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
          : Stack(
              children: [
                CustomScrollView(
                  slivers: [
                    SliverToBoxAdapter(child: _buildStudentBanner()),
                    SliverPadding(
                      padding: const EdgeInsets.fromLTRB(20, 0, 20, 140),
                      sliver: SliverList(
                        delegate: SliverChildBuilderDelegate(
                          (context, i) =>
                              _buildQuestionCard(_questions[i], i + 1),
                          childCount: _questions.length,
                        ),
                      ),
                    ),
                  ],
                ),

                // ── Bottom action bar ──
                Positioned(
                  bottom: 0,
                  left: 0,
                  right: 0,
                  child: _buildBottomBar(isBusy),
                ),

                // ── Busy overlay ──
                if (isBusy)
                  Positioned.fill(
                    child: Container(
                      color: Colors.black.withOpacity(0.15),
                      child: const Center(
                          child: CircularProgressIndicator(color: _T.primary)),
                    ),
                  ),
              ],
            ),
    );
  }

  Widget _buildStudentBanner() {
    final status = _attempt['status'] ?? 'submitted';
    final studentName = _displayStudentName();

    return Container(
      margin: const EdgeInsets.fromLTRB(20, 20, 20, 16),
      padding: const EdgeInsets.all(16),
      decoration: _T.card,
      child: Row(
        children: [
          CircleAvatar(
            radius: 24,
            backgroundColor: _T.accent.withOpacity(0.1),
            child: Text(
              studentName.isNotEmpty ? studentName[0].toUpperCase() : '?',
              style: const TextStyle(
                  color: _T.accent, fontWeight: FontWeight.bold, fontSize: 18),
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(studentName,
                    style: const TextStyle(
                        fontWeight: FontWeight.bold,
                        fontSize: 15,
                        color: _T.textDark)),
                Text(_student['email'] ?? '',
                    style:
                        const TextStyle(fontSize: 12, color: _T.textMid)),
              ],
            ),
          ),
          Container(
            padding:
                const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
            decoration: BoxDecoration(
              color: _statusColor(status).withOpacity(0.1),
              borderRadius: BorderRadius.circular(20),
            ),
            child: Text(
              _statusLabel(status),
              style: TextStyle(
                  fontSize: 11,
                  color: _statusColor(status),
                  fontWeight: FontWeight.w600),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildQuestionCard(Map<String, dynamic> q, int index) {
    final studentAnswerId = q['student_answer_id'] as int;
    final questionType = q['question_type'] ?? '';
    final questionText = q['question_text'] ?? '';
    final maxPoints = (q['max_points'] as num).toDouble();
    final answerGiven = q['answer_given']?.toString() ?? '';
    final options =
        List<Map<String, dynamic>>.from(q['answer_options'] ?? []);

    // Ticket 10.5 — current error for this question
    final pointsError = _pointsErrors[studentAnswerId];
    final hasError = pointsError != null;

    return Container(
      margin: const EdgeInsets.only(bottom: 16),
      decoration: _T.card,
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // ── Question header ──
            Row(
              children: [
                Container(
                  width: 28,
                  height: 28,
                  decoration: BoxDecoration(
                    color: _T.accent.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Center(
                    child: Text('$index',
                        style: const TextStyle(
                            color: _T.accent,
                            fontWeight: FontWeight.bold,
                            fontSize: 13)),
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: Text(
                    _questionTypeLabel(questionType),
                    style:
                        const TextStyle(fontSize: 11, color: _T.textLight),
                  ),
                ),
                Text(
                  '${maxPoints.toStringAsFixed(maxPoints.truncateToDouble() == maxPoints ? 0 : 1)} pts max',
                  style: const TextStyle(
                      fontSize: 12,
                      color: _T.textMid,
                      fontWeight: FontWeight.w600),
                ),
              ],
            ),
            const SizedBox(height: 10),

            // ── Question text ──
            Text(questionText,
                style: const TextStyle(
                    fontSize: 15,
                    fontWeight: FontWeight.w600,
                    color: _T.textDark)),
            const SizedBox(height: 14),
            Divider(color: Colors.grey.shade100, height: 1),
            const SizedBox(height: 14),

            // ── Answer display ──
            _buildAnswerDisplay(questionType, answerGiven, options),
            const SizedBox(height: 16),
            Divider(color: Colors.grey.shade100, height: 1),
            const SizedBox(height: 14),

            // ── Points input ──
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Expanded(
                  flex: 2,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          const Text('Points Awarded',
                              style: TextStyle(
                                  fontSize: 12,
                                  fontWeight: FontWeight.w600,
                                  color: _T.textMid)),
                          // Ticket 10.5 — required indicator
                          if (!_readOnly)
                            const Text(' *',
                                style: TextStyle(
                                    fontSize: 12,
                                    color: _T.danger,
                                    fontWeight: FontWeight.bold)),
                        ],
                      ),
                      const SizedBox(height: 6),
                      TextField(
                        controller: _pointsControllers[studentAnswerId],
                        enabled: !_readOnly,
                        keyboardType: const TextInputType.numberWithOptions(
                            decimal: true),
                        inputFormatters: [
                          FilteringTextInputFormatter.allow(
                              RegExp(r'^\d*\.?\d{0,2}')),
                        ],
                        decoration: InputDecoration(
                          hintText: '0 – $maxPoints',
                          hintStyle:
                              const TextStyle(color: _T.textLight),
                          contentPadding: const EdgeInsets.symmetric(
                              horizontal: 12, vertical: 10),
                          // Ticket 10.5 — red border when invalid
                          border: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(10),
                              borderSide: BorderSide(
                                  color: hasError
                                      ? _T.danger
                                      : Colors.grey.shade300)),
                          enabledBorder: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(10),
                            borderSide: BorderSide(
                                color: hasError
                                    ? _T.danger
                                    : Colors.grey.shade300),
                          ),
                          focusedBorder: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(10),
                            borderSide: BorderSide(
                                color: hasError ? _T.danger : _T.primary,
                                width: 2),
                          ),
                          disabledBorder: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(10),
                            borderSide:
                                BorderSide(color: Colors.grey.shade200),
                          ),
                          suffixText: '/ $maxPoints',
                          suffixStyle: const TextStyle(
                              fontSize: 12, color: _T.textLight),
                          // Ticket 10.5 — inline error text
                          errorText: hasError ? pointsError : null,
                          errorStyle: const TextStyle(
                              fontSize: 11, color: _T.danger),
                        ),
                        style: TextStyle(
                            fontSize: 15,
                            fontWeight: FontWeight.bold,
                            color: hasError ? _T.danger : _T.textDark),
                      ),
                    ],
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),

            // ── Feedback ──
            const Text('Feedback (optional)',
                style: TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.w600,
                    color: _T.textMid)),
            const SizedBox(height: 6),
            TextField(
              controller: _feedbackControllers[studentAnswerId],
              enabled: !_readOnly,
              maxLines: 3,
              decoration: InputDecoration(
                hintText: 'Leave feedback for the student...',
                hintStyle: const TextStyle(color: _T.textLight),
                contentPadding: const EdgeInsets.all(12),
                border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(10)),
                focusedBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(10),
                  borderSide:
                      const BorderSide(color: _T.primary, width: 2),
                ),
                disabledBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(10),
                  borderSide: BorderSide(color: Colors.grey.shade200),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  // ─── Answer display per question type ──────────────────────────────────────

  Widget _buildAnswerDisplay(
      String type, String answerGiven, List<Map<String, dynamic>> options) {
    switch (type) {
      case 'multiple_choice':
      case 'true_false':
        return _buildOptionAnswerDisplay(answerGiven, options);
      case 'identification':
        return _buildIdentificationDisplay(answerGiven, options);
      case 'matching':
        return _buildMatchingDisplay(answerGiven, options);
      default:
        return _buildRawAnswerDisplay(answerGiven);
    }
  }

  Widget _buildOptionAnswerDisplay(
      String answerGiven, List<Map<String, dynamic>> options) {
    final selectedId = int.tryParse(answerGiven);

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text('Student Answer',
            style: TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.w600,
                color: _T.textMid)),
        const SizedBox(height: 8),
        ...options.map((opt) {
          final isSelected = opt['id'] == selectedId;
          final isCorrect = opt['is_correct'] == true;

          Color borderColor = Colors.grey.shade200;
          Color bgColor = Colors.grey.shade50;
          Widget? trailingIcon;

          if (isSelected && isCorrect) {
            borderColor = _T.success;
            bgColor = _T.success.withOpacity(0.08);
            trailingIcon = const Icon(Icons.check_circle_rounded,
                color: _T.success, size: 18);
          } else if (isSelected && !isCorrect) {
            borderColor = _T.danger;
            bgColor = _T.danger.withOpacity(0.08);
            trailingIcon =
                const Icon(Icons.cancel_rounded, color: _T.danger, size: 18);
          } else if (!isSelected && isCorrect) {
            borderColor = _T.success.withOpacity(0.4);
            bgColor = _T.success.withOpacity(0.04);
            trailingIcon = const Icon(Icons.check_rounded,
                color: _T.success, size: 16);
          }

          return Container(
            margin: const EdgeInsets.only(bottom: 6),
            padding:
                const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
            decoration: BoxDecoration(
              color: bgColor,
              borderRadius: BorderRadius.circular(10),
              border: Border.all(color: borderColor),
            ),
            child: Row(
              children: [
                if (isSelected)
                  Icon(Icons.radio_button_checked_rounded,
                      size: 16,
                      color: isCorrect ? _T.success : _T.danger)
                else
                  Icon(Icons.radio_button_unchecked_rounded,
                      size: 16, color: Colors.grey.shade400),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(opt['option_text'] ?? '',
                      style: TextStyle(
                          fontSize: 13,
                          color: _T.textDark,
                          fontWeight: isSelected
                              ? FontWeight.w600
                              : FontWeight.normal)),
                ),
                if (trailingIcon != null) trailingIcon,
              ],
            ),
          );
        }),
      ],
    );
  }

  Widget _buildIdentificationDisplay(
      String answerGiven, List<Map<String, dynamic>> options) {
    final correctOption =
        options.firstWhere((o) => o['is_correct'] == true, orElse: () => {});
    final correctText = correctOption['option_text'] ?? '';
    final isCorrect = answerGiven.toLowerCase().trim() ==
        correctText.toLowerCase().trim();

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text('Student Answer',
            style: TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.w600,
                color: _T.textMid)),
        const SizedBox(height: 6),
        Container(
          width: double.infinity,
          padding: const EdgeInsets.all(12),
          decoration: BoxDecoration(
            color: isCorrect
                ? _T.success.withOpacity(0.08)
                : _T.danger.withOpacity(0.08),
            borderRadius: BorderRadius.circular(10),
            border: Border.all(
                color: isCorrect
                    ? _T.success.withOpacity(0.5)
                    : _T.danger.withOpacity(0.5)),
          ),
          child: Row(
            children: [
              Expanded(
                child: Text(
                  answerGiven.isEmpty ? '(no answer)' : answerGiven,
                  style: const TextStyle(
                      fontSize: 14,
                      color: _T.textDark,
                      fontWeight: FontWeight.w500),
                ),
              ),
              Icon(
                isCorrect ? Icons.check_circle_rounded : Icons.cancel_rounded,
                color: isCorrect ? _T.success : _T.danger,
                size: 18,
              ),
            ],
          ),
        ),
        const SizedBox(height: 8),
        const Text('Correct Answer',
            style: TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.w600,
                color: _T.textMid)),
        const SizedBox(height: 6),
        Container(
          width: double.infinity,
          padding: const EdgeInsets.all(12),
          decoration: BoxDecoration(
            color: _T.success.withOpacity(0.06),
            borderRadius: BorderRadius.circular(10),
            border: Border.all(color: _T.success.withOpacity(0.3)),
          ),
          child: Text(
            correctText,
            style: const TextStyle(
                fontSize: 14,
                color: _T.success,
                fontWeight: FontWeight.w600),
          ),
        ),
      ],
    );
  }

  Widget _buildMatchingDisplay(
      String answerGiven, List<Map<String, dynamic>> options) {
    final correctPairs =
        options.where((o) => o['match_pair'] != null).toList();

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text('Student Answer',
            style: TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.w600,
                color: _T.textMid)),
        const SizedBox(height: 6),
        Container(
          width: double.infinity,
          padding: const EdgeInsets.all(12),
          decoration: BoxDecoration(
            color: Colors.grey.shade50,
            borderRadius: BorderRadius.circular(10),
            border: Border.all(color: Colors.grey.shade200),
          ),
          child: Text(
            answerGiven.isEmpty ? '(no answer)' : answerGiven,
            style: const TextStyle(fontSize: 13, color: _T.textDark),
          ),
        ),
        if (correctPairs.isNotEmpty) ...[
          const SizedBox(height: 10),
          const Text('Correct Pairs',
              style: TextStyle(
                  fontSize: 12,
                  fontWeight: FontWeight.w600,
                  color: _T.textMid)),
          const SizedBox(height: 6),
          ...correctPairs.map((opt) => Container(
                margin: const EdgeInsets.only(bottom: 6),
                padding: const EdgeInsets.symmetric(
                    horizontal: 12, vertical: 8),
                decoration: BoxDecoration(
                  color: _T.success.withOpacity(0.06),
                  borderRadius: BorderRadius.circular(10),
                  border: Border.all(color: _T.success.withOpacity(0.3)),
                ),
                child: Row(
                  children: [
                    Expanded(
                        child: Text(opt['option_text'] ?? '',
                            style: const TextStyle(
                                fontSize: 13, color: _T.textDark))),
                    const Icon(Icons.arrow_forward_rounded,
                        size: 14, color: _T.textLight),
                    const SizedBox(width: 8),
                    Expanded(
                        child: Text(opt['match_pair'] ?? '',
                            style: const TextStyle(
                                fontSize: 13,
                                color: _T.success,
                                fontWeight: FontWeight.w600))),
                  ],
                ),
              )),
        ],
      ],
    );
  }

  Widget _buildRawAnswerDisplay(String answerGiven) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text('Student Answer',
            style: TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.w600,
                color: _T.textMid)),
        const SizedBox(height: 6),
        Container(
          width: double.infinity,
          padding: const EdgeInsets.all(12),
          decoration: BoxDecoration(
            color: Colors.grey.shade50,
            borderRadius: BorderRadius.circular(10),
            border: Border.all(color: Colors.grey.shade200),
          ),
          child: Text(
            answerGiven.isEmpty ? '(no answer)' : answerGiven,
            style: const TextStyle(fontSize: 13, color: _T.textDark),
          ),
        ),
      ],
    );
  }

  // ─── Bottom bar ────────────────────────────────────────────────────────────

  Widget _buildBottomBar(bool isBusy) {
    return Container(
      padding: EdgeInsets.fromLTRB(
          20, 12, 20, MediaQuery.of(context).padding.bottom + 12),
      decoration: BoxDecoration(
        color: _T.surface,
        boxShadow: [
          BoxShadow(
              color: Colors.black.withOpacity(0.08),
              blurRadius: 16,
              offset: const Offset(0, -4))
        ],
      ),
      child: _readOnly ? _buildReadOnlyBar(isBusy) : _buildEditBar(isBusy),
    );
  }

  Widget _buildEditBar(bool isBusy) {
    // Ticket 10.5 — Finalize disabled when _canFinalize is false or busy
    final finalizeEnabled = !isBusy && _canFinalize;

    return Row(
      children: [
        Expanded(
          child: OutlinedButton.icon(
            onPressed: isBusy ? null : _saveDraft,
            icon: _isSaving
                ? const SizedBox(
                    width: 16,
                    height: 16,
                    child: CircularProgressIndicator(
                        strokeWidth: 2, color: _T.accent))
                : const Icon(Icons.save_outlined, size: 18),
            label: const Text('Save Draft'),
            style: OutlinedButton.styleFrom(
              foregroundColor: _T.accent,
              side: const BorderSide(color: _T.accent),
              padding: const EdgeInsets.symmetric(vertical: 14),
              shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12)),
            ),
          ),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: Tooltip(
            // Ticket 10.5 — hint explaining why button is disabled
            message: finalizeEnabled
                ? ''
                : 'Enter valid points for all questions to finalize',
            child: ElevatedButton.icon(
              onPressed: finalizeEnabled ? _finalize : null,
              icon: _isFinalizing
                  ? const SizedBox(
                      width: 16,
                      height: 16,
                      child: CircularProgressIndicator(
                          strokeWidth: 2, color: Colors.white))
                  : const Icon(Icons.check_circle_outline_rounded,
                      size: 18, color: Colors.white),
              label: const Text('Finalize',
                  style: TextStyle(color: Colors.white)),
              style: ElevatedButton.styleFrom(
                // Ticket 10.5 — greyed out when disabled
                backgroundColor:
                    finalizeEnabled ? _T.primary : Colors.grey.shade300,
                padding: const EdgeInsets.symmetric(vertical: 14),
                shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12)),
                elevation: 0,
                disabledBackgroundColor: Colors.grey.shade300,
                disabledForegroundColor: Colors.grey.shade500,
              ),
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildReadOnlyBar(bool isBusy) {
    return SizedBox(
      width: double.infinity,
      child: OutlinedButton.icon(
        onPressed: isBusy ? null : _reopen,
        icon: _isReopening
            ? const SizedBox(
                width: 16,
                height: 16,
                child: CircularProgressIndicator(
                    strokeWidth: 2, color: _T.orange))
            : const Icon(Icons.lock_open_rounded, size: 18),
        label: const Text('Reopen for Editing'),
        style: OutlinedButton.styleFrom(
          foregroundColor: _T.orange,
          side: const BorderSide(color: _T.orange),
          padding: const EdgeInsets.symmetric(vertical: 14),
          shape:
              RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
        ),
      ),
    );
  }

  String _questionTypeLabel(String type) {
    switch (type) {
      case 'multiple_choice':
        return 'Multiple Choice';
      case 'true_false':
        return 'True / False';
      case 'identification':
        return 'Identification';
      case 'matching':
        return 'Matching';
      default:
        return type;
    }
  }
}