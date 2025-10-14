import 'package:flutter/material.dart';
import '../services/api_service.dart';

class AuthProvider with ChangeNotifier {
  Map<String, dynamic>? _currentUser;
  bool _isLoggedIn = false;
  bool _isLoading = false;
  String? _errorMessage;
  String? _action;

  // Getters
  Map<String, dynamic>? get currentUser => _currentUser;
  bool get isLoggedIn => _isLoggedIn;
  bool get isLoading => _isLoading;
  String? get errorMessage => _errorMessage;
  String? get action => _action;
  
  // Clear action and notify listeners (used after navigation)
  void clearAction() {
    _action = null;
    notifyListeners();
  }

  // Initialize auth state
  Future<void> initializeAuth() async {
    _isLoading = true;
    notifyListeners();

    try {
      _isLoggedIn = await ApiService.isLoggedIn();
      if (_isLoggedIn) {
        _currentUser = await ApiService.getCurrentUser();
      }
    } catch (e) {
      _errorMessage = 'Failed to initialize authentication';
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  // Login method
  Future<bool> login(String email, String password) async {
    print('🔄 AuthProvider: Starting login process');
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      print('📞 AuthProvider: Calling ApiService.login');
      final result = await ApiService.login(email, password);
      print('📋 AuthProvider: API result: $result');
      print('🔍 AuthProvider: Response keys: ${result.keys.toList()}');
      print('🔍 AuthProvider: success: ${result['success']}');
      print('🔍 AuthProvider: message: ${result['message']}');
      print('🔍 AuthProvider: action: ${result['action']}');
      
      if (result['success']) {
        print('✅ AuthProvider: Login successful');
        _isLoggedIn = true;
        _currentUser = result['user'];
        _errorMessage = null;
        _isLoading = false;
        notifyListeners();
        return true;
      } else {
        print('❌ AuthProvider: Login failed with message: ${result['message']}');
        _errorMessage = result['message'];
        _action = result['action'];
        _isLoading = false;
        notifyListeners();
        return false;
      }
    } catch (e) {
      print('🚨 AuthProvider: Exception caught: $e');
      _errorMessage = 'Login failed: $e';
      _isLoading = false;
      notifyListeners();
      return false;
    } finally {
      print('🏁 AuthProvider: Login process completed');
    }
  }

  // Register method
  Future<bool> register(String name, String email, String password) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final result = await ApiService.register(name, email, password);
      
      if (result['success']) {
        _isLoggedIn = true;
        _currentUser = result['user'];
        _errorMessage = null;
        notifyListeners();
        return true;
      } else {
        _errorMessage = result['message'];
        notifyListeners();
        return false;
      }
    } catch (e) {
      _errorMessage = 'Registration failed: $e';
      notifyListeners();
      return false;
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  // Set password method
  Future<bool> setPassword(String email, String oldPassword, String newPassword, String confirmPassword) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final result = await ApiService.setPassword(email, oldPassword, newPassword, confirmPassword);
      
      if (result['success']) {
        // If token is returned, store user data
        if (result['token'] != null && result['user'] != null) {
          _currentUser = result['user'];
          _isLoggedIn = true;
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
      _errorMessage = 'Failed to set password: $e';
      notifyListeners();
      return false;
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  // Update profile method
  Future<bool> updateProfile(String name, String email) async {
    if (_currentUser == null) return false;

    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      final result = await ApiService.updateProfile(_currentUser!['id'], name, email);
      
      if (result['success']) {
        _currentUser!['name'] = name;
        _currentUser!['email'] = email;
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

  // Logout method
  Future<void> logout() async {
    _isLoading = true;
    notifyListeners();

    try {
      await ApiService.logout();
      _currentUser = null;
      _isLoggedIn = false;
      _errorMessage = null;
    } catch (e) {
      _errorMessage = 'Logout failed: $e';
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  // Clear error message
  void clearError() {
    _errorMessage = null;
    notifyListeners();
  }
}
