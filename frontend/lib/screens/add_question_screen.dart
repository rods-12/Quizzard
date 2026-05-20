import 'package:flutter/material.dart';
import '../services/auth_service.dart';
import '../widgets/image_picker_widget.dart';
import '../widgets/video_picker_widget.dart';
import '../widgets/video_player_widget.dart';
import '../widgets/audio_picker_widget.dart';
import '../widgets/audio_player_widget.dart';

class AddQuestionScreen extends StatefulWidget {
  final int quizId;
  const AddQuestionScreen({super.key, required this.quizId});

  @override
  State<AddQuestionScreen> createState() => _AddQuestionScreenState();
}

class _AddQuestionScreenState extends State<AddQuestionScreen> {
  // ── Brand palette ──────────────────────────────────────────────
  static const Color primaryColor      = Color(0xFF5B2A9B);
  static const Color primaryDark       = Color(0xFF3A1A6B);
  static const Color primaryLight      = Color(0xFFEDE7F2);
  static const Color accentGold        = Color(0xFFF2C94C);
  static const Color softPurple        = Color(0xFFC9A8F0);
  static const Color highlightPurple   = Color(0xFFA14BC9);
  static const Color background        = Color(0xFFFAF6EC);
  static const Color textPrimary       = Color(0xFF1F1235);
  static const Color textMuted         = Color(0xFF7B6E99);
  static const Color textSubtle        = Color(0xFFA99BC4);
  static const Color plumShadow        = Color(0xFF2A1247);
  static const Color danger            = Color(0xFFEF4444);
  static const Color success           = Color(0xFF22C55E);
  // ───────────────────────────────────────────────────────────────

  String _selectedType = 'multiple_choice';
  bool _loading = false;

  // Common fields
  final _questionTextController = TextEditingController();
  final _pointsController = TextEditingController(text: '1');

  // Question media — all 3 separate
  String? _questionImagePath;
  String? _questionVideoPath;
  String? _questionVideoUrl;
  String? _questionAudioPath;
  String? _questionAudioUrl;

  // Multiple choice
  final List<TextEditingController> _mcOptions =
      List.generate(4, (_) => TextEditingController());
  final List<String?> _mcOptionImagePaths = List.generate(4, (_) => null);
  final List<String?> _mcOptionVideoPaths = List.generate(4, (_) => null);
  final List<String?> _mcOptionVideoUrls  = List.generate(4, (_) => null);
  final List<String?> _mcOptionAudioPaths = List.generate(4, (_) => null);
  final List<String?> _mcOptionAudioUrls  = List.generate(4, (_) => null);
  int _mcCorrectIndex = 0;

  // True/False
  bool _tfCorrectAnswer = true;

  // Identification
  final _identAnswerController = TextEditingController();

  // Matching (4 pairs)
  final List<TextEditingController> _matchLeft =
      List.generate(4, (_) => TextEditingController());
  final List<TextEditingController> _matchRight =
      List.generate(4, (_) => TextEditingController());

  @override
  void dispose() {
    _questionTextController.dispose();
    _pointsController.dispose();
    for (var c in _mcOptions) c.dispose();
    _identAnswerController.dispose();
    for (var c in _matchLeft) c.dispose();
    for (var c in _matchRight) c.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (_questionTextController.text.trim().isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
            content: Text('Question text is required.'),
            backgroundColor: danger),
      );
      return;
    }

    setState(() => _loading = true);

    Map<String, dynamic> body = {};
    String endpoint = '';

    switch (_selectedType) {
      case 'multiple_choice':
        final options = _mcOptions.map((c) => c.text.trim()).toList();
        if (options.any((o) => o.isEmpty)) {
          setState(() => _loading = false);
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
                content: Text('Fill in all 4 options.'),
                backgroundColor: danger),
          );
          return;
        }
        body = {
          'question_text': _questionTextController.text.trim(),
          'image_path': _questionImagePath,
          'video_path': _questionVideoPath,
          'audio_path': _questionAudioPath,
          'points': int.tryParse(_pointsController.text) ?? 1,
          'options': List.generate(4, (i) => {
                'option_text': _mcOptions[i].text.trim(),
                'is_correct': i == _mcCorrectIndex,
                'image_path': _mcOptionImagePaths[i],
                'video_path': _mcOptionVideoPaths[i],
                'audio_path': _mcOptionAudioPaths[i],
              }),
        };
        endpoint = '/quizzes/${widget.quizId}/questions/multiple-choice';
        break;

      case 'true_false':
        body = {
          'question_text': _questionTextController.text.trim(),
          'image_path': _questionImagePath,
          'video_path': _questionVideoPath,
          'audio_path': _questionAudioPath,
          'points': int.tryParse(_pointsController.text) ?? 1,
          'correct_answer': _tfCorrectAnswer,
        };
        endpoint = '/quizzes/${widget.quizId}/questions/true-false';
        break;

      case 'identification':
        if (_identAnswerController.text.trim().isEmpty) {
          setState(() => _loading = false);
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
                content: Text('Answer is required.'),
                backgroundColor: danger),
          );
          return;
        }
        body = {
          'question_text': _questionTextController.text.trim(),
          'image_path': _questionImagePath,
          'video_path': _questionVideoPath,
          'audio_path': _questionAudioPath,
          'points': int.tryParse(_pointsController.text) ?? 1,
          'answer': _identAnswerController.text.trim(),
        };
        endpoint = '/quizzes/${widget.quizId}/questions/identification';
        break;

      case 'matching':
        final lefts = _matchLeft.map((c) => c.text.trim()).toList();
        final rights = _matchRight.map((c) => c.text.trim()).toList();
        if (lefts.any((v) => v.isEmpty) || rights.any((v) => v.isEmpty)) {
          setState(() => _loading = false);
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
                content: Text('Fill in all 4 matching pairs.'),
                backgroundColor: danger),
          );
          return;
        }
        body = {
          'question_text': _questionTextController.text.trim(),
          'image_path': _questionImagePath,
          'video_path': _questionVideoPath,
          'audio_path': _questionAudioPath,
          'points': int.tryParse(_pointsController.text) ?? 1,
          'pairs': List.generate(4, (i) => {
                'left': lefts[i],
                'right': rights[i],
              }),
        };
        endpoint = '/quizzes/${widget.quizId}/questions/matching';
        break;
    }

    final result = await AuthService.authPost(endpoint, body);
    setState(() => _loading = false);

    if (result['success']) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
              content: Text('Question added!'),
              backgroundColor: success),
        );
        Navigator.pop(context, true);
      }
    } else {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
              content: Text(result['message']),
              backgroundColor: danger),
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
        title: const Text(
          'Add Question',
          style: TextStyle(fontWeight: FontWeight.bold),
        ),
        elevation: 0,
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Text(
              'Question Type',
              style: TextStyle(
                  fontWeight: FontWeight.bold,
                  fontSize: 14,
                  color: textPrimary),
            ),
            const SizedBox(height: 8),
            _buildTypeSelector(),
            const SizedBox(height: 20),
            TextField(
              controller: _questionTextController,
              maxLines: 3,
              style: const TextStyle(color: textPrimary),
              decoration: InputDecoration(
                labelText: 'Question Text *',
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
                filled: true,
                fillColor: Colors.white,
              ),
            ),
            const SizedBox(height: 16),

            // Question media section
            Container(
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(14),
                border: Border.all(color: softPurple.withOpacity(0.5)),
                boxShadow: [
                  BoxShadow(
                    color: plumShadow.withOpacity(0.08),
                    blurRadius: 10,
                    offset: const Offset(0, 4),
                  ),
                ],
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'Question Media (optional)',
                    style: TextStyle(
                        fontWeight: FontWeight.bold,
                        fontSize: 14,
                        color: primaryColor),
                  ),
                  const SizedBox(height: 12),

                  // Image
                  const Text(
                    'Image',
                    style: TextStyle(
                        fontWeight: FontWeight.w600,
                        fontSize: 13,
                        color: textMuted),
                  ),
                  const SizedBox(height: 4),
                  ImagePickerWidget(
                    label: 'Add image to question',
                    onImageSelected: (path, url) =>
                        setState(() => _questionImagePath = path),
                  ),
                  const SizedBox(height: 12),

                  // Video
                  const Text(
                    'Video',
                    style: TextStyle(
                        fontWeight: FontWeight.w600,
                        fontSize: 13,
                        color: textMuted),
                  ),
                  const SizedBox(height: 4),
                  VideoPickerWidget(
                    onVideoUploaded: (videoUrl, videoPath) => setState(() {
                      _questionVideoPath = videoPath;
                      _questionVideoUrl = videoUrl;
                    }),
                    onVideoRemoved: () => setState(() {
                      _questionVideoPath = null;
                      _questionVideoUrl = null;
                    }),
                  ),
                  if (_questionVideoUrl != null) ...[
                    const SizedBox(height: 8),
                    VideoPlayerWidget(videoUrl: _questionVideoUrl!),
                  ],
                  const SizedBox(height: 12),

                  // Audio
                  const Text(
                    'Audio',
                    style: TextStyle(
                        fontWeight: FontWeight.w600,
                        fontSize: 13,
                        color: textMuted),
                  ),
                  const SizedBox(height: 4),
                  AudioPickerWidget(
                    onAudioUploaded: (audioUrl, audioPath) => setState(() {
                      _questionAudioPath = audioPath;
                      _questionAudioUrl = audioUrl;
                    }),
                    onAudioRemoved: () => setState(() {
                      _questionAudioPath = null;
                      _questionAudioUrl = null;
                    }),
                  ),
                  if (_questionAudioUrl != null) ...[
                    const SizedBox(height: 8),
                    AudioPlayerWidget(audioUrl: _questionAudioUrl!),
                  ],
                ],
              ),
            ),
            const SizedBox(height: 16),

            TextField(
              controller: _pointsController,
              keyboardType: TextInputType.number,
              style: const TextStyle(color: textPrimary),
              decoration: InputDecoration(
                labelText: 'Points',
                labelStyle: const TextStyle(color: textMuted),
                prefixIcon:
                    const Icon(Icons.star, color: accentGold),
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
                filled: true,
                fillColor: Colors.white,
              ),
            ),
            const SizedBox(height: 24),
            _buildTypeFields(),
            const SizedBox(height: 32),
            ElevatedButton(
              onPressed: _loading ? null : _submit,
              style: ElevatedButton.styleFrom(
                backgroundColor: accentGold,
                foregroundColor: textPrimary,
                padding: const EdgeInsets.symmetric(vertical: 16),
                elevation: 3,
                shadowColor: plumShadow.withOpacity(0.3),
                shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12)),
              ),
              child: _loading
                  ? const SizedBox(
                      height: 20,
                      width: 20,
                      child: CircularProgressIndicator(
                          color: textPrimary, strokeWidth: 2))
                  : const Text(
                      'Add Question',
                      style: TextStyle(
                          fontSize: 16, fontWeight: FontWeight.bold),
                    ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildTypeSelector() {
    final types = [
      {'value': 'multiple_choice', 'label': 'Multiple Choice', 'icon': Icons.radio_button_checked},
      {'value': 'true_false',      'label': 'True / False',    'icon': Icons.check_circle_outline},
      {'value': 'identification',  'label': 'Identification',  'icon': Icons.edit},
      {'value': 'matching',        'label': 'Matching',        'icon': Icons.compare_arrows},
    ];

    return Wrap(
      spacing: 8,
      runSpacing: 8,
      children: types.map((t) {
        final selected = _selectedType == t['value'];
        return ChoiceChip(
          label: Text(t['label'] as String),
          avatar: Icon(
            t['icon'] as IconData,
            size: 16,
            color: selected ? primaryColor : textMuted,
          ),
          selected: selected,
          selectedColor: primaryLight,
          backgroundColor: Colors.white,
          side: BorderSide(
            color: selected ? primaryColor : softPurple,
            width: selected ? 1.5 : 1,
          ),
          labelStyle: TextStyle(
            color: selected ? primaryColor : textPrimary,
            fontWeight: FontWeight.w600,
          ),
          onSelected: (_) =>
              setState(() => _selectedType = t['value'] as String),
        );
      }).toList(),
    );
  }

  Widget _buildTypeFields() {
    switch (_selectedType) {
      case 'multiple_choice':
        return _buildMCFields();
      case 'true_false':
        return _buildTFFields();
      case 'identification':
        return _buildIdentFields();
      case 'matching':
        return _buildMatchingFields();
      default:
        return const SizedBox();
    }
  }

  Widget _buildMCFields() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text('Answer Options',
            style: TextStyle(
                fontWeight: FontWeight.bold, color: textPrimary)),
        const Text('Tap the circle to mark the correct answer',
            style: TextStyle(fontSize: 12, color: textSubtle)),
        const SizedBox(height: 12),
        ...List.generate(4, (i) => Padding(
              padding: const EdgeInsets.only(bottom: 16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Radio<int>(
                        value: i,
                        groupValue: _mcCorrectIndex,
                        activeColor: primaryColor,
                        onChanged: (v) =>
                            setState(() => _mcCorrectIndex = v!),
                      ),
                      Expanded(
                        child: TextField(
                          controller: _mcOptions[i],
                          style: const TextStyle(color: textPrimary),
                          decoration: InputDecoration(
                            labelText: 'Option ${i + 1}',
                            labelStyle:
                                const TextStyle(color: textMuted),
                            border: OutlineInputBorder(
                                borderRadius: BorderRadius.circular(10),
                                borderSide:
                                    const BorderSide(color: softPurple)),
                            enabledBorder: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(10),
                              borderSide:
                                  const BorderSide(color: softPurple),
                            ),
                            focusedBorder: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(10),
                              borderSide: const BorderSide(
                                  color: primaryColor, width: 2),
                            ),
                            filled: true,
                            fillColor: Colors.white,
                          ),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 6),
                  Padding(
                    padding: const EdgeInsets.only(left: 48),
                    child: Container(
                      padding: const EdgeInsets.all(10),
                      decoration: BoxDecoration(
                        color: primaryLight.withOpacity(0.4),
                        borderRadius: BorderRadius.circular(10),
                        border: Border.all(
                            color: softPurple.withOpacity(0.5)),
                        boxShadow: [
                          BoxShadow(
                            color: plumShadow.withOpacity(0.06),
                            blurRadius: 6,
                            offset: const Offset(0, 2),
                          ),
                        ],
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text('Option Media',
                              style: TextStyle(
                                  fontSize: 12,
                                  fontWeight: FontWeight.w600,
                                  color: highlightPurple)),
                          const SizedBox(height: 6),
                          ImagePickerWidget(
                            label: 'Add image to option ${i + 1}',
                            onImageSelected: (path, url) => setState(
                                () => _mcOptionImagePaths[i] = path),
                          ),
                          const SizedBox(height: 6),
                          VideoPickerWidget(
                            onVideoUploaded: (videoUrl, videoPath) {
                              final index = i;
                              setState(() {
                                _mcOptionVideoPaths[index] = videoPath;
                                _mcOptionVideoUrls[index] = videoUrl;
                              });
                            },
                            onVideoRemoved: () {
                              final index = i;
                              setState(() {
                                _mcOptionVideoPaths[index] = null;
                                _mcOptionVideoUrls[index] = null;
                              });
                            },
                          ),
                          if (_mcOptionVideoUrls[i] != null) ...[
                            const SizedBox(height: 6),
                            VideoPlayerWidget(
                              videoUrl: _mcOptionVideoUrls[i]!,
                            ),
                          ],
                          const SizedBox(height: 6),
                          AudioPickerWidget(
                            onAudioUploaded: (audioUrl, audioPath) {
                              final index = i;
                              setState(() {
                                _mcOptionAudioPaths[index] = audioPath;
                                _mcOptionAudioUrls[index] = audioUrl;
                              });
                            },
                            onAudioRemoved: () {
                              final index = i;
                              setState(() {
                                _mcOptionAudioPaths[index] = null;
                                _mcOptionAudioUrls[index] = null;
                              });
                            },
                          ),
                          if (_mcOptionAudioUrls[i] != null) ...[
                            const SizedBox(height: 6),
                            AudioPlayerWidget(
                              audioUrl: _mcOptionAudioUrls[i]!,
                            ),
                          ],
                        ],
                      ),
                    ),
                  ),
                ],
              ),
            )),
      ],
    );
  }

  Widget _buildTFFields() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text('Correct Answer',
            style: TextStyle(
                fontWeight: FontWeight.bold, color: textPrimary)),
        const SizedBox(height: 12),
        Row(
          children: [
            Expanded(
              child: GestureDetector(
                onTap: () => setState(() => _tfCorrectAnswer = true),
                child: Container(
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    gradient: _tfCorrectAnswer
                        ? const LinearGradient(
                            colors: [primaryColor, primaryDark],
                            begin: Alignment.topLeft,
                            end: Alignment.bottomRight,
                          )
                        : null,
                    color: _tfCorrectAnswer ? null : Colors.white,
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(
                        color: primaryColor,
                        width: _tfCorrectAnswer ? 0 : 1.5),
                    boxShadow: _tfCorrectAnswer
                        ? [
                            BoxShadow(
                              color: plumShadow.withOpacity(0.25),
                              blurRadius: 8,
                              offset: const Offset(0, 3),
                            )
                          ]
                        : null,
                  ),
                  child: Center(
                    child: Text(
                      'TRUE',
                      style: TextStyle(
                          color: _tfCorrectAnswer
                              ? Colors.white
                              : primaryColor,
                          fontWeight: FontWeight.bold,
                          fontSize: 16),
                    ),
                  ),
                ),
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: GestureDetector(
                onTap: () => setState(() => _tfCorrectAnswer = false),
                child: Container(
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    gradient: !_tfCorrectAnswer
                        ? const LinearGradient(
                            colors: [primaryColor, primaryDark],
                            begin: Alignment.topLeft,
                            end: Alignment.bottomRight,
                          )
                        : null,
                    color: !_tfCorrectAnswer ? null : Colors.white,
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(
                        color: primaryColor,
                        width: !_tfCorrectAnswer ? 0 : 1.5),
                    boxShadow: !_tfCorrectAnswer
                        ? [
                            BoxShadow(
                              color: plumShadow.withOpacity(0.25),
                              blurRadius: 8,
                              offset: const Offset(0, 3),
                            )
                          ]
                        : null,
                  ),
                  child: Center(
                    child: Text(
                      'FALSE',
                      style: TextStyle(
                          color: !_tfCorrectAnswer
                              ? Colors.white
                              : primaryColor,
                          fontWeight: FontWeight.bold,
                          fontSize: 16),
                    ),
                  ),
                ),
              ),
            ),
          ],
        ),
      ],
    );
  }

  Widget _buildIdentFields() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text('Correct Answer',
            style: TextStyle(
                fontWeight: FontWeight.bold, color: textPrimary)),
        const SizedBox(height: 12),
        TextField(
          controller: _identAnswerController,
          style: const TextStyle(color: textPrimary),
          decoration: InputDecoration(
            labelText: 'Answer *',
            labelStyle: const TextStyle(color: textMuted),
            hintText: 'e.g. Filipino',
            hintStyle: const TextStyle(color: textSubtle),
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
            filled: true,
            fillColor: Colors.white,
          ),
        ),
      ],
    );
  }

  Widget _buildMatchingFields() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text('Matching Pairs',
            style: TextStyle(
                fontWeight: FontWeight.bold, color: textPrimary)),
        const Text('Left column → Right column (correct pairs)',
            style: TextStyle(fontSize: 12, color: textSubtle)),
        const SizedBox(height: 12),
        ...List.generate(
            4,
            (i) => Padding(
                  padding: const EdgeInsets.only(bottom: 12),
                  child: Row(
                    children: [
                      Expanded(
                        child: TextField(
                          controller: _matchLeft[i],
                          style: const TextStyle(color: textPrimary),
                          decoration: InputDecoration(
                            labelText: 'Left ${i + 1}',
                            labelStyle:
                                const TextStyle(color: textMuted),
                            border: OutlineInputBorder(
                                borderRadius: BorderRadius.circular(10),
                                borderSide:
                                    const BorderSide(color: softPurple)),
                            enabledBorder: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(10),
                              borderSide:
                                  const BorderSide(color: softPurple),
                            ),
                            focusedBorder: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(10),
                              borderSide: const BorderSide(
                                  color: primaryColor, width: 2),
                            ),
                            filled: true,
                            fillColor: Colors.white,
                          ),
                        ),
                      ),
                      const Padding(
                        padding: EdgeInsets.symmetric(horizontal: 8),
                        child: Icon(Icons.arrow_forward,
                            color: highlightPurple),
                      ),
                      Expanded(
                        child: TextField(
                          controller: _matchRight[i],
                          style: const TextStyle(color: textPrimary),
                          decoration: InputDecoration(
                            labelText: 'Right ${i + 1}',
                            labelStyle:
                                const TextStyle(color: textMuted),
                            border: OutlineInputBorder(
                                borderRadius: BorderRadius.circular(10),
                                borderSide:
                                    const BorderSide(color: softPurple)),
                            enabledBorder: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(10),
                              borderSide:
                                  const BorderSide(color: softPurple),
                            ),
                            focusedBorder: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(10),
                              borderSide: const BorderSide(
                                  color: primaryColor, width: 2),
                            ),
                            filled: true,
                            fillColor: Colors.white,
                          ),
                        ),
                      ),
                    ],
                  ),
                )),
      ],
    );
  }
}