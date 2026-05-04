import 'package:flutter/material.dart';
import 'screens/login_screen.dart';
import 'screens/register_screen.dart';
import 'screens/student_dashboard_screen.dart';
import 'screens/teacher_dashboard_screen.dart';
import 'screens/admin_dashboard_screen.dart';
import 'screens/question_preview_screen.dart';
import 'screens/quiz_taking_screen.dart';
import 'screens/quiz_result_screen.dart';
import 'screens/create_quiz_screen.dart';
import 'screens/edit_quiz_screen.dart';
import 'screens/quiz_detail_screen.dart';
import 'screens/add_question_screen.dart';
import 'services/auth_service.dart';
import 'screens/edit_question_screen.dart';
import 'screens/quiz_results_screen.dart';
import 'screens/student_attempt_detail_screen.dart';
import 'screens/class_detail_screen.dart';
import 'screens/student_class_quizzes_screen.dart';
import 'screens/quiz_analytics_screen.dart';
import 'screens/student_info_screen.dart';
import 'screens/ai_quiz_generate_screen.dart';





void main() {
  runApp(const QuizzardApp());
}

class QuizzardApp extends StatelessWidget {
  const QuizzardApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Quizzard',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        colorScheme: ColorScheme.fromSeed(
            seedColor: const Color(0xFF6C63FF)),
        useMaterial3: true,
        fontFamily: 'Roboto',
      ),
      home: const SplashScreen(),
      routes: {
        '/login':              (context) => const LoginScreen(),
        '/register':           (context) => const RegisterScreen(),
        '/student-dashboard':  (context) => const StudentDashboardScreen(),
        '/teacher-dashboard':  (context) => const TeacherDashboardScreen(),
        '/admin-dashboard':    (context) => const AdminDashboardScreen(),
        '/question-preview':   (context) => const QuestionPreviewScreen(),
        '/create-quiz':        (context) => const CreateQuizScreen(),
        '/quiz-result':        (context) => const QuizResultScreen(),
        '/quiz-analytics': (context) {
            final args = ModalRoute.of(context)!.settings.arguments as Map<String, dynamic>;
            return QuizAnalyticsScreen(
              quizId: args['quiz_id'],
              quizTitle: args['quiz_title'],
            );
        },
        '/quiz-taking': (context) {
          final args = ModalRoute.of(context)!.settings.arguments as Map<String, dynamic>;
          return QuizTakingScreen(
            quizId: args['quiz_id'],
            quizTitle: args['quiz_title'],
          );
        },
        '/quiz-detail': (context) {
          final args = ModalRoute.of(context)!.settings.arguments as Map<String, dynamic>;
          return QuizDetailScreen(
            quizId: args['quiz_id'],
            quizTitle: args['quiz_title'],
          );
        },
        '/edit-quiz': (context) {
          final args = ModalRoute.of(context)!.settings.arguments as Map<String, dynamic>;
          return EditQuizScreen(
            quizId: args['quiz_id'],
            initialTitle: args['title'],
            initialDescription: args['description'] ?? '',
          );
        },
        '/add-question': (context) {
          final args = ModalRoute.of(context)!.settings.arguments as Map<String, dynamic>;
          return AddQuestionScreen(quizId: args['quiz_id']);
        },

        '/ai-quiz-generate': (context) {
          final args = ModalRoute.of(context)!.settings.arguments as Map<String, dynamic>;
          return AiQuizGenerateScreen(
            quizId: args['quiz_id'],
            quizTitle: args['quiz_title'],
          );
        },
        
        '/edit-question': (context) {
          final args = ModalRoute.of(context)!.settings.arguments as Map<String, dynamic>;
          return EditQuestionScreen(
            quizId: args['quiz_id'],
            question: args['question'],
          );
        },
        '/quiz-results': (context) {
          final args = ModalRoute.of(context)!.settings.arguments as Map<String, dynamic>;
          return QuizResultsScreen(
            quizId: args['quiz_id'],
            quizTitle: args['quiz_title'],
          );
        },
        '/student-attempt-detail': (context) {
          final args = ModalRoute.of(context)!.settings.arguments as Map<String, dynamic>;
          return StudentAttemptDetailScreen(
            quizId: args['quiz_id'],
            quizTitle: args['quiz_title'],
            attemptId: args['attempt_id'],
            studentName: args['student_name'],
          );
        },
        '/class-detail': (context) {
          final args = ModalRoute.of(context)!.settings.arguments as Map<String, dynamic>;
          return ClassDetailScreen(
            classId: args['class_id'],
            className: args['class_name'],
          );
        },
        '/student-class-quizzes': (context) {
          final args = ModalRoute.of(context)!.settings.arguments as Map<String, dynamic>;
          return StudentClassQuizzesScreen(
            classId: args['class_id'],
            className: args['class_name'],
          );
        },
        '/student-info': (context) => const StudentInfoScreen(),
        
      },
    );
  }
}

class SplashScreen extends StatefulWidget {
  const SplashScreen({super.key});

  @override
  State<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends State<SplashScreen> {
  @override
  void initState() {
    super.initState();
    _checkLogin();
  }

  Future<void> _checkLogin() async {
    await Future.delayed(const Duration(seconds: 1));
    final isLoggedIn = await AuthService.isLoggedIn();
    if (!mounted) return;
    if (isLoggedIn) {
      final role = await AuthService.getRole();
      if (role == 'admin') {
        Navigator.pushReplacementNamed(context, '/admin-dashboard');
      } else if (role == 'teacher') {
        Navigator.pushReplacementNamed(context, '/teacher-dashboard');
      } else {
        Navigator.pushReplacementNamed(context, '/student-dashboard');
      }
    } else {
      Navigator.pushReplacementNamed(context, '/login');
    }
  }

  @override
  Widget build(BuildContext context) {
    return const Scaffold(
      backgroundColor: Color(0xFF6C63FF),
      body: Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.quiz, size: 80, color: Colors.white),
            SizedBox(height: 20),
            Text(
              'Quizzard',
              style: TextStyle(
                fontSize: 36,
                fontWeight: FontWeight.bold,
                color: Colors.white,
              ),
            ),
            SizedBox(height: 20),
            CircularProgressIndicator(color: Colors.white),
          ],
        ),
      ),
    );
  }
}