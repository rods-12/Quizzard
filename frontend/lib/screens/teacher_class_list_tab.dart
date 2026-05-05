import 'dart:ui';
import 'package:flutter/material.dart';
import '../services/auth_service.dart';

// ─── THEME ────────────────────────────────────────────────────────────────────
class _T {
  static const Color primary = Color(0xFF2ECC71);
  static const Color primaryDark = Color(0xFF1BA35A);
  static const Color primaryLight = Color(0xFFE8F8F0);
  static const Color accent = Color(0xFF6C63FF);
  static const Color bg = Color(0xFFF4F7F5);
  static const Color surface = Colors.white;
  static const Color textDark = Color(0xFF1A2E22);
  static const Color textMid = Color(0xFF6B7580);
  static const Color textLight = Color(0xFFADB5BD);
  static const Color success = Color(0xFF22C55E);
  static const Color danger = Color(0xFFEF4444);
  static const Color warning = Color(0xFFF59E0B);

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

class TeacherClassListTab extends StatefulWidget {
  final VoidCallback onRefresh;

  const TeacherClassListTab({super.key, required this.onRefresh});

  @override
  State<TeacherClassListTab> createState() => _TeacherClassListTabState();
}

class _TeacherClassListTabState extends State<TeacherClassListTab> {
  bool _isLoading = true;
  bool _isActionLoading = false;
  String? _errorMessage;
  List<dynamic> _classes = [];
  List<dynamic> _filtered = [];
  final TextEditingController _searchController = TextEditingController();

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
        final name = (c['name'] ?? '').toString().toLowerCase();
        final desc = (c['description'] ?? '').toString().toLowerCase();
        return query.isEmpty || name.contains(query) || desc.contains(query);
      }).toList();
    });
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
        _filtered = _classes;
      } else {
        _errorMessage = result['message'];
      }
    });
  }

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
            child: const Text('Cancel'),
          ),
          ElevatedButton(
            onPressed: () => Navigator.pop(context, true),
            style: ElevatedButton.styleFrom(
              backgroundColor: _T.primary,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
            ),
            child: const Text('Create', style: TextStyle(color: Colors.white)),
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
      _loadClasses();
      widget.onRefresh();
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
        title: const Text('Edit Class', style: TextStyle(fontWeight: FontWeight.bold)),
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
            child: const Text('Cancel'),
          ),
          ElevatedButton(
            onPressed: () => Navigator.pop(context, true),
            style: ElevatedButton.styleFrom(
              backgroundColor: _T.primary,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
            ),
            child: const Text('Save', style: TextStyle(color: Colors.white)),
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
      _loadClasses();
      widget.onRefresh();
      _showSnackbar('Class updated successfully!');
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
              backgroundColor: _T.danger,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
            ),
            child: const Text('Delete', style: TextStyle(color: Colors.white)),
          ),
        ],
      ),
    );

    if (confirm != true) return;

    setState(() => _isActionLoading = true);
    final result = await AuthService.authDelete('/classes/${cls['id']}');
    if (mounted) setState(() => _isActionLoading = false);

    if (result['success']) {
      _loadClasses();
      widget.onRefresh();
      _showSnackbar('Class deleted successfully!');
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: _T.bg,
      floatingActionButton: FloatingActionButton.extended(
        onPressed: _createClass,
        backgroundColor: _T.primary,
        icon: const Icon(Icons.add_rounded, color: Colors.white),
        label: const Text('New Class', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
        elevation: 4,
      ),
      body: Stack(
        children: [
          _buildBody(),
          if (_isActionLoading)
            Positioned.fill(
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
                Container(width: 140, height: 24, decoration: BoxDecoration(color: Colors.white.withOpacity(0.4), borderRadius: BorderRadius.circular(8))),
                const SizedBox(height: 10),
                Container(width: 100, height: 14, decoration: BoxDecoration(color: Colors.white.withOpacity(0.25), borderRadius: BorderRadius.circular(8))),
                const SizedBox(height: 24),
                Container(height: 46, decoration: BoxDecoration(color: Colors.white.withOpacity(0.2), borderRadius: BorderRadius.circular(14))),
              ],
            ),
          ),
          const Spacer(),
          CircularProgressIndicator(color: _T.primary, strokeWidth: 3),
          const SizedBox(height: 16),
          const Text('Loading classes...', style: TextStyle(color: _T.textMid, fontSize: 14)),
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
                decoration: BoxDecoration(color: _T.danger.withOpacity(0.1), shape: BoxShape.circle),
                child: const Icon(Icons.error_outline_rounded, size: 48, color: _T.danger),
              ),
              const SizedBox(height: 16),
              Text(_errorMessage!, textAlign: TextAlign.center, style: const TextStyle(color: _T.textMid)),
              const SizedBox(height: 20),
              ElevatedButton(
                onPressed: _loadClasses,
                style: ElevatedButton.styleFrom(backgroundColor: _T.primary),
                child: const Text('Retry', style: TextStyle(color: Colors.white)),
              ),
            ],
          ),
        ),
      );
    }

    return RefreshIndicator(
      onRefresh: _loadClasses,
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
                  const Text(
                    'My Classes',
                    style: TextStyle(color: Colors.white, fontSize: 26, fontWeight: FontWeight.bold, letterSpacing: -0.5),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    '${_classes.length} class${_classes.length == 1 ? '' : 'es'}',
                    style: const TextStyle(color: Colors.white70, fontSize: 13),
                  ),
                  const SizedBox(height: 20),
                  TextField(
                    controller: _searchController,
                    style: const TextStyle(fontSize: 14, color: _T.textDark),
                    decoration: InputDecoration(
                      hintText: 'Search classes...',
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

          // ── Count ──
          SliverToBoxAdapter(
            child: Padding(
              padding: const EdgeInsets.fromLTRB(20, 14, 20, 4),
              child: Text(
                '${_filtered.length} class${_filtered.length == 1 ? '' : 'es'}',
                style: const TextStyle(color: _T.textMid, fontSize: 13),
              ),
            ),
          ),

          // ── List or empty ──
          if (_classes.isEmpty)
            SliverFillRemaining(
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
                      const Text('No classes yet.', style: TextStyle(color: _T.textMid, fontSize: 16, fontWeight: FontWeight.w500)),
                      const SizedBox(height: 4),
                      const Text('Tap "New Class" to create your first class!', textAlign: TextAlign.center, style: TextStyle(color: _T.textLight, fontSize: 13)),
                    ],
                  ),
                ),
              ),
            )
          else if (_filtered.isEmpty)
            SliverFillRemaining(
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
                        child: Icon(Icons.search_off_rounded, size: 48, color: _T.primary.withOpacity(0.5)),
                      ),
                      const SizedBox(height: 16),
                      const Text('No classes found.', style: TextStyle(color: _T.textMid, fontSize: 16, fontWeight: FontWeight.w500)),
                    ],
                  ),
                ),
              ),
            )
          else
            SliverPadding(
              padding: const EdgeInsets.fromLTRB(20, 4, 20, 100),
              sliver: SliverList(
                delegate: SliverChildBuilderDelegate(
                  (context, index) => _buildClassCard(Map<String, dynamic>.from(_filtered[index])),
                  childCount: _filtered.length,
                ),
              ),
            ),
        ],
      ),
    );
  }

  Widget _buildClassCard(Map<String, dynamic> cls) {
    final colors = [
      const Color(0xFF2ECC71), const Color(0xFF6C63FF), const Color(0xFF3B82F6),
      const Color(0xFFF59E0B), const Color(0xFFEF4444), const Color(0xFF8B5CF6),
    ];
    final classColor = colors[(cls['name'] as String).codeUnitAt(0) % colors.length];

    return Container(
      margin: const EdgeInsets.only(bottom: 14),
      decoration: _T.card,
      child: InkWell(
        borderRadius: BorderRadius.circular(16),
        onTap: () async {
          await Navigator.pushNamed(
            context,
            '/class-detail',
            arguments: {'class_id': cls['id'], 'class_name': cls['name']},
          );
          _loadClasses();
        },
        child: Column(
          children: [
            // ── Color band header ──
            Container(
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
                    decoration: BoxDecoration(color: classColor, borderRadius: BorderRadius.circular(13)),
                    child: Center(
                      child: Text(
                        (cls['name'] as String)[0].toUpperCase(),
                        style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 20),
                      ),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(cls['name'], style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: _T.textDark)),
                        if (cls['description'] != null && (cls['description'] as String).isNotEmpty)
                          Text(
                            cls['description'],
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: const TextStyle(fontSize: 12, color: _T.textMid),
                          ),
                      ],
                    ),
                  ),
                  Icon(Icons.chevron_right_rounded, color: classColor),
                ],
              ),
            ),

            // ── Body ──
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 12, 16, 14),
              child: Column(
                children: [
                  Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                        decoration: BoxDecoration(
                          color: classColor.withOpacity(0.1),
                          borderRadius: BorderRadius.circular(8),
                          border: Border.all(color: classColor.withOpacity(0.3)),
                        ),
                        child: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Icon(Icons.key_rounded, size: 12, color: classColor),
                            const SizedBox(width: 5),
                            Text(
                              cls['class_code'] ?? '',
                              style: TextStyle(color: classColor, fontWeight: FontWeight.bold, fontSize: 12, letterSpacing: 1.5),
                            ),
                          ],
                        ),
                      ),
                      const Spacer(),
                      _buildMeta(Icons.people_rounded, '${cls['students_count']}'),
                      const SizedBox(width: 12),
                      _buildMeta(Icons.quiz_rounded, '${cls['quizzes_count']} quizzes'),
                    ],
                  ),
                  const SizedBox(height: 10),
                  Divider(color: Colors.grey.shade100, height: 1),
                  const SizedBox(height: 10),
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
                                border: Border.all(color: _T.accent.withOpacity(0.3)),
                                borderRadius: BorderRadius.circular(20),
                              ),
                              child: const Row(
                                children: [
                                  Icon(Icons.edit_rounded, size: 13, color: _T.accent),
                                  SizedBox(width: 4),
                                  Text('Edit', style: TextStyle(color: _T.accent, fontSize: 12, fontWeight: FontWeight.w600)),
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
                                arguments: {'class_id': cls['id'], 'class_name': cls['name']},
                              );
                              _loadClasses();
                            },
                            child: Container(
                              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 6),
                              decoration: BoxDecoration(
                                color: classColor,
                                borderRadius: BorderRadius.circular(20),
                              ),
                              child: const Row(
                                children: [
                                  Text('Manage', style: TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.bold)),
                                  SizedBox(width: 4),
                                  Icon(Icons.arrow_forward_rounded, size: 13, color: Colors.white),
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

  Widget _buildMeta(IconData icon, String label) {
    return Row(
      children: [
        Icon(icon, size: 13, color: _T.textLight),
        const SizedBox(width: 4),
        Text(label, style: const TextStyle(fontSize: 12, color: _T.textMid)),
      ],
    );
  }
}