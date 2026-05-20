import 'dart:ui';
import 'package:flutter/material.dart';
import '../services/auth_service.dart';
import '../widgets/profile_widget.dart';
import 'teacher_class_list_tab.dart';
import 'teacher_quizzes_tab.dart';

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
  bool _isActionLoading = false;
  String _teacherName = '';
  String _teacherEmail = '';
  List<Map<String, dynamic>> _classes = [];
  Map<String, dynamic> _stats = {};

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
          _classes = List<Map<String, dynamic>>.from(data['classes'] ?? []);
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

  Future<void> _logout() async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: const Text('Log Out', style: TextStyle(fontWeight: FontWeight.bold, color: _T.textDark)),
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

  // ─── Class Actions ────────────────────────────────────────────────────────

  Future<void> _createClass() async {
    final nameController = TextEditingController();
    final descController = TextEditingController();

    final confirm = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: const Text('Create New Class', style: TextStyle(fontWeight: FontWeight.bold, color: _T.textDark)),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            TextField(
              controller: nameController,
              maxLength: 100,
              decoration: InputDecoration(
                labelText: 'Class Name',
                hintText: 'e.g. Math 101',
                prefixIcon: const Icon(Icons.class_rounded, color: _T.primary),
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                focusedBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(12),
                  borderSide: const BorderSide(color: _T.primary, width: 2),
                ),
              ),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: descController,
              maxLines: 3,
              maxLength: 200,
              decoration: InputDecoration(
                labelText: 'Description (optional)',
                hintText: 'e.g. Introduction to Mathematics',
                prefixIcon: const Icon(Icons.description_rounded, color: _T.primary),
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                focusedBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(12),
                  borderSide: const BorderSide(color: _T.primary, width: 2),
                ),
                alignLabelWithHint: true,
              ),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Cancel', style: TextStyle(color: _T.textMid)),
          ),
          ElevatedButton(
            onPressed: () => Navigator.pop(context, true),
            style: ElevatedButton.styleFrom(
              backgroundColor: _T.accent,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
            ),
            child: const Text('Create', style: TextStyle(color: _T.textDark, fontWeight: FontWeight.bold)),
          ),
        ],
      ),
    );

    if (confirm != true) return;
    if (nameController.text.trim().isEmpty) {
      _showSnackbar('Class name is required!', isWarning: true);
      return;
    }

    setState(() => _isActionLoading = true);
    final result = await AuthService.authPost('/classes', {
      'name': nameController.text.trim(),
      'description': descController.text.trim(),
    });
    if (mounted) setState(() => _isActionLoading = false);

    if (result['success']) {
      _loadDashboard();
      _showSnackbar('Class created successfully!');
    } else {
      _showSnackbar(result['message'], isError: true);
    }
  }

  Future<void> _editClass(Map<String, dynamic> cls) async {
    final nameController = TextEditingController(text: cls['name']);
    final descController = TextEditingController(text: cls['description'] ?? '');

    final confirm = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: const Text('Edit Class', style: TextStyle(fontWeight: FontWeight.bold, color: _T.textDark)),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            TextField(
              controller: nameController,
              maxLength: 100,
              decoration: InputDecoration(
                labelText: 'Class Name',
                prefixIcon: const Icon(Icons.class_rounded, color: _T.primary),
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                focusedBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(12),
                  borderSide: const BorderSide(color: _T.primary, width: 2),
                ),
              ),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: descController,
              maxLines: 3,
              maxLength: 200,
              decoration: InputDecoration(
                labelText: 'Description (optional)',
                prefixIcon: const Icon(Icons.description_rounded, color: _T.primary),
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                focusedBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(12),
                  borderSide: const BorderSide(color: _T.primary, width: 2),
                ),
                alignLabelWithHint: true,
              ),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Cancel', style: TextStyle(color: _T.textMid)),
          ),
          ElevatedButton(
            onPressed: () => Navigator.pop(context, true),
            style: ElevatedButton.styleFrom(
              backgroundColor: _T.accent,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
            ),
            child: const Text('Save', style: TextStyle(color: _T.textDark, fontWeight: FontWeight.bold)),
          ),
        ],
      ),
    );

    if (confirm != true) return;

    setState(() => _isActionLoading = true);
    final result = await AuthService.authPut(
      '/classes/${cls['id']}',
      {
        'name': nameController.text.trim(),
        'description': descController.text.trim(),
      },
    );
    if (mounted) setState(() => _isActionLoading = false);

    if (result['success']) {
      _loadDashboard();
      _showSnackbar('Class updated successfully!');
    } else {
      _showSnackbar(result['message'], isError: true);
    }
  }

  Future<void> _deleteClass(Map<String, dynamic> cls) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: const Text('Delete Class', style: TextStyle(fontWeight: FontWeight.bold, color: _T.textDark)),
        content: Text('Are you sure you want to delete "${cls['name']}"? All student enrollments and quiz assignments will be lost.'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Cancel', style: TextStyle(color: _T.textMid))),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: _T.danger, shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10))),
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('Delete', style: TextStyle(color: Colors.white)),
          ),
        ],
      ),
    );
    if (confirmed != true) return;

    setState(() => _isActionLoading = true);
    final result = await AuthService.authDelete('/classes/${cls['id']}');
    if (mounted) setState(() => _isActionLoading = false);

    if (result['success']) {
      setState(() => _classes.removeWhere((c) => c['id'] == cls['id']));
      _showSnackbar('Class deleted.');
    } else {
      _showSnackbar(result['message'] ?? 'Failed to delete class.', isError: true);
    }
  }

  // ─── Helpers ──────────────────────────────────────────────────────────────

  void _showSnackbar(String message, {bool isError = false, bool isWarning = false}) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: isError ? _T.danger : isWarning ? _T.warning : _T.success,
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
        margin: const EdgeInsets.all(16),
      ),
    );
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
                    _buildHomeTab(),
                    TeacherQuizzesTab(
                      teacherName: _teacherName,
                      onRefresh: _loadDashboard,
                      onExportStart: () => setState(() => _isExporting = true),
                      onExportEnd: () => setState(() => _isExporting = false),
                    ),
                    TeacherClassListTab(onRefresh: _loadDashboard),
                    ProfileWidget(onLogout: _logout),
                  ],
                ),
          if (_isExporting) _buildBlurOverlay('Generating Report...'),
          if (_isActionLoading)
            Positioned.fill(
              child: BackdropFilter(
                filter: ImageFilter.blur(sigmaX: 4, sigmaY: 4),
                child: Container(
                  color: _T.plumShadow.withOpacity(0.30),
                  child: Center(
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 28, vertical: 22),
                      decoration: BoxDecoration(
                        color: Colors.white.withOpacity(0.95),
                        borderRadius: BorderRadius.circular(20),
                        boxShadow: [BoxShadow(color: _T.plumShadow.withOpacity(0.18), blurRadius: 24, offset: const Offset(0, 8))],
                      ),
                      child: Column(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          CircularProgressIndicator(color: _T.primary, strokeWidth: 3),
                          const SizedBox(height: 16),
                          const Text(
                            'Please wait...',
                            style: TextStyle(color: _T.textDark, fontWeight: FontWeight.w600, fontSize: 14),
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
      floatingActionButton: _buildFloatingActionButton(),
      bottomNavigationBar: _buildBottomNav(),
    );
  }

  Widget? _buildFloatingActionButton() {
    if (_selectedIndex == 1 && !_isLoading) {
      return FloatingActionButton.extended(
        onPressed: () async {
          final result = await Navigator.pushNamed(context, '/create-quiz');
          if (result == true) _loadDashboard();
        },
        backgroundColor: _T.accent,
        elevation: 4,
        icon: const Icon(Icons.add_rounded, color: _T.textDark),
        label: const Text('New Quiz', style: TextStyle(color: _T.textDark, fontWeight: FontWeight.bold)),
      );
    }
    if (_selectedIndex == 0 && !_isLoading) {
      return FloatingActionButton.extended(
        onPressed: _createClass,
        backgroundColor: _T.accent,
        elevation: 4,
        icon: const Icon(Icons.add_rounded, color: _T.textDark),
        label: const Text('New Class', style: TextStyle(color: _T.textDark, fontWeight: FontWeight.bold)),
      );
    }
    return null;
  }

  Widget _buildBlurOverlay(String message) {
    return Positioned.fill(
      child: BackdropFilter(
        filter: ImageFilter.blur(sigmaX: 4, sigmaY: 4),
        child: Container(
          color: _T.plumShadow.withOpacity(0.30),
          child: Center(
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 28, vertical: 22),
              decoration: BoxDecoration(
                color: Colors.white.withOpacity(0.95),
                borderRadius: BorderRadius.circular(20),
                boxShadow: [BoxShadow(color: _T.plumShadow.withOpacity(0.18), blurRadius: 24, offset: const Offset(0, 8))],
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
        boxShadow: [BoxShadow(color: _T.plumShadow.withOpacity(0.10), blurRadius: 20, offset: const Offset(0, -4))],
      ),
      child: SafeArea(
        child: SizedBox(
          height: 64,
          child: Row(
            children: [
              _buildNavItem(0, Icons.home_rounded, Icons.home_outlined, 'Home'),
              _buildNavItem(1, Icons.quiz_rounded, Icons.quiz_outlined, 'Quizzes'),
              _buildNavItem(2, Icons.class_rounded, Icons.class_outlined, 'Classes'),
              _buildNavItem(3, Icons.person_rounded, Icons.person_outlined, 'Profile'),
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

  // ─── HOME TAB ─────────────────────────────────────────────────────────────

  Widget _buildHomeTab() {
    final totalClasses = _stats['total_classes'] ?? _classes.length;
    final totalStudents = _stats['total_students'] ?? 0;
    final totalQuizzes = _stats['total_quizzes'] ?? 0;
    final pendingReviewCount = _stats['pending_review_count'] ?? 0;

    final hour = DateTime.now().hour;
    final greeting = hour < 12 ? 'Good morning' : hour < 17 ? 'Good afternoon' : 'Good evening';
    final firstName = _teacherName.split(' ').first;

    return RefreshIndicator(
      onRefresh: () async => _loadDashboard(),
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
                        onTap: _logout,
                        child: Container(
                          padding: const EdgeInsets.all(10),
                          decoration: BoxDecoration(color: Colors.white.withOpacity(0.15), borderRadius: BorderRadius.circular(12)),
                          child: const Icon(Icons.logout_rounded, color: Colors.white, size: 20),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 20),
                  Row(
                    children: [
                      _buildStatCard(Icons.class_rounded, '$totalClasses', 'Classes'),
                      const SizedBox(width: 10),
                      _buildStatCard(Icons.people_rounded, '$totalStudents', 'Students'),
                      const SizedBox(width: 10),
                      _buildStatCard(Icons.quiz_rounded, '$totalQuizzes', 'Quizzes'),
                    ],
                  ),
                ],
              ),
            ),
          ),

          // ── Pending Reviews Banner (only shown when > 0) ──
          if (pendingReviewCount > 0)
            SliverToBoxAdapter(
              child: Padding(
                padding: const EdgeInsets.fromLTRB(20, 20, 20, 0),
                child: GestureDetector(
                  onTap: () async {
                    await Navigator.pushNamed(context, '/manual-review-quizzes');
                    _loadDashboard();
                  },
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
                    decoration: BoxDecoration(
                      color: const Color(0xFFFFF7ED),
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(color: _T.orange.withOpacity(0.4)),
                      boxShadow: [
                        BoxShadow(
                          color: _T.orange.withOpacity(0.08),
                          blurRadius: 12,
                          offset: const Offset(0, 4),
                        ),
                      ],
                    ),
                    child: Row(
                      children: [
                        Container(
                          padding: const EdgeInsets.all(10),
                          decoration: BoxDecoration(
                            color: _T.orange.withOpacity(0.12),
                            borderRadius: BorderRadius.circular(12),
                          ),
                          child: Icon(Icons.rate_review_rounded, color: _T.orange, size: 22),
                        ),
                        const SizedBox(width: 14),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                'Pending Reviews',
                                style: TextStyle(
                                  color: _T.orange,
                                  fontWeight: FontWeight.bold,
                                  fontSize: 14,
                                ),
                              ),
                              const SizedBox(height: 2),
                              Text(
                                '$pendingReviewCount submission${pendingReviewCount != 1 ? 's' : ''} awaiting your review',
                                style: TextStyle(color: _T.orange.withOpacity(0.8), fontSize: 12),
                              ),
                            ],
                          ),
                        ),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                          decoration: BoxDecoration(
                            color: _T.orange,
                            borderRadius: BorderRadius.circular(20),
                          ),
                          child: Row(
                            children: [
                              Text(
                                'Review',
                                style: const TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.bold),
                              ),
                              const SizedBox(width: 4),
                              const Icon(Icons.arrow_forward_rounded, color: Colors.white, size: 13),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ),
            ),

          // ── Section title ──
          SliverToBoxAdapter(
            child: Padding(
              padding: const EdgeInsets.fromLTRB(20, 24, 20, 12),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  const Text(
                    'Your Classes',
                    style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: _T.textDark),
                  ),
                  GestureDetector(
                    onTap: () => setState(() => _selectedIndex = 2),
                    child: Text(
                      'See all',
                      style: TextStyle(fontSize: 13, color: _T.primary, fontWeight: FontWeight.w600),
                    ),
                  ),
                ],
              ),
            ),
          ),

          // ── Classes list or empty ──
          _classes.isEmpty
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
                            child: Icon(Icons.class_outlined, size: 48, color: _T.primary.withOpacity(0.5)),
                          ),
                          const SizedBox(height: 16),
                          const Text(
                            'No classes yet.',
                            style: TextStyle(color: _T.textMid, fontSize: 16, fontWeight: FontWeight.w500),
                          ),
                          const SizedBox(height: 4),
                          const Text(
                            'Tap "New Class" to create your first class!',
                            textAlign: TextAlign.center,
                            style: TextStyle(color: _T.textLight, fontSize: 13),
                          ),
                        ],
                      ),
                    ),
                  ),
                )
              : SliverPadding(
                  padding: const EdgeInsets.fromLTRB(20, 0, 20, 100),
                  sliver: SliverList(
                    delegate: SliverChildBuilderDelegate(
                      (context, index) => _buildClassCard(_classes[index]),
                      childCount: _classes.length,
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

  Widget _buildClassCard(Map<String, dynamic> cls) {
    final int classId = cls['id'];
    final String name = cls['name'] ?? 'Untitled Class';
    final String description = cls['description'] ?? '';
    final int studentCount = cls['students_count'] ?? 0;
    final int quizCount = cls['quizzes_count'] ?? 0;
    final String? code = cls['code'];

    final colors = [
      const Color(0xFF5B2A9B),
      const Color(0xFFA14BC9),
      const Color(0xFF3A1A6B),
      const Color(0xFFC9A8F0),
      const Color(0xFF7C3ABD),
      const Color(0xFF8B5CF6),
    ];
    final classColor = colors[name.codeUnitAt(0) % colors.length];

    return Container(
      margin: const EdgeInsets.only(bottom: 14),
      decoration: _T.card,
      child: InkWell(
        borderRadius: BorderRadius.circular(16),
        onTap: () async {
          await Navigator.pushNamed(
            context,
            '/class-detail',
            arguments: {'class_id': classId, 'class_name': name},
          );
          _loadDashboard();
        },
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Container(
              padding: const EdgeInsets.fromLTRB(16, 14, 16, 14),
              decoration: BoxDecoration(
                color: classColor.withOpacity(0.10),
                borderRadius: const BorderRadius.only(topLeft: Radius.circular(16), topRight: Radius.circular(16)),
              ),
              child: Row(
                children: [
                  Container(
                    width: 46,
                    height: 46,
                    decoration: BoxDecoration(
                      color: classColor,
                      borderRadius: BorderRadius.circular(13),
                    ),
                    child: Center(
                      child: Text(
                        name[0].toUpperCase(),
                        style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 20),
                      ),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(name, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15, color: _T.textDark)),
                        if (description.isNotEmpty) ...[
                          const SizedBox(height: 2),
                          Text(description, maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(fontSize: 12, color: _T.textMid)),
                        ],
                      ],
                    ),
                  ),
                  Icon(Icons.chevron_right_rounded, color: classColor),
                ],
              ),
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 12, 16, 14),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      if (code != null && code.isNotEmpty)
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                          decoration: BoxDecoration(
                            color: classColor.withOpacity(0.10),
                            borderRadius: BorderRadius.circular(8),
                            border: Border.all(color: classColor.withOpacity(0.3)),
                          ),
                          child: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Icon(Icons.key_rounded, size: 12, color: classColor),
                              const SizedBox(width: 5),
                              Text(
                                code,
                                style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: classColor, letterSpacing: 1.5),
                              ),
                            ],
                          ),
                        ),
                      const Spacer(),
                      _buildMeta(Icons.people_outline_rounded, '$studentCount student${studentCount != 1 ? 's' : ''}'),
                      const SizedBox(width: 12),
                      _buildMeta(Icons.quiz_outlined, '$quizCount quiz${quizCount != 1 ? 'zes' : ''}'),
                    ],
                  ),
                  const SizedBox(height: 12),
                  Divider(color: _T.primaryLight, height: 1),
                  const SizedBox(height: 12),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      GestureDetector(
                        onTap: () => _deleteClass(cls),
                        child: Container(
                          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                          decoration: BoxDecoration(
                            border: Border.all(color: _T.danger.withOpacity(0.3)),
                            borderRadius: BorderRadius.circular(20),
                          ),
                          child: const Row(
                            children: [
                              Icon(Icons.delete_rounded, size: 13, color: _T.danger),
                              SizedBox(width: 4),
                              Text('Delete', style: TextStyle(color: _T.danger, fontSize: 12, fontWeight: FontWeight.w600)),
                            ],
                          ),
                        ),
                      ),
                      Row(
                        children: [
                          GestureDetector(
                            onTap: () => _editClass(cls),
                            child: Container(
                              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                              decoration: BoxDecoration(
                                border: Border.all(color: _T.softPurple.withOpacity(0.5)),
                                borderRadius: BorderRadius.circular(20),
                              ),
                              child: const Row(
                                children: [
                                  Icon(Icons.edit_rounded, size: 13, color: _T.primary),
                                  SizedBox(width: 4),
                                  Text('Edit', style: TextStyle(color: _T.primary, fontSize: 12, fontWeight: FontWeight.w600)),
                                ],
                              ),
                            ),
                          ),
                          const SizedBox(width: 8),
                          GestureDetector(
                            onTap: () async {
                              await Navigator.pushNamed(
                                context,
                                '/class-detail',
                                arguments: {'class_id': classId, 'class_name': name},
                              );
                              _loadDashboard();
                            },
                            child: Container(
                              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 6),
                              decoration: BoxDecoration(
                                color: _T.accent,
                                borderRadius: BorderRadius.circular(20),
                              ),
                              child: const Row(
                                children: [
                                  Text('Manage', style: TextStyle(color: _T.textDark, fontSize: 12, fontWeight: FontWeight.bold)),
                                  SizedBox(width: 4),
                                  Icon(Icons.arrow_forward_rounded, size: 13, color: _T.textDark),
                                ],
                              ),
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ],
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