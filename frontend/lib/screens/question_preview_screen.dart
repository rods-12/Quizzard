import 'package:flutter/material.dart';
import '../widgets/multiple_choice_widget.dart';
import '../widgets/multiple_choice_result_widget.dart';
import '../widgets/true_false_widget.dart';
import '../widgets/true_false_result_widget.dart';
import '../widgets/identification_widget.dart';
import '../widgets/identification_result_widget.dart';
import '../widgets/matching_widget.dart';
import '../widgets/matching_result_widget.dart';

class QuestionPreviewScreen extends StatefulWidget {
  const QuestionPreviewScreen({super.key});

  @override
  State<QuestionPreviewScreen> createState() =>
      _QuestionPreviewScreenState();
}

class _QuestionPreviewScreenState
    extends State<QuestionPreviewScreen> {
  int? _mcSelectedId;
  int? _tfSelectedId;
  String _idAnswer = '';
  Map<String, String> _matchAnswers = {};
  bool _mcSubmitted = false;
  bool _tfSubmitted = false;
  bool _idSubmitted = false;
  bool _matchSubmitted = false;

  final Map<String, dynamic> _mcQuestion = {
    'id': 1,
    'question_text': 'What is the capital of the Philippines?',
    'question_type': 'multiple_choice',
    'points': 1,
    'answer_options': [
      {'id': 1, 'option_text': 'Cebu', 'is_correct': false, 'order': 1},
      {'id': 2, 'option_text': 'Manila', 'is_correct': true, 'order': 2},
      {'id': 3, 'option_text': 'Davao', 'is_correct': false, 'order': 3},
      {'id': 4, 'option_text': 'Quezon City', 'is_correct': false, 'order': 4},
    ],
  };

  final Map<String, dynamic> _tfQuestion = {
    'id': 2,
    'question_text': 'The Philippines has more than 7,000 islands.',
    'question_type': 'true_false',
    'points': 1,
    'answer_options': [
      {'id': 5, 'option_text': 'True', 'is_correct': true, 'order': 1},
      {'id': 6, 'option_text': 'False', 'is_correct': false, 'order': 2},
    ],
  };

  final Map<String, dynamic> _idQuestion = {
    'id': 3,
    'question_text': 'What is the national language of the Philippines?',
    'question_type': 'identification',
    'points': 2,
    'answer_options': [
      {'id': 7, 'option_text': 'Filipino', 'is_correct': true, 'order': 1},
    ],
  };

  final Map<String, dynamic> _matchQuestion = {
    'id': 4,
    'question_text': 'Match the Filipino heroes to their contributions.',
    'question_type': 'matching',
    'points': 4,
    'answer_options': [
      {
        'id': 8,
        'option_text': 'Jose Rizal',
        'match_pair': 'Wrote Noli Me Tangere',
        'is_correct': true,
        'order': 1
      },
      {
        'id': 9,
        'option_text': 'Andres Bonifacio',
        'match_pair': 'Founded the Katipunan',
        'is_correct': true,
        'order': 2
      },
      {
        'id': 10,
        'option_text': 'Emilio Aguinaldo',
        'match_pair': 'First President of the Philippines',
        'is_correct': true,
        'order': 3
      },
      {
        'id': 11,
        'option_text': 'Apolinario Mabini',
        'match_pair': 'Brains of the Revolution',
        'is_correct': true,
        'order': 4
      },
    ],
  };

  Widget _buildSubmitButton({
    required bool submitted,
    required VoidCallback onSubmit,
    required VoidCallback onReset,
    required bool hasAnswer,
  }) {
    return SizedBox(
      width: double.infinity,
      height: 50,
      child: ElevatedButton(
        onPressed: () {
          if (submitted) {
            onReset();
          } else {
            if (!hasAnswer) {
              ScaffoldMessenger.of(context).showSnackBar(
                const SnackBar(
                  content: Text('Please answer the question first!'),
                  backgroundColor: Color(0xFFF59E0B),
                ),
              );
              return;
            }
            onSubmit();
          }
        },
        style: ElevatedButton.styleFrom(
          backgroundColor: submitted
              ? const Color(0xFFEDE7F2)
              : const Color(0xFFF2C94C),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(12),
          ),
          elevation: 0,
        ),
        child: Text(
          submitted ? 'Try Again' : 'Submit Answer',
          style: TextStyle(
            color: submitted
                ? const Color(0xFF5B2A9B)
                : const Color(0xFF1F1235),
            fontSize: 16,
            fontWeight: FontWeight.bold,
          ),
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFFAF6EC),
      appBar: AppBar(
        title: const Text('Question Types Preview'),
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
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // ── Multiple Choice ──
            const Text(
              'Multiple Choice',
              style: TextStyle(
                fontSize: 20,
                fontWeight: FontWeight.bold,
                color: Color(0xFF1F1235),
              ),
            ),
            const SizedBox(height: 12),
            _mcSubmitted
                ? MultipleChoiceResultWidget(
                    question: _mcQuestion,
                    selectedAnswerId: _mcSelectedId)
                : MultipleChoiceWidget(
                    question: _mcQuestion,
                    selectedAnswerId: _mcSelectedId,
                    onAnswerSelected: (id) =>
                        setState(() => _mcSelectedId = id)),
            const SizedBox(height: 12),
            _buildSubmitButton(
              submitted: _mcSubmitted,
              hasAnswer: _mcSelectedId != null,
              onSubmit: () => setState(() => _mcSubmitted = true),
              onReset: () => setState(() {
                _mcSubmitted = false;
                _mcSelectedId = null;
              }),
            ),

            const Divider(height: 40, color: Color(0xFFEDE7F2)),

            // ── True or False ──
            const Text(
              'True or False',
              style: TextStyle(
                fontSize: 20,
                fontWeight: FontWeight.bold,
                color: Color(0xFF1F1235),
              ),
            ),
            const SizedBox(height: 12),
            _tfSubmitted
                ? TrueFalseResultWidget(
                    question: _tfQuestion,
                    selectedAnswerId: _tfSelectedId)
                : TrueFalseWidget(
                    question: _tfQuestion,
                    selectedAnswerId: _tfSelectedId,
                    onAnswerSelected: (id) =>
                        setState(() => _tfSelectedId = id)),
            const SizedBox(height: 12),
            _buildSubmitButton(
              submitted: _tfSubmitted,
              hasAnswer: _tfSelectedId != null,
              onSubmit: () => setState(() => _tfSubmitted = true),
              onReset: () => setState(() {
                _tfSubmitted = false;
                _tfSelectedId = null;
              }),
            ),

            const Divider(height: 40, color: Color(0xFFEDE7F2)),

            // ── Identification ──
            const Text(
              'Identification',
              style: TextStyle(
                fontSize: 20,
                fontWeight: FontWeight.bold,
                color: Color(0xFF1F1235),
              ),
            ),
            const SizedBox(height: 12),
            _idSubmitted
                ? IdentificationResultWidget(
                    question: _idQuestion,
                    studentAnswer: _idAnswer)
                : IdentificationWidget(
                    question: _idQuestion,
                    currentAnswer: _idAnswer,
                    onAnswerChanged: (val) =>
                        setState(() => _idAnswer = val)),
            const SizedBox(height: 12),
            _buildSubmitButton(
              submitted: _idSubmitted,
              hasAnswer: _idAnswer.trim().isNotEmpty,
              onSubmit: () => setState(() => _idSubmitted = true),
              onReset: () => setState(() {
                _idSubmitted = false;
                _idAnswer = '';
              }),
            ),

            const Divider(height: 40, color: Color(0xFFEDE7F2)),

            // ── Matching Type ──
            const Text(
              'Matching Type',
              style: TextStyle(
                fontSize: 20,
                fontWeight: FontWeight.bold,
                color: Color(0xFF1F1235),
              ),
            ),
            const SizedBox(height: 12),
            _matchSubmitted
                ? MatchingResultWidget(
                    question: _matchQuestion,
                    studentAnswers: _matchAnswers)
                : MatchingWidget(
                    question: _matchQuestion,
                    currentAnswers: _matchAnswers,
                    onAnswerChanged: (answers) =>
                        setState(() => _matchAnswers = answers)),
            const SizedBox(height: 12),
            _buildSubmitButton(
              submitted: _matchSubmitted,
              hasAnswer: _matchAnswers.length ==
                  (_matchQuestion['answer_options'] as List).length,
              onSubmit: () =>
                  setState(() => _matchSubmitted = true),
              onReset: () => setState(() {
                _matchSubmitted = false;
                _matchAnswers = {};
              }),
            ),

            const SizedBox(height: 20),
          ],
        ),
      ),
    );
  }
}