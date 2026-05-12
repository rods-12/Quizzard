import 'dart:ui';
import 'package:flutter/material.dart';
import '../services/auth_service.dart';
import '../widgets/profile_widget.dart';
import 'student_quizzes_tab.dart'; // ← External file

// ─── THEME CONSTANTS ─────────────────────────────────────────────────────────
class _AppTheme {
  static const Color primary = Color(0xFF6C63FF);
  static const Color primaryDark = Color(0xFF4B44CC);
  static const Color primaryLight = Color(0xFFEEEDFF);
  static const Color accent = Color(0xFF00C9A7);
  static const Color bg = Color(0xFFF4F6FB);
  static const Color surface = Colors.white;
  static const Color textDark = Color(0xFF1A1D2E);
  static const Color textMid = Color(0xFF6B7080);
  static const Color textLight = Color(0xFFADB5BD);
  static const Color success = Color(0xFF22C55E);
  static const Color warning = Color(0xFFF59E0B);
  static const Color danger = Color(0xFFEF4444);

  static BoxDecoration get cardDecoration => BoxDecoration(
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

  static InputDecoration searchDecoration(String hint) => InputDecoration(
        hintText: hint,
        hintStyle: const TextStyle(color: _AppTheme.textLight, fontSize: 14),
        prefixIcon: const Icon(Icons.search_rounded, color: _AppTheme.primary, size: 20),
        filled: true,
        fillColor: Colors.white,
        contentPadding: const EdgeInsets.symmetric(vertical: 12),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(14),
          borderSide: BorderSide.none,
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(14),
          borderSide: const BorderSide(color: _AppTheme.primary, width: 1.5),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(14),
          borderSide: BorderSide(color: Colors.grey.shade200, width: 1),
        ),
      );
}

// ─── STUDENT DASHBOARD ───────────────────────────────────────────────────────
class StudentDashboardScreen extends StatefulWidget {
  const StudentDashboardScreen({super.key});

  @override
  State<StudentDashboardScreen> createState() => _StudentDashboardScreenState();
}

class _StudentDashboardScreenState extends State<StudentDashboardScreen> {
  bool _isLoading = true;
  bool _isActionLoading = false;
  Map<String, dynamic>? _dashboardData;
  String? _errorMessage;
  int _currentIndex = 0;

  @override
  void initState() {
    super.initState();
    _loadDashboard();
  }

  Future<void> _loadDashboard() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    final result = await AuthService.authGet('/student/dashboard');

    setState(() {
      _isLoading = false;
      if (result['success']) {
        _dashboardData = result['data'];
      } else {
        _errorMessage = result['message'];
      }
    });
  }

  Future<void> _logout() async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: const Text('Log Out', style: TextStyle(fontWeight: FontWeight.bold)),
        content: const Text('Are you sure you want to log out?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: Text('Cancel', style: TextStyle(color: _AppTheme.textMid)),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(
              backgroundColor: _AppTheme.danger,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
            ),
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('Log Out', style: TextStyle(color: Colors.white)),
          ),
        ],
      ),
    );

    if (confirm != true) return;
    await AuthService.logout();
    if (!mounted) return;
    Navigator.pushReplacementNamed(context, '/login');
  }

  // ─── Helpers from Code 2 ──────────────────────────────────────────────────
  String _getTabTitle() {
    switch (_currentIndex) {
      case 0:
        return 'Dashboard';
      case 1:
        return 'My Quizzes';
      case 2:
        return 'My Classes';
      case 3:
        return 'Profile';
      default:
        return '';
    }
  }

  String _getInitials(String name) {
    final parts = name.split(' ').where((s) => s.isNotEmpty).toList();
    if (parts.isEmpty) return '?';
    if (parts.length == 1) return parts[0][0].toUpperCase();
    return (parts[0][0] + parts[1][0]).toUpperCase();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: _AppTheme.bg,
      body: Stack(
        children: [
          _buildBody(),
          if (_isActionLoading) _buildBlurOverlay('Please wait...'),
        ],
      ),
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
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withOpacity(0.12),
                    blurRadius: 24,
                    offset: const Offset(0, 8),
                  ),
                ],
              ),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  const CircularProgressIndicator(color: _AppTheme.primary, strokeWidth: 3),
                  const SizedBox(height: 16),
                  Text(
                    message,
                    style: const TextStyle(
                      color: _AppTheme.textDark,
                      fontWeight: FontWeight.w600,
                      fontSize: 14,
                    ),
                  ),
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
    final isSelected = _currentIndex == index;
    return Expanded(
      child: GestureDetector(
        onTap: () => setState(() => _currentIndex = index),
        behavior: HitTestBehavior.opaque,
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            AnimatedContainer(
              duration: const Duration(milliseconds: 200),
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
              decoration: BoxDecoration(
                color: isSelected ? _AppTheme.primaryLight : Colors.transparent,
                borderRadius: BorderRadius.circular(12),
              ),
              child: Icon(
                isSelected ? activeIcon : inactiveIcon,
                color: isSelected ? _AppTheme.primary : _AppTheme.textLight,
                size: 22,
              ),
            ),
            const SizedBox(height: 2),
            Text(
              label,
              style: TextStyle(
                fontSize: 10,
                fontWeight: isSelected ? FontWeight.w700 : FontWeight.w400,
                color: isSelected ? _AppTheme.primary : _AppTheme.textLight,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildBody() {
    if (_isLoading) {
      return Column(
        children: [
          Container(
            width: double.infinity,
            padding: const EdgeInsets.fromLTRB(24, 56, 24, 32),
            decoration: BoxDecoration(
              gradient: LinearGradient(
                colors: [_AppTheme.primary.withOpacity(0.85), _AppTheme.primaryDark.withOpacity(0.85)],
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
                Container(width: 180, height: 24, decoration: BoxDecoration(color: Colors.white.withOpacity(0.45), borderRadius: BorderRadius.circular(8))),
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
          const CircularProgressIndicator(color: _AppTheme.primary, strokeWidth: 3),
          const SizedBox(height: 16),
          const Text('Loading dashboard...', style: TextStyle(color: _AppTheme.textMid, fontSize: 14)),
          const Spacer(),
        ],
      );
    }

    if (_errorMessage != null) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(32),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Container(
                padding: const EdgeInsets.all(20),
                decoration: BoxDecoration(
                  color: _AppTheme.danger.withOpacity(0.1),
                  shape: BoxShape.circle,
                ),
                child: Icon(Icons.error_outline_rounded, size: 48, color: _AppTheme.danger),
              ),
              const SizedBox(height: 16),
              Text(_errorMessage!, textAlign: TextAlign.center, style: const TextStyle(color: _AppTheme.textMid)),
              const SizedBox(height: 20),
              ElevatedButton(
                onPressed: _loadDashboard,
                style: ElevatedButton.styleFrom(backgroundColor: _AppTheme.primary),
                child: const Text('Retry', style: TextStyle(color: Colors.white)),
              ),
            ],
          ),
        ),
      );
    }

    switch (_currentIndex) {
      case 0: return _buildHomeTab();
      case 1: return const StudentQuizzesTab(); // ← Uses external file
      case 2: return _buildClassesTab();
      case 3: return _buildProfileTab();
      default: return _buildHomeTab();
    }
  }

  // ─── HOME TAB ─────────────────────────────────────────────────────────────
  Widget _buildHomeTab() {
    final student = _dashboardData!['student'];
    final quizzes = _dashboardData!['available_quizzes'] as List;
    final scores = _dashboardData!['recent_scores'] as List;
    final totalTaken = _dashboardData!['total_quizzes_taken'];

    final hour = DateTime.now().hour;
    final greeting = hour < 12 ? 'Good morning' : hour < 17 ? 'Good afternoon' : 'Good evening';
    final firstName = student['first_name'] ?? (student['name'] as String).split(' ').first;

    int avgScore = 0;
    if (scores.isNotEmpty) {
      avgScore = (scores.map((s) => s['percentage'] as int).reduce((a, b) => a + b) / scores.length).round();
    }

    return RefreshIndicator(
      onRefresh: _loadDashboard,
      color: _AppTheme.primary,
      child: SingleChildScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // ── Header ──
            Container(
              width: double.infinity,
              padding: const EdgeInsets.fromLTRB(24, 56, 24, 28),
              decoration: const BoxDecoration(
                gradient: LinearGradient(
                  colors: [_AppTheme.primary, _AppTheme.primaryDark],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                ),
                borderRadius: BorderRadius.only(
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
                            Text(
                              '$greeting,',
                              style: const TextStyle(color: Colors.white70, fontSize: 14),
                            ),
                            const SizedBox(height: 2),
                            Text(
                              firstName,
                              style: const TextStyle(
                                color: Colors.white,
                                fontSize: 26,
                                fontWeight: FontWeight.bold,
                                letterSpacing: -0.5,
                              ),
                            ),
                          ],
                        ),
                      ),
                      // Profile avatar from Code 2 logic
                      GestureDetector(
                        onTap: _logout,
                        child: Container(
                          decoration: BoxDecoration(
                            border: Border.all(color: Colors.white.withOpacity(0.4), width: 2),
                            shape: BoxShape.circle,
                          ),
                          child: CircleAvatar(
                            radius: 20,
                            backgroundImage: student['profile_picture'] != null
                                ? NetworkImage(student['profile_picture'])
                                : null,
                            backgroundColor: Colors.white.withOpacity(0.2),
                            child: student['profile_picture'] == null
                                ? Text(
                                    _getInitials(student['name']),
                                    style: const TextStyle(
                                      color: Colors.white,
                                      fontWeight: FontWeight.bold,
                                      fontSize: 14,
                                    ),
                                  )
                                : null,
                          ),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 24),
                  // Stats cards
                  Row(
                    children: [
                      _buildHeaderStatCard(
                        icon: Icons.quiz_rounded,
                        label: 'Available',
                        value: '${quizzes.length}',
                      ),
                      const SizedBox(width: 10),
                      _buildHeaderStatCard(
                        icon: Icons.check_circle_rounded,
                        label: 'Completed',
                        value: '$totalTaken',
                      ),
                      const SizedBox(width: 10),
                      _buildHeaderStatCard(
                        icon: Icons.star_rounded,
                        label: 'Avg Score',
                        value: scores.isEmpty ? 'N/A' : '$avgScore%',
                      ),
                    ],
                  ),
                ],
              ),
            ),

            const SizedBox(height: 24),

            // ── Recent Scores ──
            if (scores.isNotEmpty) ...[
              _buildSectionHeader('Recent Scores', Icons.bar_chart_rounded),
              const SizedBox(height: 12),
              SizedBox(
                height: 108,
                child: ListView.builder(
                  scrollDirection: Axis.horizontal,
                  padding: const EdgeInsets.symmetric(horizontal: 20),
                  itemCount: scores.length,
                  itemBuilder: (context, index) => _buildScoreCard(scores[index]),
                ),
              ),
              const SizedBox(height: 24),
            ],

            // ── Available Quizzes ──
            _buildSectionHeader('Available Quizzes', Icons.assignment_rounded),
            const SizedBox(height: 12),
            quizzes.isEmpty
                ? _buildEmptyState(
                    icon: Icons.quiz_outlined,
                    message: 'No quizzes available yet.',
                    subMessage: 'Check back later!',
                  )
                : ListView.builder(
                    shrinkWrap: true,
                    physics: const NeverScrollableScrollPhysics(),
                    padding: const EdgeInsets.symmetric(horizontal: 20),
                    itemCount: quizzes.length,
                    itemBuilder: (context, index) => _buildQuizCard(quizzes[index]),
                  ),
            const SizedBox(height: 24),
          ],
        ),
      ),
    );
  }

  // ─── CLASSES TAB ──────────────────────────────────────────────────────────
  Widget _buildClassesTab() {
    return _StudentClassesTab(onRefresh: _loadDashboard);
  }

  // ─── PROFILE TAB ──────────────────────────────────────────────────────────
  Widget _buildProfileTab() {
    return ProfileWidget(onLogout: _logout);
  }

  // ─── HELPER WIDGETS ───────────────────────────────────────────────────────
  Widget _buildSectionHeader(String title, IconData icon) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 20),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(6),
            decoration: BoxDecoration(
              color: _AppTheme.primaryLight,
              borderRadius: BorderRadius.circular(8),
            ),
            child: Icon(icon, color: _AppTheme.primary, size: 16),
          ),
          const SizedBox(width: 10),
          Text(
            title,
            style: const TextStyle(
              fontSize: 17,
              fontWeight: FontWeight.bold,
              color: _AppTheme.textDark,
              letterSpacing: -0.3,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildHeaderStatCard({required IconData icon, required String label, required String value}) {
    return Expanded(
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 14, horizontal: 10),
        decoration: BoxDecoration(
          color: Colors.white.withOpacity(0.18),
          borderRadius: BorderRadius.circular(14),
          border: Border.all(color: Colors.white.withOpacity(0.25), width: 1),
        ),
        child: Column(
          children: [
            Icon(icon, color: Colors.white, size: 20),
            const SizedBox(height: 6),
            Text(
              value,
              style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 18),
            ),
            Text(label, style: const TextStyle(color: Colors.white70, fontSize: 10)),
          ],
        ),
      ),
    );
  }

  Widget _buildScoreCard(Map<String, dynamic> score) {
    final percentage = score['percentage'] as int;
    final color = _getScoreColor(percentage);
    return Container(
      width: 155,
      margin: const EdgeInsets.only(right: 12),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(color: Colors.black.withOpacity(0.05), blurRadius: 10, offset: const Offset(0, 4)),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(
            score['quiz_title'],
            maxLines: 2,
            overflow: TextOverflow.ellipsis,
            style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13, color: _AppTheme.textDark),
          ),
          Row(
            children: [
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                decoration: BoxDecoration(
                  color: color.withOpacity(0.12),
                  borderRadius: BorderRadius.circular(20),
                ),
                child: Text(
                  '$percentage%',
                  style: TextStyle(color: color, fontWeight: FontWeight.bold, fontSize: 12),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildQuizCard(Map<String, dynamic> quiz) {
    final alreadyTaken = quiz['already_taken'] as bool;
    final status = quiz['status'] as String?;
    final isPending = alreadyTaken && status != 'reviewed';
    final isDone = alreadyTaken && status == 'reviewed';
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: _AppTheme.cardDecoration,
      child: InkWell(
        borderRadius: BorderRadius.circular(16),
        onTap: alreadyTaken
            ? null
            : () => Navigator.pushNamed(
                  context,
                  '/quiz-taking',
                  arguments: {'quiz_id': quiz['id'], 'quiz_title': quiz['title']},
                ),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Row(
            children: [
              Container(
                width: 50,
                height: 50,
                decoration: BoxDecoration(
                  color: isDone
                      ? _AppTheme.success.withOpacity(0.1)
                      : isPending
                          ? const Color(0xFFF59E0B).withOpacity(0.1)
                          : _AppTheme.primaryLight,
                  borderRadius: BorderRadius.circular(13),
                ),
                child: Icon(
                  isDone
                      ? Icons.check_circle_rounded
                      : isPending
                          ? Icons.hourglass_top_rounded
                          : Icons.quiz_rounded,
                  color: isDone
                      ? _AppTheme.success
                      : isPending
                          ? const Color(0xFFF59E0B)
                          : _AppTheme.primary,
                  size: 26,
                ),
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      quiz['title'],
                      style: const TextStyle(
                          fontWeight: FontWeight.bold,
                          fontSize: 15,
                          color: _AppTheme.textDark),
                    ),
                    const SizedBox(height: 3),
                    Text(
                      'By ${quiz['teacher_name']}',
                      style: const TextStyle(
                          color: _AppTheme.textMid, fontSize: 12),
                    ),
                    if (isDone) ...[
                      const SizedBox(height: 4),
                      Text(
                        'Score: ${quiz['score']}/${quiz['total_points']}',
                        style: const TextStyle(
                            color: _AppTheme.success,
                            fontSize: 12,
                            fontWeight: FontWeight.w600),
                      ),
                    ] else if (isPending) ...[
                      const SizedBox(height: 4),
                      const Text(
                        'Pending teacher review',
                        style: TextStyle(
                            color: Color(0xFFF59E0B),
                            fontSize: 12,
                            fontWeight: FontWeight.w600),
                      ),
                    ],
                  ],
                ),
              ),
              if (!alreadyTaken)
                Container(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 14, vertical: 7),
                  decoration: BoxDecoration(
                    color: _AppTheme.primary,
                    borderRadius: BorderRadius.circular(20),
                  ),
                  child: const Text('Take',
                      style: TextStyle(
                          color: Colors.white,
                          fontWeight: FontWeight.bold,
                          fontSize: 12)),
                )
              else if (isPending)
                Container(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 14, vertical: 7),
                  decoration: BoxDecoration(
                    color: const Color(0xFFF59E0B).withOpacity(0.1),
                    borderRadius: BorderRadius.circular(20),
                    border: Border.all(
                        color: const Color(0xFFF59E0B).withOpacity(0.3)),
                  ),
                  child: const Text('Pending',
                      style: TextStyle(
                          color: Color(0xFFF59E0B),
                          fontWeight: FontWeight.bold,
                          fontSize: 12)),
                )
              else
                Container(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 14, vertical: 7),
                  decoration: BoxDecoration(
                    color: _AppTheme.success.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(20),
                    border: Border.all(
                        color: _AppTheme.success.withOpacity(0.3)),
                  ),
                  child: const Text('Done',
                      style: TextStyle(
                          color: _AppTheme.success,
                          fontWeight: FontWeight.bold,
                          fontSize: 12)),
                ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildEmptyState({required IconData icon, required String message, String? subMessage}) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(40),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              padding: const EdgeInsets.all(24),
              decoration: BoxDecoration(
                color: _AppTheme.primaryLight,
                shape: BoxShape.circle,
              ),
              child: Icon(icon, size: 48, color: _AppTheme.primary.withOpacity(0.5)),
            ),
            const SizedBox(height: 16),
            Text(message, textAlign: TextAlign.center, style: const TextStyle(color: _AppTheme.textMid, fontSize: 16, fontWeight: FontWeight.w500)),
            if (subMessage != null) ...[
              const SizedBox(height: 4),
              Text(subMessage, textAlign: TextAlign.center, style: TextStyle(color: _AppTheme.textLight, fontSize: 13)),
            ],
          ],
        ),
      ),
    );
  }

  Color _getScoreColor(int percentage) {
    if (percentage >= 80) return _AppTheme.success;
    if (percentage >= 60) return _AppTheme.warning;
    return _AppTheme.danger;
  }
}

// ─── CLASSES TAB ─────────────────────────────────────────────────────────────
class _StudentClassesTab extends StatefulWidget {
  final VoidCallback onRefresh;

  const _StudentClassesTab({required this.onRefresh});

  @override
  State<_StudentClassesTab> createState() => _StudentClassesTabState();
}

class _StudentClassesTabState extends State<_StudentClassesTab> {
  bool _isLoading = true;
  bool _isActionLoading = false;
  String? _errorMessage;
  List<dynamic> _classes = [];
  final TextEditingController _searchController = TextEditingController();
  List<dynamic> _filtered = [];

  @override
  void initState() {
    super.initState();
    _loadClasses();
    _searchController.addListener(_applyFilter);
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
      _filtered = _classes.where((c) {
        final name = (c['name'] as String).toLowerCase();
        final teacher = (c['teacher_name'] as String).toLowerCase();
        return query.isEmpty || name.contains(query) || teacher.contains(query);
      }).toList();
    });
  }

  Future<void> _loadClasses() async {
    setState(() { _isLoading = true; _errorMessage = null; });

    final result = await AuthService.authGet('/student/classes');

    setState(() {
      _isLoading = false;
      if (result['success']) {
        _classes = result['data']['classes'] as List;
        _filtered = _classes;
      } else {
        _errorMessage = result['message'];
      }
    });
  }

  Future<void> _joinClass() async {
    final codeController = TextEditingController();

    final confirm = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: const Text('Join a Class', style: TextStyle(fontWeight: FontWeight.bold)),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(
              'Enter the class code provided by your teacher.',
              style: TextStyle(color: _AppTheme.textMid, fontSize: 13),
            ),
            const SizedBox(height: 16),
            TextField(
              controller: codeController,
              textCapitalization: TextCapitalization.characters,
              style: const TextStyle(fontWeight: FontWeight.bold, letterSpacing: 2),
              decoration: InputDecoration(
                labelText: 'Class Code',
                hintText: 'e.g. Y9ZJAV',
                prefixIcon: const Icon(Icons.key_rounded, color: _AppTheme.primary),
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                focusedBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(12),
                  borderSide: const BorderSide(color: _AppTheme.primary, width: 2),
                ),
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
              backgroundColor: _AppTheme.primary,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
            ),
            child: const Text('Join', style: TextStyle(color: Colors.white)),
          ),
        ],
      ),
    );

    if (confirm != true) return;
    if (codeController.text.trim().isEmpty) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Please enter a class code.'), backgroundColor: _AppTheme.danger),
      );
      return;
    }

    setState(() => _isActionLoading = true);
    final result = await AuthService.authPost('/student/classes/join', {'class_code': codeController.text.trim()});
    if (mounted) setState(() => _isActionLoading = false);

    if (result['success']) {
      _loadClasses();
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(result['data']['message'] ?? 'Successfully joined the class!'),
          backgroundColor: _AppTheme.success,
        ),
      );
    } else {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(result['message']), backgroundColor: _AppTheme.danger),
      );
    }
  }

  Future<void> _leaveClass(Map<String, dynamic> cls) async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: const Text('Leave Class', style: TextStyle(fontWeight: FontWeight.bold)),
        content: Text('Are you sure you want to leave "${cls['name']}"?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Cancel'),
          ),
          ElevatedButton(
            onPressed: () => Navigator.pop(context, true),
            style: ElevatedButton.styleFrom(
              backgroundColor: _AppTheme.danger,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
            ),
            child: const Text('Leave', style: TextStyle(color: Colors.white)),
          ),
        ],
      ),
    );

    if (confirm != true) return;

    setState(() => _isActionLoading = true);
    final result = await AuthService.authDelete('/student/classes/${cls['id']}/leave');
    if (mounted) setState(() => _isActionLoading = false);

    if (result['success']) {
      _loadClasses();
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('You have left the class.'), backgroundColor: _AppTheme.success),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: _AppTheme.bg,
      floatingActionButton: FloatingActionButton.extended(
        onPressed: _joinClass,
        backgroundColor: _AppTheme.primary,
        icon: const Icon(Icons.add_rounded, color: Colors.white),
        label: const Text('Join Class', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
        elevation: 4,
      ),
      body: Stack(
        children: [
          _buildBody(),
          if (_isActionLoading) _buildBlurOverlay(),
        ],
      ),
    );
  }

  Widget _buildBlurOverlay() {
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
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withOpacity(0.12),
                    blurRadius: 24,
                    offset: const Offset(0, 8),
                  ),
                ],
              ),
              child: const Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  CircularProgressIndicator(color: _AppTheme.primary, strokeWidth: 3),
                  SizedBox(height: 16),
                  Text(
                    'Please wait...',
                    style: TextStyle(
                      color: _AppTheme.textDark,
                      fontWeight: FontWeight.w600,
                      fontSize: 14,
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildBody() {
    if (_isLoading) {
      return Column(
        children: [
          Container(
            width: double.infinity,
            padding: const EdgeInsets.fromLTRB(24, 56, 24, 32),
            decoration: BoxDecoration(
              gradient: LinearGradient(
                colors: [_AppTheme.primary.withOpacity(0.85), _AppTheme.primaryDark.withOpacity(0.85)],
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
                Container(width: 140, height: 24, decoration: BoxDecoration(color: Colors.white.withOpacity(0.4), borderRadius: BorderRadius.circular(8))),
                const SizedBox(height: 10),
                Container(width: 100, height: 14, decoration: BoxDecoration(color: Colors.white.withOpacity(0.25), borderRadius: BorderRadius.circular(8))),
                const SizedBox(height: 24),
                Container(height: 46, decoration: BoxDecoration(color: Colors.white.withOpacity(0.2), borderRadius: BorderRadius.circular(14))),
              ],
            ),
          ),
          const Spacer(),
          const CircularProgressIndicator(color: _AppTheme.primary, strokeWidth: 3),
          const SizedBox(height: 16),
          const Text('Loading classes...', style: TextStyle(color: _AppTheme.textMid, fontSize: 14)),
          const Spacer(),
        ],
      );
    }

    if (_errorMessage != null) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(32),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(Icons.error_outline_rounded, size: 60, color: _AppTheme.danger),
              const SizedBox(height: 16),
              Text(_errorMessage!, textAlign: TextAlign.center, style: const TextStyle(color: _AppTheme.textMid)),
              const SizedBox(height: 20),
              ElevatedButton(
                onPressed: _loadClasses,
                style: ElevatedButton.styleFrom(backgroundColor: _AppTheme.primary),
                child: const Text('Retry', style: TextStyle(color: Colors.white)),
              ),
            ],
          ),
        ),
      );
    }

    return RefreshIndicator(
      onRefresh: _loadClasses,
      color: _AppTheme.primary,
      child: CustomScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        slivers: [
          // ── Header ──
          SliverToBoxAdapter(
            child: Container(
              padding: const EdgeInsets.fromLTRB(24, 56, 24, 24),
              decoration: const BoxDecoration(
                gradient: LinearGradient(
                  colors: [_AppTheme.primary, _AppTheme.primaryDark],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                ),
                borderRadius: BorderRadius.only(
                  bottomLeft: Radius.circular(32),
                  bottomRight: Radius.circular(32),
                ),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'My Classes',
                    style: TextStyle(color: Colors.white, fontSize: 26, fontWeight: FontWeight.bold, letterSpacing: -0.5),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    '${_classes.length} class${_classes.length == 1 ? '' : 'es'} enrolled',
                    style: const TextStyle(color: Colors.white70, fontSize: 13),
                  ),
                  const SizedBox(height: 20),
                  TextField(
                    controller: _searchController,
                    style: const TextStyle(fontSize: 14, color: _AppTheme.textDark),
                    decoration: InputDecoration(
                      hintText: 'Search classes or teacher...',
                      hintStyle: TextStyle(color: Colors.grey.shade400, fontSize: 14),
                      prefixIcon: const Icon(Icons.search_rounded, color: _AppTheme.primary, size: 20),
                      suffixIcon: _searchController.text.isNotEmpty
                          ? IconButton(
                              icon: const Icon(Icons.clear_rounded, size: 18),
                              color: _AppTheme.textMid,
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
                            decoration: BoxDecoration(color: _AppTheme.primaryLight, shape: BoxShape.circle),
                            child: Icon(Icons.class_outlined, size: 48, color: _AppTheme.primary.withOpacity(0.5)),
                          ),
                          const SizedBox(height: 16),
                          const Text('No classes yet.', style: TextStyle(color: _AppTheme.textMid, fontSize: 16, fontWeight: FontWeight.w500)),
                          const SizedBox(height: 4),
                          const Text('Ask your teacher for a class code\nand tap "Join Class"!', textAlign: TextAlign.center, style: TextStyle(color: _AppTheme.textLight, fontSize: 13)),
                        ],
                      ),
                    ),
                  ),
                )
              : _filtered.isEmpty
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
                                decoration: BoxDecoration(color: _AppTheme.primaryLight, shape: BoxShape.circle),
                                child: Icon(Icons.search_off_rounded, size: 48, color: _AppTheme.primary.withOpacity(0.5)),
                              ),
                              const SizedBox(height: 16),
                              const Text('No classes found.', style: TextStyle(color: _AppTheme.textMid, fontSize: 16, fontWeight: FontWeight.w500)),
                            ],
                          ),
                        ),
                      ),
                    )
                  : SliverPadding(
                      padding: const EdgeInsets.fromLTRB(20, 16, 20, 100),
                      sliver: SliverList(
                        delegate: SliverChildBuilderDelegate(
                          (context, index) {
                            final cls = Map<String, dynamic>.from(_filtered[index]);
                            return _buildClassCard(cls);
                          },
                          childCount: _filtered.length,
                        ),
                      ),
                    ),
        ],
      ),
    );
  }

  Widget _buildClassCard(Map<String, dynamic> cls) {
    // Generate a consistent color per class based on name
    final colors = [
      const Color(0xFF6C63FF),
      const Color(0xFF00C9A7),
      const Color(0xFFFF6B6B),
      const Color(0xFFF59E0B),
      const Color(0xFF3B82F6),
      const Color(0xFF8B5CF6),
    ];
    final colorIndex = cls['name'].toString().codeUnitAt(0) % colors.length;
    final classColor = colors[colorIndex];

    return Container(
      margin: const EdgeInsets.only(bottom: 14),
      decoration: _AppTheme.cardDecoration,
      child: InkWell(
        borderRadius: BorderRadius.circular(16),
        onTap: () => Navigator.pushNamed(
          context,
          '/student-class-quizzes',
          arguments: {'class_id': cls['id'], 'class_name': cls['name']},
        ),
        child: Column(
          children: [
            // Color band header
            Container(
              width: double.infinity,
              padding: const EdgeInsets.fromLTRB(16, 14, 16, 14),
              decoration: BoxDecoration(
                color: classColor.withOpacity(0.1),
                borderRadius: const BorderRadius.only(
                  topLeft: Radius.circular(16),
                  topRight: Radius.circular(16),
                ),
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
                        cls['name'].toString()[0].toUpperCase(),
                        style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 20),
                      ),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          cls['name'],
                          style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: _AppTheme.textDark),
                        ),
                        if (cls['description'] != null && (cls['description'] as String).isNotEmpty)
                          Text(
                            cls['description'],
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: const TextStyle(fontSize: 12, color: _AppTheme.textMid),
                          ),
                      ],
                    ),
                  ),
                  Icon(Icons.chevron_right_rounded, color: classColor),
                ],
              ),
            ),

            // Body
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 12, 16, 14),
              child: Column(
                children: [
                  Row(
                    children: [
                      Icon(Icons.school_rounded, size: 15, color: _AppTheme.textLight),
                      const SizedBox(width: 6),
                      Text(cls['teacher_name'], style: const TextStyle(fontSize: 13, color: _AppTheme.textMid)),
                      const Spacer(),
                      _buildClassMeta(Icons.people_rounded, '${cls['students_count']}'),
                      const SizedBox(width: 14),
                      _buildClassMeta(Icons.quiz_rounded, '${cls['quizzes_count']} quizzes'),
                    ],
                  ),
                  const SizedBox(height: 10),
                  Divider(color: Colors.grey.shade100, height: 1),
                  const SizedBox(height: 10),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      GestureDetector(
                        onTap: () => _leaveClass(cls),
                        child: Container(
                          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                          decoration: BoxDecoration(
                            border: Border.all(color: _AppTheme.danger.withOpacity(0.3)),
                            borderRadius: BorderRadius.circular(20),
                          ),
                          child: Row(
                            children: [
                              Icon(Icons.exit_to_app_rounded, size: 13, color: _AppTheme.danger),
                              const SizedBox(width: 4),
                              Text('Leave', style: TextStyle(color: _AppTheme.danger, fontSize: 12, fontWeight: FontWeight.w600)),
                            ],
                          ),
                        ),
                      ),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 6),
                        decoration: BoxDecoration(
                          color: classColor,
                          borderRadius: BorderRadius.circular(20),
                        ),
                        child: Row(
                          children: const [
                            Text('View Quizzes', style: TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.bold)),
                            SizedBox(width: 4),
                            Icon(Icons.arrow_forward_rounded, size: 13, color: Colors.white),
                          ],
                        ),
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

  Widget _buildClassMeta(IconData icon, String label) {
    return Row(
      children: [
        Icon(icon, size: 13, color: _AppTheme.textLight),
        const SizedBox(width: 4),
        Text(label, style: const TextStyle(fontSize: 12, color: _AppTheme.textMid)),
      ],
    );
  }
}