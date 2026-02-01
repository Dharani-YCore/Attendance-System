import 'package:flutter/material.dart';
import '../services/api_service.dart';

class UserProvider with ChangeNotifier {
  Map<String, dynamic>? _userProfile;
  Map<String, dynamic>? _userStats;
  bool _isLoading = false;
  String? _errorMessage;

  // Getters
  Map<String, dynamic>? get userProfile => _userProfile;
  Map<String, dynamic>? get userStats => _userStats;
  bool get isLoading => _isLoading;
  String? get errorMessage => _errorMessage;

  // Load user profile
  Future<void> loadUserProfile(int userId) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final result = await ApiService.getUserProfile(userId);
      
      if (result['success']) {
        _userProfile = result['user'];
        _userStats = result['stats'];
        _errorMessage = null;
      } else {
        _errorMessage = result['message'];
      }
    } catch (e) {
      _errorMessage = 'Failed to load user profile: $e';
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  // Update user profile
  Future<bool> updateUserProfile(int userId, String name, String email) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final result = await ApiService.updateProfile(userId, name, email);
      
      if (result['success']) {
        if (_userProfile != null) {
          _userProfile!['name'] = name;
          _userProfile!['email'] = email;
        }
        _errorMessage = null;
        notifyListeners();
        return true;
      } else {
        _errorMessage = result['message'];
        notifyListeners();
        return false;
      }
    } catch (e) {
      _errorMessage = 'Failed to update profile: $e';
      notifyListeners();
      return false;
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  // Get user's full name
  String getUserName() {
    return _userProfile?['name'] ?? 'User';
  }

  // Get user's email
  String getUserEmail() {
    return _userProfile?['email'] ?? '';
  }

  // Get user's join date
  String getJoinDate() {
    if (_userProfile?['created_at'] == null) return 'Unknown';
    
    try {
      final date = DateTime.parse(_userProfile!['created_at']);
      return '${date.day}/${date.month}/${date.year}';
    } catch (e) {
      return 'Unknown';
    }
  }

  // Get attendance statistics
  Map<String, int> getAttendanceStats() {
    if (_userStats == null) {
      return {
        'total_days': 0,
        'present_days': 0,
        'late_days': 0,
        'absent_days': 0,
      };
    }

    return {
      'total_days': _userStats!['total_days'] ?? 0,
      'present_days': _userStats!['present_days'] ?? 0,
      'late_days': _userStats!['late_days'] ?? 0,
      'absent_days': _userStats!['absent_days'] ?? 0,
    };
  }

  // Calculate attendance percentage
  double getAttendancePercentage() {
    final stats = getAttendanceStats();
    final totalDays = stats['total_days']!;
    final presentDays = stats['present_days']! + stats['late_days']!;
    
    if (totalDays == 0) return 0.0;
    return (presentDays / totalDays) * 100;
  }

  // Get performance status
  String getPerformanceStatus() {
    final percentage = getAttendancePercentage();
    
    if (percentage >= 95) return 'Excellent';
    if (percentage >= 85) return 'Good';
    if (percentage >= 75) return 'Average';
    if (percentage >= 60) return 'Below Average';
    return 'Poor';
  }

  // Get performance color
  Color getPerformanceColor() {
    final percentage = getAttendancePercentage();
    
    if (percentage >= 95) return Colors.green;
    if (percentage >= 85) return Colors.lightGreen;
    if (percentage >= 75) return Colors.orange;
    if (percentage >= 60) return Colors.deepOrange;
    return Colors.red;
  }

  // Clear error message
  void clearError() {
    _errorMessage = null;
    notifyListeners();
  }

  // Clear user data
  void clearUserData() {
    _userProfile = null;
    _userStats = null;
    _errorMessage = null;
    notifyListeners();
  }
}
