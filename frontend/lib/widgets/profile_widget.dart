import 'package:flutter/material.dart';
import '../services/auth_service.dart';

class ProfileWidget extends StatefulWidget {
  final VoidCallback onLogout;

  const ProfileWidget({
    super.key,
    required this.onLogout,
  });

  @override
  State<ProfileWidget> createState() => _ProfileWidgetState();  
}

class _ProfileWidgetState extends State<ProfileWidget> {
  // ── Brand palette ──────────────────────────────────────────────
  static const Color primaryColor    = Color(0xFF5B2A9B);
  static const Color primaryDark     = Color(0xFF3A1A6B);
  static const Color primaryLight    = Color(0xFFEDE7F2);
  static const Color accentGold      = Color(0xFFF2C94C);
  static const Color softPurple      = Color(0xFFC9A8F0);
  static const Color highlightPurple = Color(0xFFA14BC9);
  static const Color background      = Color(0xFFFAF6EC);
  static const Color textPrimary     = Color(0xFF1F1235);
  static const Color textMuted       = Color(0xFF7B6E99);
  static const Color textSubtle      = Color(0xFFA99BC4);
  static const Color plumShadow      = Color(0xFF2A1247);
  static const Color danger          = Color(0xFFEF4444);
  static const Color success         = Color(0xFF22C55E);
  // ───────────────────────────────────────────────────────────────

  bool _isLoading = true;
  Map<String, dynamic>? _user;

  @override
  void initState() {
    super.initState();
    _loadProfile();
  }

  Future<void> _loadProfile() async {
    setState(() => _isLoading = true);
    final result = await AuthService.authGet('/me');
    setState(() {
      _isLoading = false;
      if (result['success']) {
        _user = result['data'];
      }
    });
  }

  Color _getRoleColor(String role) {
    switch (role) {
      case 'admin':
        return const Color(0xFF3A1A6B); // primaryDark for admin
      case 'teacher':
        return const Color(0xFF5B2A9B); // primaryColor for teacher
      default:
        return const Color(0xFFA14BC9); // highlightPurple for student
    }
  }

  IconData _getRoleIcon(String role) {
    switch (role) {
      case 'admin':
        return Icons.admin_panel_settings;
      case 'teacher':
        return Icons.school;
      default:
        return Icons.person;
    }
  }

  Future<void> _editName() async {
    final firstNameController =
        TextEditingController(text: _user?['first_name'] ?? '');
    final middleInitialController =
        TextEditingController(text: _user?['middle_initial'] ?? '');
    final surnameController =
        TextEditingController(text: _user?['surname'] ?? '');

    final confirm = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(16)),
        backgroundColor: Colors.white,
        title: const Text(
          'Edit Name',
          style: TextStyle(
              color: textPrimary, fontWeight: FontWeight.bold),
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            TextField(
              controller: firstNameController,
              maxLength: 100,
              style: const TextStyle(color: textPrimary),
              decoration: InputDecoration(
                labelText: 'First Name',
                labelStyle: const TextStyle(color: textMuted),
                border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(12),
                    borderSide: const BorderSide(color: softPurple)),
                enabledBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(12),
                  borderSide: const BorderSide(color: softPurple),
                ),
                focusedBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(12),
                  borderSide:
                      const BorderSide(color: primaryColor, width: 2),
                ),
              ),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: middleInitialController,
              maxLength: 1,
              style: const TextStyle(color: textPrimary),
              decoration: InputDecoration(
                labelText: 'Middle Initial (optional)',
                labelStyle: const TextStyle(color: textMuted),
                helperText: 'Single letter',
                helperStyle: const TextStyle(color: textSubtle),
                border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(12),
                    borderSide: const BorderSide(color: softPurple)),
                enabledBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(12),
                  borderSide: const BorderSide(color: softPurple),
                ),
                focusedBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(12),
                  borderSide:
                      const BorderSide(color: primaryColor, width: 2),
                ),
              ),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: surnameController,
              maxLength: 100,
              style: const TextStyle(color: textPrimary),
              decoration: InputDecoration(
                labelText: 'Surname',
                labelStyle: const TextStyle(color: textMuted),
                border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(12),
                    borderSide: const BorderSide(color: softPurple)),
                enabledBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(12),
                  borderSide: const BorderSide(color: softPurple),
                ),
                focusedBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(12),
                  borderSide:
                      const BorderSide(color: primaryColor, width: 2),
                ),
              ),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            style: TextButton.styleFrom(foregroundColor: textMuted),
            child: const Text('Cancel'),
          ),
          ElevatedButton(
            onPressed: () => Navigator.pop(context, true),
            style: ElevatedButton.styleFrom(
              backgroundColor: accentGold,
              foregroundColor: textPrimary,
              shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(10)),
            ),
            child: const Text('Save',
                style: TextStyle(fontWeight: FontWeight.bold)),
          ),
        ],
      ),
    );

    if (confirm != true) return;

    final firstName = firstNameController.text.trim();
    final surname = surnameController.text.trim();
    final emojiRegex = RegExp(r'^[\p{L}\s\-\.]+$', unicode: true);

    if (firstName.isEmpty || surname.isEmpty) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('First name and surname are required!'),
          backgroundColor: danger,
        ),
      );
      return;
    }

    if (!emojiRegex.hasMatch(firstName) || !emojiRegex.hasMatch(surname)) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Name must not contain emojis or special characters.'),
          backgroundColor: danger,
        ),
      );
      return;
    }

    final result = await AuthService.authPut('/profile', {
      'first_name': firstName,
      'middle_initial': middleInitialController.text.trim().isEmpty
          ? null
          : middleInitialController.text.trim()[0],
      'surname': surname,
    });

    if (result['success']) {
      _loadProfile();
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Name updated successfully!'),
          backgroundColor: success,
        ),
      );
    } else {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(result['message']),
          backgroundColor: danger,
        ),
      );
    }
  }

  Future<void> _changePassword() async {
    final currentController = TextEditingController();
    final newController = TextEditingController();
    final confirmController = TextEditingController();
    bool showCurrent = false;
    bool showNew = false;
    bool showConfirm = false;

    final confirm = await showDialog<bool>(
      context: context,
      builder: (context) => StatefulBuilder(
        builder: (context, setDialogState) => AlertDialog(
          shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(16)),
          backgroundColor: Colors.white,
          title: const Text(
            'Change Password',
            style: TextStyle(
                color: textPrimary, fontWeight: FontWeight.bold),
          ),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              TextField(
                controller: currentController,
                obscureText: !showCurrent,
                style: const TextStyle(color: textPrimary),
                decoration: InputDecoration(
                  labelText: 'Current Password',
                  labelStyle: const TextStyle(color: textMuted),
                  border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: const BorderSide(color: softPurple)),
                  enabledBorder: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(12),
                    borderSide: const BorderSide(color: softPurple),
                  ),
                  focusedBorder: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(12),
                    borderSide:
                        const BorderSide(color: primaryColor, width: 2),
                  ),
                  suffixIcon: IconButton(
                    icon: Icon(
                      showCurrent
                          ? Icons.visibility_off
                          : Icons.visibility,
                      color: textMuted,
                    ),
                    onPressed: () => setDialogState(
                        () => showCurrent = !showCurrent),
                  ),
                ),
              ),
              const SizedBox(height: 12),
              TextField(
                controller: newController,
                obscureText: !showNew,
                maxLength: 50,
                style: const TextStyle(color: textPrimary),
                decoration: InputDecoration(
                  labelText: 'New Password',
                  labelStyle: const TextStyle(color: textMuted),
                  border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: const BorderSide(color: softPurple)),
                  enabledBorder: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(12),
                    borderSide: const BorderSide(color: softPurple),
                  ),
                  focusedBorder: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(12),
                    borderSide:
                        const BorderSide(color: primaryColor, width: 2),
                  ),
                  suffixIcon: IconButton(
                    icon: Icon(
                      showNew ? Icons.visibility_off : Icons.visibility,
                      color: textMuted,
                    ),
                    onPressed: () =>
                        setDialogState(() => showNew = !showNew),
                  ),
                ),
              ),
              const SizedBox(height: 12),
              TextField(
                controller: confirmController,
                obscureText: !showConfirm,
                maxLength: 50,
                style: const TextStyle(color: textPrimary),
                decoration: InputDecoration(
                  labelText: 'Confirm New Password',
                  labelStyle: const TextStyle(color: textMuted),
                  border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: const BorderSide(color: softPurple)),
                  enabledBorder: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(12),
                    borderSide: const BorderSide(color: softPurple),
                  ),
                  focusedBorder: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(12),
                    borderSide:
                        const BorderSide(color: primaryColor, width: 2),
                  ),
                  suffixIcon: IconButton(
                    icon: Icon(
                      showConfirm
                          ? Icons.visibility_off
                          : Icons.visibility,
                      color: textMuted,
                    ),
                    onPressed: () => setDialogState(
                        () => showConfirm = !showConfirm),
                  ),
                ),
              ),
              const SizedBox(height: 8),
              const Text(
                '8+ chars, uppercase, lowercase, number, special char',
                style: TextStyle(fontSize: 11, color: textSubtle),
              ),
            ],
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context, false),
              style: TextButton.styleFrom(foregroundColor: textMuted),
              child: const Text('Cancel'),
            ),
            ElevatedButton(
              onPressed: () => Navigator.pop(context, true),
              style: ElevatedButton.styleFrom(
                backgroundColor: accentGold,
                foregroundColor: textPrimary,
                shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(10)),
              ),
              child: const Text('Change',
                  style: TextStyle(fontWeight: FontWeight.bold)),
            ),
          ],
        ),
      ),
    );

    if (confirm != true) return;

    if (newController.text != confirmController.text) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('New passwords do not match!'),
          backgroundColor: danger,
        ),
      );
      return;
    }

    if (newController.text == currentController.text) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text(
              'New password must be different from your current password.'),
          backgroundColor: danger,
        ),
      );
      return;
    }

    if (newController.text.length > 50) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Password must not exceed 50 characters.'),
          backgroundColor: danger,
        ),
      );
      return;
    }

    final result = await AuthService.authPut('/profile', {
      'current_password': currentController.text,
      'new_password': newController.text,
    });

    if (result['success']) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Password changed successfully!'),
          backgroundColor: success,
        ),
      );
    } else {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content:
              Text(result['message'] ?? 'Failed to change password.'),
          backgroundColor: danger,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) {
      return const Center(
        child: CircularProgressIndicator(color: primaryColor),
      );
    }

    if (_user == null) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const Icon(Icons.error_outline, size: 60, color: danger),
            const SizedBox(height: 16),
            const Text(
              'Failed to load profile.',
              style: TextStyle(color: textPrimary),
            ),
            const SizedBox(height: 16),
            ElevatedButton(
              onPressed: _loadProfile,
              style: ElevatedButton.styleFrom(
                backgroundColor: accentGold,
                foregroundColor: textPrimary,
                shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(10)),
              ),
              child: const Text('Retry'),
            ),
          ],
        ),
      );
    }

    final role = _user!['role'] as String;
    final roleColor = _getRoleColor(role);
    final name = _user!['name'] as String;
    final email = _user!['email'] as String;

    return SingleChildScrollView(
      child: Column(
        children: [
          // Header
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(30),
            decoration: BoxDecoration(
              gradient: LinearGradient(
                colors: [roleColor, primaryDark],
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
              borderRadius: const BorderRadius.only(
                bottomLeft: Radius.circular(30),
                bottomRight: Radius.circular(30),
              ),
              boxShadow: [
                BoxShadow(
                  color: plumShadow.withOpacity(0.3),
                  blurRadius: 16,
                  offset: const Offset(0, 6),
                ),
              ],
            ),
            child: Column(
              children: [
                // Avatar
                Stack(
                  children: [
                    Container(
                      decoration: BoxDecoration(
                        shape: BoxShape.circle,
                        border: Border.all(
                            color: accentGold.withOpacity(0.7),
                            width: 3),
                        boxShadow: [
                          BoxShadow(
                            color: plumShadow.withOpacity(0.4),
                            blurRadius: 12,
                            offset: const Offset(0, 4),
                          ),
                        ],
                      ),
                      child: CircleAvatar(
                        radius: 50,
                        backgroundColor:
                            Colors.white.withOpacity(0.15),
                        backgroundImage:
                            _user!['profile_picture'] != null
                                ? NetworkImage(_user!['profile_picture'])
                                : null,
                        child: _user!['profile_picture'] == null
                            ? Text(
                                name[0].toUpperCase(),
                                style: const TextStyle(
                                  color: Colors.white,
                                  fontSize: 36,
                                  fontWeight: FontWeight.bold,
                                ),
                              )
                            : null,
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 16),
                Text(
                  name,
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 22,
                    fontWeight: FontWeight.bold,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  email,
                  style: TextStyle(
                    color: Colors.white.withOpacity(0.75),
                    fontSize: 14,
                  ),
                ),
                const SizedBox(height: 12),
                // Role badge
                Container(
                  padding: const EdgeInsets.symmetric(
                      horizontal: 16, vertical: 6),
                  decoration: BoxDecoration(
                    color: accentGold.withOpacity(0.2),
                    borderRadius: BorderRadius.circular(20),
                    border: Border.all(
                        color: accentGold.withOpacity(0.6)),
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Icon(_getRoleIcon(role),
                          color: accentGold, size: 16),
                      const SizedBox(width: 6),
                      Text(
                        role[0].toUpperCase() + role.substring(1),
                        style: const TextStyle(
                          color: accentGold,
                          fontWeight: FontWeight.bold,
                          fontSize: 13,
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),

          const SizedBox(height: 24),

          // Account Info Section
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 20),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'Account Information',
                  style: TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.bold,
                    color: textPrimary,
                  ),
                ),
                const SizedBox(height: 12),

                // Name tile
                _buildInfoTile(
                  icon: Icons.person,
                  label: 'Full Name',
                  value: name,
                  onEdit: _editName,
                ),
                const SizedBox(height: 10),

                // Email tile (not editable)
                _buildInfoTile(
                  icon: Icons.email,
                  label: 'Email Address',
                  value: email,
                  onEdit: null,
                ),
                const SizedBox(height: 10),

                // Role tile (not editable)
                _buildInfoTile(
                  icon: _getRoleIcon(role),
                  label: 'Role',
                  value: role[0].toUpperCase() + role.substring(1),
                  onEdit: null,
                ),

                const SizedBox(height: 24),

                // Security Section
                const Text(
                  'Security',
                  style: TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.bold,
                    color: textPrimary,
                  ),
                ),
                const SizedBox(height: 12),

                // Change password button
                SizedBox(
                  width: double.infinity,
                  height: 50,
                  child: OutlinedButton.icon(
                    onPressed: _changePassword,
                    icon: const Icon(Icons.lock, color: primaryColor),
                    label: const Text(
                      'Change Password',
                      style: TextStyle(
                          color: primaryColor, fontSize: 15),
                    ),
                    style: OutlinedButton.styleFrom(
                      side: const BorderSide(color: primaryColor),
                      backgroundColor: primaryLight.withOpacity(0.4),
                      shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(12)),
                    ),
                  ),
                ),

                const SizedBox(height: 12),

                // Student Info button (only for students)
                if (role == 'student')
                  SizedBox(
                    width: double.infinity,
                    height: 50,
                    child: OutlinedButton.icon(
                      onPressed: () {
                        Navigator.pushNamed(context, '/student-info');
                      },
                      icon: const Icon(Icons.info_outline,
                          color: primaryColor),
                      label: const Text(
                        'Edit Student Info',
                        style:
                            TextStyle(color: primaryColor, fontSize: 15),
                      ),
                      style: OutlinedButton.styleFrom(
                        side: const BorderSide(color: primaryColor),
                        backgroundColor: primaryLight.withOpacity(0.4),
                        shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(12)),
                      ),
                    ),
                  ),

                const SizedBox(height: 30),

                // Logout button
                SizedBox(
                  width: double.infinity,
                  height: 50,
                  child: ElevatedButton.icon(
                    onPressed: widget.onLogout,
                    icon: const Icon(Icons.logout, color: Colors.white),
                    label: const Text(
                      'Logout',
                      style:
                          TextStyle(color: Colors.white, fontSize: 16),
                    ),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: danger,
                      shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(12)),
                      shadowColor: danger.withOpacity(0.4),
                      elevation: 3,
                    ),
                  ),
                ),
                const SizedBox(height: 30),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildInfoTile({
    required IconData icon,
    required String label,
    required String value,
    required VoidCallback? onEdit,
  }) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: softPurple.withOpacity(0.4)),
        boxShadow: [
          BoxShadow(
            color: plumShadow.withOpacity(0.07),
            blurRadius: 8,
            offset: const Offset(0, 3),
          ),
        ],
      ),
      child: Row(
        children: [
          Icon(icon, color: primaryColor, size: 20),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  label,
                  style: const TextStyle(
                    fontSize: 11,
                    color: textSubtle,
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  value,
                  style: const TextStyle(
                    fontSize: 15,
                    fontWeight: FontWeight.w600,
                    color: textPrimary,
                  ),
                ),
              ],
            ),
          ),
          if (onEdit != null)
            IconButton(
              onPressed: onEdit,
              icon: const Icon(Icons.edit, color: highlightPurple, size: 20),
              tooltip: 'Edit',
            ),
        ],
      ),
    );
  }
}