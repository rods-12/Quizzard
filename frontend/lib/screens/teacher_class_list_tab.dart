import 'package:flutter/material.dart';
import '../services/auth_service.dart';

class TeacherClassListTab extends StatefulWidget {
  final VoidCallback onRefresh;

  const TeacherClassListTab({super.key, required this.onRefresh});

  @override
  State<TeacherClassListTab> createState() => _TeacherClassListTabState();
}

class _TeacherClassListTabState extends State<TeacherClassListTab> {
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

    final result = await AuthService.authGet('/classes');

    setState(() {
      _isLoading = false;
      if (result['success']) {
        _classes = result['data']['classes'] as List;
      } else {
        _errorMessage = result['message'];
      }
    });
  }

  Future<void> _createClass() async {
    final nameController = TextEditingController();
    final descController = TextEditingController();

    final confirm = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: const Text('Create New Class', style: TextStyle(fontWeight: FontWeight.bold)),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            TextField(
              controller: nameController,
              maxLength: 100,
              decoration: InputDecoration(
                labelText: 'Class Name',
                hintText: 'e.g. Math 101',
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                focusedBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(12),
                  borderSide: const BorderSide(color: Color(0xFF4CAF50), width: 2),
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
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                focusedBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(12),
                  borderSide: const BorderSide(color: Color(0xFF4CAF50), width: 2),
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
              backgroundColor: const Color(0xFF4CAF50),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
            ),
            child: const Text('Create', style: TextStyle(color: Colors.white)),
          ),
        ],
      ),
    );

    if (confirm != true) return;
    if (nameController.text.trim().isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Class name is required!'),
          backgroundColor: Colors.orange,
        ),
      );
      return;
    }

    final result = await AuthService.authPost('/classes', {
      'name': nameController.text.trim(),
      'description': descController.text.trim(),
    });

    if (result['success']) {
      _loadClasses();
      widget.onRefresh();
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Class created successfully!'),
          backgroundColor: Color(0xFF4CAF50),
          behavior: SnackBarBehavior.floating,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.all(Radius.circular(12))),
          margin: EdgeInsets.all(16),
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

  Future<void> _editClass(Map<String, dynamic> cls) async {
    final nameController = TextEditingController(text: cls['name']);
    final descController = TextEditingController(text: cls['description'] ?? '');

    final confirm = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: const Text('Edit Class', style: TextStyle(fontWeight: FontWeight.bold)),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            TextField(
              controller: nameController,
              maxLength: 100,
              decoration: InputDecoration(
                labelText: 'Class Name',
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                focusedBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(12),
                  borderSide: const BorderSide(color: Color(0xFF4CAF50), width: 2),
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
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                focusedBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(12),
                  borderSide: const BorderSide(color: Color(0xFF4CAF50), width: 2),
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
              backgroundColor: const Color(0xFF4CAF50),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
            ),
            child: const Text('Save', style: TextStyle(color: Colors.white)),
          ),
        ],
      ),
    );

    if (confirm != true) return;

    final result = await AuthService.authPut(
      '/classes/${cls['id']}',
      {
        'name': nameController.text.trim(),
        'description': descController.text.trim(),
      },
    );

    if (result['success']) {
      _loadClasses();
      widget.onRefresh();
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Class updated successfully!'),
          backgroundColor: Color(0xFF4CAF50),
          behavior: SnackBarBehavior.floating,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.all(Radius.circular(12))),
          margin: EdgeInsets.all(16),
        ),
      );
    }
  }

  Future<void> _deleteClass(Map<String, dynamic> cls) async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: const Text('Delete Class', style: TextStyle(fontWeight: FontWeight.bold)),
        content: Text('Are you sure you want to delete "${cls['name']}"? This cannot be undone.'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Cancel'),
          ),
          ElevatedButton(
            onPressed: () => Navigator.pop(context, true),
            style: ElevatedButton.styleFrom(
              backgroundColor: Colors.red,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
            ),
            child: const Text('Delete', style: TextStyle(color: Colors.white)),
          ),
        ],
      ),
    );

    if (confirm != true) return;

    final result = await AuthService.authDelete('/classes/${cls['id']}');

    if (result['success']) {
      _loadClasses();
      widget.onRefresh();
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Class deleted successfully!'),
          backgroundColor: Color(0xFF4CAF50),
          behavior: SnackBarBehavior.floating,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.all(Radius.circular(12))),
          margin: EdgeInsets.all(16),
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF4F6FB),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: _createClass,
        backgroundColor: const Color(0xFF4CAF50),
        icon: const Icon(Icons.add_rounded, color: Colors.white),
        label: const Text('New Class', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
        elevation: 4,
      ),
      body: _buildBody(),
    );
  }

  Widget _buildBody() {
    if (_isLoading) {
      return Column(
        children: [
          Container(
            width: double.infinity,
            padding: const EdgeInsets.fromLTRB(24, 56, 24, 32),
            decoration: const BoxDecoration(
              gradient: LinearGradient(
                colors: [Color(0xFF4CAF50), Color(0xFF388E3C)],
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
                Container(width: 140, height: 24, decoration: BoxDecoration(color: Colors.white.withOpacity(0.4), borderRadius: BorderRadius.circular(8))),
                const SizedBox(height: 10),
                Container(width: 100, height: 14, decoration: BoxDecoration(color: Colors.white.withOpacity(0.25), borderRadius: BorderRadius.circular(8))),
                const SizedBox(height: 24),
                Container(height: 46, decoration: BoxDecoration(color: Colors.white.withOpacity(0.2), borderRadius: BorderRadius.circular(14))),
              ],
            ),
          ),
          const Spacer(),
          const CircularProgressIndicator(color: Color(0xFF4CAF50), strokeWidth: 3),
          const SizedBox(height: 16),
          const Text('Loading classes...', style: TextStyle(color: Colors.grey, fontSize: 14)),
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
                  color: Colors.red.withOpacity(0.1),
                  shape: BoxShape.circle,
                ),
                child: Icon(Icons.error_outline_rounded, size: 48, color: Colors.red),
              ),
              const SizedBox(height: 16),
              Text(_errorMessage!, textAlign: TextAlign.center, style: const TextStyle(color: Colors.grey)),
              const SizedBox(height: 20),
              ElevatedButton(
                onPressed: _loadClasses,
                style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFF4CAF50)),
                child: const Text('Retry', style: TextStyle(color: Colors.white)),
              ),
            ],
          ),
        ),
      );
    }

    return RefreshIndicator(
      onRefresh: _loadClasses,
      color: const Color(0xFF4CAF50),
      child: CustomScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        slivers: [
          // ── Header ──
          SliverToBoxAdapter(
            child: Container(
              padding: const EdgeInsets.fromLTRB(24, 56, 24, 24),
              decoration: const BoxDecoration(
                gradient: LinearGradient(
                  colors: [Color(0xFF4CAF50), Color(0xFF388E3C)],
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
                    '${_classes.length} class${_classes.length == 1 ? '' : 'es'} created',
                    style: const TextStyle(color: Colors.white70, fontSize: 13),
                  ),
                  const SizedBox(height: 20),
                  // Search could go here in future
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
                            decoration: BoxDecoration(
                              color: const Color(0xFF4CAF50).withOpacity(0.1),
                              shape: BoxShape.circle,
                            ),
                            child: Icon(Icons.class_outlined, size: 48, color: const Color(0xFF4CAF50).withOpacity(0.5)),
                          ),
                          const SizedBox(height: 16),
                          const Text('No classes yet.', style: TextStyle(color: Colors.grey, fontSize: 16, fontWeight: FontWeight.w500)),
                          const SizedBox(height: 4),
                          const Text('Tap "New Class" to create your first class!', textAlign: TextAlign.center, style: TextStyle(color: Colors.grey, fontSize: 13)),
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
                        final cls = Map<String, dynamic>.from(_classes[index]);
                        return _buildClassCard(cls);
                      },
                      childCount: _classes.length,
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
      const Color(0xFF4CAF50),
      const Color(0xFF6C63FF),
      const Color(0xFFFF6B6B),
      const Color(0xFFF59E0B),
      const Color(0xFF3B82F6),
      const Color(0xFF8B5CF6),
    ];
    final colorIndex = cls['name'].toString().codeUnitAt(0) % colors.length;
    final classColor = colors[colorIndex];

    return Container(
      margin: const EdgeInsets.only(bottom: 14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.05),
            blurRadius: 12,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: InkWell(
        borderRadius: BorderRadius.circular(16),
        onTap: () async {
          await Navigator.pushNamed(
            context,
            '/class-detail',
            arguments: {
              'class_id': cls['id'],
              'class_name': cls['name'],
            },
          );
          _loadClasses();
        },
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
                          style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: Color(0xFF1A1D2E)),
                        ),
                        if (cls['description'] != null && (cls['description'] as String).isNotEmpty)
                          Text(
                            cls['description'],
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: const TextStyle(fontSize: 12, color: Color(0xFF6B7080)),
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
                  // Class code chip
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                    decoration: BoxDecoration(
                      color: classColor.withOpacity(0.1),
                      borderRadius: BorderRadius.circular(8),
                      border: Border.all(color: classColor.withOpacity(0.3)),
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Icon(Icons.key_rounded, size: 14, color: classColor),
                        const SizedBox(width: 6),
                        Text(
                          'Code: ${cls['class_code']}',
                          style: TextStyle(
                            color: classColor,
                            fontWeight: FontWeight.bold,
                            fontSize: 13,
                            letterSpacing: 1.5,
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 10),

                  Row(
                    children: [
                      Icon(Icons.school_rounded, size: 15, color: Colors.grey),
                      const SizedBox(width: 6),
                      Text(cls['teacher_name'] ?? 'You', style: const TextStyle(fontSize: 13, color: Color(0xFF6B7080))),
                      const Spacer(),
                      _buildMeta(Icons.people_rounded, '${cls['students_count']}'),
                      const SizedBox(width: 14),
                      _buildMeta(Icons.quiz_rounded, '${cls['quizzes_count']} quizzes'),
                    ],
                  ),
                  const SizedBox(height: 10),
                  Divider(color: Colors.grey.shade100, height: 1),
                  const SizedBox(height: 10),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.end,
                    children: [
                      GestureDetector(
                        onTap: () => _editClass(cls),
                        child: Container(
                          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                          decoration: BoxDecoration(
                            border: Border.all(color: const Color(0xFF6C63FF).withOpacity(0.3)),
                            borderRadius: BorderRadius.circular(20),
                          ),
                          child: Row(
                            children: const [
                              Icon(Icons.edit_rounded, size: 13, color: Color(0xFF6C63FF)),
                              SizedBox(width: 4),
                              Text('Edit', style: TextStyle(color: Color(0xFF6C63FF), fontSize: 12, fontWeight: FontWeight.w600)),
                            ],
                          ),
                        ),
                      ),
                      const SizedBox(width: 8),
                      GestureDetector(
                        onTap: () => _deleteClass(cls),
                        child: Container(
                          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                          decoration: BoxDecoration(
                            border: Border.all(color: Colors.red.withOpacity(0.3)),
                            borderRadius: BorderRadius.circular(20),
                          ),
                          child: Row(
                            children: const [
                              Icon(Icons.delete_rounded, size: 13, color: Colors.red),
                              SizedBox(width: 4),
                              Text('Delete', style: TextStyle(color: Colors.red, fontSize: 12, fontWeight: FontWeight.w600)),
                            ],
                          ),
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

  Widget _buildMeta(IconData icon, String label) {
    return Row(
      children: [
        Icon(icon, size: 13, color: Colors.grey),
        const SizedBox(width: 4),
        Text(label, style: const TextStyle(fontSize: 12, color: Color(0xFF6B7080))),
      ],
    );
  }
}