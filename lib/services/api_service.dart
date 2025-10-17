import 'dart:convert';
import 'dart:io' show Platform;
import 'package:flutter/foundation.dart' show kIsWeb;
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

class ApiService {
  // ⚙️ CONFIGURATION: Change this IP for physical Android devices
  // For emulator: use 10.0.2.2
  // For physical device: use your computer's local IP (e.g., 192.168.1.100)
  // To find your IP: Run 'ipconfig' on Windows and look for IPv4 Address
  static const String _localIpAddress = '192.168.118.191';
    static const String _emulatorIpAddress = '10.0.2.2';
  
  // Use Android emulator loopback when running on Android; use 127.0.0.1 for Web/others
  static String get baseUrl {
    if (kIsWeb) {
      // When running Flutter Web on the same PC as XAMPP
      return 'http://127.0.0.1/Attendance-System/backend';
    }
    try {
      if (Platform.isAndroid) {
        return 'http://$_localIpAddress/Attendance-System/backend';
      }
    } catch (_) {
      // Platform may be unavailable; fall back to localhost
    }
    return 'http://localhost/Attendance-System/backend';
  }
  
  // Authentication endpoints
  static Future<Map<String, dynamic>> login(String email, String password) async {
    try {
      print('🔗 API: Sending login request to $baseUrl/auth/login.php');
      final response = await http.post(
        Uri.parse('$baseUrl/auth/login.php'),
        headers: {'Content-Type': 'application/json'},
        body: json.encode({
          'email': email,
          'password': password,
        }),
      ).timeout(
        const Duration(seconds: 30),
        onTimeout: () {
          throw Exception('Connection timeout. Please check your network configuration.');
        },
      );
      
      print('📡 API: Response status: ${response.statusCode}');
      print('📄 API: Response body: ${response.body}');
      
      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        
        // Store token and user data if login successful
        if (data['success'] == true && data['token'] != null) {
          final prefs = await SharedPreferences.getInstance();
          await prefs.setString('token', data['token']);
          await prefs.setString('user', json.encode(data['user']));
        }
        
        return data;
      } else {
        return {
          'success': false,
          'message': 'Server error: ${response.statusCode}'
        };
      }
    } catch (e) {
      print('❌ API: Error in login: $e');
      return {
        'success': false,
        'message': 'Network error: $e'
      };
    }
  }
  
  static Future<Map<String, dynamic>> register(String name, String email, String password) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/auth/register.php'),
        headers: {'Content-Type': 'application/json'},
        body: json.encode({
          'name': name,
          'email': email,
          'password': password,
        }),
      ).timeout(
        const Duration(seconds: 30),
        onTimeout: () {
          throw Exception('Connection timeout. Please check your network configuration.');
        },
      );
      
      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        
        // Store token and user data if registration successful
        if (data['success'] == true && data['token'] != null) {
          final prefs = await SharedPreferences.getInstance();
          await prefs.setString('token', data['token']);
          await prefs.setString('user', json.encode(data['user']));
        }
        
        return data;
      } else {
        return {
          'success': false,
          'message': 'Server error: ${response.statusCode}'
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': 'Network error: $e'
      };
    }
  }
  
  static Future<Map<String, dynamic>> setPassword(
    String email,
    String oldPassword,
    String newPassword,
    String confirmPassword,
  ) async {
    try {
      print('🔗 API: Setting password for $email');
      final response = await http.post(
        Uri.parse('$baseUrl/auth/set_password.php'),
        headers: {'Content-Type': 'application/json'},
        body: json.encode({
          'email': email,
          'old_password': oldPassword,
          'new_password': newPassword,
          'confirm_password': confirmPassword,
        }),
      );
      
      print('📡 API: Response status: ${response.statusCode}');
      print('📄 API: Response body: ${response.body}');
      
      if (response.statusCode == 200) {
        return json.decode(response.body);
      } else {
        return {
          'success': false,
          'message': 'Server error: ${response.statusCode}'
        };
      }
    } catch (e) {
      print('❌ API: Error in setPassword: $e');
      return {
        'success': false,
        'message': 'Network error: $e'
      };
    }
  }
  
  static Future<Map<String, dynamic>> forgotPassword(String email) async {
    try {
      final url = '$baseUrl/auth/forgot_password.php';
      print('🔄 Forgot password for: $email');
      print('🌐 API URL: $url');
      final response = await http.post(
        Uri.parse(url),
        headers: {'Content-Type': 'application/json'},
        body: json.encode({'email': email}),
      ).timeout(
        const Duration(seconds: 30),
        onTimeout: () {
          throw Exception('Connection timeout. Please check your network configuration.');
        },
      );
      print('📡 API: Response status: ${response.statusCode}');
      print('📄 API: Response body: ${response.body}');
      
      if (response.statusCode == 200) {
        return json.decode(response.body);
      } else {
        return {
          'success': false,
          'message': 'Server error: ${response.statusCode}'
        };
      }
    } catch (e) {
      print('❌ API: Error in forgotPassword: $e');
      return {
        'success': false,
        'message': 'Network error: $e'
      };
    }
  }
  
  static Future<Map<String, dynamic>> verifyOTP(String email, String otp) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/auth/verify_otp.php'),
        headers: {'Content-Type': 'application/json'},
        body: json.encode({
          'email': email,
          'otp': otp,
        }),
      );
      
      if (response.statusCode == 200) {
        return json.decode(response.body);
      } else {
        return {
          'success': false,
          'message': 'Server error: ${response.statusCode}'
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': 'Network error: $e'
      };
    }
  }
  
  static Future<Map<String, dynamic>> resetPassword(String email, String password) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/auth/reset_password.php'),
        headers: {'Content-Type': 'application/json'},
        body: json.encode({
          'email': email,
          'password': password,
        }),
      );
      
      if (response.statusCode == 200) {
        return json.decode(response.body);
      } else {
        return {
          'success': false,
          'message': 'Server error: ${response.statusCode}'
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': 'Network error: $e'
      };
    }
  }
  
  // User management endpoints
  static Future<Map<String, dynamic>?> getCurrentUser() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final userString = prefs.getString('user');
      if (userString != null) {
        return json.decode(userString);
      }
      return null;
    } catch (e) {
      return null;
    }
  }
  
  static Future<Map<String, dynamic>> getUserProfile(int userId) async {
    try {
      final token = await _getToken();
      if (token == null) {
        print('❌ API: No token available for getUserProfile');
        return {
          'success': false,
          'message': 'Authentication required. Please login again.'
        };
      }
      print('🔗 API: Fetching user profile for user_id=$userId');
      final response = await http.get(
        Uri.parse('$baseUrl/user/profile.php?user_id=$userId'),
        headers: {
          'Content-Type': 'application/json',
          'Authorization': 'Bearer $token',
        },
      ).timeout(
        const Duration(seconds: 30),
        onTimeout: () {
          throw Exception('Connection timeout. Please check your network configuration.');
        },
      );
      
      print('📡 API: Profile response status: ${response.statusCode}');
      print('📄 API: Profile response body: ${response.body}');
      
      if (response.statusCode == 200) {
        return json.decode(response.body);
      } else {
        return {
          'success': false,
          'message': 'Server error: ${response.statusCode}'
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': 'Network error: $e'
      };
    }
  }
  
  static Future<Map<String, dynamic>> updateProfile(int userId, String name, String email) async {
    try {
      final token = await _getToken();
      final response = await http.post(
        Uri.parse('$baseUrl/user/update.php'),
        headers: {
          'Content-Type': 'application/json',
          'Authorization': 'Bearer $token',
        },
        body: json.encode({
          'user_id': userId,
          'name': name,
          'email': email,
        }),
      );
      
      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        
        // Update stored user data if successful
        if (data['success'] == true) {
          final prefs = await SharedPreferences.getInstance();
          final currentUser = await getCurrentUser();
          if (currentUser != null) {
            currentUser['name'] = name;
            currentUser['email'] = email;
            await prefs.setString('user', json.encode(currentUser));
          }
        }
        
        return data;
      } else {
        return {
          'success': false,
          'message': 'Server error: ${response.statusCode}'
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': 'Network error: $e'
      };
    }
  }
  
  // Attendance endpoints
  static Future<Map<String, dynamic>> markAttendance(int userId, String status) async {
    try {
      final token = await _getToken();
      final response = await http.post(
        Uri.parse('$baseUrl/attendance/mark.php'),
        headers: {
          'Content-Type': 'application/json',
          'Authorization': 'Bearer $token',
        },
        body: json.encode({
          'user_id': userId,
          'status': status,
        }),
      );
      
      if (response.statusCode == 200) {
        return json.decode(response.body);
      } else {
        return {
          'success': false,
          'message': 'Server error: ${response.statusCode}'
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': 'Network error: $e'
      };
    }
  }
  
  static Future<Map<String, dynamic>> getAttendanceHistory(int userId, {int limit = 30}) async {
    try {
      final token = await _getToken();
      final response = await http.get(
        Uri.parse('$baseUrl/attendance/history.php?user_id=$userId&limit=$limit'),
        headers: {
          'Content-Type': 'application/json',
          'Authorization': 'Bearer $token',
        },
      );
      
      if (response.statusCode == 200) {
        return json.decode(response.body);
      } else {
        return {
          'success': false,
          'message': 'Server error: ${response.statusCode}'
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': 'Network error: $e'
      };
    }
  }
  
  static Future<Map<String, dynamic>> getAttendanceReport(int userId, String startDate, String endDate) async {
    try {
      final token = await _getToken();
      if (token == null) {
        print('❌ API: No token available for getAttendanceReport');
        return {
          'success': false,
          'message': 'Authentication required. Please login again.'
        };
      }
      print('🔗 API: Fetching attendance report for user_id=$userId, $startDate to $endDate');
      final response = await http.get(
        Uri.parse('$baseUrl/attendance/report.php?user_id=$userId&start_date=$startDate&end_date=$endDate'),
        headers: {
          'Content-Type': 'application/json',
          'Authorization': 'Bearer $token',
        },
      ).timeout(
        const Duration(seconds: 30),
        onTimeout: () {
          throw Exception('Connection timeout. Please check your network configuration.');
        },
      );
      
      print('📡 API: Report response status: ${response.statusCode}');
      
      if (response.statusCode == 200) {
        return json.decode(response.body);
      } else {
        return {
          'success': false,
          'message': 'Server error: ${response.statusCode}'
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': 'Network error: $e'
      };
    }
  }
  
  static Future<Map<String, dynamic>> getHolidays(String startDate, String endDate) async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/attendance/holidays.php?start_date=$startDate&end_date=$endDate'),
        headers: {
          'Content-Type': 'application/json',
        },
      );
      
      if (response.statusCode == 200) {
        return json.decode(response.body);
      } else {
        return {
          'success': false,
          'message': 'Server error: ${response.statusCode}'
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': 'Network error: $e'
      };
    }
  }
  
  // Authentication helpers
  static Future<bool> isLoggedIn() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('token');
      return token != null;
    } catch (e) {
      return false;
    }
  }
  
  static Future<void> logout() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.remove('token');
      await prefs.remove('user');
    } catch (e) {
      // Ignore errors during logout
    }
  }
  
  static Future<String?> _getToken() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('token');
      print('🔑 API: Retrieved token: ${token != null ? "Token exists (${token.substring(0, 20)}...)" : "No token found"}');
      return token;
    } catch (e) {
      print('❌ API: Error retrieving token: $e');
      return null;
    }
  }
}
