import 'dart:ui';
import 'package:flutter/material.dart';
import '../services/auth_service.dart';
import '../widgets/profile_widget.dart';
import 'teacher_class_list_tab.dart';

class TeacherDashboardScreen extends StatefulWidget {
  const TeacherDashboardScreen({super.key});

  @override
  State<TeacherDashboardScreen> createState() => _TeacherDashboardScreenState();
}

class _TeacherDashboardScreenState extends State<TeacherDashboardScreen> {
  // ─── Theme ────────────────────────────────────────────────
  static const Color _purple = Color(0xFF6C63FF);
  static const Color _green = Color(0xFF4CAF50);
  static const Color _bg = Color(0xFFF4F6FB);

  // ─── State ────────────────────────────────────────────────
  int _selectedIndex = 0;
  bool _isLoading = true;
  bool _isExporting = false;
  String _teacherName = '';
  String _teacherEmail = '';
  List<Map<String, dynamic>> _quizzes = [];
  Map<String, dynamic> _stats = {};
  final Set<int> _togglingIds = {};

  @override
  void initState() {
    super.initState();
    _loadDashboard();
  }

  // ─── API ──────────────────────────────────────────────────
  Future<void> _loadDashboard() async {
    setState(() => _isLoading = true);
    try {
      final response = await AuthService.authGet('/teacher/dashboard');
      if (response['success'] == true) {
        final data = response['data'];
        setState(() {
          _teacherName = data['teacher']['name'] ?? '';
          _teacherEmail = data['teacher']['email'] ?? '';
          _quizzes = List<Map<String, dynamic>>.from(data['quizzes'] ?? []);
          _stats = Map<String, dynamic>.from(data['stats'] ?? {});
        });
      } else {
        _showSnackbar(
          response['message'] ?? 'Failed to load dashboard',
          isError: true,
        );
      }
    } catch (e) {
      _showSnackbar('Network error: $e', isError: true);
    } finally {
      setState(() => _isLoading = false);
    }
  }

  Future<void> _togglePublish(Map<String, dynamic> quiz) async {
    final int quizId = quiz['id'];
    final bool isPublished =
        quiz['is_published'] == true || quiz['is_published'] == 1;
    final String quizTitle = quiz['title'] ?? 'this quiz';

    final confirmed = await _showPublishConfirmation(quizTitle, isPublished);
    if (!confirmed) return;

    setState(() => _togglingIds.add(quizId));
    try {
      final response = await AuthService.authPatch(
        '/quizzes/$quizId/publish-toggle',
        {},
      );
      if (response['success'] == true) {
        final updatedQuiz = response['data'];
        setState(() {
          final idx = _quizzes.indexWhere((q) => q['id'] == quizId);
          if (idx != -1) {
            _quizzes[idx] = {
              ..._quizzes[idx],
              'is_published': updatedQuiz['is_published'],
            };
          }
        });
        _showSnackbar(response['message'] ?? 'Status updated.');
      } else {
        _showSnackbar(
          response['message'] ?? 'Failed to toggle.',
          isError: true,
        );
      }
    } catch (e) {
      _showSnackbar('Network error: $e', isError: true);
    } finally {
      setState(() => _togglingIds.remove(quizId));
    }
  }

  Future<void> _deleteQuiz(Map<String, dynamic> quiz) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: const Text('Delete Quiz'),
        content: Text(
          'Are you sure you want to delete "${quiz['title']}"? This cannot be undone.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: const Text('Cancel'),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: Colors.red),
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text(
              'Delete',
              style: TextStyle(color: Colors.white),
            ),
          ),
        ],
      ),
    );

    if (confirmed != true) return;

    final result = await AuthService.authDelete('/quizzes/${quiz['id']}');
    if (result['success']) {
      setState(() => _quizzes.removeWhere((q) => q['id'] == quiz['id']));
      _showSnackbar('Quiz deleted.');
    } else {
      _showSnackbar(result['message'], isError: true);
    }
  }

  Future<void> _logout() async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: const Text('Log Out'),
        content: const Text('Are you sure you want to log out?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: const Text('Cancel'),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: Colors.red),
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('Log Out', style: TextStyle(color: Colors.white)),
          ),
        ],
      ),
    );

    if (confirm != true) return;
    await AuthService.authPost('/logout', {});
    await AuthService.logout();
    if (!mounted) return;
    Navigator.pushReplacementNamed(context, '/login');
  }

  // ─── Helpers ──────────────────────────────────────────────
  void _showSnackbar(String message, {bool isError = false}) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: isError ? Colors.red.shade700 : _green,
        behavior: SnackBarBehavior.floating,
      ),
    );
  }

  Future<bool> _showPublishConfirmation(
    String quizTitle,
    bool isCurrentlyPublished,
  ) async {
    final actionLabel = isCurrentlyPublished ? 'Unpublish' : 'Publish';
    final color = isCurrentlyPublished ? Colors.orange : _green;

    final result = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: Text('$actionLabel Quiz?'),
        content: Text(
          isCurrentlyPublished
              ? 'Are you sure you want to unpublish "$quizTitle"? Students will no longer be able to access it.'
              : 'Are you sure you want to publish "$quizTitle"? Students will be able to see and take it.',
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(ctx).pop(false),
            child: const Text('Cancel'),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: color),
            onPressed: () => Navigator.of(ctx).pop(true),
            child: Text(
              actionLabel,
              style: const TextStyle(color: Colors.white),
            ),
          ),
        ],
      ),
    );
    return result ?? false;
  }

  // ─── Build ────────────────────────────────────────────────
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: _bg,
      body: Stack(
        children: [
          _isLoading
              ? const Center(child: CircularProgressIndicator(color: _green))
              : IndexedStack(
                  index: _selectedIndex,
                  children: [
                    _buildQuizzesTab(),
                    TeacherClassListTab(onRefresh: _loadDashboard),
                    _buildProfileTab(),
                  ],
                ),

          // Export overlay
          if (_isExporting)
            Positioned.fill(
              child: BackdropFilter(
                filter: ImageFilter.blur(sigmaX: 4, sigmaY: 4),
                child: Container(
                  color: Colors.black.withOpacity(0.6),
                  child: Center(
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 28, vertical: 24),
                      decoration: BoxDecoration(
                        borderRadius: BorderRadius.circular(20),
                        gradient: const LinearGradient(
                          colors: [_purple, _green],
                          begin: Alignment.topLeft,
                          end: Alignment.bottomRight,
                        ),
                        boxShadow: [
                          BoxShadow(
                            color: Colors.black.withOpacity(0.3),
                            blurRadius: 20,
                            offset: const Offset(0, 10),
                          ),
                        ],
                      ),
                      child: Column(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          const SizedBox(
                            width: 50,
                            height: 50,
                            child: CircularProgressIndicator(
                              strokeWidth: 4,
                              valueColor: AlwaysStoppedAnimation<Color>(Colors.white),
                            ),
                          ),
                          const SizedBox(height: 18),
                          const Text(
                            'Generating Report',
                            style: TextStyle(
                              color: Colors.white,
                              fontSize: 18,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                          const SizedBox(height: 6),
                          const Text(
                            'Please wait while we prepare your Excel file...',
                            textAlign: TextAlign.center,
                            style: TextStyle(
                              color: Colors.white70,
                              fontSize: 13,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                ),
              ),
            ),
        ],
      ),
      bottomNavigationBar: _buildBottomNav(),
    );
  }

  Widget _buildBottomNav() {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.08),
            blurRadius: 20,
            offset: const Offset(0, -4),
          ),
        ],
      ),
      child: SafeArea(
        child: SizedBox(
          height: 64,
          child: Row(
            children: [
              _buildNavItem(0, Icons.quiz_rounded, Icons.quiz_outlined, 'Quizzes'),
              _buildNavItem(1, Icons.class_rounded, Icons.class_outlined, 'Classes'),
              _buildNavItem(2, Icons.person_rounded, Icons.person_outlined, 'Profile'),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildNavItem(int index, IconData activeIcon, IconData inactiveIcon, String label) {
    final isSelected = _selectedIndex == index;
    return Expanded(
      child: GestureDetector(
        onTap: () => setState(() => _selectedIndex = index),
        behavior: HitTestBehavior.opaque,
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            AnimatedContainer(
              duration: const Duration(milliseconds: 200),
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
              decoration: BoxDecoration(
                color: isSelected ? _green.withOpacity(0.1) : Colors.transparent,
                borderRadius: BorderRadius.circular(12),
              ),
              child: Icon(
                isSelected ? activeIcon : inactiveIcon,
                color: isSelected ? _green : Colors.grey,
                size: 22,
              ),
            ),
            const SizedBox(height: 2),
            Text(
              label,
              style: TextStyle(
                fontSize: 10,
                fontWeight: isSelected ? FontWeight.w700 : FontWeight.w400,
                color: isSelected ? _green : Colors.grey,
              ),
            ),
          ],
        ),
      ),
    );
  }

  // ─── Quizzes Tab ──────────────────────────────────────────
  Widget _buildQuizzesTab() {
    return RefreshIndicator(
      onRefresh: _loadDashboard,
      color: _green,
      child: CustomScrollView(
        slivers: [
          SliverToBoxAdapter(child: _buildStatsBar()),

          if (_quizzes.isEmpty)
            const SliverFillRemaining(
              child: Center(
                child: Text(
                  'No quizzes yet.\nTap "New Quiz" to get started!',
                  textAlign: TextAlign.center,
                  style: TextStyle(fontSize: 16, color: Colors.grey),
                ),
              ),
            )
          else
            SliverList(
              delegate: SliverChildBuilderDelegate(
                (context, index) => _buildQuizCard(_quizzes[index]),
                childCount: _quizzes.length,
              ),
            ),
        ],
      ),
    );
  }

  Widget _buildStatsBar() {
    final totalQuizzes = _stats['total_quizzes'] ?? _quizzes.length;
    final publishedQuizzes =
        _stats['published_quizzes'] ??
        _quizzes
            .where((q) => q['is_published'] == true || q['is_published'] == 1)
            .length;
    final totalStudents = _stats['total_students'] ?? 0;

    return Container(
      color: _green,
      padding: const EdgeInsets.fromLTRB(16, 50, 16, 20),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                'Welcome, $_teacherName 👋',
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 18,
                  fontWeight: FontWeight.bold,
                ),
              ),
              GestureDetector(
                onTap: _logout,
                child: Container(
                  padding: const EdgeInsets.all(8),
                  decoration: BoxDecoration(
                    color: Colors.white.withOpacity(0.2),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: const Icon(Icons.logout_rounded, color: Colors.white, size: 18),
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          Row(
            children: [
              _statChip(Icons.quiz_outlined, '$totalQuizzes', 'Total Quizzes'),
              const SizedBox(width: 10),
              _statChip(Icons.publish, '$publishedQuizzes', 'Published'),
              const SizedBox(width: 10),
              _statChip(Icons.people_outline, '$totalStudents', 'Students'),
            ],
          ),
        ],
      ),
    );
  }

  Widget _statChip(IconData icon, String value, String label) {
    return Expanded(
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 10, horizontal: 8),
        decoration: BoxDecoration(
          color: Colors.white.withOpacity(0.2),
          borderRadius: BorderRadius.circular(12),
        ),
        child: Column(
          children: [
            Icon(icon, color: Colors.white, size: 20),
            const SizedBox(height: 4),
            Text(
              value,
              style: const TextStyle(
                color: Colors.white,
                fontWeight: FontWeight.bold,
                fontSize: 16,
              ),
            ),
            Text(
              label,
              style: const TextStyle(color: Colors.white70, fontSize: 11),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildQuizCard(Map<String, dynamic> quiz) {
    final int quizId = quiz['id'];
    final bool isPublished =
        quiz['is_published'] == true || quiz['is_published'] == 1;
    final bool isToggling = _togglingIds.contains(quizId);
    final String title = quiz['title'] ?? 'Untitled Quiz';
    final String description = quiz['description'] ?? '';
    final int questions = quiz['questions_count'] ?? 0;

    return Card(
      margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      elevation: 3,
      child: InkWell(
        borderRadius: BorderRadius.circular(16),
        onTap: () async {
          final result = await Navigator.pushNamed(
            context,
            '/quiz-detail',
            arguments: {
              'quiz_id': quizId,
              'quiz_title': title,
            },
          );
          if (result == true) _loadDashboard();
        },
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Expanded(
                    child: Text(
                      title,
                      style: const TextStyle(
                        fontSize: 17,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ),
                  const SizedBox(width: 8),
                  _buildPublishedBadge(isPublished),
                ],
              ),
              if (description.isNotEmpty) ...[
                const SizedBox(height: 6),
                Text(
                  description,
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(color: Colors.grey, fontSize: 13),
                ),
              ],
              const SizedBox(height: 8),
              Text(
                '$questions question${questions != 1 ? 's' : ''}',
                style: const TextStyle(color: Colors.blueGrey, fontSize: 13),
              ),
              const Divider(height: 20),

              SingleChildScrollView(
                scrollDirection: Axis.horizontal,
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.end,
                  children: [
                    if (quiz['has_attempts'] != true) ...[
                      TextButton.icon(
                        onPressed: () async {
                          final result = await Navigator.pushNamed(
                            context,
                            '/edit-quiz',
                            arguments: {
                              'quiz_id': quizId,
                              'title': title,
                              'description': description,
                            },
                          );
                          if (result == true) _loadDashboard();
                        },
                        icon: const Icon(Icons.edit, size: 16, color: _purple),
                        label: const Text('Edit', style: TextStyle(color: _purple)),
                      ),
                      const SizedBox(width: 4),
                      TextButton.icon(
                        onPressed: () => _deleteQuiz(quiz),
                        icon: const Icon(Icons.delete, size: 16, color: Colors.red),
                        label: const Text('Delete', style: TextStyle(color: Colors.red)),
                      ),
                      const SizedBox(width: 6),
                    ],
                    TextButton.icon(
                      onPressed: () => Navigator.pushNamed(
                        context,
                        '/quiz-results',
                        arguments: {
                          'quiz_id': quizId,
                          'quiz_title': title,
                        },
                      ),
                      style: TextButton.styleFrom(
                        backgroundColor: _green.withOpacity(0.10),
                        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                      ),
                      icon: const Icon(Icons.bar_chart, size: 16, color: _green),
                      label: const Text(
                        'Results',
                        style: TextStyle(color: _green, fontWeight: FontWeight.w600),
                      ),
                    ),
                    const SizedBox(width: 6),
                    TextButton.icon(
                      onPressed: () => Navigator.pushNamed(
                        context,
                        '/quiz-analytics',
                        arguments: {
                          'quiz_id': quizId,
                          'quiz_title': title,
                        },
                      ),
                      style: TextButton.styleFrom(
                        backgroundColor: _purple.withOpacity(0.10),
                        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                      ),
                      icon: const Icon(Icons.analytics_outlined, size: 16, color: _purple),
                      label: const Text(
                        'Analytics',
                        style: TextStyle(color: _purple, fontWeight: FontWeight.w600),
                      ),
                    ),
                    const SizedBox(width: 6),
                    TextButton.icon(
                      onPressed: _isExporting
                          ? null
                          : () async {
                              setState(() => _isExporting = true);
                              try {
                                final res = await AuthService.downloadFile(
                                  '/teacher/quizzes/$quizId/export-full',
                                  'quiz_${quizId}_report.xlsx',
                                );
                                if (!mounted) return;
                                if (res['success']) {
                                  _showSnackbar('Report downloaded');
                                } else {
                                  _showSnackbar(res['message'] ?? 'Download failed', isError: true);
                                }
                              } catch (e) {
                                if (!mounted) return;
                                _showSnackbar('Error: $e', isError: true);
                              } finally {
                                if (mounted) setState(() => _isExporting = false);
                              }
                            },
                      style: TextButton.styleFrom(
                        backgroundColor: Colors.orange.withOpacity(0.10),
                        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                      ),
                      icon: const Icon(Icons.download, size: 16, color: Colors.orange),
                      label: const Text(
                        'Export',
                        style: TextStyle(color: Colors.orange, fontWeight: FontWeight.w600),
                      ),
                    ),
                    const SizedBox(width: 6),
                    isToggling
                        ? const SizedBox(
                            width: 24,
                            height: 24,
                            child: CircularProgressIndicator(strokeWidth: 2),
                          )
                        : OutlinedButton.icon(
                            onPressed: () => _togglePublish(quiz),
                            icon: Icon(
                              isPublished ? Icons.unpublished_outlined : Icons.publish,
                              size: 18,
                              color: isPublished ? Colors.orange : _green,
                            ),
                            label: Text(
                              isPublished ? 'Unpublish' : 'Publish',
                              style: TextStyle(color: isPublished ? Colors.orange : _green),
                            ),
                            style: OutlinedButton.styleFrom(
                              side: BorderSide(color: isPublished ? Colors.orange : _green),
                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                            ),
                          ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildPublishedBadge(bool isPublished) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        color: isPublished ? _green.withOpacity(0.15) : Colors.grey.withOpacity(0.15),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(
          color: isPublished ? _green : Colors.grey,
          width: 1,
        ),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(
            isPublished ? Icons.visibility : Icons.visibility_off,
            size: 13,
            color: isPublished ? _green : Colors.grey,
          ),
          const SizedBox(width: 4),
          Text(
            isPublished ? 'Published' : 'Draft',
            style: TextStyle(
              fontSize: 12,
              fontWeight: FontWeight.w600,
              color: isPublished ? _green : Colors.grey,
            ),
          ),
        ],
      ),
    );
  }

  // ─── Profile Tab ──────────────────────────────────────────
  Widget _buildProfileTab() {
    return ProfileWidget(onLogout: _logout);
  }
}
