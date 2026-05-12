import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import '../services/auth_service.dart';

// ─────────────────────────────────────────────────────────────────────────────
// Screen
// ─────────────────────────────────────────────────────────────────────────────

class ClassDetailScreen extends StatefulWidget {
  final int classId;
  final String className;

  const ClassDetailScreen({
    super.key,
    required this.classId,
    required this.className,
  });

  @override
  State<ClassDetailScreen> createState() => _ClassDetailScreenState();
}

class _ClassDetailScreenState extends State<ClassDetailScreen>
    with SingleTickerProviderStateMixin {
  // ── Core state ──────────────────────────────────────────────────────────────
  late TabController _tabController;
  bool _isLoading = true;
  String? _errorMessage;
  Map<String, dynamic>? _classData;

  // ── Student performance state ────────────────────────────────────────────────
  bool _isLoadingStudents = false;
  String? _studentsError;
  Map<String, dynamic>? _studentPerformanceData;
  List<dynamic> _studentResults = [];
  String _studentSortColumn = 'surname';
  bool _studentSortAscending = true;
  bool _isExportingStudents = false;

  // ─────────────────────────────────────────────────────────────────────────────
  // Lifecycle
  // ─────────────────────────────────────────────────────────────────────────────

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
    _loadClassDetail();
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  // ─────────────────────────────────────────────────────────────────────────────
  // Data loading
  // ─────────────────────────────────────────────────────────────────────────────

  Future<void> _loadClassDetail() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    final result = await AuthService.authGet('/classes/${widget.classId}');

    setState(() {
      _isLoading = false;
      if (result['success']) {
        _classData = result['data']['class'];
      } else {
        _errorMessage = result['message'];
      }
    });
  }

  Future<void> _loadStudentPerformance() async {
    setState(() {
      _isLoadingStudents = true;
      _studentsError = null;
    });

    final result = await AuthService.authGet(
      '/classes/${widget.classId}/students/performance',
    );

    setState(() {
      _isLoadingStudents = false;
      if (result['success'] == true || result['students'] != null) {
        _studentPerformanceData = result['data'] ?? result;
        _studentResults = List.from(_studentPerformanceData?['students'] ?? []);
        _sortStudentResults();
      } else {
        _studentsError =
            result['message'] ?? 'Failed to load student performance';
      }
    });
  }

  // ─────────────────────────────────────────────────────────────────────────────
  // Student sorting
  // ─────────────────────────────────────────────────────────────────────────────

  void _sortStudentResults() {
    _studentResults.sort((a, b) {
      dynamic valA;
      dynamic valB;

      switch (_studentSortColumn) {
        case 'student_id':
          valA = (a['student_id'] ?? '').toString().toLowerCase();
          valB = (b['student_id'] ?? '').toString().toLowerCase();
          break;
        case 'first_name':
          valA = (a['first_name'] ?? '').toString().toLowerCase();
          valB = (b['first_name'] ?? '').toString().toLowerCase();
          break;
        case 'surname':
          valA = (a['surname'] ?? '').toString().toLowerCase();
          valB = (b['surname'] ?? '').toString().toLowerCase();
          break;
        case 'quizzes_taken':
          valA = (a['quizzes_taken'] ?? 0).toDouble();
          valB = (b['quizzes_taken'] ?? 0).toDouble();
          break;
        case 'overall_percentage':
          valA = (a['overall_percentage'] ?? 0).toDouble();
          valB = (b['overall_percentage'] ?? 0).toDouble();
          break;
        default:
          valA = (a['surname'] ?? '').toString().toLowerCase();
          valB = (b['surname'] ?? '').toString().toLowerCase();
      }

      final comparison = (valA is num && valB is num)
          ? valA.compareTo(valB)
          : valA.toString().compareTo(valB.toString());

      return _studentSortAscending ? comparison : -comparison;
    });
  }

  void _onStudentSort(String column) {
    setState(() {
      if (_studentSortColumn == column) {
        _studentSortAscending = !_studentSortAscending;
      } else {
        _studentSortColumn = column;
        _studentSortAscending = true;
      }
      _sortStudentResults();
    });
  }

  // ─────────────────────────────────────────────────────────────────────────────
  // Actions
  // ─────────────────────────────────────────────────────────────────────────────

  void _copyClassCode() {
    final code = _classData?['class_code'] ?? '';
    Clipboard.setData(ClipboardData(text: code));
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text('Class code "$code" copied to clipboard!'),
        backgroundColor: Colors.green,
      ),
    );
  }

  /// Pick date + time for a deadline.
  Future<DateTime?> _pickDueDate({DateTime? initialDate}) async {
    final date = await showDatePicker(
      context: context,
      initialDate: initialDate ?? DateTime.now().add(const Duration(days: 1)),
      firstDate: DateTime.now(),
      lastDate: DateTime.now().add(const Duration(days: 365)),
    );
    if (date == null) return null;

    final time = await showTimePicker(
      context: context,
      initialTime: initialDate != null
          ? TimeOfDay(hour: initialDate.hour, minute: initialDate.minute)
          : const TimeOfDay(hour: 23, minute: 59),
    );
    if (time == null) return null;

    return DateTime(date.year, date.month, date.day, time.hour, time.minute);
  }

  Future<void> _editDueDate(Map<String, dynamic> quiz) async {
    final currentDue = quiz['pivot']?['due_date'];
    DateTime? selectedDueDate =
        currentDue != null ? DateTime.tryParse(currentDue) : null;

    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) {
        return StatefulBuilder(
          builder: (context, setState) => AlertDialog(
            shape:
                RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
            title: const Text('Edit Due Date'),
            content: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                const Text('Update the deadline for this quiz.'),
                const SizedBox(height: 16),
                if (selectedDueDate != null)
                  Text(
                    'Due: ${selectedDueDate!.toLocal().toString().substring(0, 16)}',
                    style: const TextStyle(
                      fontWeight: FontWeight.bold,
                      color: Color(0xFF4CAF50),
                    ),
                  )
                else
                  const Text(
                    'No deadline currently set',
                    style: TextStyle(color: Colors.grey),
                  ),
                const SizedBox(height: 8),
                OutlinedButton.icon(
                  icon: const Icon(Icons.calendar_today),
                  label: Text(selectedDueDate == null
                      ? 'Pick Due Date'
                      : 'Change Due Date'),
                  onPressed: () async {
                    final picked =
                        await _pickDueDate(initialDate: selectedDueDate);
                    if (picked != null) setState(() => selectedDueDate = picked);
                  },
                ),
                if (selectedDueDate != null)
                  TextButton(
                    onPressed: () => setState(() => selectedDueDate = null),
                    child: const Text('Clear Deadline',
                        style: TextStyle(color: Colors.red)),
                  ),
              ],
            ),
            actions: [
              TextButton(
                onPressed: () => Navigator.pop(context, false),
                child: const Text('Cancel'),
              ),
              ElevatedButton(
                onPressed: () => Navigator.pop(context, true),
                child: const Text('Save'),
              ),
            ],
          ),
        );
      },
    );

    if (confirmed != true) return;

    final body = <String, dynamic>{
      'due_date': selectedDueDate?.toIso8601String(),
    };

    final result = await AuthService.authPatch(
      '/classes/${widget.classId}/quizzes/${quiz['id']}/due-date',
      body,
    );

    if (!mounted) return;

    if (result['success']) {
      _loadClassDetail();
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Due date updated successfully!'),
          backgroundColor: Colors.green,
        ),
      );
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(result['message'] ?? 'Failed to update due date'),
          backgroundColor: Colors.red,
        ),
      );
    }
  }


  Future<void> _editGradingMode(Map<String, dynamic> quiz) async {
    final currentMode = quiz['pivot']?['grading_mode'] ?? 'automatic';
    String selectedMode = currentMode;

    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) {
        return StatefulBuilder(
          builder: (context, setDialogState) => AlertDialog(
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
            title: const Text('Edit Grading Mode'),
            content: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text('Change how student answers are graded for this class.'),
                const SizedBox(height: 16),
                _GradingModeSelector(
                  value: selectedMode,
                  onChanged: (v) => setDialogState(() => selectedMode = v),
                ),
              ],
            ),
            actions: [
              TextButton(
                onPressed: () => Navigator.pop(context, false),
                child: const Text('Cancel'),
              ),
              ElevatedButton(
                onPressed: () => Navigator.pop(context, true),
                style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFF4CAF50)),
                child: const Text('Save', style: TextStyle(color: Colors.white)),
              ),
            ],
          ),
        );
      },
    );

    if (confirmed != true) return;
    if (selectedMode == currentMode) return;

    final result = await AuthService.authPatch(
      '/classes/${widget.classId}/quizzes/${quiz['id']}/grading-mode',
      {'grading_mode': selectedMode},
    );

    if (!mounted) return;

    if (result['success'] == true) {
      _loadClassDetail();
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Grading mode updated to ${selectedMode == 'manual' ? 'Manual' : 'Automatic'}!'),
          backgroundColor: Colors.green,
        ),
      );
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(result['message'] ?? 'Failed to update grading mode'),
          backgroundColor: Colors.red,
        ),
      );
    }
  }



  void _goToReviewSubmissions(Map<String, dynamic> quiz) async {
    await Navigator.pushNamed(
      context,
      '/manual-review-students',
      arguments: {
        'class_id': widget.classId,
        'class_name': widget.className,
        'quiz_id': quiz['id'],
        'quiz_title': quiz['title'],
      },
    );
    _loadClassDetail();
  }




  // ── Assign existing quiz ────────────────────────────────────────────────────

  Future<void> _assignQuiz() async {
    final result = await AuthService.authGet('/quizzes');
    if (!result['success']) return;

    final data = result['data'];
    final allQuizzes =
        (data is List ? data : (data['quizzes'] ?? data['data'] ?? [])) as List;
    final assignedIds =
        (_classData!['quizzes'] as List).map((q) => q['id']).toSet();
    final availableQuizzes =
        allQuizzes.where((q) => !assignedIds.contains(q['id'])).toList();

    if (!mounted) return;

    if (availableQuizzes.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content:
              Text('All your quizzes are already assigned to this class.'),
          backgroundColor: Colors.orange,
        ),
      );
      return;
    }

    final selectedQuiz = await showDialog<Map<String, dynamic>>(
      context: context,
      builder: (context) => AlertDialog(
        shape:
            RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: const Text('Assign Quiz'),
        content: SizedBox(
          width: double.maxFinite,
          child: ListView.builder(
            shrinkWrap: true,
            itemCount: availableQuizzes.length,
            itemBuilder: (context, index) {
              final quiz = availableQuizzes[index];
              return ListTile(
                title: Text(quiz['title']),
                subtitle: Text(
                  quiz['is_published'] == true ? 'Published' : 'Draft',
                  style: TextStyle(
                    color: quiz['is_published'] == true
                        ? Colors.green
                        : Colors.grey,
                  ),
                ),
                leading:
                    const Icon(Icons.quiz, color: Color(0xFF4CAF50)),
                onTap: () =>
                    Navigator.pop(context, Map<String, dynamic>.from(quiz)),
              );
            },
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Cancel'),
          ),
        ],
      ),
    );

    if (selectedQuiz == null || !mounted) return;

    // ── Due date + grading mode dialog ──
    DateTime? selectedDueDate;
    String selectedGradingMode = 'automatic';

    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) {
        return StatefulBuilder(
          builder: (context, setDialogState) => AlertDialog(
            shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(16)),
            title: const Text('Quiz Settings'),
            content: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // ── Grading mode ──
                const Text(
                  'Grading Mode',
                  style: TextStyle(
                    fontWeight: FontWeight.bold,
                    fontSize: 13,
                    color: Color(0xFF333333),
                  ),
                ),
                const SizedBox(height: 8),
                _GradingModeSelector(
                  value: selectedGradingMode,
                  onChanged: (v) =>
                      setDialogState(() => selectedGradingMode = v),
                ),
                const SizedBox(height: 20),
                // ── Due date ──
                const Text(
                  'Due Date (Optional)',
                  style: TextStyle(
                    fontWeight: FontWeight.bold,
                    fontSize: 13,
                    color: Color(0xFF333333),
                  ),
                ),
                const SizedBox(height: 8),
                if (selectedDueDate != null)
                  Padding(
                    padding: const EdgeInsets.only(bottom: 8),
                    child: Text(
                      'Due: ${selectedDueDate!.toLocal().toString().substring(0, 16)}',
                      style: const TextStyle(
                        fontWeight: FontWeight.bold,
                        color: Color(0xFF4CAF50),
                      ),
                    ),
                  ),
                OutlinedButton.icon(
                  icon: const Icon(Icons.calendar_today),
                  label: Text(selectedDueDate == null
                      ? 'Pick Due Date'
                      : 'Change Due Date'),
                  onPressed: () async {
                    final picked = await _pickDueDate();
                    if (picked != null) {
                      setDialogState(() => selectedDueDate = picked);
                    }
                  },
                ),
                if (selectedDueDate != null)
                  TextButton(
                    onPressed: () =>
                        setDialogState(() => selectedDueDate = null),
                    child: const Text('Clear',
                        style: TextStyle(color: Colors.red)),
                  ),
              ],
            ),
            actions: [
              TextButton(
                onPressed: () => Navigator.pop(context, false),
                child: const Text('Cancel'),
              ),
              ElevatedButton(
                onPressed: () => Navigator.pop(context, true),
                style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFF4CAF50)),
                child: const Text('Assign',
                    style: TextStyle(color: Colors.white)),
              ),
            ],
          ),
        );
      },
    );

    if (confirmed != true) return;

    final body = <String, dynamic>{
      'quiz_id': selectedQuiz['id'],
      'grading_mode': selectedGradingMode,
    };
    if (selectedDueDate != null) {
      body['due_date'] = selectedDueDate!.toIso8601String();
    }

    final assignResult = await AuthService.authPost(
      '/classes/${widget.classId}/assign-quiz',
      body,
    );

    if (!mounted) return;

    if (assignResult['success']) {
      _loadClassDetail();
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Quiz assigned successfully!'),
          backgroundColor: Colors.green,
        ),
      );
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(assignResult['message']),
          backgroundColor: Colors.red,
        ),
      );
    }
  }

  Future<void> _unassignQuiz(Map<String, dynamic> quiz) async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: const Text('Remove Quiz'),
        content: Text('Remove "${quiz['title']}" from this class?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Cancel'),
          ),
          ElevatedButton(
            onPressed: () => Navigator.pop(context, true),
            style: ElevatedButton.styleFrom(backgroundColor: Colors.red),
            child:
                const Text('Remove', style: TextStyle(color: Colors.white)),
          ),
        ],
      ),
    );

    if (confirm != true) return;

    final result = await AuthService.authDelete(
      '/classes/${widget.classId}/quizzes/${quiz['id']}',
    );

    if (result['success']) {
      _loadClassDetail();
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Quiz removed from class!'),
          backgroundColor: Colors.green,
        ),
      );
    }
  }

  Future<void> _exportStudentPerformance() async {
    setState(() => _isExportingStudents = true);
    try {
      final filename =
          '${widget.className}_student_performance.xlsx'.replaceAll(' ', '_');

      final res = await AuthService.downloadFile(
        '/classes/${widget.classId}/students/export-performance',
        filename,
      );

      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        res['success']
            ? const SnackBar(
                content: Text('Excel file downloaded successfully!'),
                backgroundColor: Color(0xFF4CAF50),
              )
            : SnackBar(
                content: Text(res['message'] ?? 'Download failed'),
                backgroundColor: Colors.red,
              ),
      );
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Error: $e'), backgroundColor: Colors.red),
      );
    } finally {
      if (mounted) setState(() => _isExportingStudents = false);
    }
  }

  void _viewClassQuizResults(Map<String, dynamic> quiz) {
    Navigator.pushNamed(
      context,
      '/class-quiz-results',
      arguments: {
        'class_id': widget.classId,
        'class_name': widget.className,
        'quiz_id': quiz['id'],
        'quiz_title': quiz['title'],
      },
    );
  }

  Future<void> _showQuizOptions() async {
    await showModalBottomSheet(
      context: context,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) => Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'Add Quiz to Class',
              style: TextStyle(
                  fontSize: 18,
                  fontWeight: FontWeight.bold,
                  color: Color(0xFF333333)),
            ),
            const SizedBox(height: 8),
            Text(
              'Choose how you want to add a quiz to this class.',
              style: TextStyle(color: Colors.grey.shade600),
            ),
            const SizedBox(height: 24),
            ListTile(
              onTap: () async {
                Navigator.pop(context);
                await _createAndAssignQuiz();
              },
              leading: Container(
                width: 48,
                height: 48,
                decoration: BoxDecoration(
                  color: const Color(0xFF6C63FF).withOpacity(0.1),
                  borderRadius: BorderRadius.circular(12),
                ),
                child:
                    const Icon(Icons.add_circle, color: Color(0xFF6C63FF)),
              ),
              title: const Text('Create New Quiz',
                  style: TextStyle(fontWeight: FontWeight.bold)),
              subtitle: const Text(
                  'Create a brand new quiz and assign it to this class'),
              trailing: const Icon(Icons.chevron_right),
            ),
            const Divider(),
            ListTile(
              onTap: () async {
                Navigator.pop(context);
                await _assignQuiz();
              },
              leading: Container(
                width: 48,
                height: 48,
                decoration: BoxDecoration(
                  color: const Color(0xFF4CAF50).withOpacity(0.1),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: const Icon(Icons.playlist_add,
                    color: Color(0xFF4CAF50)),
              ),
              title: const Text('Assign Existing Quiz',
                  style: TextStyle(fontWeight: FontWeight.bold)),
              subtitle: const Text(
                  'Pick from your existing quizzes and assign to this class'),
              trailing: const Icon(Icons.chevron_right),
            ),
            const SizedBox(height: 16),
          ],
        ),
      ),
    );
  }

  // ── Create new quiz then assign ─────────────────────────────────────────────

  Future<void> _createAndAssignQuiz() async {
    final nameController = TextEditingController();
    final descController = TextEditingController();

    final confirm = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        shape:
            RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: const Text('Create New Quiz'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            TextField(
              controller: nameController,
              maxLength: 100,
              decoration: InputDecoration(
                labelText: 'Quiz Title',
                hintText: 'e.g. Chapter 1 Quiz',
                border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(12)),
              ),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: descController,
              maxLength: 200,
              maxLines: 3,
              decoration: InputDecoration(
                labelText: 'Description (optional)',
                border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(12)),
              ),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Cancel'),
          ),
          ElevatedButton(
            onPressed: () => Navigator.pop(context, true),
            style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFF6C63FF)),
            child:
                const Text('Create', style: TextStyle(color: Colors.white)),
          ),
        ],
      ),
    );

    if (confirm != true) return;

    if (nameController.text.trim().isEmpty) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Quiz title is required!'),
          backgroundColor: Colors.orange,
        ),
      );
      return;
    }

    final createResult = await AuthService.authPost('/quizzes', {
      'title': nameController.text.trim(),
      'description': descController.text.trim(),
    });

    if (!createResult['success']) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(createResult['message']),
          backgroundColor: Colors.red,
        ),
      );
      return;
    }

    final newQuizId = createResult['data']['data']['id'];

    // ── Grading mode dialog for new quiz ──
    String selectedGradingMode = 'automatic';

    if (!mounted) return;
    final gradingConfirmed = await showDialog<bool>(
      context: context,
      builder: (context) {
        return StatefulBuilder(
          builder: (context, setDialogState) => AlertDialog(
            shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(16)),
            title: const Text('Grading Mode'),
            content: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text('Choose how student answers will be graded.'),
                const SizedBox(height: 16),
                _GradingModeSelector(
                  value: selectedGradingMode,
                  onChanged: (v) =>
                      setDialogState(() => selectedGradingMode = v),
                ),
              ],
            ),
            actions: [
              TextButton(
                onPressed: () => Navigator.pop(context, false),
                child: const Text('Cancel'),
              ),
              ElevatedButton(
                onPressed: () => Navigator.pop(context, true),
                style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFF4CAF50)),
                child: const Text('Assign',
                    style: TextStyle(color: Colors.white)),
              ),
            ],
          ),
        );
      },
    );

    if (gradingConfirmed != true) return;

    final assignResult = await AuthService.authPost(
      '/classes/${widget.classId}/assign-quiz',
      {
        'quiz_id': newQuizId,
        'grading_mode': selectedGradingMode,
      },
    );

    if (!mounted) return;

    if (assignResult['success']) {
      _loadClassDetail();
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Quiz created and assigned to class!'),
          backgroundColor: Colors.green,
        ),
      );
      await Navigator.pushNamed(
        context,
        '/quiz-detail',
        arguments: {
          'quiz_id': newQuizId,
          'quiz_title': nameController.text.trim(),
        },
      );
      _loadClassDetail();
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(assignResult['message']),
          backgroundColor: Colors.red,
        ),
      );
    }
  }

  // ─────────────────────────────────────────────────────────────────────────────
  // Helpers
  // ─────────────────────────────────────────────────────────────────────────────

  Color _getGradeColor(double percentage) {
    if (percentage >= 75) return const Color(0xFF4CAF50);
    if (percentage >= 50) return Colors.orange;
    return Colors.red;
  }

  // ─────────────────────────────────────────────────────────────────────────────
  // Build
  // ─────────────────────────────────────────────────────────────────────────────

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF5F5F5),
      appBar: AppBar(
        title: Text(widget.className, style: const TextStyle(fontSize: 16)),
        backgroundColor: const Color(0xFF4CAF50),
        foregroundColor: Colors.white,
        bottom: TabBar(
          controller: _tabController,
          indicatorColor: Colors.white,
          labelColor: Colors.white,
          unselectedLabelColor: Colors.white70,
          tabs: const [
            Tab(icon: Icon(Icons.people), text: 'Students'),
            Tab(icon: Icon(Icons.quiz), text: 'Quizzes'),
          ],
        ),
      ),
      body: _buildBody(),
    );
  }

  Widget _buildBody() {
    if (_isLoading) {
      return const Center(
        child: CircularProgressIndicator(color: Color(0xFF4CAF50)),
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
              Text(_errorMessage!,
                  textAlign: TextAlign.center,
                  style: const TextStyle(color: Colors.red)),
              const SizedBox(height: 16),
              ElevatedButton(
                onPressed: _loadClassDetail,
                child: const Text('Retry'),
              ),
            ],
          ),
        ),
      );
    }

    final students = _classData!['students'] as List;
    final quizzes = _classData!['quizzes'] as List;
    final classCode = _classData!['class_code'] as String;

    return Column(
      children: [
        // ── Class code banner ──
        Container(
          width: double.infinity,
          padding:
              const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
          color: const Color(0xFF4CAF50).withOpacity(0.1),
          child: Row(
            children: [
              const Icon(Icons.key, color: Color(0xFF4CAF50), size: 20),
              const SizedBox(width: 8),
              Text(
                'Class Code: ',
                style: TextStyle(
                    color: Colors.grey.shade600, fontSize: 14),
              ),
              Text(
                classCode,
                style: const TextStyle(
                  color: Color(0xFF4CAF50),
                  fontWeight: FontWeight.bold,
                  fontSize: 18,
                  letterSpacing: 2,
                ),
              ),
              const Spacer(),
              IconButton(
                onPressed: _copyClassCode,
                icon: const Icon(Icons.copy,
                    color: Color(0xFF4CAF50), size: 20),
                tooltip: 'Copy code',
              ),
            ],
          ),
        ),

        // ── Tab views ──
        Expanded(
          child: TabBarView(
            controller: _tabController,
            children: [
              _buildStudentsTab(students),
              _buildQuizzesTab(quizzes),
            ],
          ),
        ),
      ],
    );
  }

  // ─────────────────────────────────────────────────────────────────────────────
  // Students tab
  // ─────────────────────────────────────────────────────────────────────────────

  Widget _buildStudentsTab(List students) {
    if (_studentPerformanceData == null &&
        !_isLoadingStudents &&
        _studentsError == null) {
      WidgetsBinding.instance
          .addPostFrameCallback((_) => _loadStudentPerformance());
    }

    if (_isLoadingStudents) {
      return const Center(
        child: CircularProgressIndicator(color: Color(0xFF4CAF50)),
      );
    }

    if (_studentsError != null) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(20),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              const Icon(Icons.error_outline, size: 60, color: Colors.red),
              const SizedBox(height: 16),
              Text(_studentsError!,
                  textAlign: TextAlign.center,
                  style: const TextStyle(color: Colors.red)),
              const SizedBox(height: 16),
              ElevatedButton(
                onPressed: _loadStudentPerformance,
                child: const Text('Retry'),
              ),
            ],
          ),
        ),
      );
    }

    if (_studentResults.isEmpty) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.people_outline,
                size: 70, color: Colors.grey.shade400),
            const SizedBox(height: 16),
            Text('No students yet.',
                style: TextStyle(
                    fontSize: 16, color: Colors.grey.shade600)),
            const SizedBox(height: 8),
            Text(
              'Share the class code with your students!',
              textAlign: TextAlign.center,
              style: TextStyle(color: Colors.grey.shade500),
            ),
            const SizedBox(height: 16),
            ElevatedButton.icon(
              onPressed: _copyClassCode,
              icon: const Icon(Icons.copy, color: Colors.white),
              label: Text(
                'Copy Code: ${_classData!['class_code']}',
                style: const TextStyle(color: Colors.white),
              ),
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFF4CAF50),
                shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12)),
              ),
            ),
          ],
        ),
      );
    }

    final summary = _studentPerformanceData?['summary'] ?? {};
    final totalStudents = summary['total_students'] ?? 0;
    final totalQuizzes = summary['total_quizzes'] ?? 0;
    final classAverage =
        (summary['class_average_percentage'] ?? 0).toDouble();

    return RefreshIndicator(
      onRefresh: _loadStudentPerformance,
      color: const Color(0xFF4CAF50),
      child: SingleChildScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        child: Column(
          children: [
            // ── Summary cards ──
            Padding(
              padding: const EdgeInsets.all(16),
              child: Row(
                children: [
                  Expanded(
                    child: _buildSummaryCard(
                      'Total Students',
                      totalStudents.toString(),
                      Icons.people,
                      const Color(0xFF6C63FF),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: _buildSummaryCard(
                      'Total Quizzes',
                      totalQuizzes.toString(),
                      Icons.quiz,
                      const Color(0xFF4CAF50),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: _buildSummaryCard(
                      'Class Average',
                      '${classAverage.toStringAsFixed(1)}%',
                      Icons.percent,
                      classAverage >= 75
                          ? const Color(0xFF4CAF50)
                          : classAverage >= 50
                              ? Colors.orange
                              : Colors.red,
                    ),
                  ),
                ],
              ),
            ),

            // ── Export button ──
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16),
              child: SizedBox(
                width: double.infinity,
                child: ElevatedButton.icon(
                  onPressed: _isExportingStudents
                      ? null
                      : _exportStudentPerformance,
                  icon: _isExportingStudents
                      ? const SizedBox(
                          width: 18,
                          height: 18,
                          child: CircularProgressIndicator(
                              strokeWidth: 2, color: Colors.white),
                        )
                      : const Icon(Icons.download, color: Colors.white),
                  label: Text(
                    _isExportingStudents ? 'Exporting...' : 'Export to Excel',
                    style: const TextStyle(color: Colors.white),
                  ),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFF4CAF50),
                    padding: const EdgeInsets.symmetric(vertical: 14),
                    shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12)),
                  ),
                ),
              ),
            ),

            const SizedBox(height: 16),

            // ── Performance table ──
            Container(
              margin: const EdgeInsets.symmetric(
                  horizontal: 16, vertical: 8),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(16),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withOpacity(0.05),
                    blurRadius: 10,
                    offset: const Offset(0, 4),
                  ),
                ],
              ),
              child: ClipRRect(
                borderRadius: BorderRadius.circular(16),
                child: SingleChildScrollView(
                  scrollDirection: Axis.horizontal,
                  child: SizedBox(
                    width: 720,
                    child: Column(
                      children: [
                        // Header row
                        Container(
                          color: const Color(0xFF4CAF50),
                          child: Row(
                            children: [
                              SizedBox(
                                width: 120,
                                child: _buildStudentSortableHeader(
                                    'Student ID', 'student_id'),
                              ),
                              SizedBox(
                                width: 120,
                                child: _buildStudentSortableHeader(
                                    'First Name', 'first_name'),
                              ),
                              SizedBox(
                                width: 120,
                                child: _buildStudentSortableHeader(
                                    'Last Name', 'surname'),
                              ),
                              SizedBox(
                                width: 120,
                                child: _buildStudentSortableHeader(
                                    'Quizzes Taken', 'quizzes_taken'),
                              ),
                              SizedBox(
                                width: 120,
                                child: _buildStudentSortableHeader(
                                    'Overall Grade', 'overall_percentage'),
                              ),
                            ],
                          ),
                        ),
                        // Data rows
                        ListView.separated(
                          shrinkWrap: true,
                          physics: const NeverScrollableScrollPhysics(),
                          itemCount: _studentResults.length,
                          separatorBuilder: (_, __) => Divider(
                              height: 1, color: Colors.grey.shade200),
                          itemBuilder: (context, index) {
                            final student = _studentResults[index];
                            final percentage =
                                (student['overall_percentage'] ?? 0)
                                    .toDouble();
                            final gradeColor =
                                _getGradeColor(percentage);
                            final quizzesTaken =
                                student['quizzes_taken'] ?? 0;
                            final totalQuizCount =
                                student['total_quizzes'] ?? 0;
                            final hasTakenAny =
                                student['has_taken_any'] == true;

                            return Container(
                              color: index % 2 == 0
                                  ? Colors.white
                                  : const Color(0xFFFAFAFA),
                              child: Row(
                                children: [
                                  SizedBox(
                                    width: 120,
                                    child: _buildStudentDataCell(
                                      student['student_id']
                                              ?.toString() ??
                                          '-',
                                      fontWeight: FontWeight.w500,
                                    ),
                                  ),
                                  SizedBox(
                                    width: 120,
                                    child: _buildStudentDataCell(
                                        student['first_name'] ?? '-'),
                                  ),
                                  SizedBox(
                                    width: 120,
                                    child: _buildStudentDataCell(
                                      student['surname'] ?? '-',
                                      fontWeight: FontWeight.w600,
                                    ),
                                  ),
                                  SizedBox(
                                    width: 120,
                                    child: _buildStudentDataCell(
                                      '$quizzesTaken / $totalQuizCount',
                                      fontWeight: FontWeight.w600,
                                      color: hasTakenAny
                                          ? const Color(0xFF333333)
                                          : Colors.grey,
                                    ),
                                  ),
                                  SizedBox(
                                    width: 120,
                                    child: Padding(
                                      padding: const EdgeInsets.symmetric(
                                          horizontal: 8, vertical: 8),
                                      child: Container(
                                        padding:
                                            const EdgeInsets.symmetric(
                                                horizontal: 8, vertical: 4),
                                        decoration: BoxDecoration(
                                          color: gradeColor
                                              .withOpacity(0.1),
                                          borderRadius:
                                              BorderRadius.circular(8),
                                        ),
                                        child: Text(
                                          '${percentage.toStringAsFixed(2)}%',
                                          textAlign: TextAlign.center,
                                          style: TextStyle(
                                            fontSize: 12,
                                            fontWeight: FontWeight.bold,
                                            color: gradeColor,
                                          ),
                                        ),
                                      ),
                                    ),
                                  ),
                                ],
                              ),
                            );
                          },
                        ),
                      ],
                    ),
                  ),
                ),
              ),
            ),
            const SizedBox(height: 24),
          ],
        ),
      ),
    );
  }

  // ─────────────────────────────────────────────────────────────────────────────
  // Quizzes tab
  // ─────────────────────────────────────────────────────────────────────────────

  Widget _buildQuizzesTab(List quizzes) {
    return Stack(
      children: [
        quizzes.isEmpty
            ? Center(
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Icon(Icons.quiz_outlined,
                        size: 70, color: Colors.grey.shade400),
                    const SizedBox(height: 16),
                    Text(
                      'No quizzes assigned yet.',
                      style: TextStyle(
                          fontSize: 16, color: Colors.grey.shade600),
                    ),
                    const SizedBox(height: 8),
                    Text(
                      'Tap the button below to add a quiz!',
                      textAlign: TextAlign.center,
                      style: TextStyle(color: Colors.grey.shade500),
                    ),
                  ],
                ),
              )
            : RefreshIndicator(
                onRefresh: _loadClassDetail,
                color: const Color(0xFF4CAF50),
                child: ListView.builder(
                  padding: const EdgeInsets.fromLTRB(16, 16, 16, 80),
                  itemCount: quizzes.length,
                  itemBuilder: (context, index) {
                    final quiz =
                        Map<String, dynamic>.from(quizzes[index]);
                    return _buildQuizCard(quiz);
                  },
                ),
              ),

        // FAB replacement — avoids nested Scaffold
        Positioned(
          right: 16,
          bottom: 16,
          child: FloatingActionButton.extended(
            onPressed: _showQuizOptions,
            backgroundColor: const Color(0xFF4CAF50),
            icon: const Icon(Icons.add, color: Colors.white),
            label: const Text('Add Quiz',
                style: TextStyle(color: Colors.white)),
          ),
        ),
      ],
    );
  }

  // ─────────────────────────────────────────────────────────────────────────────
  // Quiz card
  // ─────────────────────────────────────────────────────────────────────────────

  Widget _buildQuizCard(Map<String, dynamic> quiz) {
    final dueDate = quiz['pivot']?['due_date'];
    final parsedDue = dueDate != null ? DateTime.tryParse(dueDate) : null;
    final isOverdue = parsedDue != null && parsedDue.isBefore(DateTime.now());
    final dueDateColor = isOverdue ? Colors.red : Colors.orange.shade700;

    final gradingMode = quiz['pivot']?['grading_mode'] ?? 'automatic';
    final isManual = gradingMode == 'manual';
    final hasAttempts = (quiz['class_attempts_count'] ?? 0) > 0;

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      shape:
          RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      elevation: 2,
      child: InkWell(
        borderRadius: BorderRadius.circular(16),
        onTap: () async {
          await Navigator.pushNamed(
            context,
            '/quiz-detail',
            arguments: {
              'quiz_id': quiz['id'],
              'quiz_title': quiz['title'],
            },
          );
          _loadClassDetail();
        },
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Container(
                    width: 44,
                    height: 44,
                    decoration: BoxDecoration(
                      color: const Color(0xFF4CAF50).withOpacity(0.1),
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: const Icon(Icons.quiz, color: Color(0xFF4CAF50)),
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
                            color: Color(0xFF333333),
                          ),
                        ),
                        const SizedBox(height: 4),
                        // ── Status badges row ──
                        Wrap(
                          spacing: 6,
                          runSpacing: 4,
                          children: [
                            // Published / Draft badge
                            _buildBadge(
                              label: quiz['is_published'] == true
                                  ? 'Published'
                                  : 'Draft',
                              color: quiz['is_published'] == true
                                  ? Colors.green
                                  : Colors.grey,
                            ),
                            // Grading mode badge
                            _buildBadge(
                              label: isManual ? 'Manual' : 'Auto',
                              color: isManual
                                  ? const Color(0xFF6C63FF)
                                  : const Color(0xFF4CAF50),
                              icon: isManual
                                  ? Icons.rate_review_outlined
                                  : Icons.bolt,
                            ),
                          ],
                        ),
                        const SizedBox(height: 4),
                        Text(
                          '${quiz['questions_count'] ?? 0} questions',
                          style: TextStyle(
                              fontSize: 12, color: Colors.grey.shade600),
                        ),
                        const SizedBox(height: 4),
                        if (parsedDue != null)
                          Row(
                            children: [
                              Icon(Icons.access_time,
                                  size: 13, color: dueDateColor),
                              const SizedBox(width: 4),
                              Text(
                                'Due: ${parsedDue.toLocal().toString().substring(0, 16)}',
                                style: TextStyle(
                                  fontSize: 12,
                                  color: dueDateColor,
                                  fontWeight: FontWeight.w500,
                                ),
                              ),
                            ],
                          )
                        else
                          const Row(
                            children: [
                              Icon(Icons.info_outline,
                                  size: 13, color: Colors.grey),
                              SizedBox(width: 4),
                              Text(
                                'No deadline set',
                                style: TextStyle(
                                  fontSize: 12,
                                  color: Colors.grey,
                                  fontWeight: FontWeight.w500,
                                ),
                              ),
                            ],
                          ),
                      ],
                    ),
                  ),
                  const Icon(Icons.chevron_right, color: Colors.grey),
                ],
              ),
              const Divider(height: 20),
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  // ── Lock indicator when attempts exist ──
                  if (hasAttempts)
                    Tooltip(
                      message: isManual
                          ? 'Grading mode locked — attempts exist'
                          : 'Has attempts',
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Icon(
                            Icons.lock_outline,
                            size: 14,
                            color: Colors.orange.shade700,
                          ),
                          const SizedBox(width: 4),
                          Text(
                            isManual
                                ? 'Locked · Manual'
                                : 'Has attempts',
                            style: TextStyle(
                              fontSize: 12,
                              color: Colors.orange.shade700,
                              fontWeight: FontWeight.w500,
                            ),
                          ),
                        ],
                      ),
                    )
                  else
                    const SizedBox.shrink(),

                  // ── Actions menu ──
                  PopupMenuButton<String>(
                    icon: const Icon(Icons.more_vert, color: Colors.grey),
                    onSelected: (value) {
                      if (value == 'view_results') _viewClassQuizResults(quiz);
                      if (value == 'edit_due') _editDueDate(quiz);
                      if (value == 'edit_grading_mode') _editGradingMode(quiz);
                      if (value == 'review_submissions') _goToReviewSubmissions(quiz); 
                      if (value == 'remove') _unassignQuiz(quiz);
                    },
                    itemBuilder: (context) => [
                      const PopupMenuItem(
                        value: 'view_results',
                        child: Row(
                          children: [
                            Icon(Icons.bar_chart,
                                size: 18, color: Color(0xFF6C63FF)),
                            SizedBox(width: 8),
                            Text('View Results'),
                          ],
                        ),
                      ),
                      const PopupMenuItem(
                        value: 'edit_due',
                        child: Row(
                          children: [
                            Icon(Icons.edit_calendar,
                                size: 18, color: Color(0xFF4CAF50)),
                            SizedBox(width: 8),
                            Text('Edit Due Date'),
                          ],
                        ),
                      ),
                      const PopupMenuItem(
                        value: 'edit_grading_mode',
                        child: Row(
                          children: [
                            Icon(Icons.tune, size: 18, color: Color(0xFF6C63FF)),
                            SizedBox(width: 8),
                            Text('Edit Grading Mode'),
                          ],
                        ),
                      ),
                      if (isManual)
                        const PopupMenuItem(
                          value: 'review_submissions',
                          child: Row(
                            children: [
                              Icon(Icons.rate_review_outlined, size: 18, color: Color(0xFF6C63FF)),
                              SizedBox(width: 8),
                              Text('Review Submissions',
                                  style: TextStyle(color: Color(0xFF6C63FF))),
                            ],
                          ),
                        ),
                      if (!hasAttempts)
                        const PopupMenuItem(
                          value: 'remove',
                          child: Row(
                            children: [
                              Icon(Icons.remove_circle,
                                  size: 18, color: Colors.red),
                              SizedBox(width: 8),
                              Text('Remove',
                                  style: TextStyle(color: Colors.red)),
                            ],
                          ),
                        ),
                    ],
                  ),
                 



                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  // ─────────────────────────────────────────────────────────────────────────────
  // Reusable small widgets
  // ─────────────────────────────────────────────────────────────────────────────

  Widget _buildBadge({
    required String label,
    required Color color,
    IconData? icon,
  }) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
      decoration: BoxDecoration(
        color: color.withOpacity(0.1),
        borderRadius: BorderRadius.circular(8),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          if (icon != null) ...[
            Icon(icon, size: 11, color: color),
            const SizedBox(width: 3),
          ],
          Text(
            label,
            style: TextStyle(
              fontSize: 11,
              color: color,
              fontWeight: FontWeight.bold,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSummaryCard(
      String label, String value, IconData icon, Color color) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.05),
            blurRadius: 6,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        children: [
          Icon(icon, color: color, size: 22),
          const SizedBox(height: 6),
          Text(
            value,
            style: TextStyle(
                fontSize: 16, fontWeight: FontWeight.bold, color: color),
          ),
          const SizedBox(height: 4),
          Text(
            label,
            style:
                TextStyle(fontSize: 10, color: Colors.grey.shade600),
            textAlign: TextAlign.center,
          ),
        ],
      ),
    );
  }

  Widget _buildStudentSortableHeader(String label, String column) {
    final isSorted = _studentSortColumn == column;
    return InkWell(
      onTap: () => _onStudentSort(column),
      child: Container(
        padding:
            const EdgeInsets.symmetric(horizontal: 8, vertical: 12),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Flexible(
              child: Text(
                label,
                style: const TextStyle(
                  fontWeight: FontWeight.bold,
                  fontSize: 12,
                  color: Colors.white,
                ),
                overflow: TextOverflow.ellipsis,
              ),
            ),
            if (isSorted) ...[
              const SizedBox(width: 4),
              Icon(
                _studentSortAscending
                    ? Icons.arrow_upward
                    : Icons.arrow_downward,
                size: 12,
                color: Colors.white,
              ),
            ],
          ],
        ),
      ),
    );
  }

  Widget _buildStudentDataCell(String text,
      {Color? color, FontWeight? fontWeight}) {
    return Padding(
      padding:
          const EdgeInsets.symmetric(horizontal: 8, vertical: 12),
      child: Text(
        text,
        style: TextStyle(
          fontSize: 12,
          color: color ?? const Color(0xFF333333),
          fontWeight: fontWeight ?? FontWeight.normal,
        ),
        overflow: TextOverflow.ellipsis,
      ),
    );
  }
}

// ─────────────────────────────────────────────────────────────────────────────
// Grading Mode Selector Widget
// ─────────────────────────────────────────────────────────────────────────────

class _GradingModeSelector extends StatelessWidget {
  final String value;
  final ValueChanged<String> onChanged;

  const _GradingModeSelector({
    required this.value,
    required this.onChanged,
  });

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Expanded(
          child: _ModeOption(
            label: 'Automatic',
            description: 'Instant scoring',
            icon: Icons.bolt,
            color: const Color(0xFF4CAF50),
            selected: value == 'automatic',
            onTap: () => onChanged('automatic'),
          ),
        ),
        const SizedBox(width: 10),
        Expanded(
          child: _ModeOption(
            label: 'Manual',
            description: 'Teacher reviews',
            icon: Icons.rate_review_outlined,
            color: const Color(0xFF6C63FF),
            selected: value == 'manual',
            onTap: () => onChanged('manual'),
          ),
        ),
      ],
    );
  }
}

class _ModeOption extends StatelessWidget {
  final String label;
  final String description;
  final IconData icon;
  final Color color;
  final bool selected;
  final VoidCallback onTap;

  const _ModeOption({
    required this.label,
    required this.description,
    required this.icon,
    required this.color,
    required this.selected,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 150),
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
        decoration: BoxDecoration(
          color: selected ? color.withOpacity(0.1) : Colors.grey.shade100,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(
            color: selected ? color : Colors.grey.shade300,
            width: selected ? 2 : 1,
          ),
        ),
        child: Column(
          children: [
            Icon(icon, color: selected ? color : Colors.grey, size: 22),
            const SizedBox(height: 4),
            Text(
              label,
              style: TextStyle(
                fontSize: 13,
                fontWeight: FontWeight.bold,
                color: selected ? color : Colors.grey.shade600,
              ),
            ),
            Text(
              description,
              style: TextStyle(
                fontSize: 10,
                color: selected ? color.withOpacity(0.8) : Colors.grey.shade500,
              ),
              textAlign: TextAlign.center,
            ),
          ],
        ),
      ),
    );
  }
}