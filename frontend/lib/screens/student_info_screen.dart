import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import '../services/auth_service.dart';

// ─── THEME CONSTANTS ──────────────────────────────────────────────────────────
class _AppTheme {
  static const Color primary = Color(0xFF6C63FF);
  static const Color primaryDark = Color(0xFF4B44CC);
  static const Color primaryLight = Color(0xFFEEEDFF);
  static const Color bg = Color(0xFFF4F6FB);
  static const Color surface = Colors.white;
  static const Color textDark = Color(0xFF1A1D2E);
  static const Color textMid = Color(0xFF6B7080);
  static const Color textLight = Color(0xFFADB5BD);
  static const Color success = Color(0xFF22C55E);
  static const Color danger = Color(0xFFEF4444);
}

class StudentInfoScreen extends StatefulWidget {
  const StudentInfoScreen({super.key});

  @override
  State<StudentInfoScreen> createState() => _StudentInfoScreenState();
}

class _StudentInfoScreenState extends State<StudentInfoScreen> {
  final _formKey = GlobalKey<FormState>();

  final _studentIdController = TextEditingController();
  final _contactController = TextEditingController();
  final _sectionController = TextEditingController();

  String? _selectedGender;
  String? _selectedGradeLevel;
  DateTime? _selectedDate;
  bool _isLoading = true;
  bool _isSaving = false;

  final List<String> _genderOptions = ['male', 'female', 'other'];

  final List<String> _gradeLevelOptions = [
    'Grade 7',
    'Grade 8',
    'Grade 9',
    'Grade 10',
    'Grade 11',
    'Grade 12',
    'Year 1',
    'Year 2',
    'Year 3',
    'Year 4',
  ];

  @override
  void initState() {
    super.initState();
    _loadProfile();
  }

  Future<void> _loadProfile() async {
    final result = await AuthService.authGet('/student/profile');
    if (result['success']) {
      final profile = result['data']['profile'];
      setState(() {
        _studentIdController.text = profile['student_id'] ?? '';
        _contactController.text = profile['contact_number'] ?? '';
        _sectionController.text = profile['section'] ?? '';
        _selectedGender = profile['gender'];
        _selectedGradeLevel = profile['grade_level'];
        if (profile['date_of_birth'] != null) {
          _selectedDate = DateTime.parse(profile['date_of_birth']);
        }
        _isLoading = false;
      });
    } else {
      setState(() => _isLoading = false);
    }
  }

  Future<void> _pickDate() async {
    final picked = await showDatePicker(
      context: context,
      initialDate: _selectedDate ?? DateTime(2000),
      firstDate: DateTime(1950),
      lastDate: DateTime.now(),
      builder: (context, child) {
        return Theme(
          data: Theme.of(context).copyWith(
            colorScheme: const ColorScheme.light(
              primary: _AppTheme.primary,
              onPrimary: Colors.white,
              surface: Colors.white,
            ),
          ),
          child: child!,
        );
      },
    );
    if (picked != null) {
      setState(() => _selectedDate = picked);
    }
  }

  Future<void> _saveProfile() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _isSaving = true);

    final result = await AuthService.authPut('/student/profile', {
      'student_id': _studentIdController.text.trim(),
      'gender': _selectedGender,
      'date_of_birth': _selectedDate != null
          ? '${_selectedDate!.year}-${_selectedDate!.month.toString().padLeft(2, '0')}-${_selectedDate!.day.toString().padLeft(2, '0')}'
          : null,
      'contact_number': _contactController.text.trim(),
      'grade_level': _selectedGradeLevel,
      'section': _sectionController.text.trim(),
    });

    setState(() => _isSaving = false);

    if (!mounted) return;

    if (result['success']) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: const Row(
            children: [
              Icon(Icons.check_circle_rounded, color: Colors.white, size: 18),
              SizedBox(width: 8),
              Text('Profile updated successfully!'),
            ],
          ),
          backgroundColor: _AppTheme.success,
          behavior: SnackBarBehavior.floating,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
          margin: const EdgeInsets.all(16),
        ),
      );
      Navigator.pop(context);
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(result['message'] ?? 'Failed to update profile.'),
          backgroundColor: _AppTheme.danger,
          behavior: SnackBarBehavior.floating,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
          margin: const EdgeInsets.all(16),
        ),
      );
    }
  }

  @override
  void dispose() {
    _studentIdController.dispose();
    _contactController.dispose();
    _sectionController.dispose();
    super.dispose();
  }

  String _formatDate(DateTime date) {
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    return '${months[date.month - 1]} ${date.day}, ${date.year}';
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: _AppTheme.bg,
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: _AppTheme.primary))
          : CustomScrollView(
              slivers: [
                // ── Header ──
                SliverToBoxAdapter(
                  child: Container(
                    padding: const EdgeInsets.fromLTRB(20, 50, 20, 28),
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
                      children: [
                        // Back + title
                        Row(
                          children: [
                            GestureDetector(
                              onTap: () => Navigator.pop(context),
                              child: Container(
                                padding: const EdgeInsets.all(8),
                                decoration: BoxDecoration(
                                  color: Colors.white.withOpacity(0.2),
                                  borderRadius: BorderRadius.circular(10),
                                ),
                                child: const Icon(Icons.arrow_back_ios_new_rounded, color: Colors.white, size: 18),
                              ),
                            ),
                            const SizedBox(width: 12),
                            const Text(
                              'Student Info',
                              style: TextStyle(color: Colors.white, fontSize: 20, fontWeight: FontWeight.bold),
                            ),
                          ],
                        ),
                        const SizedBox(height: 24),

                        // Avatar
                        Container(
                          width: 80,
                          height: 80,
                          decoration: BoxDecoration(
                            color: Colors.white.withOpacity(0.2),
                            shape: BoxShape.circle,
                            border: Border.all(color: Colors.white.withOpacity(0.4), width: 2),
                          ),
                          child: const Icon(Icons.person_rounded, color: Colors.white, size: 44),
                        ),
                        const SizedBox(height: 12),
                        const Text(
                          'Edit your profile details',
                          style: TextStyle(color: Colors.white70, fontSize: 13),
                        ),
                      ],
                    ),
                  ),
                ),

                // ── Form ──
                SliverToBoxAdapter(
                  child: Padding(
                    padding: const EdgeInsets.fromLTRB(20, 24, 20, 40),
                    child: Form(
                      key: _formKey,
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          // ── Section: Academic Info ──
                          _buildSectionLabel('Academic Information', Icons.school_rounded),
                          const SizedBox(height: 12),

                          _buildCard(
                            children: [
                              _buildFieldRow(
                                icon: Icons.badge_rounded,
                                child: TextFormField(
                                  controller: _studentIdController,
                                  maxLength: 15,
                                  inputFormatters: [
                                    FilteringTextInputFormatter.allow(RegExp(r'[a-zA-Z0-9\-]')),
                                  ],
                                  style: const TextStyle(fontSize: 14, color: _AppTheme.textDark),
                                  decoration: _fieldDecoration(
                                    label: 'Student ID',
                                    hint: 'e.g. 2024-00123',
                                    counter: '',
                                  ),
                                ),
                              ),
                              _buildDivider(),
                              _buildFieldRow(
                                icon: Icons.class_rounded,
                                child: DropdownButtonFormField<String>(
                                  value: _selectedGradeLevel,
                                  style: const TextStyle(fontSize: 14, color: _AppTheme.textDark),
                                  decoration: _fieldDecoration(label: 'Grade / Year Level'),
                                  items: _gradeLevelOptions.map((level) {
                                    return DropdownMenuItem(value: level, child: Text(level));
                                  }).toList(),
                                  onChanged: (value) => setState(() => _selectedGradeLevel = value),
                                ),
                              ),
                              _buildDivider(),
                              _buildFieldRow(
                                icon: Icons.group_rounded,
                                child: TextFormField(
                                  controller: _sectionController,
                                  maxLength: 20,
                                  inputFormatters: [
                                    FilteringTextInputFormatter.allow(RegExp(r'[a-zA-Z0-9 ]')),
                                  ],
                                  style: const TextStyle(fontSize: 14, color: _AppTheme.textDark),
                                  decoration: _fieldDecoration(
                                    label: 'Section',
                                    hint: 'e.g. BSIT-3A',
                                    counter: '',
                                  ),
                                ),
                              ),
                            ],
                          ),

                          const SizedBox(height: 20),

                          // ── Section: Personal Info ──
                          _buildSectionLabel('Personal Information', Icons.person_rounded),
                          const SizedBox(height: 12),

                          _buildCard(
                            children: [
                              _buildFieldRow(
                                icon: Icons.wc_rounded,
                                child: DropdownButtonFormField<String>(
                                  value: _selectedGender,
                                  style: const TextStyle(fontSize: 14, color: _AppTheme.textDark),
                                  decoration: _fieldDecoration(label: 'Gender'),
                                  items: _genderOptions.map((gender) {
                                    return DropdownMenuItem(
                                      value: gender,
                                      child: Text(gender[0].toUpperCase() + gender.substring(1)),
                                    );
                                  }).toList(),
                                  onChanged: (value) => setState(() => _selectedGender = value),
                                ),
                              ),
                              _buildDivider(),

                              // Date of Birth
                              _buildFieldRow(
                                icon: Icons.cake_rounded,
                                child: GestureDetector(
                                  onTap: _pickDate,
                                  child: AbsorbPointer(
                                    child: TextFormField(
                                      style: const TextStyle(fontSize: 14, color: _AppTheme.textDark),
                                      decoration: _fieldDecoration(
                                        label: 'Date of Birth',
                                        hint: 'Select date',
                                      ),
                                      controller: TextEditingController(
                                        text: _selectedDate != null ? _formatDate(_selectedDate!) : '',
                                      ),
                                    ),
                                  ),
                                ),
                              ),

                              _buildDivider(),
                              _buildFieldRow(
                                icon: Icons.phone_rounded,
                                child: TextFormField(
                                  controller: _contactController,
                                  maxLength: 15,
                                  keyboardType: TextInputType.phone,
                                  inputFormatters: [
                                    FilteringTextInputFormatter.allow(RegExp(r'[0-9\+]')),
                                  ],
                                  style: const TextStyle(fontSize: 14, color: _AppTheme.textDark),
                                  decoration: _fieldDecoration(
                                    label: 'Contact Number',
                                    hint: 'e.g. 09xxxxxxxxx',
                                    counter: '',
                                  ),
                                ),
                              ),
                            ],
                          ),

                          const SizedBox(height: 32),

                          // ── Save Button ──
                          SizedBox(
                            width: double.infinity,
                            height: 52,
                            child: ElevatedButton(
                              onPressed: _isSaving ? null : _saveProfile,
                              style: ElevatedButton.styleFrom(
                                backgroundColor: _AppTheme.primary,
                                disabledBackgroundColor: _AppTheme.primary.withOpacity(0.5),
                                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                                elevation: 0,
                              ),
                              child: _isSaving
                                  ? const SizedBox(
                                      width: 22,
                                      height: 22,
                                      child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2.5),
                                    )
                                  : const Row(
                                      mainAxisAlignment: MainAxisAlignment.center,
                                      children: [
                                        Icon(Icons.save_rounded, color: Colors.white, size: 20),
                                        SizedBox(width: 8),
                                        Text(
                                          'Save Changes',
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
                    ),
                  ),
                ),
              ],
            ),
    );
  }

  Widget _buildSectionLabel(String label, IconData icon) {
    return Row(
      children: [
        Container(
          padding: const EdgeInsets.all(6),
          decoration: BoxDecoration(
            color: _AppTheme.primaryLight,
            borderRadius: BorderRadius.circular(8),
          ),
          child: Icon(icon, color: _AppTheme.primary, size: 15),
        ),
        const SizedBox(width: 8),
        Text(
          label,
          style: const TextStyle(
            fontSize: 13,
            fontWeight: FontWeight.w700,
            color: _AppTheme.textMid,
            letterSpacing: 0.3,
          ),
        ),
      ],
    );
  }

  Widget _buildCard({required List<Widget> children}) {
    return Container(
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
      child: Column(children: children),
    );
  }

  Widget _buildFieldRow({required IconData icon, required Widget child}) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.center,
        children: [
          Container(
            width: 34,
            height: 34,
            decoration: BoxDecoration(
              color: _AppTheme.primaryLight,
              borderRadius: BorderRadius.circular(9),
            ),
            child: Icon(icon, color: _AppTheme.primary, size: 17),
          ),
          const SizedBox(width: 12),
          Expanded(child: child),
        ],
      ),
    );
  }

  Widget _buildDivider() {
    return Divider(
      height: 1,
      color: Colors.grey.shade100,
      indent: 16,
      endIndent: 16,
    );
  }

  InputDecoration _fieldDecoration({required String label, String? hint, String? counter}) {
    return InputDecoration(
      labelText: label,
      hintText: hint,
      counterText: counter,
      labelStyle: TextStyle(color: _AppTheme.textLight, fontSize: 13),
      hintStyle: TextStyle(color: _AppTheme.textLight.withOpacity(0.7), fontSize: 13),
      border: InputBorder.none,
      focusedBorder: InputBorder.none,
      enabledBorder: InputBorder.none,
      errorBorder: InputBorder.none,
      contentPadding: const EdgeInsets.symmetric(vertical: 14),
      isDense: true,
    );
  }
}