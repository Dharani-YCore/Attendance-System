import 'package:flutter/material.dart';
import '../services/api_service.dart';

class AttendanceProvider with ChangeNotifier {
  List<dynamic> _attendanceHistory = [];
  Map<String, dynamic>? _attendanceSummary;
  Map<String, dynamic>? _reportData;
  bool _isLoading = false;
  String? _errorMessage;

  // Getters
  List<dynamic> get attendanceHistory => _attendanceHistory;
  Map<String, dynamic>? get attendanceSummary => _attendanceSummary;
  Map<String, dynamic>? get reportData => _reportData;
  bool get isLoading => _isLoading;
  String? get errorMessage => _errorMessage;

  // Mark attendance
  Future<bool> markAttendance(int userId, String status) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final result = await ApiService.markAttendance(userId, status);
      
      if (result['success']) {
        // Refresh attendance history after marking
        await loadAttendanceHistory(userId);
        _errorMessage = null;
        notifyListeners();
        return true;
      } else {
        _errorMessage = result['message'];
        notifyListeners();
        return false;
      }
    } catch (e) {
      _errorMessage = 'Failed to mark attendance: $e';
      notifyListeners();
      return false;
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  // Load attendance history
  Future<void> loadAttendanceHistory(int userId, {int? limit}) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final result = await ApiService.getAttendanceHistory(userId, limit: limit);
      
      if (result['success']) {
        _attendanceHistory = result['data'];
        _attendanceSummary = result['summary'];
        _errorMessage = null;
      } else {
        _errorMessage = result['message'];
      }
    } catch (e) {
      _errorMessage = 'Failed to load attendance history: $e';
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  // Load attendance report
  Future<void> loadAttendanceReport(int userId, String startDate, String endDate) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final result = await ApiService.getAttendanceReport(userId, startDate, endDate);
      
      if (result['success']) {
        _reportData = result;
        _errorMessage = null;
      } else {
        _errorMessage = result['message'];
      }
    } catch (e) {
      _errorMessage = 'Failed to load attendance report: $e';
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  // Get today's attendance status
  String? getTodayAttendanceStatus() {
    if (_attendanceHistory.isEmpty) return null;
    
    final today = DateTime.now();
    final todayString = '${today.year}-${today.month.toString().padLeft(2, '0')}-${today.day.toString().padLeft(2, '0')}';
    
    for (var record in _attendanceHistory) {
      if (record['date'] == todayString) {
        return record['status'];
      }
    }
    return null;
  }

  // Check if attendance is already marked today
  bool isAttendanceMarkedToday() {
    return getTodayAttendanceStatus() != null;
  }

  // Get attendance percentage
  double getAttendancePercentage() {
    if (_attendanceSummary == null) return 0.0;
    
    final totalDays = _attendanceSummary!['total_days'] ?? 0;
    final presentDays = (_attendanceSummary!['present_days'] ?? 0) + 
                       (_attendanceSummary!['late_days'] ?? 0);
    
    if (totalDays == 0) return 0.0;
    return (presentDays / totalDays) * 100;
  }

  // Get recent attendance trend (last 7 days)
  List<Map<String, dynamic>> getRecentTrend() {
    if (_attendanceHistory.isEmpty) return [];
    
    final now = DateTime.now();
    final last7Days = <Map<String, dynamic>>[];
    
    for (int i = 6; i >= 0; i--) {
      final date = now.subtract(Duration(days: i));
      final dateString = '${date.year}-${date.month.toString().padLeft(2, '0')}-${date.day.toString().padLeft(2, '0')}';
      
      String status = 'Absent';
      for (var record in _attendanceHistory) {
        if (record['date'] == dateString) {
          status = record['status'];
          break;
        }
      }
      
      last7Days.add({
        'date': dateString,
        'day': _getDayName(date.weekday),
        'status': status,
      });
    }
    
    return last7Days;
  }

  String _getDayName(int weekday) {
    switch (weekday) {
      case 1: return 'Mon';
      case 2: return 'Tue';
      case 3: return 'Wed';
      case 4: return 'Thu';
      case 5: return 'Fri';
      case 6: return 'Sat';
      case 7: return 'Sun';
      default: return '';
    }
  }

  // Clear error message
  void clearError() {
    _errorMessage = null;
    notifyListeners();
  }

  // Clear all data
  void clearData() {
    _attendanceHistory.clear();
    _attendanceSummary = null;
    _reportData = null;
    _errorMessage = null;
    notifyListeners();
  }
}
