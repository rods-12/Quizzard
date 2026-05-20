import 'package:flutter/material.dart';
import '../services/auth_service.dart';

class CreateQuizScreen extends StatefulWidget {
  const CreateQuizScreen({super.key});

  @override
  State<CreateQuizScreen> createState() => _CreateQuizScreenState();
}

class _CreateQuizScreenState extends State<CreateQuizScreen> {
  // ── Quizzard Brand Colors ──────────────────────────────────────
  static const Color primaryColor      = Color(0xFF5B2A9B); // Royal Purple
  static const Color primaryDark       = Color(0xFF3A1A6B); // Deep Violet
  static const Color primaryLight      = Color(0xFFEDE7F2); // Wizard Beard White
  static const Color accentGold        = Color(0xFFF2C94C); // Enchanted Gold
  static const Color background        = Color(0xFFFAF6EC); // Parchment Cream
  static const Color midnightPlum      = Color(0xFF1F1235); // Primary Text
  static const Color mutedLavender     = Color(0xFFA99BC4); // Subtle Text
  static const Color plumShadow        = Color(0xFF2A1247); // Deep dark
  static const Color successColor      = Color(0xFF22C55E);
  static const Color dangerColor       = Color(0xFFEF4444);
  // ──────────────────────────────────────────────────────────────

  final _formKey = GlobalKey<FormState>();
  final _titleController = TextEditingController();
  final _descController = TextEditingController();
  bool _loading = false;

  @override
  void dispose() {
    _titleController.dispose();
    _descController.dispose();
    super.dispose();
  }

  Future<void> _createQuiz() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _loading = true);

    final result = await AuthService.authPost('/quizzes', {
      'title': _titleController.text.trim(),
      'description': _descController.text.trim(),
    });

    setState(() => _loading = false);

    if (result['success']) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Quiz created!'), backgroundColor: successColor),
        );
        Navigator.pop(context, true);
      }
    } else {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(result['message']), backgroundColor: dangerColor),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: background,
      appBar: AppBar(
        flexibleSpace: Container(
          decoration: const BoxDecoration(
            gradient: LinearGradient(
              colors: [primaryColor, primaryDark],
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
            ),
          ),
        ),
        backgroundColor: Colors.transparent,
        foregroundColor: Colors.white,
        title: const Text('Create Quiz'),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(24),
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              const Text(
                'Quiz Details',
                style: TextStyle(
                  fontSize: 20,
                  fontWeight: FontWeight.bold,
                  color: midnightPlum,
                ),
              ),
              const SizedBox(height: 24),
              TextFormField(
                controller: _titleController,
                maxLength: 100,
                style: const TextStyle(color: midnightPlum),
                decoration: InputDecoration(
                  labelText: 'Quiz Title *',
                  labelStyle: const TextStyle(color: mutedLavender),
                  hintText: 'e.g. Chapter 1: The Philippines',
                  hintStyle: TextStyle(color: mutedLavender.withOpacity(0.7)),
                  prefixIcon: const Icon(Icons.quiz, color: primaryColor),
                  filled: true,
                  fillColor: Colors.white,
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(12),
                    borderSide: const BorderSide(color: mutedLavender),
                  ),
                  enabledBorder: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(12),
                    borderSide: BorderSide(color: mutedLavender.withOpacity(0.5)),
                  ),
                  focusedBorder: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(12),
                    borderSide: const BorderSide(color: primaryColor, width: 2),
                  ),
                ),
                validator: (v) => (v == null || v.trim().isEmpty) ? 'Title is required' : null,
              ),
              const SizedBox(height: 16),
              TextFormField(
                controller: _descController,
                maxLength: 100,
                maxLines: 4,
                style: const TextStyle(color: midnightPlum),
                decoration: InputDecoration(
                  labelText: 'Description (optional)',
                  labelStyle: const TextStyle(color: mutedLavender),
                  hintText: 'Add a short description of this quiz...',
                  hintStyle: TextStyle(color: mutedLavender.withOpacity(0.7)),
                  prefixIcon: const Icon(Icons.description, color: primaryColor),
                  filled: true,
                  fillColor: Colors.white,
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(12),
                    borderSide: const BorderSide(color: mutedLavender),
                  ),
                  enabledBorder: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(12),
                    borderSide: BorderSide(color: mutedLavender.withOpacity(0.5)),
                  ),
                  focusedBorder: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(12),
                    borderSide: const BorderSide(color: primaryColor, width: 2),
                  ),
                  alignLabelWithHint: true,
                ),
              ),
              const SizedBox(height: 32),
              ElevatedButton(
                onPressed: _loading ? null : _createQuiz,
                style: ElevatedButton.styleFrom(
                  backgroundColor: accentGold,
                  foregroundColor: midnightPlum,
                  disabledBackgroundColor: accentGold.withOpacity(0.6),
                  padding: const EdgeInsets.symmetric(vertical: 16),
                  elevation: 4,
                  shadowColor: plumShadow.withOpacity(0.4),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                ),
                child: _loading
                    ? const SizedBox(
                        height: 20,
                        width: 20,
                        child: CircularProgressIndicator(color: midnightPlum, strokeWidth: 2),
                      )
                    : const Text(
                        'Create Quiz',
                        style: TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.bold,
                          color: midnightPlum,
                        ),
                      ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}