import 'package:flutter/material.dart';
import '../services/auth_service.dart';

class QuizDetailScreen extends StatefulWidget {
  final int quizId;
  final String quizTitle;

  const QuizDetailScreen({
    super.key,
    required this.quizId,
    required this.quizTitle,
  });

  @override
  State<QuizDetailScreen> createState() => _QuizDetailScreenState();
}

class _QuizDetailScreenState extends State<QuizDetailScreen> {
  static const Color primaryColor = Color(0xFF5B2A9B);

  List<dynamic> _questions = [];
  bool _loading = true;
  bool _hasAttempts = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _loadQuiz();
  }

  Future<void> _loadQuiz() async {
    setState(() {
      _loading = true;
      _error = null;
    });

    final result = await AuthService.authGet('/quizzes/${widget.quizId}');

    if (result['success']) {
      setState(() {
        _questions = result['data']['data']['questions'] ?? [];
        _hasAttempts = result['data']['has_attempts'] ?? false;
        _loading = false;
      });
    } else {
      setState(() {
        _error = result['message'];
        _loading = false;
      });
    }
  }

  Future<void> _deleteQuestion(int questionId) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        backgroundColor: const Color(0xFFFAF6EC),
        title: const Text(
          'Delete Question',
          style: TextStyle(color: Color(0xFF1F1235)),
        ),
        content: const Text(
          'Are you sure you want to delete this question?',
          style: TextStyle(color: Color(0xFF1F1235)),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            style: TextButton.styleFrom(
              foregroundColor: Color(0xFF5B2A9B),
            ),
            child: const Text('Cancel'),
          ),
          TextButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text(
              'Delete',
              style: TextStyle(color: Color(0xFFEF4444)),
            ),
          ),
        ],
      ),
    );

    if (confirmed != true) return;

    final result = await AuthService.authDelete(
        '/quizzes/${widget.quizId}/questions/$questionId');

    if (result['success']) {
      _loadQuiz();
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Question deleted.'),
            backgroundColor: Color(0xFF22C55E),
          ),
        );
      }
    } else {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(result['message']),
            backgroundColor: Color(0xFFEF4444),
          ),
        );
      }
    }
  }

  void _navigateToAddQuestion() async {
    final result = await Navigator.pushNamed(
      context,
      '/add-question',
      arguments: {'quiz_id': widget.quizId},
    );
    if (result == true) _loadQuiz();
  }

  void _navigateToEditQuiz() async {
    final result = await Navigator.pushNamed(
      context,
      '/edit-quiz',
      arguments: {
        'quiz_id': widget.quizId,
        'title': widget.quizTitle,
        'description': '',
      },
    );
    if (result == true) _loadQuiz();
  }

  String _questionTypeLabel(String type) {
    switch (type) {
      case 'multiple_choice': return 'Multiple Choice';
      case 'true_false':      return 'True / False';
      case 'identification':  return 'Identification';
      case 'matching':        return 'Matching';
      default:                return type;
    }
  }

  Color _questionTypeColor(String type) {
    switch (type) {
      case 'multiple_choice': return const Color(0xFF5B2A9B);
      case 'true_false':      return const Color(0xFFF59E0B);
      case 'identification':  return const Color(0xFFA14BC9);
      case 'matching':        return const Color(0xFFC9A8F0);
      default:                return const Color(0xFFA99BC4);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFFAF6EC),
      appBar: AppBar(
        flexibleSpace: Container(
          decoration: const BoxDecoration(
            gradient: LinearGradient(
              colors: [Color(0xFF5B2A9B), Color(0xFF3A1A6B)],
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
            ),
          ),
        ),
        backgroundColor: Colors.transparent,
        foregroundColor: Colors.white,
        title: Text(widget.quizTitle, overflow: TextOverflow.ellipsis),
        actions: [
          if (!_hasAttempts)
            IconButton(
              icon: const Icon(Icons.edit),
              tooltip: 'Edit Quiz',
              onPressed: _navigateToEditQuiz,
            ),
        ],
      ),
      floatingActionButton: _hasAttempts
          ? null
          : Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                FloatingActionButton.extended(
                  heroTag: 'ai',
                  onPressed: () async {
                    final result = await Navigator.pushNamed(
                      context,
                      '/ai-quiz-generate',
                      arguments: {
                        'quiz_id':    widget.quizId,
                        'quiz_title': widget.quizTitle,
                      },
                    );
                    if (result == true) _loadQuiz();
                  },
                  backgroundColor: const Color(0xFF5B2A9B),
                  icon: const Icon(Icons.auto_awesome, color: Color(0xFFF2C94C)),
                  label: const Text(
                    'Generate with AI',
                    style: TextStyle(color: Colors.white),
                  ),
                ),
                const SizedBox(height: 10),
                FloatingActionButton.extended(
                  heroTag: 'add',
                  onPressed: _navigateToAddQuestion,
                  backgroundColor: const Color(0xFFF2C94C),
                  icon: const Icon(Icons.add, color: Color(0xFF1F1235)),
                  label: const Text(
                    'Add Question',
                    style: TextStyle(color: Color(0xFF1F1235)),
                  ),
                ),
              ],
            ),
      body: _loading
          ? const Center(
              child: CircularProgressIndicator(color: Color(0xFF5B2A9B)),
            )
          : _error != null
              ? Center(
                  child: Text(
                    _error!,
                    style: const TextStyle(color: Color(0xFFEF4444)),
                  ),
                )
              : Column(
                  children: [
                    if (_hasAttempts)
                      Container(
                        width: double.infinity,
                        padding: const EdgeInsets.symmetric(
                            horizontal: 16, vertical: 12),
                        color: const Color(0xFFF59E0B).withOpacity(0.12),
                        child: Row(
                          children: [
                            const Icon(
                              Icons.lock_outline,
                              color: Color(0xFFF59E0B),
                              size: 18,
                            ),
                            const SizedBox(width: 8),
                            const Expanded(
                              child: Text(
                                'This quiz has been taken by students and cannot be modified.',
                                style: TextStyle(
                                  color: Color(0xFFE0A93B),
                                  fontSize: 13,
                                  fontWeight: FontWeight.w500,
                                ),
                              ),
                            ),
                          ],
                        ),
                      ),
                    Expanded(
                      child: _questions.isEmpty
                          ? _buildEmptyState()
                          : _buildQuestionList(),
                    ),
                  ],
                ),
    );
  }

  Widget _buildEmptyState() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(Icons.quiz_outlined, size: 80, color: const Color(0xFFC9A8F0)),
          const SizedBox(height: 16),
          const Text(
            'No questions yet',
            style: TextStyle(fontSize: 18, color: Color(0xFFA99BC4)),
          ),
          const SizedBox(height: 8),
          const Text(
            'Tap "Add Question" to get started',
            style: TextStyle(color: Color(0xFFA99BC4)),
          ),
        ],
      ),
    );
  }

  Widget _buildQuestionList() {
    return ListView.builder(
      padding: const EdgeInsets.fromLTRB(16, 16, 16, 100),
      itemCount: _questions.length,
      itemBuilder: (context, index) {
        final q = _questions[index];
        final type = q['question_type'] ?? '';
        return Card(
          margin: const EdgeInsets.only(bottom: 12),
          color: Colors.white,
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(12),
          ),
          elevation: 2,
          shadowColor: const Color(0xFF2A1247).withOpacity(0.15),
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.symmetric(
                          horizontal: 8, vertical: 4),
                      decoration: BoxDecoration(
                        color: _questionTypeColor(type).withOpacity(0.12),
                        borderRadius: BorderRadius.circular(6),
                      ),
                      child: Text(
                        _questionTypeLabel(type),
                        style: TextStyle(
                          fontSize: 11,
                          fontWeight: FontWeight.bold,
                          color: _questionTypeColor(type),
                        ),
                      ),
                    ),
                    const Spacer(),
                    Text(
                      '${q['points'] ?? 1} pt${(q['points'] ?? 1) != 1 ? 's' : ''}',
                      style: const TextStyle(
                        fontSize: 12,
                        color: Color(0xFFA99BC4),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 10),
                Text(
                  'Q${index + 1}. ${q['question_text'] ?? ''}',
                  style: const TextStyle(
                    fontSize: 15,
                    fontWeight: FontWeight.w600,
                    color: Color(0xFF1F1235),
                  ),
                ),
                if (!_hasAttempts) ...[
                  const SizedBox(height: 12),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.end,
                    children: [
                      TextButton.icon(
                        onPressed: () async {
                          final result = await Navigator.pushNamed(
                            context,
                            '/edit-question',
                            arguments: {
                              'quiz_id':  widget.quizId,
                              'question': Map<String, dynamic>.from(q),
                            },
                          );
                          if (result == true) _loadQuiz();
                        },
                        icon: const Icon(Icons.edit,
                            size: 16, color: Color(0xFF5B2A9B)),
                        label: const Text(
                          'Edit',
                          style: TextStyle(color: Color(0xFF5B2A9B)),
                        ),
                      ),
                      const SizedBox(width: 8),
                      TextButton.icon(
                        onPressed: () => _deleteQuestion(q['id']),
                        icon: const Icon(Icons.delete,
                            size: 16, color: Color(0xFFEF4444)),
                        label: const Text(
                          'Delete',
                          style: TextStyle(color: Color(0xFFEF4444)),
                        ),
                      ),
                    ],
                  ),
                ],
              ],
            ),
          ),
        );
      },
    );
  }
}