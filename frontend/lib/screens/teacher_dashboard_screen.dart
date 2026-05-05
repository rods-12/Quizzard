import 'dart:ui';
import 'package:flutter/material.dart';
import '../services/auth_service.dart';
import '../widgets/profile_widget.dart';
import 'teacher_class_list_tab.dart';

// ─── THEME ────────────────────────────────────────────────────────────────────
class _T {
  static const Color primary = Color(0xFF2ECC71);
  static const Color primaryDark = Color(0xFF1BA35A);
  static const Color primaryLight = Color(0xFFE8F8F0);
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

  static LinearGradient get headerGradient => const LinearGradient(
        colors: [Color(0xFF2ECC71), Color(0xFF1BA35A)],
        begin: Alignment.topLeft,
        end: Alignment.bottomRight,
      );
}

// ─── TEACHER DASHBOARD ────────────────────────────────────────────────────────
class TeacherDashboardScreen extends StatefulWidget {
  const TeacherDashboardScreen({super.key});

  @override
  State<TeacherDashboardScreen> createState() => _TeacherDashboardScreenState();
}

class _TeacherDashboardScreenState extends State<TeacherDashboardScreen> {
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

  // ─── API ──────────────────────────────────────────────────────────────────

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
        _showSnackbar(response['message'] ?? 'Failed to load dashboard', isError: true);
      }
    } catch (e) {
      _showSnackbar('Network error: $e', isError: true);
    } finally {
      setState(() => _isLoading = false);
    }
  }

  Future<void> _togglePublish(Map<String, dynamic> quiz) async {
    final int quizId = quiz['id'];
    final bool isPublished = quiz['is_published'] == true || quiz['is_published'] == 1;
    final String quizTitle = quiz['title'] ?? 'this quiz';

    final confirmed = await _showPublishConfirmation(quizTitle, isPublished);
    if (!confirmed) return;

    setState(() => _togglingIds.add(quizId));
    try {
      final response = await AuthService.authPatch('/quizzes/$quizId/publish-toggle', {});
      if (response['success'] == true) {
        final updatedQuiz = response['data'];
        setState(() {
          final idx = _quizzes.indexWhere((q) => q['id'] == quizId);
          if (idx != -1) {
            _quizzes[idx] = {..._quizzes[idx], 'is_published': updatedQuiz['is_published']};
          }
        });
        _showSnackbar(response['message'] ?? 'Status updated.');
      } else {
        _showSnackbar(response['message'] ?? 'Failed to toggle.', isError: true);
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
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: const Text('Delete Quiz', style: TextStyle(fontWeight: FontWeight.bold)),
        content: Text('Are you sure you want to delete "${quiz['title']}"? This cannot be undone.'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Cancel')),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: _T.danger, shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10))),
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('Delete', style: TextStyle(color: Colors.white)),
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
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: const Text('Log Out', style: TextStyle(fontWeight: FontWeight.bold)),
        content: const Text('Are you sure you want to log out?'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: Text('Cancel', style: TextStyle(color: _T.textMid))),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: _T.danger, shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10))),
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

  // ─── Helpers ──────────────────────────────────────────────────────────────

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

  Future<bool> _showPublishConfirmation(String quizTitle, bool isCurrentlyPublished) async {
    final actionLabel = isCurrentlyPublished ? 'Unpublish' : 'Publish';
    final color = isCurrentlyPublished ? _T.orange : _T.success;
    final result = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: Text('$actionLabel Quiz?', style: const TextStyle(fontWeight: FontWeight.bold)),
        content: Text(
          isCurrentlyPublished
              ? 'Students will no longer be able to access "$quizTitle".'
              : 'Students will be able to see and take "$quizTitle".',
        ),
        actions: [
          TextButton(onPressed: () => Navigator.of(ctx).pop(false), child: const Text('Cancel')),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: color, shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10))),
            onPressed: () => Navigator.of(ctx).pop(true),
            child: Text(actionLabel, style: const TextStyle(color: Colors.white)),
          ),
        ],
      ),
    );
    return result ?? false;
  }

  // ─── BUILD ────────────────────────────────────────────────────────────────

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: _T.bg,
      body: Stack(
        children: [
          _isLoading
              ? _buildSkeletonLoading()
              : IndexedStack(
                  index: _selectedIndex,
                  children: [
                    _buildQuizzesTab(),
                    TeacherClassListTab(onRefresh: _loadDashboard),
                    ProfileWidget(onLogout: _logout),
                  ],
                ),
          if (_isExporting) _buildBlurOverlay('Generating Report...'),
        ],
      ),
      floatingActionButton: _selectedIndex == 0 && !_isLoading
          ? FloatingActionButton.extended(
              onPressed: () async {
                final result = await Navigator.pushNamed(context, '/create-quiz');
                if (result == true) _loadDashboard();
              },
              backgroundColor: _T.accent,
              elevation: 4,
              icon: const Icon(Icons.add_rounded, color: Colors.white),
              label: const Text('New Quiz', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
            )
          : null,
      bottomNavigationBar: _buildBottomNav(),
    );
  }

  Widget _buildBlurOverlay(String message) {
    return Positioned.fill(
      child: BackdropFilter(
        filter: ImageFilter.blur(sigmaX: 4, sigmaY: 4),
        child: Container(
          color: Colors.black.withOpacity(0.25),
          child: Center(
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 28, vertical: 22),
              decoration: BoxDecoration(
                color: Colors.white.withOpacity(0.92),
                borderRadius: BorderRadius.circular(20),
                boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.12), blurRadius: 24, offset: const Offset(0, 8))],
              ),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  CircularProgressIndicator(color: _T.primary, strokeWidth: 3),
                  const SizedBox(height: 16),
                  Text(message, style: const TextStyle(color: _T.textDark, fontWeight: FontWeight.w600, fontSize: 14)),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildBottomNav() {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.08), blurRadius: 20, offset: const Offset(0, -4))],
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
              padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 6),
              decoration: BoxDecoration(
                color: isSelected ? _T.primaryLight : Colors.transparent,
                borderRadius: BorderRadius.circular(12),
              ),
              child: Icon(
                isSelected ? activeIcon : inactiveIcon,
                color: isSelected ? _T.primary : _T.textLight,
                size: 22,
              ),
            ),
            const SizedBox(height: 2),
            Text(
              label,
              style: TextStyle(
                fontSize: 10,
                fontWeight: isSelected ? FontWeight.w700 : FontWeight.w400,
                color: isSelected ? _T.primary : _T.textLight,
              ),
            ),
          ],
        ),
      ),
    );
  }

  // ─── SKELETON LOADING ─────────────────────────────────────────────────────

  Widget _buildSkeletonLoading() {
    return Column(
      children: [
        Container(
          width: double.infinity,
          padding: const EdgeInsets.fromLTRB(24, 56, 24, 32),
          decoration: BoxDecoration(
            gradient: LinearGradient(
              colors: [_T.primary.withOpacity(0.85), _T.primaryDark.withOpacity(0.85)],
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
            ),
            borderRadius: const BorderRadius.only(
              bottomLeft: Radius.circular(32),
              bottomRight: Radius.circular(32),
            ),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(width: 120, height: 14, decoration: BoxDecoration(color: Colors.white.withOpacity(0.35), borderRadius: BorderRadius.circular(8))),
              const SizedBox(height: 8),
              Container(width: 200, height: 24, decoration: BoxDecoration(color: Colors.white.withOpacity(0.45), borderRadius: BorderRadius.circular(8))),
              const SizedBox(height: 28),
              Row(
                children: List.generate(3, (_) => Expanded(
                  child: Container(
                    margin: const EdgeInsets.symmetric(horizontal: 4),
                    height: 72,
                    decoration: BoxDecoration(color: Colors.white.withOpacity(0.2), borderRadius: BorderRadius.circular(14)),
                  ),
                )),
              ),
            ],
          ),
        ),
        const Spacer(),
        CircularProgressIndicator(color: _T.primary, strokeWidth: 3),
        const SizedBox(height: 16),
        const Text('Loading dashboard...', style: TextStyle(color: _T.textMid, fontSize: 14)),
        const Spacer(),
      ],
    );
  }

  // ─── QUIZZES TAB ──────────────────────────────────────────────────────────

  Widget _buildQuizzesTab() {
    return _QuizzesTab(
      quizzes: _quizzes,
      stats: _stats,
      teacherName: _teacherName,
      togglingIds: _togglingIds,
      isExporting: _isExporting,
      onRefresh: _loadDashboard,
      onTogglePublish: _togglePublish,
      onDeleteQuiz: _deleteQuiz,
      onLogout: _logout,
      onExportStart: () => setState(() => _isExporting = true),
      onExportEnd: () => setState(() => _isExporting = false),
    );
  }
}

// ─── QUIZZES TAB WIDGET ───────────────────────────────────────────────────────
class _QuizzesTab extends StatefulWidget {
  final List<Map<String, dynamic>> quizzes;
  final Map<String, dynamic> stats;
  final String teacherName;
  final Set<int> togglingIds;
  final bool isExporting;
  final VoidCallback onRefresh;
  final Future<void> Function(Map<String, dynamic>) onTogglePublish;
  final Future<void> Function(Map<String, dynamic>) onDeleteQuiz;
  final VoidCallback onLogout;
  final VoidCallback onExportStart;
  final VoidCallback onExportEnd;

  const _QuizzesTab({
    required this.quizzes,
    required this.stats,
    required this.teacherName,
    required this.togglingIds,
    required this.isExporting,
    required this.onRefresh,
    required this.onTogglePublish,
    required this.onDeleteQuiz,
    required this.onLogout,
    required this.onExportStart,
    required this.onExportEnd,
  });

  @override
  State<_QuizzesTab> createState() => _QuizzesTabState();
}

class _QuizzesTabState extends State<_QuizzesTab> {
  final TextEditingController _searchController = TextEditingController();
  String _filter = 'all';
  List<Map<String, dynamic>> _filtered = [];

  @override
  void initState() {
    super.initState();
    _filtered = widget.quizzes;
    _searchController.addListener(_applyFilter);
  }

  @override
  void didUpdateWidget(_QuizzesTab oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.quizzes != widget.quizzes) _applyFilter();
  }

  @override
  void dispose() {
    _searchController.removeListener(_applyFilter);
    _searchController.dispose();
    super.dispose();
  }

  void _applyFilter() {
    final query = _searchController.text.trim().toLowerCase();
    setState(() {
      _filtered = widget.quizzes.where((q) {
        final title = (q['title'] ?? '').toString().toLowerCase();
        final desc = (q['description'] ?? '').toString().toLowerCase();
        final matchesQuery = query.isEmpty || title.contains(query) || desc.contains(query);
        final isPublished = q['is_published'] == true || q['is_published'] == 1;
        final matchesFilter = _filter == 'all' ? true : _filter == 'published' ? isPublished : !isPublished;
        return matchesQuery && matchesFilter;
      }).toList();
    });
  }

  void _setFilter(String val) {
    setState(() => _filter = val);
    _applyFilter();
  }

  @override
  Widget build(BuildContext context) {
    final totalQuizzes = widget.stats['total_quizzes'] ?? widget.quizzes.length;
    final publishedCount = widget.stats['published_quizzes'] ??
        widget.quizzes.where((q) => q['is_published'] == true || q['is_published'] == 1).length;
    final totalStudents = widget.stats['total_students'] ?? 0;
    final draftCount = (totalQuizzes as int) - (publishedCount as int);

    final hour = DateTime.now().hour;
    final greeting = hour < 12 ? 'Good morning' : hour < 17 ? 'Good afternoon' : 'Good evening';
    final firstName = widget.teacherName.split(' ').first;

    return RefreshIndicator(
      onRefresh: () async => widget.onRefresh(),
      color: _T.primary,
      child: CustomScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        slivers: [
          // ── Header ──
          SliverToBoxAdapter(
            child: Container(
              padding: const EdgeInsets.fromLTRB(24, 56, 24, 24),
              decoration: BoxDecoration(
                gradient: _T.headerGradient,
                borderRadius: const BorderRadius.only(
                  bottomLeft: Radius.circular(32),
                  bottomRight: Radius.circular(32),
                ),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text('$greeting,', style: const TextStyle(color: Colors.white70, fontSize: 14)),
                            const SizedBox(height: 2),
                            Text(
                              firstName,
                              style: const TextStyle(color: Colors.white, fontSize: 26, fontWeight: FontWeight.bold, letterSpacing: -0.5),
                            ),
                          ],
                        ),
                      ),
                      GestureDetector(
                        onTap: widget.onLogout,
                        child: Container(
                          padding: const EdgeInsets.all(10),
                          decoration: BoxDecoration(color: Colors.white.withOpacity(0.15), borderRadius: BorderRadius.circular(12)),
                          child: const Icon(Icons.logout_rounded, color: Colors.white, size: 20),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 20),
                  // Stat cards
                  Row(
                    children: [
                      _buildStatCard(Icons.quiz_rounded, '$totalQuizzes', 'Total'),
                      const SizedBox(width: 10),
                      _buildStatCard(Icons.visibility_rounded, '$publishedCount', 'Published'),
                      const SizedBox(width: 10),
                      _buildStatCard(Icons.people_rounded, '$totalStudents', 'Students'),
                    ],
                  ),
                  const SizedBox(height: 20),
                  // Search
                  TextField(
                    controller: _searchController,
                    style: const TextStyle(fontSize: 14, color: _T.textDark),
                    decoration: InputDecoration(
                      hintText: 'Search quizzes...',
                      hintStyle: TextStyle(color: Colors.grey.shade400, fontSize: 14),
                      prefixIcon: const Icon(Icons.search_rounded, color: _T.primary, size: 20),
                      suffixIcon: _searchController.text.isNotEmpty
                          ? IconButton(
                              icon: const Icon(Icons.clear_rounded, size: 18),
                              color: _T.textMid,
                              onPressed: () => _searchController.clear(),
                            )
                          : null,
                      filled: true,
                      fillColor: Colors.white,
                      contentPadding: const EdgeInsets.symmetric(vertical: 12),
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(14), borderSide: BorderSide.none),
                    ),
                  ),
                ],
              ),
            ),
          ),

          // ── Filter chips ──
          SliverToBoxAdapter(
            child: Padding(
              padding: const EdgeInsets.fromLTRB(20, 16, 20, 4),
              child: Row(
                children: [
                  _buildFilterChip('all', 'All ($totalQuizzes)'),
                  const SizedBox(width: 8),
                  _buildFilterChip('published', 'Published ($publishedCount)'),
                  const SizedBox(width: 8),
                  _buildFilterChip('draft', 'Draft ($draftCount)'),
                ],
              ),
            ),
          ),

          // ── Count ──
          SliverToBoxAdapter(
            child: Padding(
              padding: const EdgeInsets.fromLTRB(20, 10, 20, 4),
              child: Text(
                '${_filtered.length} quiz${_filtered.length == 1 ? '' : 'zes'}',
                style: const TextStyle(color: _T.textMid, fontSize: 13),
              ),
            ),
          ),

          // ── List or empty ──
          _filtered.isEmpty
              ? SliverFillRemaining(
                  hasScrollBody: false,
                  child: Center(
                    child: Padding(
                      padding: const EdgeInsets.all(40),
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Container(
                            padding: const EdgeInsets.all(24),
                            decoration: BoxDecoration(color: _T.primaryLight, shape: BoxShape.circle),
                            child: Icon(Icons.quiz_outlined, size: 48, color: _T.primary.withOpacity(0.5)),
                          ),
                          const SizedBox(height: 16),
                          Text(
                            widget.quizzes.isEmpty ? 'No quizzes yet.' : 'No quizzes found.',
                            style: const TextStyle(color: _T.textMid, fontSize: 16, fontWeight: FontWeight.w500),
                          ),
                          const SizedBox(height: 4),
                          Text(
                            widget.quizzes.isEmpty ? 'Tap "New Quiz" to get started!' : 'Try adjusting your search or filter.',
                            textAlign: TextAlign.center,
                            style: const TextStyle(color: _T.textLight, fontSize: 13),
                          ),
                        ],
                      ),
                    ),
                  ),
                )
              : SliverPadding(
                  padding: const EdgeInsets.fromLTRB(20, 4, 20, 100),
                  sliver: SliverList(
                    delegate: SliverChildBuilderDelegate(
                      (context, index) => _buildQuizCard(_filtered[index]),
                      childCount: _filtered.length,
                    ),
                  ),
                ),
        ],
      ),
    );
  }

  Widget _buildStatCard(IconData icon, String value, String label) {
    return Expanded(
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 10),
        decoration: BoxDecoration(
          color: Colors.white.withOpacity(0.18),
          borderRadius: BorderRadius.circular(14),
          border: Border.all(color: Colors.white.withOpacity(0.25)),
        ),
        child: Column(
          children: [
            Icon(icon, color: Colors.white, size: 20),
            const SizedBox(height: 6),
            Text(value, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 18)),
            Text(label, style: const TextStyle(color: Colors.white70, fontSize: 10)),
          ],
        ),
      ),
    );
  }

  Widget _buildFilterChip(String value, String label) {
    final isSelected = _filter == value;
    return GestureDetector(
      onTap: () => _setFilter(value),
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 180),
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 7),
        decoration: BoxDecoration(
          color: isSelected ? _T.primary : Colors.white,
          borderRadius: BorderRadius.circular(20),
          border: Border.all(color: isSelected ? _T.primary : Colors.grey.shade300),
          boxShadow: isSelected ? [BoxShadow(color: _T.primary.withOpacity(0.25), blurRadius: 8, offset: const Offset(0, 2))] : [],
        ),
        child: Text(
          label,
          style: TextStyle(
            color: isSelected ? Colors.white : _T.textMid,
            fontWeight: isSelected ? FontWeight.bold : FontWeight.normal,
            fontSize: 12,
          ),
        ),
      ),
    );
  }

  Widget _buildQuizCard(Map<String, dynamic> quiz) {
    final int quizId = quiz['id'];
    final bool isPublished = quiz['is_published'] == true || quiz['is_published'] == 1;
    final bool isToggling = widget.togglingIds.contains(quizId);
    final String title = quiz['title'] ?? 'Untitled Quiz';
    final String description = quiz['description'] ?? '';
    final int questions = quiz['questions_count'] ?? 0;
    final bool hasAttempts = quiz['has_attempts'] == true;

    return Container(
      margin: const EdgeInsets.only(bottom: 14),
      decoration: _T.card,
      child: InkWell(
        borderRadius: BorderRadius.circular(16),
        onTap: () async {
          final result = await Navigator.pushNamed(
            context,
            '/quiz-detail',
            arguments: {'quiz_id': quizId, 'quiz_title': title},
          );
          if (result == true) widget.onRefresh();
        },
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // ── Color band top ──
            Container(
              padding: const EdgeInsets.fromLTRB(16, 14, 16, 14),
              decoration: BoxDecoration(
                color: isPublished ? _T.primaryLight : Colors.grey.shade50,
                borderRadius: const BorderRadius.only(topLeft: Radius.circular(16), topRight: Radius.circular(16)),
              ),
              child: Row(
                children: [
                  Container(
                    width: 46,
                    height: 46,
                    decoration: BoxDecoration(
                      color: isPublished ? _T.primary : Colors.grey.shade300,
                      borderRadius: BorderRadius.circular(13),
                    ),
                    child: const Icon(Icons.quiz_rounded, color: Colors.white, size: 24),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(title, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15, color: _T.textDark)),
                        if (description.isNotEmpty) ...[
                          const SizedBox(height: 2),
                          Text(description, maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(fontSize: 12, color: _T.textMid)),
                        ],
                      ],
                    ),
                  ),
                  const SizedBox(width: 8),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                    decoration: BoxDecoration(
                      color: isPublished ? _T.primary.withOpacity(0.12) : Colors.grey.withOpacity(0.12),
                      borderRadius: BorderRadius.circular(20),
                      border: Border.all(color: isPublished ? _T.primary.withOpacity(0.3) : Colors.grey.withOpacity(0.3)),
                    ),
                    child: Text(
                      isPublished ? 'Published' : 'Draft',
                      style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: isPublished ? _T.primary : _T.textMid),
                    ),
                  ),
                ],
              ),
            ),

            // ── Body ──
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 12, 16, 14),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      _buildMeta(Icons.help_outline_rounded, '$questions question${questions != 1 ? 's' : ''}'),
                      if (hasAttempts) ...[
                        const SizedBox(width: 14),
                        _buildMeta(Icons.lock_outline_rounded, 'Has attempts', color: _T.orange),
                      ],
                    ],
                  ),
                  const SizedBox(height: 12),
                  Divider(color: Colors.grey.shade100, height: 1),
                  const SizedBox(height: 12),

                  // ── Action buttons ──
                  // ── Action buttons ──
SingleChildScrollView(
  scrollDirection: Axis.horizontal,
  child: Row(
    children: [
      _buildActionBtn(
        icon: Icons.bar_chart_rounded,
        label: 'Results',
        color: _T.primary,
        onTap: () => Navigator.pushNamed(context, '/quiz-results', arguments: {'quiz_id': quizId, 'quiz_title': title}),
      ),
      const SizedBox(width: 8),
      _buildActionBtn(
        icon: Icons.analytics_outlined,
        label: 'Analytics',
        color: _T.accent,
        onTap: () => Navigator.pushNamed(context, '/quiz-analytics', arguments: {'quiz_id': quizId, 'quiz_title': title}),
      ),
      const SizedBox(width: 8),
      if (!hasAttempts) ...[
        _buildIconBtn(
          icon: Icons.edit_rounded,
          color: _T.accent,
          tooltip: 'Edit',
          onTap: () async {
            final result = await Navigator.pushNamed(
              context,
              '/edit-quiz',
              arguments: {'quiz_id': quizId, 'title': title, 'description': description},
            );
            if (result == true) widget.onRefresh();
          },
        ),
        const SizedBox(width: 6),
        _buildIconBtn(
          icon: Icons.delete_rounded,
          color: _T.danger,
          tooltip: 'Delete',
          onTap: () => widget.onDeleteQuiz(quiz),
        ),
        const SizedBox(width: 6),
      ],
      _buildIconBtn(
        icon: Icons.download_rounded,
        color: _T.orange,
        tooltip: 'Export',
        onTap: () async {
          widget.onExportStart();
          try {
            final res = await AuthService.downloadFile(
              '/teacher/quizzes/$quizId/export-full',
              'quiz_${quizId}_report.xlsx',
            );
            if (!mounted) return;
            ScaffoldMessenger.of(context).showSnackBar(
              SnackBar(
                content: Text(res['success'] ? 'Report downloaded' : (res['message'] ?? 'Download failed')),
                backgroundColor: res['success'] ? _T.success : _T.danger,
                behavior: SnackBarBehavior.floating,
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                margin: const EdgeInsets.all(16),
              ),
            );
          } catch (e) {
            if (mounted) {
              ScaffoldMessenger.of(context).showSnackBar(
                SnackBar(content: Text('Error: $e'), backgroundColor: _T.danger, behavior: SnackBarBehavior.floating),
              );
            }
          } finally {
            widget.onExportEnd();
          }
        },
      ),
      const SizedBox(width: 6),
      isToggling
          ? const SizedBox(width: 36, height: 36, child: CircularProgressIndicator(strokeWidth: 2, color: _T.primary))
          : _buildIconBtn(
              icon: isPublished ? Icons.visibility_off_rounded : Icons.visibility_rounded,
              color: isPublished ? _T.orange : _T.primary,
              tooltip: isPublished ? 'Unpublish' : 'Publish',
              onTap: () => widget.onTogglePublish(quiz),
            ),
    ],
  ),
),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildActionBtn({required IconData icon, required String label, required Color color, required VoidCallback onTap}) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 7),
        decoration: BoxDecoration(
          color: color.withOpacity(0.1),
          borderRadius: BorderRadius.circular(10),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(icon, size: 14, color: color),
            const SizedBox(width: 5),
            Text(label, style: TextStyle(fontSize: 12, color: color, fontWeight: FontWeight.w600)),
          ],
        ),
      ),
    );
  }

  Widget _buildIconBtn({required IconData icon, required Color color, required String tooltip, required VoidCallback onTap}) {
    return Tooltip(
      message: tooltip,
      child: GestureDetector(
        onTap: onTap,
        child: Container(
          width: 36,
          height: 36,
          decoration: BoxDecoration(
            color: color.withOpacity(0.1),
            borderRadius: BorderRadius.circular(10),
          ),
          child: Icon(icon, size: 18, color: color),
        ),
      ),
    );
  }

  Widget _buildMeta(IconData icon, String label, {Color? color}) {
    final c = color ?? _T.textLight;
    return Row(
      children: [
        Icon(icon, size: 13, color: c),
        const SizedBox(width: 4),
        Text(label, style: TextStyle(fontSize: 12, color: c)),
      ],
    );
  }
}