import 'package:flutter/material.dart';
import '../services/auth_service.dart';

class AiQuizGenerateScreen extends StatefulWidget {
  final int quizId;
  final String quizTitle;

  const AiQuizGenerateScreen({
    super.key,
    required this.quizId,
    required this.quizTitle,
  });

  @override
  State<AiQuizGenerateScreen> createState() => _AiQuizGenerateScreenState();
}

class _AiQuizGenerateScreenState extends State<AiQuizGenerateScreen> {
  // ── Brand colors ──────────────────────────────────────────────
  static const Color primaryColor      = Color(0xFF5B2A9B);
  static const Color primaryDark       = Color(0xFF3A1A6B);
  static const Color primaryLight      = Color(0xFFEDE7F2);
  static const Color accentGold        = Color(0xFFF2C94C);
  static const Color softPurple        = Color(0xFFC9A8F0);
  static const Color highlightPurple   = Color(0xFFA14BC9);
  static const Color background        = Color(0xFFFAF6EC);
  static const Color midnightPlum      = Color(0xFF1F1235);
  static const Color mutedText         = Color(0xFF7B6F8E);
  static const Color subtleText        = Color(0xFFA99BC4);
  static const Color plumShadow        = Color(0xFF2A1247);
  static const Color successGreen      = Color(0xFF22C55E);

  // ── Step 1 state ──────────────────────────────────────────────
  final _topicCtrl   = TextEditingController();
  final _passageCtrl = TextEditingController();
  int    _numQuestions = 15;
  String _difficulty   = 'medium';
  final Map<String, bool> _types = {
    'multiple_choice': true,
    'true_false':      true,
    'identification':  true,
    'matching':        true,
  };

  // ── Step state ────────────────────────────────────────────────
  int  _step    = 1;
  bool _loading = false;
  String? _error;

  // ── Preview state ─────────────────────────────────────────────
  List<Map<String, dynamic>> _questions = [];

  // ── Helpers ───────────────────────────────────────────────────
  String _typeLabel(String t) => {
    'multiple_choice': 'Multiple Choice',
    'true_false':      'True / False',
    'identification':  'Identification',
    'matching':        'Matching',
  }[t] ?? t;

  Color _typeColor(String t) => {
    'multiple_choice': primaryColor,
    'true_false':      const Color(0xFFF59E0B),
    'identification':  highlightPurple,
    'matching':        softPurple,
  }[t] ?? mutedText;

  // ── Generate ──────────────────────────────────────────────────
  Future<void> _generate() async {
    final topic   = _topicCtrl.text.trim();
    final passage = _passageCtrl.text.trim();
    final types   = _types.entries.where((e) => e.value).map((e) => e.key).toList();

    setState(() => _error = null);

    if (topic.isEmpty && passage.isEmpty) {
      setState(() => _error = 'Please provide a topic or passage.');
      return;
    }
    if (types.isEmpty) {
      setState(() => _error = 'Please select at least one question type.');
      return;
    }

    setState(() => _loading = true);

    final result = await AuthService.authPost('/ai/generate-questions', {
      'topic':          topic,
      'passage':        passage,
      'num_questions':  _numQuestions,
      'difficulty':     _difficulty,
      'question_types': types,
    });

    setState(() => _loading = false);

    if (!result['success']) {
      setState(() => _error = result['message']);
      return;
    }

    final raw = result['data']['questions'] as List<dynamic>;
    setState(() {
      _questions = raw.map((q) => Map<String, dynamic>.from(q)).toList();
      _step = 2;
    });
  }

  // ── Save ──────────────────────────────────────────────────────
  Future<void> _save() async {
    setState(() { _loading = true; _error = null; });

    final result = await AuthService.authPost(
      '/ai/quizzes/${widget.quizId}/save-questions',
      {'questions': _questions},
    );

    setState(() => _loading = false);

    if (!result['success']) {
      setState(() => _error = result['message']);
      return;
    }

    if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('${_questions.length} question(s) saved successfully!'),
          backgroundColor: successGreen,
        ),
      );
      Navigator.pop(context, true);
    }
  }

  // ── Remove question from preview ──────────────────────────────
  void _removeQuestion(int idx) {
    setState(() {
      _questions.removeAt(idx);
      if (_questions.isEmpty) _step = 1;
    });
  }

  // ── Build ─────────────────────────────────────────────────────
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
        title: Text(
          _step == 1 ? '✨ Generate with AI' : 'Preview Questions',
          overflow: TextOverflow.ellipsis,
        ),
        actions: [
          if (_step == 2)
            TextButton.icon(
              onPressed: () => setState(() { _step = 1; _error = null; }),
              icon: const Icon(Icons.refresh, color: Colors.white, size: 18),
              label: const Text('Regenerate',
                  style: TextStyle(color: Colors.white, fontSize: 13)),
            ),
        ],
      ),
      body: _loading
          ? _buildLoading()
          : _step == 1
              ? _buildStep1()
              : _buildStep2(),
    );
  }

  // ── Loading ───────────────────────────────────────────────────
  Widget _buildLoading() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          const CircularProgressIndicator(color: primaryColor),
          const SizedBox(height: 16),
          Text(
            _step == 1 ? 'AI is generating your questions...' : 'Saving questions...',
            style: const TextStyle(color: mutedText, fontSize: 14),
          ),
        ],
      ),
    );
  }

  // ── Step 1: Input Form ────────────────────────────────────────
  Widget _buildStep1() {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Error
          if (_error != null)
            Container(
              width: double.infinity,
              margin: const EdgeInsets.only(bottom: 12),
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: const Color(0xFFEF4444).withOpacity(0.08),
                border: Border.all(color: const Color(0xFFEF4444).withOpacity(0.4)),
                borderRadius: BorderRadius.circular(12),
              ),
              child: Text(_error!,
                  style: const TextStyle(color: Color(0xFFEF4444), fontSize: 13)),
            ),

          // Topic
          _label('Topic / Keyword'),
          const SizedBox(height: 6),
          TextField(
            controller: _topicCtrl,
            decoration: _inputDec('e.g. Photosynthesis, World War 2...'),
          ),
          const SizedBox(height: 16),

          // Passage
          _label('Passage / Text'),
          const SizedBox(height: 2),
          Text('optional', style: TextStyle(fontSize: 12, color: subtleText)),
          const SizedBox(height: 6),
          TextField(
            controller: _passageCtrl,
            maxLines: 5,
            decoration: _inputDec('Paste a text or reading passage here...'),
          ),
          const SizedBox(height: 16),

          // Num questions + difficulty row
          Row(
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    _label('No. of Questions'),
                    const SizedBox(height: 6),
                    TextField(
                      keyboardType: TextInputType.number,
                      decoration: _inputDec('15'),
                      onChanged: (v) => _numQuestions = int.tryParse(v) ?? 15,
                      controller: TextEditingController(text: '$_numQuestions')
                        ..selection = TextSelection.collapsed(offset: '$_numQuestions'.length),
                    ),
                  ],
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    _label('Difficulty'),
                    const SizedBox(height: 6),
                    DropdownButtonFormField<String>(
                      value: _difficulty,
                      dropdownColor: Colors.white,
                      style: const TextStyle(color: midnightPlum, fontSize: 14),
                      decoration: _inputDec('').copyWith(
                        contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 14),
                      ),
                      items: ['easy', 'medium', 'hard'].map((d) =>
                          DropdownMenuItem(
                            value: d,
                            child: Text(d[0].toUpperCase() + d.substring(1)),
                          ),
                      ).toList(),
                      onChanged: (v) => setState(() => _difficulty = v!),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),

          // Question types
          _label('Question Types'),
          const SizedBox(height: 8),
          Card(
            color: Colors.white,
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
            shadowColor: plumShadow.withOpacity(0.12),
            elevation: 2,
            child: Column(
              children: _types.keys.map((t) => CheckboxListTile(
                title: Text(_typeLabel(t),
                    style: const TextStyle(fontSize: 14, color: midnightPlum)),
                value: _types[t],
                activeColor: primaryColor,
                checkColor: Colors.white,
                onChanged: (v) => setState(() => _types[t] = v!),
                dense: true,
              )).toList(),
            ),
          ),
          const SizedBox(height: 24),

          // Generate button
          SizedBox(
            width: double.infinity,
            child: ElevatedButton.icon(
              onPressed: _generate,
              icon: const Icon(Icons.auto_awesome, color: midnightPlum),
              label: const Text('Generate Questions',
                  style: TextStyle(
                      color: midnightPlum,
                      fontSize: 15,
                      fontWeight: FontWeight.bold)),
              style: ElevatedButton.styleFrom(
                backgroundColor: accentGold,
                padding: const EdgeInsets.symmetric(vertical: 14),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                elevation: 0,
              ),
            ),
          ),
        ],
      ),
    );
  }

  // ── Step 2: Preview ───────────────────────────────────────────
  Widget _buildStep2() {
    return Column(
      children: [
        if (_error != null)
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(12),
            color: const Color(0xFFEF4444).withOpacity(0.08),
            child: Text(_error!,
                style: const TextStyle(color: Color(0xFFEF4444), fontSize: 13)),
          ),
        Expanded(
          child: ListView.builder(
            padding: const EdgeInsets.fromLTRB(16, 16, 16, 100),
            itemCount: _questions.length,
            itemBuilder: (ctx, idx) => _buildQuestionCard(idx),
          ),
        ),
        // Save bar
        Container(
          padding: const EdgeInsets.fromLTRB(16, 12, 16, 24),
          decoration: BoxDecoration(
            color: Colors.white,
            boxShadow: [
              BoxShadow(
                color: plumShadow.withOpacity(0.12),
                blurRadius: 10,
                offset: const Offset(0, -3),
              ),
            ],
          ),
          child: SizedBox(
            width: double.infinity,
            child: ElevatedButton.icon(
              onPressed: _save,
              icon: const Icon(Icons.save, color: midnightPlum),
              label: Text(
                'Save ${_questions.length} Question(s) to Quiz',
                style: const TextStyle(
                    color: midnightPlum,
                    fontSize: 15,
                    fontWeight: FontWeight.bold),
              ),
              style: ElevatedButton.styleFrom(
                backgroundColor: accentGold,
                padding: const EdgeInsets.symmetric(vertical: 14),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                elevation: 0,
              ),
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildQuestionCard(int idx) {
    final q    = _questions[idx];
    final type = q['type'] as String;

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      color: Colors.white,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      shadowColor: plumShadow.withOpacity(0.15),
      elevation: 2,
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Header row
            Row(
              children: [
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                  decoration: BoxDecoration(
                    color: _typeColor(type).withOpacity(0.12),
                    borderRadius: BorderRadius.circular(6),
                  ),
                  child: Text(
                    _typeLabel(type),
                    style: TextStyle(
                        fontSize: 11,
                        fontWeight: FontWeight.bold,
                        color: _typeColor(type)),
                  ),
                ),
                const Spacer(),
                IconButton(
                  icon: const Icon(Icons.delete_outline,
                      color: Color(0xFFEF4444), size: 20),
                  tooltip: 'Remove',
                  onPressed: () => _removeQuestion(idx),
                  visualDensity: VisualDensity.compact,
                ),
              ],
            ),
            const SizedBox(height: 8),

            // Question text
            TextField(
              controller: TextEditingController(text: q['question_text'])
                ..selection = TextSelection.collapsed(
                    offset: (q['question_text'] as String).length),
              maxLines: 2,
              style: const TextStyle(
                  fontWeight: FontWeight.w600,
                  fontSize: 14,
                  color: midnightPlum),
              decoration: _inputDec('Question text').copyWith(
                contentPadding:
                    const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
              ),
              onChanged: (v) => _questions[idx]['question_text'] = v,
            ),
            const SizedBox(height: 8),

            // Points
            Row(
              children: [
                const Text('Points:',
                    style: TextStyle(fontSize: 13, color: mutedText)),
                const SizedBox(width: 8),
                SizedBox(
                  width: 60,
                  child: TextField(
                    controller: TextEditingController(text: '${q['points']}')
                      ..selection = TextSelection.collapsed(
                          offset: '${q['points']}'.length),
                    keyboardType: TextInputType.number,
                    textAlign: TextAlign.center,
                    style: const TextStyle(fontSize: 13, color: midnightPlum),
                    decoration: _inputDec('').copyWith(
                      contentPadding:
                          const EdgeInsets.symmetric(horizontal: 8, vertical: 8),
                    ),
                    onChanged: (v) =>
                        _questions[idx]['points'] = int.tryParse(v) ?? 1,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 8),

            // Answer options
            _buildAnswerPreview(idx, type, q),
          ],
        ),
      ),
    );
  }

  Widget _buildAnswerPreview(int idx, String type, Map<String, dynamic> q) {
    if (type == 'multiple_choice') {
      final opts = (q['options'] as List).cast<Map<String, dynamic>>();
      return Column(
        children: List.generate(opts.length, (oi) => Padding(
          padding: const EdgeInsets.only(bottom: 6),
          child: Row(
            children: [
              Radio<int>(
                value: oi,
                groupValue: opts.indexWhere((o) => o['is_correct'] == true),
                activeColor: primaryColor,
                onChanged: (v) => setState(() {
                  for (var i = 0; i < opts.length; i++) {
                    opts[i]['is_correct'] = i == v;
                  }
                }),
                visualDensity: VisualDensity.compact,
              ),
              Expanded(
                child: TextField(
                  controller: TextEditingController(text: opts[oi]['option_text'])
                    ..selection = TextSelection.collapsed(
                        offset: (opts[oi]['option_text'] as String).length),
                  style: const TextStyle(fontSize: 13, color: midnightPlum),
                  decoration: _inputDec('Option').copyWith(
                    contentPadding:
                        const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
                  ),
                  onChanged: (v) => opts[oi]['option_text'] = v,
                ),
              ),
            ],
          ),
        )),
      );
    }

    if (type == 'true_false') {
      return Row(
        children: ['true', 'false'].map((val) => Padding(
          padding: const EdgeInsets.only(right: 16),
          child: Row(
            children: [
              Radio<bool>(
                value: val == 'true',
                groupValue: q['correct_answer'] == true,
                activeColor: primaryColor,
                onChanged: (v) =>
                    setState(() => _questions[idx]['correct_answer'] = v),
              ),
              Text(val[0].toUpperCase() + val.substring(1),
                  style: const TextStyle(fontSize: 13, color: midnightPlum)),
            ],
          ),
        )).toList(),
      );
    }

    if (type == 'identification') {
      return TextField(
        controller: TextEditingController(text: q['answer'])
          ..selection = TextSelection.collapsed(
              offset: (q['answer'] as String).length),
        style: const TextStyle(fontSize: 13, color: midnightPlum),
        decoration: _inputDec('Correct answer').copyWith(
          prefixText: 'Answer: ',
          prefixStyle: const TextStyle(
              color: primaryColor, fontWeight: FontWeight.w600, fontSize: 13),
          contentPadding:
              const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
        ),
        onChanged: (v) => _questions[idx]['answer'] = v,
      );
    }

    if (type == 'matching') {
      final pairs = (q['pairs'] as List).cast<Map<String, dynamic>>();
      return Column(
        children: List.generate(pairs.length, (pi) => Padding(
          padding: const EdgeInsets.only(bottom: 6),
          child: Row(
            children: [
              Expanded(
                child: TextField(
                  controller: TextEditingController(text: pairs[pi]['left'])
                    ..selection = TextSelection.collapsed(
                        offset: (pairs[pi]['left'] as String).length),
                  style: const TextStyle(fontSize: 13, color: midnightPlum),
                  decoration: _inputDec('Left').copyWith(
                    contentPadding:
                        const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
                  ),
                  onChanged: (v) => pairs[pi]['left'] = v,
                ),
              ),
              const Padding(
                padding: EdgeInsets.symmetric(horizontal: 6),
                child: Icon(Icons.arrow_forward, size: 16, color: softPurple),
              ),
              Expanded(
                child: TextField(
                  controller: TextEditingController(text: pairs[pi]['right'])
                    ..selection = TextSelection.collapsed(
                        offset: (pairs[pi]['right'] as String).length),
                  style: const TextStyle(fontSize: 13, color: midnightPlum),
                  decoration: _inputDec('Right').copyWith(
                    contentPadding:
                        const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
                  ),
                  onChanged: (v) => pairs[pi]['right'] = v,
                ),
              ),
            ],
          ),
        )),
      );
    }

    return const SizedBox.shrink();
  }

  // ── Shared UI helpers ─────────────────────────────────────────
  Widget _label(String text) => Text(
        text,
        style: const TextStyle(
            fontSize: 14,
            fontWeight: FontWeight.w600,
            color: midnightPlum),
      );

  InputDecoration _inputDec(String hint) => InputDecoration(
        hintText: hint,
        hintStyle: const TextStyle(color: subtleText, fontSize: 13),
        filled: true,
        fillColor: Colors.white,
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(10),
          borderSide: BorderSide(color: softPurple.withOpacity(0.35)),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(10),
          borderSide: BorderSide(color: softPurple.withOpacity(0.35)),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(10),
          borderSide: const BorderSide(color: primaryColor, width: 1.5),
        ),
      );

  @override
  void dispose() {
    _topicCtrl.dispose();
    _passageCtrl.dispose();
    super.dispose();
  }
}