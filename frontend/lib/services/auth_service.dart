import 'dart:convert';
import 'dart:io';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import 'package:path_provider/path_provider.dart';
import 'package:open_filex/open_filex.dart';
import 'download_stub.dart'
    if (dart.library.html) 'download_web.dart';

class AuthService {

 

  // static const String ip = 'localhost';
  static const String ip = '10.105.198.155';

  static const String baseUrl    = 'http://$ip:8000/api';
  static const String storageUrl = 'http://$ip:8000/storage';

  static Future<Map> login(String email, String password) async {
    try {
      print('URL: $baseUrl/login');
      final response = await http.post(
        Uri.parse('$baseUrl/login'),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'email': email, 'password': password}),
      );
      print('STATUS: ${response.statusCode}');
      print('BODY: ${response.body}');
      final data = jsonDecode(response.body);
      if (response.statusCode == 200) {
        final prefs = await SharedPreferences.getInstance();
        await prefs.setString('token', data['token']);
        await prefs.setString('role', data['user']['role']);
        await prefs.setString('name', data['user']['name']);
        await prefs.setString('first_name', data['user']['first_name'] ?? '');
        await prefs.setString('middle_initial', data['user']['middle_initial'] ?? '');
        await prefs.setString('surname', data['user']['surname'] ?? '');
        await prefs.setInt('user_id', data['user']['id']);
        return {'success': true, 'data': data};
      } else {
        return {'success': false, 'message': data['message']};
      }
    } catch (e) {
      return {'success': false, 'message': 'Cannot connect to server. Please check your connection.'};
    }
  }

  static Future logout() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.clear();
  }

  static Future getToken() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString('token');
  }

  static Future getRole() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString('role');
  }

  static Future getName() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString('name');
  }

  static Future getFirstName() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString('first_name');
  }

  static Future getMiddleInitial() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString('middle_initial');
  }

  static Future getSurname() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString('surname');
  }

  static Future isLoggedIn() async {
    final token = await getToken();
    return token != null;
  }

  // ─── GET ──────────────────────────────────────────────────────
  static Future<Map> authGet(String endpoint) async {
    try {
      final token = await getToken();
      final response = await http.get(
        Uri.parse('$baseUrl$endpoint'),
        headers: {
          'Content-Type': 'application/json',
          'Authorization': 'Bearer $token',
        },
      );
      final data = jsonDecode(response.body);
      if (response.statusCode == 200) {
        return {'success': true, 'data': data};
      } else {
        return {'success': false, 'message': data['message'] ?? 'Error'};
      }
    } catch (e) {
      return {'success': false, 'message': 'Cannot connect to server. Please check your connection.'};
    }
  }

  // ─── POST ─────────────────────────────────────────────────────
  static Future<Map> authPost(String endpoint, Map body) async {
    try {
      final token = await getToken();
      final response = await http.post(
        Uri.parse('$baseUrl$endpoint'),
        headers: {
          'Content-Type': 'application/json',
          'Authorization': 'Bearer $token',
        },
        body: jsonEncode(body),
      );
      final data = jsonDecode(response.body);
      if (response.statusCode == 200 || response.statusCode == 201) {
        return {'success': true, 'data': data};
      } else {
        return {'success': false, 'message': data['message'] ?? 'Error'};
      }
    } catch (e) {
      return {'success': false, 'message': 'Cannot connect to server. Please check your connection.'};
    }
  }

  // ─── PUT ──────────────────────────────────────────────────────
  static Future<Map> authPut(String endpoint, Map body) async {
    try {
      final token = await getToken();
      final response = await http.put(
        Uri.parse('$baseUrl$endpoint'),
        headers: {
          'Content-Type': 'application/json',
          'Authorization': 'Bearer $token',
        },
        body: jsonEncode(body),
      );
      final data = jsonDecode(response.body);
      if (response.statusCode == 200 || response.statusCode == 201) {
        return {'success': true, 'data': data};
      } else {
        if (data['errors'] != null) {
          final errors = data['errors'] as Map<String, dynamic>;
          final firstError = errors.values.first as List<dynamic>;
          return {'success': false, 'message': firstError.first.toString()};
        }
        return {'success': false, 'message': data['message'] ?? 'Error'};
      }
    } catch (e) {
      return {'success': false, 'message': 'Cannot connect to server. Please check your connection.'};
    }
  }

  // ─── PATCH ────────────────────────────────────────────────────
  static Future<Map> authPatch(String endpoint, Map body) async {
    try {
      final token = await getToken();
      final response = await http.patch(
        Uri.parse('$baseUrl$endpoint'),
        headers: {
          'Content-Type': 'application/json',
          'Authorization': 'Bearer $token',
        },
        body: jsonEncode(body),
      );
      final data = jsonDecode(response.body);
      if (response.statusCode >= 200 && response.statusCode < 300) {
        return {'success': true, 'data': data['data'], 'message': data['message'] ?? ''};
      } else {
        return {'success': false, 'message': data['message'] ?? 'Error'};
      }
    } catch (e) {
      return {'success': false, 'message': 'Cannot connect to server. Please check your connection.'};
    }
  }

  // ─── DELETE ───────────────────────────────────────────────────
  static Future<Map> authDelete(String endpoint) async {
    try {
      final token = await getToken();
      final response = await http.delete(
        Uri.parse('$baseUrl$endpoint'),
        headers: {
          'Content-Type': 'application/json',
          'Authorization': 'Bearer $token',
        },
      );
      final data = jsonDecode(response.body);
      if (response.statusCode == 200) {
        return {'success': true, 'message': data['message'] ?? 'Deleted'};
      } else {
        return {'success': false, 'message': data['message'] ?? 'Error'};
      }
    } catch (e) {
      return {'success': false, 'message': 'Cannot connect to server. Please check your connection.'};
    }
  }

  static String fixImageUrl(dynamic url) {
    if (url == null) return '';
    String urlStr = url.toString();
    if (urlStr.isEmpty) return '';
    if (urlStr.startsWith('http')) {
      return urlStr
          .replaceAll('http://localhost', 'http://$ip')
          .replaceAll('http://127.0.0.1', 'http://$ip');
    }
    return 'http://$ip:8000/storage/$urlStr';
  }

  static Map<String, dynamic> parseJson(String body) {
    return jsonDecode(body) as Map<String, dynamic>;
  }

  // ─── DOWNLOAD FILE (MOBILE + WEB) ─────────────────────────────
  static Future<Map> downloadFile(String endpoint, String filename) async {
    try {
      final token = await getToken();

      final response = await http.get(
        Uri.parse('$baseUrl$endpoint'),
        headers: {
          'Authorization': 'Bearer $token',
        },
      );

      if (response.statusCode == 200) {
        final bytes = response.bodyBytes;

        try {
          // On web: triggers browser download
          await downloadFileForWeb(bytes, filename);
        } catch (_) {
          // On mobile: save to public Downloads folder and open
          final dir = Directory('/storage/emulated/0/Download');
          if (!await dir.exists()) {
            await dir.create(recursive: true);
          }
          final filePath = '${dir.path}/$filename';
          final file = File(filePath);
          await file.writeAsBytes(bytes);
          await OpenFilex.open(filePath);
        }

        return {'success': true};
      } else {
        return {
          'success': false,
          'message': 'Download failed (${response.statusCode})'
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': 'Download failed: $e'
      };
    }
  }
}