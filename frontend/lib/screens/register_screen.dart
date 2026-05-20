import 'package:flutter/material.dart';
import 'dart:convert';
import 'package:http/http.dart' as http;
import '../services/auth_service.dart';

class RegisterScreen extends StatefulWidget {
  const RegisterScreen({super.key});

  @override
  State<RegisterScreen> createState() => _RegisterScreenState();
}

class _RegisterScreenState extends State<RegisterScreen> {
  final _firstNameController = TextEditingController();
  final _middleInitialController = TextEditingController();
  final _surnameController = TextEditingController();
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  final _confirmPasswordController = TextEditingController();
  String _selectedRole = 'student';
  bool _isLoading = false;
  bool _obscurePassword = true;
  bool _obscureConfirmPassword = true;
  String? _errorMessage;
  String? _successMessage;

  // ── Brand palette ──────────────────────────────────────────────
  static const Color _primaryPurple  = Color(0xFF5B2A9B);
  static const Color _deepViolet     = Color(0xFF3A1A6B);
  static const Color _primaryLight   = Color(0xFFEDE7F2);
  static const Color _parchment      = Color(0xFFFAF6EC);
  static const Color _midnightPlum   = Color(0xFF1F1235);
  static const Color _mutedLavender  = Color(0xFFA99BC4);
  static const Color _enchantedGold  = Color(0xFFF2C94C);
  static const Color _plumShadow     = Color(0x402A1247);
  static const Color _success        = Color(0xFF22C55E);
  static const Color _danger         = Color(0xFFEF4444);
  // ───────────────────────────────────────────────────────────────

  @override
  void dispose() {
    _firstNameController.dispose();
    _middleInitialController.dispose();
    _surnameController.dispose();
    _emailController.dispose();
    _passwordController.dispose();
    _confirmPasswordController.dispose();
    super.dispose();
  }

  Future<void> _register() async {
    setState(() {
      _errorMessage = null;
      _successMessage = null;
      _isLoading = true;
    });

    if (_firstNameController.text.trim().isEmpty ||
        _surnameController.text.trim().isEmpty ||
        _emailController.text.trim().isEmpty ||
        _passwordController.text.isEmpty ||
        _confirmPasswordController.text.isEmpty) {
      setState(() {
        _errorMessage = 'Please fill in all required fields.';
        _isLoading = false;
      });
      return;
    }

    String middleInitial = _middleInitialController.text.trim();
    if (middleInitial.isNotEmpty) {
      if (middleInitial.length != 1 || !RegExp(r'^[a-zA-Z]$').hasMatch(middleInitial)) {
        setState(() {
          _errorMessage = 'Middle initial must be a single alphabet character.';
          _isLoading = false;
        });
        return;
      }
      middleInitial = middleInitial.toUpperCase();
    }

    if (_passwordController.text != _confirmPasswordController.text) {
      setState(() {
        _errorMessage = 'Passwords do not match.';
        _isLoading = false;
      });
      return;
    }

    try {
      final response = await http.post(
        Uri.parse('${AuthService.baseUrl}/register'),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: jsonEncode({
          'first_name': _firstNameController.text.trim(),
          'middle_initial': middleInitial.isEmpty ? null : middleInitial,
          'surname': _surnameController.text.trim(),
          'name': '${_firstNameController.text.trim()}${middleInitial.isEmpty ? '' : ' $middleInitial.'} ${_surnameController.text.trim()}',
          'email': _emailController.text.trim(),
          'password': _passwordController.text,
          'password_confirmation': _confirmPasswordController.text,
          'role': _selectedRole,
        }),
      );

      final data = jsonDecode(response.body);

      setState(() => _isLoading = false);

      if (response.statusCode == 201) {
        setState(() {
          _successMessage = data['message'];
        });
        _firstNameController.clear();
        _middleInitialController.clear();
        _surnameController.clear();
        _emailController.clear();
        _passwordController.clear();
        _confirmPasswordController.clear();
      } else {
        if (data['errors'] != null) {
          final errors = data['errors'] as Map<String, dynamic>;
          final firstError = errors.values.first as List<dynamic>;
          setState(() {
            _errorMessage = firstError.first.toString();
          });
        } else {
          setState(() {
            _errorMessage = data['message'] ?? 'Registration failed.';
          });
        }
      }
    } catch (e) {
      setState(() {
        _isLoading = false;
        _errorMessage = 'Cannot connect to server. Please check your connection.';
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: _primaryPurple,
      body: SafeArea(
        child: SingleChildScrollView(
          child: Column(
            children: [
              // ── Top hero section ────────────────────────────────
              Container(
                width: double.infinity,
                padding: const EdgeInsets.symmetric(vertical: 40),
                decoration: const BoxDecoration(
                  gradient: LinearGradient(
                    colors: [_primaryPurple, _deepViolet],
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                  ),
                ),
                child: Column(
                  children: [
                    Container(
                      width: 80,
                      height: 80,
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(40),
                        boxShadow: [
                          BoxShadow(
                            color: _plumShadow,
                            blurRadius: 20,
                            offset: const Offset(0, 10),
                          ),
                        ],
                      ),
                      child: const Icon(
                        Icons.quiz,
                        size: 45,
                        color: _primaryPurple,
                      ),
                    ),
                    const SizedBox(height: 16),
                    const Text(
                      'Quizzard',
                      style: TextStyle(
                        fontSize: 30,
                        fontWeight: FontWeight.bold,
                        color: Colors.white,
                        letterSpacing: 2,
                      ),
                    ),
                    const SizedBox(height: 6),
                    Text(
                      'Create your account',
                      style: TextStyle(
                        fontSize: 14,
                        color: Colors.white.withOpacity(0.70),
                      ),
                    ),
                  ],
                ),
              ),

              // ── Form card ───────────────────────────────────────
              Container(
                width: double.infinity,
                decoration: const BoxDecoration(
                  color: _parchment,
                  borderRadius: BorderRadius.only(
                    topLeft: Radius.circular(40),
                    topRight: Radius.circular(40),
                  ),
                ),
                padding: const EdgeInsets.all(30),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const SizedBox(height: 10),
                    const Text(
                      'Register',
                      style: TextStyle(
                        fontSize: 26,
                        fontWeight: FontWeight.bold,
                        color: _midnightPlum,
                      ),
                    ),
                    const SizedBox(height: 6),
                    const Text(
                      'Fill in the details below',
                      style: TextStyle(fontSize: 14, color: _mutedLavender),
                    ),
                    const SizedBox(height: 24),

                    // Success message
                    if (_successMessage != null)
                      Container(
                        width: double.infinity,
                        padding: const EdgeInsets.all(12),
                        margin: const EdgeInsets.only(bottom: 20),
                        decoration: BoxDecoration(
                          color: const Color(0xFFDCFCE7),
                          borderRadius: BorderRadius.circular(10),
                          border: Border.all(color: _success.withOpacity(0.40)),
                        ),
                        child: Row(
                          children: [
                            const Icon(Icons.check_circle_outline,
                                color: _success, size: 20),
                            const SizedBox(width: 8),
                            Expanded(
                              child: Text(
                                _successMessage!,
                                style: const TextStyle(
                                    color: Color(0xFF15803D), fontSize: 13),
                              ),
                            ),
                          ],
                        ),
                      ),

                    // Error message
                    if (_errorMessage != null)
                      Container(
                        width: double.infinity,
                        padding: const EdgeInsets.all(12),
                        margin: const EdgeInsets.only(bottom: 20),
                        decoration: BoxDecoration(
                          color: const Color(0xFFFEE2E2),
                          borderRadius: BorderRadius.circular(10),
                          border: Border.all(color: _danger.withOpacity(0.40)),
                        ),
                        child: Row(
                          children: [
                            const Icon(Icons.error_outline,
                                color: _danger, size: 20),
                            const SizedBox(width: 8),
                            Expanded(
                              child: Text(
                                _errorMessage!,
                                style: const TextStyle(
                                    color: Color(0xFFB91C1C), fontSize: 13),
                              ),
                            ),
                          ],
                        ),
                      ),

                    // First name
                    _buildLabel('First Name'),
                    const SizedBox(height: 8),
                    _buildTextField(
                      controller: _firstNameController,
                      hint: 'Enter your first name',
                      icon: Icons.person_outline,
                    ),
                    const SizedBox(height: 16),

                    // Middle initial
                    _buildLabel('Middle initial (optional)'),
                    const SizedBox(height: 8),
                    _buildTextField(
                      controller: _middleInitialController,
                      hint: 'E.g. A',
                      icon: Icons.text_fields,
                      maxLength: 1,
                      keyboardType: TextInputType.text,
                    ),
                    const SizedBox(height: 16),

                    // Surname
                    _buildLabel('Surname'),
                    const SizedBox(height: 8),
                    _buildTextField(
                      controller: _surnameController,
                      hint: 'Enter your surname',
                      icon: Icons.person,
                    ),
                    const SizedBox(height: 16),

                    // Email
                    _buildLabel('Email'),
                    const SizedBox(height: 8),
                    _buildTextField(
                      controller: _emailController,
                      hint: 'Enter your email',
                      icon: Icons.email_outlined,
                      keyboardType: TextInputType.emailAddress,
                      maxLength: 30,
                    ),
                    const SizedBox(height: 16),

                    // Role selector
                    _buildLabel('I am a...'),
                    const SizedBox(height: 8),
                    Container(
                      decoration: BoxDecoration(
                        color: _primaryLight,
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Row(
                        children: [
                          Expanded(
                            child: GestureDetector(
                              onTap: () =>
                                  setState(() => _selectedRole = 'student'),
                              child: Container(
                                padding:
                                    const EdgeInsets.symmetric(vertical: 14),
                                decoration: BoxDecoration(
                                  gradient: _selectedRole == 'student'
                                      ? const LinearGradient(
                                          colors: [_primaryPurple, _deepViolet],
                                          begin: Alignment.topLeft,
                                          end: Alignment.bottomRight,
                                        )
                                      : null,
                                  color: _selectedRole == 'student'
                                      ? null
                                      : Colors.transparent,
                                  borderRadius: BorderRadius.circular(12),
                                ),
                                child: Row(
                                  mainAxisAlignment: MainAxisAlignment.center,
                                  children: [
                                    Icon(
                                      Icons.school,
                                      color: _selectedRole == 'student'
                                          ? Colors.white
                                          : _mutedLavender,
                                      size: 18,
                                    ),
                                    const SizedBox(width: 6),
                                    Text(
                                      'Student',
                                      style: TextStyle(
                                        color: _selectedRole == 'student'
                                            ? Colors.white
                                            : _mutedLavender,
                                        fontWeight: FontWeight.w600,
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                            ),
                          ),
                          Expanded(
                            child: GestureDetector(
                              onTap: () =>
                                  setState(() => _selectedRole = 'teacher'),
                              child: Container(
                                padding:
                                    const EdgeInsets.symmetric(vertical: 14),
                                decoration: BoxDecoration(
                                  gradient: _selectedRole == 'teacher'
                                      ? const LinearGradient(
                                          colors: [_primaryPurple, _deepViolet],
                                          begin: Alignment.topLeft,
                                          end: Alignment.bottomRight,
                                        )
                                      : null,
                                  color: _selectedRole == 'teacher'
                                      ? null
                                      : Colors.transparent,
                                  borderRadius: BorderRadius.circular(12),
                                ),
                                child: Row(
                                  mainAxisAlignment: MainAxisAlignment.center,
                                  children: [
                                    Icon(
                                      Icons.cast_for_education,
                                      color: _selectedRole == 'teacher'
                                          ? Colors.white
                                          : _mutedLavender,
                                      size: 18,
                                    ),
                                    const SizedBox(width: 6),
                                    Text(
                                      'Teacher',
                                      style: TextStyle(
                                        color: _selectedRole == 'teacher'
                                            ? Colors.white
                                            : _mutedLavender,
                                        fontWeight: FontWeight.w600,
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 16),

                    // Password
                    _buildLabel('Password'),
                    const SizedBox(height: 8),
                    _buildPasswordField(
                      controller: _passwordController,
                      hint: 'Min 8 chars, uppercase, number, symbol',
                      obscure: _obscurePassword,
                      onToggle: () =>
                          setState(() => _obscurePassword = !_obscurePassword),
                      maxLength: 50,
                    ),
                    const SizedBox(height: 16),

                    // Confirm password
                    _buildLabel('Confirm Password'),
                    const SizedBox(height: 8),
                    _buildPasswordField(
                      controller: _confirmPasswordController,
                      hint: 'Re-enter your password',
                      obscure: _obscureConfirmPassword,
                      onToggle: () => setState(() =>
                          _obscureConfirmPassword = !_obscureConfirmPassword),
                      maxLength: 50,
                    ),
                    const SizedBox(height: 10),

                    // Password requirements hint
                    Container(
                      padding: const EdgeInsets.all(10),
                      decoration: BoxDecoration(
                        color: _primaryLight,
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: const Row(
                        children: [
                          Icon(Icons.info_outline,
                              color: _primaryPurple, size: 16),
                          SizedBox(width: 8),
                          Expanded(
                            child: Text(
                              'Password must have: 8+ characters, uppercase, lowercase, number, and special character (@\$!%*#?&)',
                              style: TextStyle(
                                  fontSize: 11, color: _primaryPurple),
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 28),

                    // Register button — Enchanted Gold CTA
                    SizedBox(
                      width: double.infinity,
                      height: 55,
                      child: ElevatedButton(
                        onPressed: _isLoading ? null : _register,
                        style: ElevatedButton.styleFrom(
                          backgroundColor: _enchantedGold,
                          disabledBackgroundColor:
                              _enchantedGold.withOpacity(0.55),
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(12),
                          ),
                          elevation: 3,
                          shadowColor: _plumShadow,
                        ),
                        child: _isLoading
                            ? const CircularProgressIndicator(
                                color: _midnightPlum)
                            : const Text(
                                'Create Account',
                                style: TextStyle(
                                  fontSize: 18,
                                  fontWeight: FontWeight.bold,
                                  color: _midnightPlum,
                                ),
                              ),
                      ),
                    ),
                    const SizedBox(height: 20),

                    // Login link
                    Center(
                      child: GestureDetector(
                        onTap: () => Navigator.pop(context),
                        child: RichText(
                          text: const TextSpan(
                            text: 'Already have an account? ',
                            style: TextStyle(color: _mutedLavender),
                            children: [
                              TextSpan(
                                text: 'Sign In',
                                style: TextStyle(
                                  color: _primaryPurple,
                                  fontWeight: FontWeight.bold,
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),
                    ),
                    const SizedBox(height: 20),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  // ─── Helper Widgets ───────────────────────────────────────

  Widget _buildLabel(String text) {
    return Text(
      text,
      style: const TextStyle(
        fontWeight: FontWeight.w600,
        color: _midnightPlum,
      ),
    );
  }

  Widget _buildTextField({
    required TextEditingController controller,
    required String hint,
    required IconData icon,
    TextInputType keyboardType = TextInputType.text,
    int? maxLength,
  }) {
    return TextField(
      controller: controller,
      keyboardType: keyboardType,
      maxLength: maxLength,
      decoration: InputDecoration(
        hintText: hint,
        hintStyle: const TextStyle(color: _mutedLavender),
        prefixIcon: const Icon(Icons.person_outline, color: _primaryPurple),
        // ignore the icon param — each call passes its own icon; keep routing below
        filled: true,
        fillColor: Colors.white,
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: BorderSide.none,
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: BorderSide(color: _primaryLight, width: 1.5),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: _primaryPurple, width: 2),
        ),
      ),
    );
  }

  Widget _buildPasswordField({
    required TextEditingController controller,
    required String hint,
    required bool obscure,
    required VoidCallback onToggle,
    int? maxLength,
  }) {
    return TextField(
      controller: controller,
      obscureText: obscure,
      maxLength: maxLength,
      decoration: InputDecoration(
        hintText: hint,
        hintStyle: const TextStyle(color: _mutedLavender),
        prefixIcon: const Icon(Icons.lock_outlined, color: _primaryPurple),
        suffixIcon: IconButton(
          icon: Icon(
            obscure ? Icons.visibility_off : Icons.visibility,
            color: _mutedLavender,
          ),
          onPressed: onToggle,
        ),
        filled: true,
        fillColor: Colors.white,
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: BorderSide.none,
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: BorderSide(color: _primaryLight, width: 1.5),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: _primaryPurple, width: 2),
        ),
      ),
    );
  }
}