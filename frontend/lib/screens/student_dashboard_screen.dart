import 'package:flutter/material.dart';
import '../services/auth_service.dart';
import '../widgets/profile_widget.dart';
import 'student_quizzes_tab.dart';

class StudentDashboardScreen extends StatefulWidget {
  const StudentDashboardScreen({super.key});

  @override
  State<StudentDashboardScreen> createState() => _StudentDashboardScreenState();
}

class _StudentDashboardScreenState extends State<StudentDashboardScreen> {
  bool _isLoading = true;
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
    await AuthService.logout();
    if (!mounted) return;
    Navigator.pushReplacementNamed(context, '/login');
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF5F5F5),
      appBar: _buildAppBar(),
      body: _buildBody(),
      bottomNavigationBar: BottomNavigationBar(
        currentIndex: _currentIndex,
        onTap: (index) => setState(() => _currentIndex = index),
        selectedItemColor: const Color(0xFF6C63FF),
        unselectedItemColor: Colors.grey,
        type: BottomNavigationBarType.fixed,
        items: const [
          BottomNavigationBarItem(
            icon: Icon(Icons.home),
            label: 'Home',
          ),
          BottomNavigationBarItem(
            icon: Icon(Icons.quiz),
            label: 'Quizzes',
          ),
          BottomNavigationBarItem(
            icon: Icon(Icons.class_),
            label: 'Classes',
          ),
          BottomNavigationBarItem(
            icon: Icon(Icons.person),
            label: 'Profile',
          ),
        ],
      ),
    );
  }

  Widget _buildBody() {
    if (_isLoading) {
      return const Center(
        child: CircularProgressIndicator(color: Color(0xFF6C63FF)),
      );
    }

    if (_errorMessage != null) {
      return Center(
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
              onPressed: _loadDashboard,
              child: const Text('Retry'),
            ),
          ],
        ),
      );
    }



    Widget _buildClassesTab() {
      return _StudentClassesTab(
        onRefresh: _loadDashboard,
      );
    }

    switch (_currentIndex) {
      case 0:
        return _buildHomeTab();
      case 1:
        return _buildQuizzesTab();
      case 2:
        return _buildClassesTab();
      case 3:
        return _buildProfileTab();
      default:
        return _buildHomeTab();
    }


  }

  // ─── HOME TAB ────────────────────────────────────────────
  Widget _buildHomeTab() {
    final student = _dashboardData!['student'];
    final quizzes = _dashboardData!['available_quizzes'] as List;
    final scores = _dashboardData!['recent_scores'] as List;
    final totalTaken = _dashboardData!['total_quizzes_taken'];

    return RefreshIndicator(
      onRefresh: _loadDashboard,
      color: const Color(0xFF6C63FF),
      child: SingleChildScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Header
            Container(
              width: double.infinity,
              padding: const EdgeInsets.fromLTRB(20, 20, 20, 30),
              decoration: const BoxDecoration(
                color: Color(0xFF6C63FF),
                borderRadius: BorderRadius.only(
                  bottomLeft: Radius.circular(30),
                  bottomRight: Radius.circular(30),
                ),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Column(
                  //   crossAxisAlignment: CrossAxisAlignment.start,
                  //   children: [
                  //     const Text(
                  //       'Welcome back,',
                  //       style: TextStyle(
                  //           color: Colors.white70, fontSize: 14),
                  //     ),
                  //     Text(
                  //       student['name'],
                  //       style: const TextStyle(
                  //         color: Colors.white,
                  //         fontSize: 24,
                  //         fontWeight: FontWeight.bold,
                  //       ),
                  //     ),
                  //   ],
                  // ),
                  const SizedBox(height: 20),
                  // Stats row
                  Row(
                    children: [
                      _buildStatCard(
                        icon: Icons.quiz,
                        label: 'Available',
                        value: '${quizzes.length}',
                      ),
                      const SizedBox(width: 12),
                      _buildStatCard(
                        icon: Icons.check_circle,
                        label: 'Completed',
                        value: '$totalTaken',
                      ),
                      const SizedBox(width: 12),
                      _buildStatCard(
                        icon: Icons.star,
                        label: 'Avg Score',
                        value: scores.isEmpty
                            ? 'N/A'
                            : '${(scores.map((s) => s['percentage'] as int).reduce((a, b) => a + b) / scores.length).round()}%',
                      ),
                    ],
                  ),
                ],
              ),
            ),

            const SizedBox(height: 24),

            // Recent Scores
            if (scores.isNotEmpty) ...[
              const Padding(
                padding: EdgeInsets.symmetric(horizontal: 20),
                child: Text(
                  'Recent Scores',
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                    color: Color(0xFF333333),
                  ),
                ),
              ),
              const SizedBox(height: 12),
              SizedBox(
                height: 100,
                child: ListView.builder(
                  scrollDirection: Axis.horizontal,
                  padding: const EdgeInsets.symmetric(horizontal: 20),
                  itemCount: scores.length,
                  itemBuilder: (context, index) {
                    final score = scores[index];
                    return _buildScoreCard(score);
                  },
                ),
              ),
              const SizedBox(height: 24),
            ],

            // Available Quizzes
            const Padding(
              padding: EdgeInsets.symmetric(horizontal: 20),
              child: Text(
                'Available Quizzes',
                style: TextStyle(
                  fontSize: 18,
                  fontWeight: FontWeight.bold,
                  color: Color(0xFF333333),
                ),
              ),
            ),
            const SizedBox(height: 12),
            quizzes.isEmpty
                ? _buildEmptyState(
                    icon: Icons.quiz_outlined,
                    message: 'No quizzes available yet.\nCheck back later!',
                  )
                : ListView.builder(
                    shrinkWrap: true,
                    physics: const NeverScrollableScrollPhysics(),
                    padding: const EdgeInsets.symmetric(horizontal: 20),
                    itemCount: quizzes.length,
                    itemBuilder: (context, index) {
                      return _buildQuizCard(quizzes[index]);
                    },
                  ),
            const SizedBox(height: 20),
          ],
        ),
      ),
    );
  }

  // ─── QUIZZES TAB ─────────────────────────────────────────
  Widget _buildQuizzesTab() {
    return const StudentQuizzesTab();
  }

  // ─── SCORES TAB ──────────────────────────────────────────
  Widget _buildScoresTab() {
    final scores = _dashboardData!['recent_scores'] as List;
    return scores.isEmpty
        ? _buildEmptyState(
            icon: Icons.bar_chart,
            message: 'No scores yet.\nTake a quiz to see your results!',
          )
        : ListView.builder(
            padding: const EdgeInsets.all(20),
            itemCount: scores.length,
            itemBuilder: (context, index) {
              final score = scores[index];
              final percentage = score['percentage'] as int;
              return Card(
                margin: const EdgeInsets.only(bottom: 12),
                shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(16)),
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Row(
                    children: [
                      Container(
                        width: 60,
                        height: 60,
                        decoration: BoxDecoration(
                          color: _getScoreColor(percentage)
                              .withOpacity(0.1),
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: Center(
                          child: Text(
                            '$percentage%',
                            style: TextStyle(
                              color: _getScoreColor(percentage),
                              fontWeight: FontWeight.bold,
                              fontSize: 14,
                            ),
                          ),
                        ),
                      ),
                      const SizedBox(width: 16),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              score['quiz_title'],
                              style: const TextStyle(
                                fontWeight: FontWeight.bold,
                                fontSize: 15,
                              ),
                            ),
                            const SizedBox(height: 4),
                            Text(
                              '${score['score']} / ${score['total_points']} points',
                              style: const TextStyle(
                                  color: Colors.grey, fontSize: 13),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
              );
            },
          );
  }

  // ─── PROFILE TAB ─────────────────────────────────────────
  Widget _buildProfileTab() {
      return ProfileWidget(onLogout: _logout);
  }           
  // ─── HELPER WIDGETS ──────────────────────────────────────

  Widget _buildStatCard({
    required IconData icon,
    required String label,
    required String value,
  }) {
    return Expanded(
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 8),
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
              style:
                  const TextStyle(color: Colors.white70, fontSize: 11),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildScoreCard(Map<String, dynamic> score) {
    final percentage = score['percentage'] as int;
    return Container(
      width: 150,
      margin: const EdgeInsets.only(right: 12),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.05),
            blurRadius: 10,
          ),
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
            style: const TextStyle(
                fontWeight: FontWeight.bold, fontSize: 13),
          ),
          Row(
            children: [
              Icon(Icons.star,
                  color: _getScoreColor(percentage), size: 16),
              const SizedBox(width: 4),
              Text(
                '$percentage%',
                style: TextStyle(
                  color: _getScoreColor(percentage),
                  fontWeight: FontWeight.bold,
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
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      shape:
          RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      elevation: 2,
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Row(
          children: [
            Container(
              width: 50,
              height: 50,
              decoration: BoxDecoration(
                color: const Color(0xFF6C63FF).withOpacity(0.1),
                borderRadius: BorderRadius.circular(12),
              ),
              child: const Icon(Icons.quiz,
                  color: Color(0xFF6C63FF), size: 28),
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
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    'By ${quiz['teacher_name']}',
                    style: const TextStyle(
                        color: Colors.grey, fontSize: 12),
                  ),
                  if (alreadyTaken) ...[
                    const SizedBox(height: 4),
                    Text(
                      'Score: ${quiz['score']}/${quiz['total_points']}',
                      style: const TextStyle(
                        color: Color(0xFF6C63FF),
                        fontSize: 12,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ],
                ],
              ),
            ),
            GestureDetector(
              onTap: alreadyTaken
                  ? null
                  : () => Navigator.pushNamed(
                        context,
                        '/quiz-taking',
                        arguments: {
                          'quiz_id': quiz['id'],
                          'quiz_title': quiz['title'],
                        },
                      ),
              child: Container(
                padding: const EdgeInsets.symmetric(
                    horizontal: 12, vertical: 6),
                decoration: BoxDecoration(
                  color: alreadyTaken
                      ? Colors.green.withOpacity(0.1)
                      : const Color(0xFF6C63FF),
                  borderRadius: BorderRadius.circular(20),
                ),
                child: Text(
                  alreadyTaken ? 'Done' : 'Take',
                  style: TextStyle(
                    color: alreadyTaken ? Colors.green : Colors.white,
                    fontWeight: FontWeight.bold,
                    fontSize: 12,
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildEmptyState(
      {required IconData icon, required String message}) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(40),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(icon, size: 80, color: Colors.grey.shade300),
            const SizedBox(height: 16),
            Text(
              message,
              textAlign: TextAlign.center,
              style:
                  TextStyle(color: Colors.grey.shade500, fontSize: 16),
            ),
          ],
        ),
      ),
    );
  }

  Color _getScoreColor(int percentage) {
    if (percentage >= 80) return Colors.green;
    if (percentage >= 60) return Colors.orange;
    return Colors.red;
  }


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

  PreferredSizeWidget _buildAppBar() {
    final student = _dashboardData?['student'];
    final isHome = _currentIndex == 0;
    final bgColor = const Color(0xFF6C63FF);
    final fgColor = Colors.white;

    return AppBar(
      automaticallyImplyLeading: false,
      backgroundColor: bgColor,
      elevation: isHome ? 0 : 1,
      title: _isLoading || student == null
          ? Text(
              _getTabTitle(),
              style: TextStyle(
                color: fgColor,
                fontWeight: FontWeight.bold,
                fontSize: 20,
              ),
            )
          : Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisSize: MainAxisSize.min,
              children: [
                Text(
                  _getTabTitle(),
                  style: TextStyle(
                    color: fgColor,
                    fontSize: 12,
                    fontWeight: FontWeight.w500,
                  ),
                ),
                Text(
                  'Hi, ${student['first_name'] ?? student['name']}!',
                  style: TextStyle(
                    color: fgColor,
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ],
            ),
      actions: [
        if (isHome)
          IconButton(
            icon: const Icon(Icons.logout_outlined),
            color: Colors.white,
            onPressed: _logout,
            tooltip: 'Log Out',
          )
        else if (!_isLoading && student != null)
          Padding(
            padding: const EdgeInsets.only(right: 16),
            child: CircleAvatar(
              radius: 18,
              backgroundImage: student['profile_picture'] != null
                  ? NetworkImage(student['profile_picture'])
                  : null,
              backgroundColor: const Color(0xFF6C63FF).withOpacity(0.1),
              child: student['profile_picture'] == null
                  ? Text(
                      _getInitials(student['name']),
                      style: const TextStyle(
                        color: Color(0xFF6C63FF),
                        fontWeight: FontWeight.bold,
                        fontSize: 14,
                      ),
                    )
                  : null,
            ),
          ),
      ],
    );
  }




}



class _StudentClassesTab extends StatefulWidget {
  final VoidCallback onRefresh;

  const _StudentClassesTab({required this.onRefresh});

  @override
  State<_StudentClassesTab> createState() => _StudentClassesTabState();
}

class _StudentClassesTabState extends State<_StudentClassesTab> {
  bool _isLoading = true;
  String? _errorMessage;
  List<dynamic> _classes = [];

  @override
  void initState() {
    super.initState();
    _loadClasses();
  }

  Future<void> _loadClasses() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    final result = await AuthService.authGet('/student/classes');

    setState(() {
      _isLoading = false;
      if (result['success']) {
        _classes = result['data']['classes'] as List;
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
        shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(16)),
        title: const Text('Join a Class'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Text(
              'Enter the class code provided by your teacher.',
              style: TextStyle(color: Colors.grey),
            ),
            const SizedBox(height: 16),
            TextField(
              controller: codeController,
              textCapitalization: TextCapitalization.characters,
              decoration: InputDecoration(
                labelText: 'Class Code',
                hintText: 'e.g. Y9ZJAV',
                prefixIcon: const Icon(Icons.key,
                    color: Color(0xFF6C63FF)),
                border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(12)),
                focusedBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(12),
                  borderSide: const BorderSide(
                      color: Color(0xFF6C63FF), width: 2),
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
              backgroundColor: const Color(0xFF6C63FF),
            ),
            child: const Text('Join',
                style: TextStyle(color: Colors.white)),
          ),
        ],
      ),
    );

    if (confirm != true) return;
    if (codeController.text.trim().isEmpty) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Please enter a class code.'),
          backgroundColor: Colors.red,
        ),
      );
      return;
    }

    final result = await AuthService.authPost(
      '/student/classes/join',
      {'class_code': codeController.text.trim()},
    );

    if (result['success']) {
      _loadClasses();
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(result['data']['message'] ??
              'Successfully joined the class!'),
          backgroundColor: Colors.green,
        ),
      );
    } else {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(result['message']),
          backgroundColor: Colors.red,
        ),
      );
    }
  }

  Future<void> _leaveClass(Map<String, dynamic> cls) async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(16)),
        title: const Text('Leave Class'),
        content: Text(
            'Are you sure you want to leave "${cls['name']}"?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Cancel'),
          ),
          ElevatedButton(
            onPressed: () => Navigator.pop(context, true),
            style: ElevatedButton.styleFrom(
                backgroundColor: Colors.red),
            child: const Text('Leave',
                style: TextStyle(color: Colors.white)),
          ),
        ],
      ),
    );

    if (confirm != true) return;

    final result = await AuthService.authDelete(
      '/student/classes/${cls['id']}/leave',
    );

    if (result['success']) {
      _loadClasses();
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('You have left the class.'),
          backgroundColor: Colors.green,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF5F5F5),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: _joinClass,
        backgroundColor: const Color(0xFF6C63FF),
        icon: const Icon(Icons.add, color: Colors.white),
        label: const Text('Join Class',
            style: TextStyle(color: Colors.white)),
      ),
      body: _isLoading
          ? const Center(
              child: CircularProgressIndicator(
                  color: Color(0xFF6C63FF)),
            )
          : _errorMessage != null
              ? Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      const Icon(Icons.error_outline,
                          size: 60, color: Colors.red),
                      const SizedBox(height: 16),
                      Text(_errorMessage!,
                          textAlign: TextAlign.center,
                          style:
                              const TextStyle(color: Colors.red)),
                      const SizedBox(height: 16),
                      ElevatedButton(
                        onPressed: _loadClasses,
                        child: const Text('Retry'),
                      ),
                    ],
                  ),
                )
              : _classes.isEmpty
                  ? Center(
                      child: Column(
                        mainAxisAlignment:
                            MainAxisAlignment.center,
                        children: [
                          Icon(Icons.class_outlined,
                              size: 80,
                              color: Colors.grey.shade400),
                          const SizedBox(height: 16),
                          Text(
                            'No classes yet.',
                            style: TextStyle(
                                fontSize: 18,
                                color: Colors.grey.shade600),
                          ),
                          const SizedBox(height: 8),
                          Text(
                            'Ask your teacher for a class code\nand tap "Join Class"!',
                            textAlign: TextAlign.center,
                            style: TextStyle(
                                color: Colors.grey.shade500),
                          ),
                        ],
                      ),
                    )
                  : RefreshIndicator(
                      onRefresh: _loadClasses,
                      color: const Color(0xFF6C63FF),
                      child: ListView.builder(
                        padding: const EdgeInsets.fromLTRB(
                            16, 16, 16, 80),
                        itemCount: _classes.length,
                        itemBuilder: (context, index) {
                          final cls = Map<String, dynamic>.from(
                              _classes[index]);
                          return _buildClassCard(cls);
                        },
                      ),
                    ),
    );
  }

  Widget _buildClassCard(Map<String, dynamic> cls) {
    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(16)),
      elevation: 3,
      child: InkWell(
        borderRadius: BorderRadius.circular(16),
        onTap: () => Navigator.pushNamed(
          context,
          '/student-class-quizzes',
          arguments: {
            'class_id': cls['id'],
            'class_name': cls['name'],
          },
        ),
        child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Class name and icon
            Row(
              children: [
                Container(
                  width: 48,
                  height: 48,
                  decoration: BoxDecoration(
                    color: const Color(0xFF6C63FF)
                        .withOpacity(0.1),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: const Icon(Icons.class_,
                      color: Color(0xFF6C63FF), size: 28),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        cls['name'],
                        style: const TextStyle(
                          fontSize: 17,
                          fontWeight: FontWeight.bold,
                          color: Color(0xFF333333),
                        ),
                      ),
                      if (cls['description'] != null &&
                          cls['description'].isNotEmpty)
                        Text(
                          cls['description'],
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: TextStyle(
                              fontSize: 13,
                              color: Colors.grey.shade600),
                        ),
                    ],
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),

            // Teacher info
            Row(
              children: [
                Icon(Icons.school,
                    size: 16, color: Colors.grey.shade500),
                const SizedBox(width: 6),
                Text(
                  cls['teacher_name'],
                  style: TextStyle(
                      fontSize: 13,
                      color: Colors.grey.shade600),
                ),
              ],
            ),
            const SizedBox(height: 8),

            // Stats
            Row(
              children: [
                _buildStat(Icons.people,
                    '${cls['students_count']} students'),
                const SizedBox(width: 16),
                _buildStat(Icons.quiz,
                    '${cls['quizzes_count']} quizzes'),
              ],
            ),
            const Divider(height: 20),

            // Actions
            Row(
              mainAxisAlignment: MainAxisAlignment.end,
              children: [
                TextButton.icon(
                  onPressed: () => _leaveClass(cls),
                  icon: const Icon(Icons.exit_to_app,
                      size: 16, color: Colors.red),
                  label: const Text('Leave',
                      style: TextStyle(color: Colors.red)),
                ),
              ],
            ),
          ],
        ),
        ),
      ),
    );
  }

  Widget _buildStat(IconData icon, String label) {
    return Row(
      children: [
        Icon(icon, size: 16, color: Colors.grey),
        const SizedBox(width: 4),
        Text(label,
            style: const TextStyle(
                fontSize: 13, color: Colors.grey)),
      ],
    );
  }
}