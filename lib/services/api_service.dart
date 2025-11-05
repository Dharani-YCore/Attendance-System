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
  static const String _localIpAddress = '192.168.1.3'; // For physical device
  static const String _emulatorIpAddress = '10.0.2.2'; // For Android emulator
  // Static configuration for production
  static const String _productionBaseUrl = 'https://your-static-ip-or-domain.com/Attendance-System-Website/api';
  
  // Development configuration (override in main.dart for development)
  static String _developmentBaseUrl = 'http://192.168.70.191/Attendance-System/backend';
  
  // Get base URL based on environment
  static String get baseUrl {
    // In production, always use the production URL
    if (const bool.fromEnvironment('dart.vm.product')) {
      return _productionBaseUrl;
    }
    
    // In development, use the development URL which can be overridden
    return _developmentBaseUrl;
  }
  
  // Method to update the base URL at runtime (useful for development)
  static void setDevelopmentBaseUrl(String url) {
    _developmentBaseUrl = url.endsWith('/') ? url : '$url/';
  }
  
  // Authentication endpoints
  static Future<Map<String, dynamic>> login(String email, String password) async {
    try {
      // Ensure the URL is properly formatted
      final loginUrl = '${baseUrl.endsWith('/') ? baseUrl : '$baseUrl/'}auth/login.php';
      print('🔗 API: Sending login request to $loginUrl');
      
      final response = await http.post(
        Uri.parse(loginUrl),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: json.encode({
          'email': email,
          'password': password,
        }),
      ).timeout(
        const Duration(seconds: 30),
        onTimeout: () {
          throw Exception('Connection timeout. Please check your internet connection and try again.');
        },
      );
      
      // Log response for debugging
      print('🔵 Response status: ${response.statusCode}');
      print('🔵 Response body: ${response.body}');
      
      print('📡 API: Response status: ${response.statusCode}');
      print('📄 API: Response body: ${response.body}');
      
      // Parse response for both success and error cases
      final data = json.decode(response.body);
      
      if (response.statusCode == 200) {
        // Store token and user data if login successful
        if (data['success'] == true && data['token'] != null) {
          final prefs = await SharedPreferences.getInstance();
          await prefs.setString('token', data['token']);
          await prefs.setString('user', json.encode(data['user']));
        }
      }
      
      // Return the parsed data which includes error messages from backend
      return data;
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
      final registerUrl = '${baseUrl.endsWith('/') ? baseUrl : '$baseUrl/'}auth/register.php';
      print('🔗 API: Sending register request to $registerUrl');
      
      final response = await http.post(
        Uri.parse(registerUrl),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: json.encode({
          'name': name,
          'email': email,
          'password': password,
        }),
      ).timeout(
        const Duration(seconds: 30),
        onTimeout: () {
          throw Exception('Connection timeout. Please check your internet connection and try again.');
        },
      );
      
      // Log response for debugging
      print('🔵 Register response status: ${response.statusCode}');
      print('🔵 Register response body: ${response.body}');
      
      // Parse response for both success and error cases
      final data = json.decode(response.body);
      
      if (response.statusCode == 200) {
        // Store token and user data if registration successful
        if (data['success'] == true && data['token'] != null) {
          final prefs = await SharedPreferences.getInstance();
          await prefs.setString('token', data['token']);
          await prefs.setString('user', json.encode(data['user']));
          print('✅ User registered and logged in successfully');
        }
        return data; // Return the parsed response data
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
      final setPasswordUrl = '${baseUrl.endsWith('/') ? baseUrl : '$baseUrl/'}auth/set_password.php';
      print('🔗 API: Setting password for $email at $setPasswordUrl');
      
      final response = await http.post(
        Uri.parse(setPasswordUrl),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: json.encode({
          'email': email,
          'old_password': oldPassword,
          'new_password': newPassword,
          'confirm_password': confirmPassword,
        }),
      );
      
      print('🔵 Set password response status: ${response.statusCode}');
      print('🔵 Set password response body: ${response.body}');
      
      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        
        // Store token and user data if password change successful
        if (data['success'] == true && data['token'] != null) {
          final prefs = await SharedPreferences.getInstance();
          await prefs.setString('token', data['token']);
          await prefs.setString('user', json.encode(data['user']));
          print('✅ Password changed successfully');
        }
        return data; // Return the parsed response data
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
      final forgotPasswordUrl = '${baseUrl.endsWith('/') ? baseUrl : '$baseUrl/'}auth/forgot_password.php';
      print('🔗 API: Forgot password request to $forgotPasswordUrl');
      print('📧 Email: $email');
      
      final response = await http.post(
        Uri.parse(forgotPasswordUrl),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: json.encode({'email': email}),
      ).timeout(
        const Duration(seconds: 30),
        onTimeout: () {
          throw Exception('Connection timeout. Please check your internet connection and try again.');
        },
      );
      
      print('🔵 Forgot password response status: ${response.statusCode}');
      print('🔵 Forgot password response body: ${response.body}');
      
      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        print('📩 Password reset email sent: ${data['success']}');
        return data;
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
      final verifyOtpUrl = '${baseUrl.endsWith('/') ? baseUrl : '$baseUrl/'}auth/verify_otp.php';
      print('🔗 API: Verifying OTP at $verifyOtpUrl');
      print('📧 Email: $email, OTP: $otp');
      
      final response = await http.post(
        Uri.parse(verifyOtpUrl),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: json.encode({
          'email': email,
          'otp': otp,
        }),
      );
      
      print('🔵 Verify OTP response status: ${response.statusCode}');
      print('🔵 Verify OTP response body: ${response.body}');
      
      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        print('✅ OTP verification result: ${data['success']}');
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
  
  static Future<Map<String, dynamic>> resetPassword(String email, String password) async {
    try {
      final resetPasswordUrl = '${baseUrl.endsWith('/') ? baseUrl : '$baseUrl/'}auth/reset_password.php';
      print('🔗 API: Resetting password at $resetPasswordUrl');
      
      final response = await http.post(
        Uri.parse(resetPasswordUrl),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: json.encode({
          'email': email,
          'password': password,
        }),
      );
      
      print('🔵 Reset password response status: ${response.statusCode}');
      print('🔵 Reset password response body: ${response.body}');
      
      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        print('✅ Password reset successful: ${data['success']}');
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
  
  // User management endpoints
  static Future<Map<String, dynamic>?> getCurrentUser() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final userString = prefs.getString('user');
      if (userString != null) {
        final userData = json.decode(userString);
        return userData;
      }
      return null;
    } catch (e) {
      print('🚨 ApiService: getCurrentUser error: $e');
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
  static Future<Map<String, dynamic>> markAttendance(int userId, String status, {String? qrData}) async {
    try {
      print('🎯 ApiService: markAttendance called with userId=$userId, status=$status');
      final token = await _getToken();
      if (token == null) {
        print('🚨 ApiService: No token available for markAttendance');
        return {
          'success': false,
          'message': 'No authentication token available'
        };
      }
      print('🔗 ApiService: Making attendance request with token: ${token.substring(0, 20)}...');
      // Prepare request body
      final Map<String, dynamic> requestBody = {
        'user_id': userId,
        'status': status,
      };
      
      // Add QR data if provided
      if (qrData != null) {
        requestBody['qr_data'] = qrData;
      }
      
      final response = await http.post(
        Uri.parse('$baseUrl/attendance/mark.php'),
        headers: {
          'Content-Type': 'application/json',
          'Authorization': 'Bearer $token',
        },
        body: json.encode(requestBody),
      );
      
      print('📡 ApiService: markAttendance response status: ${response.statusCode}');
      print('📄 ApiService: markAttendance response body: ${response.body}');
      
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
      print('🚨 ApiService: isLoggedIn error: $e');
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
      if (token != null) {
        print('🔑 ApiService: Retrieved token: ${token.substring(0, 20)}...');
      } else {
        print('🔑 ApiService: No token found in storage');
      }
      return token;
    } catch (e) {
      print('🚨 ApiService: Error getting token: $e');
      return null;
    }
  }

  // Public method for debugging token state
  static Future<String?> getStoredToken() async {
    return _getToken();
  }
}
