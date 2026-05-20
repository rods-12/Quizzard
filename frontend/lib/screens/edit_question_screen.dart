import 'package:flutter/material.dart';
import '../services/auth_service.dart';
import '../widgets/image_picker_widget.dart';
import '../widgets/video_picker_widget.dart';
import '../widgets/video_player_widget.dart';
import '../widgets/audio_picker_widget.dart';
import '../widgets/audio_player_widget.dart';

class EditQuestionScreen extends StatefulWidget {
  final int quizId;
  final Map<String, dynamic> question;

  const EditQuestionScreen({
    super.key,
    required this.quizId,
    required this.question,
  });

  @override
  State<EditQuestionScreen> createState() => _EditQuestionScreenState();
}

class _EditQuestionScreenState extends State<EditQuestionScreen> {
  static const Color primaryColor    = Color(0xFF5B2A9B);
  static const Color primaryDark     = Color(0xFF3A1A6B);
  static const Color primaryLight    = Color(0xFFEDE7F2);
  static const Color accentGold      = Color(0xFFF2C94C);
  static const Color textDark        = Color(0xFF1F1235);
  static const Color textMid         = Color(0xFF7B6F96);
  static const Color textLight       = Color(0xFFA99BC4);
  static const Color plumShadow      = Color(0xFF2A1247);
  static const Color bgColor         = Color(0xFFFAF6EC);

  late String _questionType;
  bool _loading = false;

  // Common
  late TextEditingController _questionTextController;
  late TextEditingController _pointsController;

  // Question media — all 3 separate
  String? _questionImagePath;
  String? _questionImageUrl;
  String? _questionVideoPath;
  String? _questionVideoUrl;
  String? _questionAudioPath;
  String? _questionAudioUrl;

  // Multiple choice
  late List<TextEditingController> _mcOptions;
  final List<String?> _mcOptionImagePaths = List.generate(4, (_) => null);
  final List<String?> _mcOptionImageUrls  = List.generate(4, (_) => null);
  final List<String?> _mcOptionVideoPaths = List.generate(4, (_) => null);
  final List<String?> _mcOptionVideoUrls  = List.generate(4, (_) => null);
  final List<String?> _mcOptionAudioPaths = List.generate(4, (_) => null);
  final List<String?> _mcOptionAudioUrls  = List.generate(4, (_) => null);
  int _mcCorrectIndex = 0;

  // True/False
  bool _tfCorrectAnswer = true;

  // Identification
  late TextEditingController _identAnswerController;

  // Matching
  late List<TextEditingController> _matchLeft;
  late List<TextEditingController> _matchRight;

  @override
  void initState() {
    super.initState();
    _questionType = widget.question['question_type'] ?? 'multiple_choice';

    _questionTextController = TextEditingController(
      text: widget.question['question_text'] ?? '',
    );
    _pointsController = TextEditingController(
      text: '${widget.question['points'] ?? 1}',
    );

    final existingImagePath = widget.question['image_path'];
    if (existingImagePath != null && existingImagePath.toString().isNotEmpty) {
      _questionImageUrl = AuthService.fixImageUrl(existingImagePath);
      _questionImagePath = existingImagePath;
    }

    final existingVideoPath = widget.question['video_path'];
    if (existingVideoPath != null && existingVideoPath.toString().isNotEmpty) {
      _questionVideoUrl = AuthService.fixImageUrl(existingVideoPath);
      _questionVideoPath = existingVideoPath;
    }

    final existingAudioPath = widget.question['audio_path'];
    if (existingAudioPath != null && existingAudioPath.toString().isNotEmpty) {
      _questionAudioUrl = AuthService.fixImageUrl(existingAudioPath);
      _questionAudioPath = existingAudioPath;
    }

    final options = List<Map<String, dynamic>>.from(
      widget.question['answer_options'] ?? [],
    );

    _mcOptions = List.generate(4, (i) {
      return TextEditingController(
        text: i < options.length ? (options[i]['option_text'] ?? '') : '',
      );
    });
    for (int i = 0; i < options.length; i++) {
      if (options[i]['is_correct'] == true || options[i]['is_correct'] == 1) {
        _mcCorrectIndex = i;
      }
      final optionImage = options[i]['image_path'];
      if (optionImage != null && optionImage.toString().isNotEmpty) {
        _mcOptionImageUrls[i] = AuthService.fixImageUrl(optionImage);
        _mcOptionImagePaths[i] = optionImage;
      }
      final optionVideo = options[i]['video_path'];
      if (optionVideo != null && optionVideo.toString().isNotEmpty) {
        _mcOptionVideoUrls[i] = AuthService.fixImageUrl(optionVideo);
        _mcOptionVideoPaths[i] = optionVideo;
      }
      final optionAudio = options[i]['audio_path'];
      if (optionAudio != null && optionAudio.toString().isNotEmpty) {
        _mcOptionAudioUrls[i] = AuthService.fixImageUrl(optionAudio);
        _mcOptionAudioPaths[i] = optionAudio;
      }
    }

    if (_questionType == 'true_false') {
      final trueOption = options.firstWhere(
        (o) => (o['option_text'] ?? '').toString().toLowerCase() == 'true',
        orElse: () => {},
      );
      _tfCorrectAnswer =
          trueOption['is_correct'] == true || trueOption['is_correct'] == 1;
    }

    _identAnswerController = TextEditingController(
      text: options.isNotEmpty ? (options[0]['option_text'] ?? '') : '',
    );

    _matchLeft = List.generate(4, (i) => TextEditingController(
          text: i < options.length ? (options[i]['option_text'] ?? '') : '',
        ));
    _matchRight = List.generate(4, (i) => TextEditingController(
          text: i < options.length ? (options[i]['match_pair'] ?? '') : '',
        ));
  }

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
            backgroundColor: Color(0xFFEF4444)),
      );
      return;
    }

    setState(() => _loading = true);

    Map<String, dynamic> body = {
      'question_text': _questionTextController.text.trim(),
      'points': int.tryParse(_pointsController.text) ?? 1,
      'image_path': _questionImagePath,
      'video_path': _questionVideoPath,
      'audio_path': _questionAudioPath,
    };

    switch (_questionType) {
      case 'multiple_choice':
        final options = _mcOptions.map((c) => c.text.trim()).toList();
        if (options.any((o) => o.isEmpty)) {
          setState(() => _loading = false);
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
                content: Text('Fill in all 4 options.'),
                backgroundColor: Color(0xFFEF4444)),
          );
          return;
        }
        body['options'] = List.generate(4, (i) => {
              'option_text': options[i],
              'is_correct': i == _mcCorrectIndex,
              'image_path': _mcOptionImagePaths[i],
              'video_path': _mcOptionVideoPaths[i],
              'audio_path': _mcOptionAudioPaths[i],
            });
        break;

      case 'true_false':
        body['correct_answer'] = _tfCorrectAnswer;
        break;

      case 'identification':
        if (_identAnswerController.text.trim().isEmpty) {
          setState(() => _loading = false);
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
                content: Text('Answer is required.'),
                backgroundColor: Color(0xFFEF4444)),
          );
          return;
        }
        body['answer'] = _identAnswerController.text.trim();
        break;

      case 'matching':
        final lefts = _matchLeft.map((c) => c.text.trim()).toList();
        final rights = _matchRight.map((c) => c.text.trim()).toList();
        if (lefts.any((v) => v.isEmpty) || rights.any((v) => v.isEmpty)) {
          setState(() => _loading = false);
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
                content: Text('Fill in all 4 matching pairs.'),
                backgroundColor: Color(0xFFEF4444)),
          );
          return;
        }
        body['pairs'] = List.generate(4, (i) => {
              'left': lefts[i],
              'right': rights[i],
            });
        break;
    }

    final questionId = widget.question['id'];
    final result = await AuthService.authPut(
      '/quizzes/${widget.quizId}/questions/$questionId',
      body,
    );

    setState(() => _loading = false);

    if (result['success']) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
              content: Text('Question updated!'),
              backgroundColor: Color(0xFF22C55E)),
        );
        Navigator.pop(context, true);
      }
    } else {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
              content: Text(result['message']),
              backgroundColor: const Color(0xFFEF4444)),
        );
      }
    }
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

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: bgColor,
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
        title: Text('Edit ${_questionTypeLabel(_questionType)}'),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            // Question type label
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
              decoration: BoxDecoration(
                color: primaryLight,
                borderRadius: BorderRadius.circular(8),
                border: Border.all(color: primaryColor.withOpacity(0.3)),
              ),
              child: Row(
                children: [
                  const Icon(Icons.info_outline,
                      size: 16, color: primaryColor),
                  const SizedBox(width: 8),
                  Text(
                    'Type: ${_questionTypeLabel(_questionType)}',
                    style: const TextStyle(
                        color: primaryColor, fontWeight: FontWeight.w600),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 20),

            // Question text
            TextField(
              controller: _questionTextController,
              maxLines: 3,
              style: const TextStyle(color: textDark),
              decoration: InputDecoration(
                labelText: 'Question Text *',
                labelStyle: const TextStyle(color: textMid),
                filled: true,
                fillColor: primaryLight,
                border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(12),
                    borderSide: BorderSide.none),
                focusedBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(12),
                  borderSide:
                      const BorderSide(color: primaryColor, width: 2),
                ),
              ),
            ),
            const SizedBox(height: 16),

            // Question media section
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: primaryLight),
                boxShadow: [
                  BoxShadow(
                    color: plumShadow.withOpacity(0.06),
                    blurRadius: 10,
                    offset: const Offset(0, 4),
                  ),
                ],
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text('Question Media (optional)',
                      style: TextStyle(
                          fontWeight: FontWeight.bold,
                          fontSize: 14,
                          color: primaryColor)),
                  const SizedBox(height: 12),

                  // Image
                  const Text('Image',
                      style: TextStyle(
                          fontWeight: FontWeight.w600,
                          fontSize: 13,
                          color: textLight)),
                  const SizedBox(height: 4),
                  ImagePickerWidget(
                    currentImageUrl: _questionImageUrl,
                    label: 'Add image to question',
                    onImageSelected: (path, url) => setState(() {
                      _questionImagePath = path;
                      _questionImageUrl = url;
                    }),
                  ),
                  const SizedBox(height: 12),

                  // Video
                  const Text('Video',
                      style: TextStyle(
                          fontWeight: FontWeight.w600,
                          fontSize: 13,
                          color: textLight)),
                  const SizedBox(height: 4),
                  VideoPickerWidget(
                    initialVideoUrl: _questionVideoUrl,
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
                  const Text('Audio',
                      style: TextStyle(
                          fontWeight: FontWeight.w600,
                          fontSize: 13,
                          color: textLight)),
                  const SizedBox(height: 4),
                  AudioPickerWidget(
                    initialAudioUrl: _questionAudioUrl,
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

            // Points
            TextField(
              controller: _pointsController,
              keyboardType: TextInputType.number,
              style: const TextStyle(color: textDark),
              decoration: InputDecoration(
                labelText: 'Points',
                labelStyle: const TextStyle(color: textMid),
                prefixIcon:
                    const Icon(Icons.star, color: accentGold),
                filled: true,
                fillColor: primaryLight,
                border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(12),
                    borderSide: BorderSide.none),
                focusedBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(12),
                  borderSide:
                      const BorderSide(color: primaryColor, width: 2),
                ),
              ),
            ),
            const SizedBox(height: 24),

            // Type-specific fields
            _buildTypeFields(),
            const SizedBox(height: 32),

            ElevatedButton(
              onPressed: _loading ? null : _submit,
              style: ElevatedButton.styleFrom(
                backgroundColor: accentGold,
                disabledBackgroundColor: accentGold.withOpacity(0.5),
                foregroundColor: textDark,
                padding: const EdgeInsets.symmetric(vertical: 16),
                shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12)),
                elevation: 3,
                shadowColor: plumShadow.withOpacity(0.3),
              ),
              child: _loading
                  ? const SizedBox(
                      height: 20,
                      width: 20,
                      child: CircularProgressIndicator(
                          color: textDark, strokeWidth: 2))
                  : const Text('Save Changes',
                      style: TextStyle(
                          fontSize: 16, fontWeight: FontWeight.bold)),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildTypeFields() {
    switch (_questionType) {
      case 'multiple_choice': return _buildMCFields();
      case 'true_false':      return _buildTFFields();
      case 'identification':  return _buildIdentFields();
      case 'matching':        return _buildMatchingFields();
      default:                return const SizedBox();
    }
  }

  Widget _buildMCFields() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text('Answer Options',
            style: TextStyle(
                fontWeight: FontWeight.bold, color: textDark)),
        const Text('Tap the circle to mark the correct answer',
            style: TextStyle(fontSize: 12, color: textLight)),
        const SizedBox(height: 12),
        ...List.generate(
            4,
            (i) => Padding(
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
                              style: const TextStyle(color: textDark),
                              decoration: InputDecoration(
                                labelText: 'Option ${i + 1}',
                                labelStyle:
                                    const TextStyle(color: textMid),
                                filled: true,
                                fillColor: primaryLight,
                                border: OutlineInputBorder(
                                    borderRadius:
                                        BorderRadius.circular(10),
                                    borderSide: BorderSide.none),
                                focusedBorder: OutlineInputBorder(
                                  borderRadius: BorderRadius.circular(10),
                                  borderSide: const BorderSide(
                                      color: primaryColor, width: 2),
                                ),
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
                            color: Colors.white,
                            borderRadius: BorderRadius.circular(10),
                            border: Border.all(color: primaryLight),
                            boxShadow: [
                              BoxShadow(
                                color: plumShadow.withOpacity(0.05),
                                blurRadius: 8,
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
                                      color: textLight)),
                              const SizedBox(height: 6),
                              ImagePickerWidget(
                                currentImageUrl: _mcOptionImageUrls[i],
                                label: 'Add image to option ${i + 1}',
                                onImageSelected: (path, url) =>
                                    setState(() {
                                  _mcOptionImagePaths[i] = path;
                                  _mcOptionImageUrls[i] = url;
                                }),
                              ),
                              const SizedBox(height: 6),
                              VideoPickerWidget(
                                initialVideoUrl: _mcOptionVideoUrls[i],
                                onVideoUploaded: (videoUrl, videoPath) =>
                                    setState(() {
                                  _mcOptionVideoPaths[i] = videoPath;
                                  _mcOptionVideoUrls[i] = videoUrl;
                                }),
                                onVideoRemoved: () => setState(() {
                                  _mcOptionVideoPaths[i] = null;
                                  _mcOptionVideoUrls[i] = null;
                                }),
                              ),
                              if (_mcOptionVideoUrls[i] != null) ...[
                                const SizedBox(height: 6),
                                VideoPlayerWidget(
                                  videoUrl: _mcOptionVideoUrls[i]!,
                                ),
                              ],
                              const SizedBox(height: 6),
                              AudioPickerWidget(
                                initialAudioUrl: _mcOptionAudioUrls[i],
                                onAudioUploaded: (audioUrl, audioPath) =>
                                    setState(() {
                                  _mcOptionAudioPaths[i] = audioPath;
                                  _mcOptionAudioUrls[i] = audioUrl;
                                }),
                                onAudioRemoved: () => setState(() {
                                  _mcOptionAudioPaths[i] = null;
                                  _mcOptionAudioUrls[i] = null;
                                }),
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
                fontWeight: FontWeight.bold, color: textDark)),
        const SizedBox(height: 12),
        Row(
          children: [
            Expanded(
              child: GestureDetector(
                onTap: () => setState(() => _tfCorrectAnswer = true),
                child: Container(
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: _tfCorrectAnswer ? primaryColor : Colors.white,
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: primaryColor),
                    boxShadow: _tfCorrectAnswer
                        ? [
                            BoxShadow(
                              color: plumShadow.withOpacity(0.2),
                              blurRadius: 8,
                              offset: const Offset(0, 3),
                            )
                          ]
                        : [],
                  ),
                  child: Center(
                    child: Text('TRUE',
                        style: TextStyle(
                            color: _tfCorrectAnswer
                                ? Colors.white
                                : primaryColor,
                            fontWeight: FontWeight.bold,
                            fontSize: 16)),
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
                    color: !_tfCorrectAnswer ? primaryColor : Colors.white,
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: primaryColor),
                    boxShadow: !_tfCorrectAnswer
                        ? [
                            BoxShadow(
                              color: plumShadow.withOpacity(0.2),
                              blurRadius: 8,
                              offset: const Offset(0, 3),
                            )
                          ]
                        : [],
                  ),
                  child: Center(
                    child: Text('FALSE',
                        style: TextStyle(
                            color: !_tfCorrectAnswer
                                ? Colors.white
                                : primaryColor,
                            fontWeight: FontWeight.bold,
                            fontSize: 16)),
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
                fontWeight: FontWeight.bold, color: textDark)),
        const SizedBox(height: 12),
        TextField(
          controller: _identAnswerController,
          style: const TextStyle(color: textDark),
          decoration: InputDecoration(
            labelText: 'Answer *',
            labelStyle: const TextStyle(color: textMid),
            filled: true,
            fillColor: primaryLight,
            border: OutlineInputBorder(
                borderRadius: BorderRadius.circular(12),
                borderSide: BorderSide.none),
            focusedBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(12),
              borderSide: const BorderSide(color: primaryColor, width: 2),
            ),
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
                fontWeight: FontWeight.bold, color: textDark)),
        const Text('Left column → Right column',
            style: TextStyle(fontSize: 12, color: textLight)),
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
                          style: const TextStyle(color: textDark),
                          decoration: InputDecoration(
                            labelText: 'Left ${i + 1}',
                            labelStyle:
                                const TextStyle(color: textMid),
                            filled: true,
                            fillColor: primaryLight,
                            border: OutlineInputBorder(
                                borderRadius: BorderRadius.circular(10),
                                borderSide: BorderSide.none),
                            focusedBorder: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(10),
                              borderSide: const BorderSide(
                                  color: primaryColor, width: 2),
                            ),
                          ),
                        ),
                      ),
                      const Padding(
                        padding: EdgeInsets.symmetric(horizontal: 8),
                        child: Icon(Icons.arrow_forward,
                            color: textLight),
                      ),
                      Expanded(
                        child: TextField(
                          controller: _matchRight[i],
                          style: const TextStyle(color: textDark),
                          decoration: InputDecoration(
                            labelText: 'Right ${i + 1}',
                            labelStyle:
                                const TextStyle(color: textMid),
                            filled: true,
                            fillColor: primaryLight,
                            border: OutlineInputBorder(
                                borderRadius: BorderRadius.circular(10),
                                borderSide: BorderSide.none),
                            focusedBorder: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(10),
                              borderSide: const BorderSide(
                                  color: primaryColor, width: 2),
                            ),
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