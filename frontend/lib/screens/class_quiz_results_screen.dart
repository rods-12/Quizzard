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
    return student['has_taken'] == true
        ? const Color(0xFF4CAF50)
        : Colors.grey;
  }

  Color _getPercentageColor(double percentage) {
    if (percentage >= 75) return const Color(0xFF4CAF50);
    if (percentage >= 50) return Colors.orange;
    return Colors.red;
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
          color: color ?? const Color(0xFF333333),
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
              color: Colors.grey.shade600,
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
      backgroundColor: const Color(0xFFF5F5F5),
      appBar: AppBar(
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
        backgroundColor: const Color(0xFF4CAF50),
        foregroundColor: Colors.white,
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
              color: Colors.black.withOpacity(0.5),
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
            backgroundColor: Color(0xFF4CAF50),
          ),
        );
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(res['message'] ?? 'Download failed'),
            backgroundColor: Colors.red,
          ),
        );
      }
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Error: $e'),
          backgroundColor: Colors.red,
        ),
      );
    } finally {
      if (mounted) setState(() => _isExporting = false);
    }
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

    if (_studentResults.isEmpty) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.people_outline, size: 70, color: Colors.grey.shade400),
            const SizedBox(height: 16),
            Text(
              'No students in this class.',
              style: TextStyle(fontSize: 16, color: Colors.grey.shade600),
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
      color: const Color(0xFF4CAF50),
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
                          const Color(0xFF6C63FF),
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: _buildSummaryCard(
                          'Taken',
                          takenCount.toString(),
                          Icons.check_circle,
                          const Color(0xFF4CAF50),
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: _buildSummaryCard(
                          'Not Taken',
                          notTakenCount.toString(),
                          Icons.cancel,
                          Colors.grey,
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
                          Colors.orange,
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: _buildSummaryCard(
                          'Average %',
                          '${averagePercentage.toStringAsFixed(1)}%',
                          Icons.percent,
                          averagePercentage >= 75
                              ? const Color(0xFF4CAF50)
                              : averagePercentage >= 50
                                  ? Colors.orange
                                  : Colors.red,
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
                        // Table Header
                        Container(
                          color: const Color(0xFF4CAF50),
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
                            color: Colors.grey.shade200,
                          ),
                          itemBuilder: (context, index) {
                            final student = _studentResults[index];
                            final percentage = _calculatePercentage(student);
                            final percentageColor = _getPercentageColor(percentage);
                            final statusColor = _getStatusColor(student);

                            return Container(
                              color: index % 2 == 0
                                  ? Colors.white
                                  : const Color(0xFFFAFAFA),
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
                                          ? const Color(0xFF333333)
                                          : Colors.grey,
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
                                          color: statusColor.withOpacity(0.1),
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