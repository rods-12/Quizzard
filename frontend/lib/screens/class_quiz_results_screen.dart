import 'package:flutter/material.dart';
import '../services/auth_service.dart';

/// Displays a table of all students in a class and their scores
/// for a specific quiz. Shows 0 for students who haven't taken it.
class ClassQuizResultsScreen extends StatefulWidget {
  final int classId;
  final String className;
  final int quizId;
  final String quizTitle;

  const ClassQuizResultsScreen({
    super.key,
    required this.classId,
    required this.className,
    required this.quizId,
    required this.quizTitle,
  });

  @override
  State<ClassQuizResultsScreen> createState() => _ClassQuizResultsScreenState();
}

class _ClassQuizResultsScreenState extends State<ClassQuizResultsScreen> {
  // ── Quizzard Brand Colors ──────────────────────────────────────
  static const Color primaryColor       = Color(0xFF5B2A9B); // Royal Purple
  static const Color primaryDark        = Color(0xFF3A1A6B); // Deep Violet
  static const Color primaryLight       = Color(0xFFEDE7F2); // Wizard Beard White
  static const Color accentGold         = Color(0xFFF2C94C); // Enchanted Gold
  static const Color softPurple         = Color(0xFFC9A8F0); // Light Lilac
  static const Color highlightPurple    = Color(0xFFA14BC9); // Mystic Magenta
  static const Color background         = Color(0xFFFAF6EC); // Parchment Cream
  static const Color midnightPlum       = Color(0xFF1F1235); // Primary Text
  static const Color mutedLavender      = Color(0xFFA99BC4); // Subtle Text
  static const Color plumShadow         = Color(0xFF2A1247); // Deep dark
  static const Color successColor       = Color(0xFF22C55E);
  static const Color warningColor       = Color(0xFFF59E0B);
  static const Color dangerColor        = Color(0xFFEF4444);
  // ──────────────────────────────────────────────────────────────

  bool _isLoading = true;
  String? _errorMessage;
  Map<String, dynamic>? _resultsData;
  List<dynamic> _studentResults = [];
  double _totalPoints = 0;
  bool _isExporting = false;

  // Sorting state
  String _sortColumn = 'surname';
  bool _sortAscending = true;

  @override
  void initState() {
    super.initState();
    _loadResults();
  }

  Future<void> _loadResults() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    final result = await AuthService.authGet(
      '/classes/${widget.classId}/quizzes/${widget.quizId}/results',
    );

    setState(() {
      _isLoading = false;
      if (result['success'] == true) {
        _resultsData = result['data'];
        _studentResults = List.from(_resultsData?['students'] ?? []);
        _totalPoints = (_resultsData?['total_points'] ?? 0).toDouble();
        _sortResults();
      } else {
        _errorMessage = result['message'] ?? 'Failed to load results';
      }
    });
  }

  void _sortResults() {
    _studentResults.sort((a, b) {
      dynamic valA;
      dynamic valB;

      switch (_sortColumn) {
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
        case 'score':
          valA = (a['score'] ?? 0).toDouble();
          valB = (b['score'] ?? 0).toDouble();
          break;
        case 'percentage':
          valA = _calculatePercentage(a);
          valB = _calculatePercentage(b);
          break;
        case 'status':
          valA = (a['has_taken'] == true) ? 1 : 0;
          valB = (b['has_taken'] == true) ? 1 : 0;
          break;
        default:
          valA = (a['surname'] ?? '').toString().toLowerCase();
          valB = (b['surname'] ?? '').toString().toLowerCase();
      }

      int comparison;
      if (valA is num && valB is num) {
        comparison = valA.compareTo(valB);
      } else {
        comparison = valA.toString().compareTo(valB.toString());
      }

      return _sortAscending ? comparison : -comparison;
    });
  }

  void _onSort(String column) {
    setState(() {
      if (_sortColumn == column) {
        _sortAscending = !_sortAscending;
      } else {
        _sortColumn = column;
        _sortAscending = true;
      }
      _sortResults();
    });
  }

  double _calculatePercentage(Map<String, dynamic> student) {
    if (_totalPoints <= 0) return 0.0;
    final score = (student['score'] ?? 0).toDouble();
    return (score / _totalPoints) * 100;
  }

  String _formatScore(Map<String, dynamic> student) {
    final score = (student['score'] ?? 0).toDouble();
    return '${score.toStringAsFixed(1)} / ${_totalPoints.toStringAsFixed(0)}';
  }

  String _formatPercentage(Map<String, dynamic> student) {
    final percentage = _calculatePercentage(student);
    return '${percentage.toStringAsFixed(1)}%';
  }

  String _getStatusText(Map<String, dynamic> student) {
    return student['has_taken'] == true ? 'Taken' : 'Not Taken';
  }

  Color _getStatusColor(Map<String, dynamic> student) {
    return student['has_taken'] == true ? successColor : mutedLavender;
  }

  Color _getPercentageColor(double percentage) {
    if (percentage >= 75) return successColor;
    if (percentage >= 50) return warningColor;
    return dangerColor;
  }

  // ── Widget Builders ──

  Widget _buildSortableHeader(String label, String column) {
    final isSorted = _sortColumn == column;
    return InkWell(
      onTap: () => _onSort(column),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 12),
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
                _sortAscending ? Icons.arrow_upward : Icons.arrow_downward,
                size: 12,
                color: Colors.white,
              ),
            ],
          ],
        ),
      ),
    );
  }

  Widget _buildDataCell(String text, {Color? color, FontWeight? fontWeight}) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 12),
      child: Text(
        text,
        style: TextStyle(
          fontSize: 12,
          color: color ?? midnightPlum,
          fontWeight: fontWeight ?? FontWeight.normal,
        ),
        overflow: TextOverflow.ellipsis,
      ),
    );
  }

  Widget _buildSummaryCard(String label, String value, IconData icon, Color color) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        boxShadow: [
          BoxShadow(
            color: plumShadow.withOpacity(0.10),
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
              fontSize: 16,
              fontWeight: FontWeight.bold,
              color: color,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            label,
            style: TextStyle(
              fontSize: 10,
              color: mutedLavender,
            ),
            textAlign: TextAlign.center,
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: background,
      appBar: AppBar(
        flexibleSpace: Container(
          decoration: const BoxDecoration(
            gradient: LinearGradient(
              colors: [primaryColor, primaryDark],
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
            ),
          ),
        ),
        backgroundColor: Colors.transparent,
        foregroundColor: Colors.white,
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              widget.quizTitle,
              style: const TextStyle(fontSize: 16),
              overflow: TextOverflow.ellipsis,
            ),
            const Text(
              'Class Results',
              style: TextStyle(fontSize: 12, fontWeight: FontWeight.normal),
            ),
          ],
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.download),
            tooltip: 'Export to Excel',
            onPressed: _isExporting ? null : _exportToExcel,
          ),
          IconButton(
            icon: const Icon(Icons.refresh),
            tooltip: 'Refresh',
            onPressed: _loadResults,
          ),
        ],
      ),
      body: Stack(
        children: [
          _buildBody(),
          if (_isExporting)
            Container(
              color: plumShadow.withOpacity(0.6),
              child: const Center(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    CircularProgressIndicator(
                      color: Colors.white,
                      strokeWidth: 3,
                    ),
                    SizedBox(height: 16),
                    Text(
                      'Generating Excel...',
                      style: TextStyle(
                        color: Colors.white,
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ],
                ),
              ),
            ),
        ],
      ),
    );
  }

  Future<void> _exportToExcel() async {
    setState(() => _isExporting = true);
    try {
      final filename = '${widget.className}_${widget.quizTitle}_results.xlsx'
          .replaceAll(' ', '_');

      final res = await AuthService.downloadFile(
        '/teacher/classes/${widget.classId}/quizzes/${widget.quizId}/export-results',
        filename,
      );

      if (!mounted) return;
      if (res['success']) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Excel file downloaded successfully!'),
            backgroundColor: successColor,
          ),
        );
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(res['message'] ?? 'Download failed'),
            backgroundColor: dangerColor,
          ),
        );
      }
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Error: $e'),
          backgroundColor: dangerColor,
        ),
      );
    } finally {
      if (mounted) setState(() => _isExporting = false);
    }
  }

  Widget _buildBody() {
    if (_isLoading) {
      return const Center(
        child: CircularProgressIndicator(color: primaryColor),
      );
    }

    if (_errorMessage != null) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(20),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              const Icon(Icons.error_outline, size: 60, color: dangerColor),
              const SizedBox(height: 16),
              Text(
                _errorMessage!,
                textAlign: TextAlign.center,
                style: const TextStyle(color: dangerColor),
              ),
              const SizedBox(height: 16),
              ElevatedButton(
                onPressed: _loadResults,
                style: ElevatedButton.styleFrom(
                  backgroundColor: accentGold,
                  foregroundColor: midnightPlum,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(10),
                  ),
                ),
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
            Icon(Icons.people_outline, size: 70, color: softPurple),
            const SizedBox(height: 16),
            Text(
              'No students in this class.',
              style: TextStyle(fontSize: 16, color: mutedLavender),
            ),
          ],
        ),
      );
    }

    final takenCount = _resultsData?['taken_count'] ?? 0;
    final notTakenCount = _resultsData?['not_taken_count'] ?? 0;
    final averageScore = (_resultsData?['average_score'] ?? 0).toDouble();
    final averagePercentage = (_resultsData?['average_percentage'] ?? 0).toDouble();

    return RefreshIndicator(
      onRefresh: _loadResults,
      color: primaryColor,
      child: SingleChildScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        child: Column(
          children: [
            // ── Summary Cards ──
            Container(
              padding: const EdgeInsets.all(16),
              child: Column(
                children: [
                  Row(
                    children: [
                      Expanded(
                        child: _buildSummaryCard(
                          'Total Students',
                          _studentResults.length.toString(),
                          Icons.people,
                          primaryColor,
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: _buildSummaryCard(
                          'Taken',
                          takenCount.toString(),
                          Icons.check_circle,
                          successColor,
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: _buildSummaryCard(
                          'Not Taken',
                          notTakenCount.toString(),
                          Icons.cancel,
                          mutedLavender,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  Row(
                    children: [
                      Expanded(
                        child: _buildSummaryCard(
                          'Average Score',
                          '${averageScore.toStringAsFixed(1)} / ${_totalPoints.toStringAsFixed(0)}',
                          Icons.calculate,
                          highlightPurple,
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: _buildSummaryCard(
                          'Average %',
                          '${averagePercentage.toStringAsFixed(1)}%',
                          Icons.percent,
                          averagePercentage >= 75
                              ? successColor
                              : averagePercentage >= 50
                                  ? warningColor
                                  : dangerColor,
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),

            // ── Results Table ──
            Container(
              margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(16),
                boxShadow: [
                  BoxShadow(
                    color: plumShadow.withOpacity(0.10),
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
                        // Table Header
                        Container(
                          decoration: const BoxDecoration(
                            gradient: LinearGradient(
                              colors: [primaryColor, primaryDark],
                              begin: Alignment.centerLeft,
                              end: Alignment.centerRight,
                            ),
                          ),
                          child: Row(
                            children: [
                              SizedBox(width: 120, child: _buildSortableHeader('Student ID', 'student_id')),
                              SizedBox(width: 120, child: _buildSortableHeader('First Name', 'first_name')),
                              SizedBox(width: 120, child: _buildSortableHeader('Surname', 'surname')),
                              SizedBox(width: 120, child: _buildSortableHeader('Score', 'score')),
                              SizedBox(width: 100, child: _buildSortableHeader('%', 'percentage')),
                              SizedBox(width: 120, child: _buildSortableHeader('Status', 'status')),
                            ],
                          ),
                        ),
                        // Table Body
                        ListView.separated(
                          shrinkWrap: true,
                          physics: const NeverScrollableScrollPhysics(),
                          itemCount: _studentResults.length,
                          separatorBuilder: (_, __) => Divider(
                            height: 1,
                            color: softPurple.withOpacity(0.25),
                          ),
                          itemBuilder: (context, index) {
                            final student = _studentResults[index];
                            final percentage = _calculatePercentage(student);
                            final percentageColor = _getPercentageColor(percentage);
                            final statusColor = _getStatusColor(student);

                            return Container(
                              color: index % 2 == 0
                                  ? Colors.white
                                  : primaryLight.withOpacity(0.35),
                              child: Row(
                                children: [
                                  SizedBox(
                                    width: 120,
                                    child: _buildDataCell(
                                      student['student_id']?.toString() ?? '-',
                                    ),
                                  ),
                                  SizedBox(
                                    width: 120,
                                    child: _buildDataCell(
                                      student['first_name'] ?? '-',
                                    ),
                                  ),
                                  SizedBox(
                                    width: 120,
                                    child: _buildDataCell(
                                      student['surname'] ?? '-',
                                      fontWeight: FontWeight.w600,
                                    ),
                                  ),
                                  SizedBox(
                                    width: 120,
                                    child: _buildDataCell(
                                      _formatScore(student),
                                      fontWeight: FontWeight.w600,
                                      color: student['has_taken'] == true
                                          ? midnightPlum
                                          : mutedLavender,
                                    ),
                                  ),
                                  SizedBox(
                                    width: 100,
                                    child: _buildDataCell(
                                      _formatPercentage(student),
                                      fontWeight: FontWeight.bold,
                                      color: percentageColor,
                                    ),
                                  ),
                                  SizedBox(
                                    width: 120,
                                    child: Padding(
                                      padding: const EdgeInsets.symmetric(
                                          horizontal: 8, vertical: 8),
                                      child: Container(
                                        padding: const EdgeInsets.symmetric(
                                            horizontal: 8, vertical: 4),
                                        decoration: BoxDecoration(
                                          color: statusColor.withOpacity(0.12),
                                          borderRadius: BorderRadius.circular(8),
                                        ),
                                        child: Text(
                                          _getStatusText(student),
                                          textAlign: TextAlign.center,
                                          style: TextStyle(
                                            fontSize: 11,
                                            fontWeight: FontWeight.bold,
                                            color: statusColor,
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
}