import 'dart:ui';
import 'package:flutter/material.dart';
import '../services/auth_service.dart';

// ─── THEME ────────────────────────────────────────────────────────────────────
class _T {
  static const Color primary = Color(0xFF5B2A9B);
  static const Color primaryDark = Color(0xFF3A1A6B);
  static const Color primaryLight = Color(0xFFEDE7F2);
  static const Color accent = Color(0xFFF2C94C);
  static const Color accentDark = Color(0xFFE0A93B);
  static const Color softPurple = Color(0xFFC9A8F0);
  static const Color highlightPurple = Color(0xFFA14BC9);
  static const Color bg = Color(0xFFFAF6EC);
  static const Color surface = Colors.white;
  static const Color textDark = Color(0xFF1F1235);
  static const Color textMid = Color(0xFF7A6E8A);
  static const Color textLight = Color(0xFFA99BC4);
  static const Color success = Color(0xFF22C55E);
  static const Color warning = Color(0xFFF59E0B);
  static const Color danger = Color(0xFFEF4444);
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

  static LinearGradient get headerGradient => const LinearGradient(
        colors: [Color(0xFF5B2A9B), Color(0xFF3A1A6B)],
        begin: Alignment.topLeft,
        end: Alignment.bottomRight,
      );
}

// ─── TEACHER QUIZZES TAB ──────────────────────────────────────────────────────
class TeacherQuizzesTab extends StatefulWidget {
  final String teacherName;
  final VoidCallback onRefresh;
  final VoidCallback onExportStart;
  final VoidCallback onExportEnd;

  const TeacherQuizzesTab({
    super.key,
    required this.teacherName,
    required this.onRefresh,
    required this.onExportStart,
    required this.onExportEnd,
  });

  @override
  State<TeacherQuizzesTab> createState() => _TeacherQuizzesTabState();
}

class _TeacherQuizzesTabState extends State<TeacherQuizzesTab> {
  final TextEditingController _searchController = TextEditingController();
  String _filter = 'all';
  List<Map<String, dynamic>> _quizzes = [];
  List<Map<String, dynamic>> _filtered = [];
  Map<String, dynamic> _stats = {};
  bool _isLoading = true;
  final Set<int> _togglingIds = {};

  @override
  void initState() {
    super.initState();
    _searchController.addListener(_applyFilter);
    _loadQuizzes();
  }

  @override
  void dispose() {
    _searchController.removeListener(_applyFilter);
    _searchController.dispose();
    super.dispose();
  }

  Future<void> _loadQuizzes() async {
    setState(() => _isLoading = true);
    try {
      final response = await AuthService.authGet('/teacher/dashboard');
      if (response['success'] == true) {
        final data = response['data'];
        setState(() {
          _quizzes = List<Map<String, dynamic>>.from(data['quizzes'] ?? []);
          _stats = Map<String, dynamic>.from(data['stats'] ?? {});
        });
        _applyFilter();
      } else {
        _showSnackbar(response['message'] ?? 'Failed to load quizzes', isError: true);
      }
    } catch (e) {
      _showSnackbar('Network error: $e', isError: true);
    } finally {
      setState(() => _isLoading = false);
    }
  }

  void _applyFilter() {
    final query = _searchController.text.trim().toLowerCase();
    setState(() {
      _filtered = _quizzes.where((q) {
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
        _applyFilter();
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
        title: const Text('Delete Quiz', style: TextStyle(fontWeight: FontWeight.bold, color: _T.textDark)),
        content: Text('Are you sure you want to delete "${quiz['title']}"? This cannot be undone.', style: const TextStyle(color: _T.textMid)),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: const Text('Cancel', style: TextStyle(color: _T.primary)),
          ),
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
      _applyFilter();
      _showSnackbar('Quiz deleted.');
    } else {
      _showSnackbar(result['message'], isError: true);
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

  Future<bool> _showPublishConfirmation(String quizTitle, bool isCurrentlyPublished) async {
    final actionLabel = isCurrentlyPublished ? 'Unpublish' : 'Publish';
    final color = isCurrentlyPublished ? _T.orange : _T.primary;
    final result = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: Text('$actionLabel Quiz?', style: const TextStyle(fontWeight: FontWeight.bold, color: _T.textDark)),
        content: Text(
          isCurrentlyPublished
              ? 'Students will no longer be able to access "$quizTitle".'
              : 'Students will be able to see and take "$quizTitle".',
          style: const TextStyle(color: _T.textMid),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(ctx).pop(false),
            child: const Text('Cancel', style: TextStyle(color: _T.primary)),
          ),
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

  @override
  Widget build(BuildContext context) {
    final totalQuizzes = _stats['total_quizzes'] ?? _quizzes.length;
    final publishedCount = _stats['published_quizzes'] ??
        _quizzes.where((q) => q['is_published'] == true || q['is_published'] == 1).length;
    final draftCount = (totalQuizzes as int) - (publishedCount as int);

    final hour = DateTime.now().hour;
    final greeting = hour < 12 ? 'Good morning' : hour < 17 ? 'Good afternoon' : 'Good evening';
    final firstName = widget.teacherName.split(' ').first;

    if (_isLoading) {
      return _buildSkeletonLoading();
    }

    return RefreshIndicator(
      onRefresh: () async => _loadQuizzes(),
      color: _T.accent,
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
                boxShadow: [
                  BoxShadow(
                    color: _T.plumShadow.withOpacity(0.3),
                    blurRadius: 20,
                    offset: const Offset(0, 8),
                  ),
                ],
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('$greeting,', style: const TextStyle(color: Colors.white70, fontSize: 14)),
                  const SizedBox(height: 2),
                  Text(
                    firstName,
                    style: const TextStyle(color: Colors.white, fontSize: 26, fontWeight: FontWeight.bold, letterSpacing: -0.5),
                  ),
                  const SizedBox(height: 20),
                  // Stat cards
                  Row(
                    children: [
                      _buildStatCard(Icons.quiz_rounded, '$totalQuizzes', 'Total'),
                      const SizedBox(width: 10),
                      _buildStatCard(Icons.visibility_rounded, '$publishedCount', 'Published'),
                      const SizedBox(width: 10),
                      _buildStatCard(Icons.edit_note_rounded, '$draftCount', 'Drafts'),
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
                            decoration: const BoxDecoration(color: _T.primaryLight, shape: BoxShape.circle),
                            child: Icon(Icons.quiz_outlined, size: 48, color: _T.primary.withOpacity(0.5)),
                          ),
                          const SizedBox(height: 16),
                          Text(
                            _quizzes.isEmpty ? 'No quizzes yet.' : 'No quizzes found.',
                            style: const TextStyle(color: _T.textMid, fontSize: 16, fontWeight: FontWeight.w500),
                          ),
                          const SizedBox(height: 4),
                          Text(
                            _quizzes.isEmpty ? 'Tap "New Quiz" to get started!' : 'Try adjusting your search or filter.',
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
        const CircularProgressIndicator(color: _T.accent, strokeWidth: 3),
        const SizedBox(height: 16),
        const Text('Loading quizzes...', style: TextStyle(color: _T.textMid, fontSize: 14)),
        const Spacer(),
      ],
    );
  }

  Widget _buildStatCard(IconData icon, String value, String label) {
    return Expanded(
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 10),
        decoration: BoxDecoration(
          color: Colors.white.withOpacity(0.15),
          borderRadius: BorderRadius.circular(14),
          border: Border.all(color: Colors.white.withOpacity(0.25)),
        ),
        child: Column(
          children: [
            Icon(icon, color: _T.accent, size: 20),
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
          border: Border.all(color: isSelected ? _T.primary : _T.softPurple.withOpacity(0.5)),
          boxShadow: isSelected
              ? [BoxShadow(color: _T.primary.withOpacity(0.3), blurRadius: 8, offset: const Offset(0, 2))]
              : [],
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
    final bool isToggling = _togglingIds.contains(quizId);
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
          if (result == true) _loadQuizzes();
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
                      gradient: isPublished
                          ? const LinearGradient(
                              colors: [Color(0xFF5B2A9B), Color(0xFF3A1A6B)],
                              begin: Alignment.topLeft,
                              end: Alignment.bottomRight,
                            )
                          : null,
                      color: isPublished ? null : Colors.grey.shade300,
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
                      color: isPublished ? _T.primary.withOpacity(0.10) : Colors.grey.withOpacity(0.10),
                      borderRadius: BorderRadius.circular(20),
                      border: Border.all(color: isPublished ? _T.primary.withOpacity(0.35) : Colors.grey.withOpacity(0.3)),
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
                  Divider(color: _T.primaryLight, height: 1),
                  const SizedBox(height: 12),

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
                          color: _T.highlightPurple,
                          onTap: () => Navigator.pushNamed(context, '/quiz-analytics', arguments: {'quiz_id': quizId, 'quiz_title': title}),
                        ),
                        const SizedBox(width: 8),
                        if (!hasAttempts) ...[
                          _buildIconBtn(
                            icon: Icons.edit_rounded,
                            color: _T.highlightPurple,
                            tooltip: 'Edit',
                            onTap: () async {
                              final result = await Navigator.pushNamed(
                                context,
                                '/edit-quiz',
                                arguments: {'quiz_id': quizId, 'title': title, 'description': description},
                              );
                              if (result == true) _loadQuizzes();
                            },
                          ),
                          const SizedBox(width: 6),
                          _buildIconBtn(
                            icon: Icons.delete_rounded,
                            color: _T.danger,
                            tooltip: 'Delete',
                            onTap: () => _deleteQuiz(quiz),
                          ),
                          const SizedBox(width: 6),
                        ],
                        _buildIconBtn(
                          icon: Icons.download_rounded,
                          color: _T.accentDark,
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
                                onTap: () => _togglePublish(quiz),
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
          color: color.withOpacity(0.10),
          borderRadius: BorderRadius.circular(10),
          border: Border.all(color: color.withOpacity(0.2)),
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
            color: color.withOpacity(0.10),
            borderRadius: BorderRadius.circular(10),
            border: Border.all(color: color.withOpacity(0.2)),
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