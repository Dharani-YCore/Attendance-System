import 'package:flutter/material.dart';
import '../services/biometric_service.dart';

class BiometricAuthScreen extends StatefulWidget {
  final VoidCallback? onAuthenticated;
  final VoidCallback? onFailed;

  const BiometricAuthScreen({
    super.key,
    this.onAuthenticated,
    this.onFailed,
  });

  @override
  State<BiometricAuthScreen> createState() => _BiometricAuthScreenState();
}

class _BiometricAuthScreenState extends State<BiometricAuthScreen> {
  bool _isAuthenticating = false;
  bool _biometricAvailable = false;
  String _errorMessage = '';
  bool _hasAttemptedAuth = false;

  @override
  void initState() {
    super.initState();
    _checkBiometricAvailability();
    // Auto-trigger authentication after a short delay, but only once
    Future.delayed(const Duration(milliseconds: 500), () {
      if (mounted && !_hasAttemptedAuth) {
        _authenticate();
      }
    });
  }

  Future<void> _checkBiometricAvailability() async {
    final available = await BiometricService.isAvailable();
    if (mounted) {
      setState(() {
        _biometricAvailable = available;
      });
    }
  }

  Future<void> _authenticate() async {
    if (_isAuthenticating || _hasAttemptedAuth) return;

    setState(() {
      _isAuthenticating = true;
      _errorMessage = '';
      _hasAttemptedAuth = true;
    });

    try {
      final success = await BiometricService.authenticate();
      
      if (mounted) {
        if (success) {
          // Small delay to ensure state is stable before calling callback
          await Future.delayed(const Duration(milliseconds: 100));
          if (mounted) {
            widget.onAuthenticated?.call();
          }
        } else {
          setState(() {
            _errorMessage = 'Authentication failed. Please try again.';
            _isAuthenticating = false;
            // Allow retry by resetting _hasAttemptedAuth on failure
            _hasAttemptedAuth = false;
          });
          widget.onFailed?.call();
        }
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _errorMessage = 'An error occurred during authentication.';
          _isAuthenticating = false;
          // Allow retry by resetting _hasAttemptedAuth on error
          _hasAttemptedAuth = false;
        });
        widget.onFailed?.call();
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
      body: Container(
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            colors: [Color(0xFFE0F7FA), Color(0xFFFFFFFF)],
            begin: Alignment.topCenter,
            end: Alignment.bottomCenter,
          ),
        ),
        child: SafeArea(
          child: Center(
            child: Padding(
              padding: const EdgeInsets.all(32.0),
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  // Biometric Icon
                  Container(
                    padding: const EdgeInsets.all(24),
                    decoration: BoxDecoration(
                      color: const Color(0xFF4DD0E1).withOpacity(0.1),
                      shape: BoxShape.circle,
                    ),
                    child: const Icon(
                      Icons.fingerprint,
                      size: 80,
                      color: Color(0xFF4DD0E1),
                    ),
                  ),
                  
                  const SizedBox(height: 40),
                  
                  // Title
                  const Text(
                    'Authentication Required',
                    style: TextStyle(
                      fontSize: 24,
                      fontWeight: FontWeight.bold,
                      color: Colors.black87,
                    ),
                    textAlign: TextAlign.center,
                  ),
                  
                  const SizedBox(height: 16),
                  
                  // Description
                  Text(
                    _biometricAvailable
                        ? 'Please authenticate using your fingerprint, face ID, or device passcode to continue'
                        : 'Please authenticate to continue',
                    style: const TextStyle(
                      fontSize: 16,
                      color: Colors.black54,
                    ),
                    textAlign: TextAlign.center,
                  ),
                  
                  const SizedBox(height: 32),
                  
                  // Error message
                  if (_errorMessage.isNotEmpty)
                    Container(
                      padding: const EdgeInsets.all(12),
                      margin: const EdgeInsets.only(bottom: 16),
                      decoration: BoxDecoration(
                        color: Colors.redAccent.withOpacity(0.1),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: Text(
                        _errorMessage,
                        style: const TextStyle(
                          color: Colors.redAccent,
                          fontSize: 14,
                        ),
                        textAlign: TextAlign.center,
                      ),
                    ),
                  
                  // Authenticate Button
                  if (!_isAuthenticating)
                    SizedBox(
                      width: double.infinity,
                      child: ElevatedButton.icon(
                        onPressed: _authenticate,
                        icon: const Icon(Icons.fingerprint),
                        label: const Text('Authenticate'),
                        style: ElevatedButton.styleFrom(
                          backgroundColor: const Color(0xFF4DD0E1),
                          padding: const EdgeInsets.symmetric(vertical: 16),
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(8),
                          ),
                          elevation: 2,
                        ),
                      ),
                    ),
                  
                  // Loading indicator
                  if (_isAuthenticating)
                    const Column(
                      children: [
                        CircularProgressIndicator(
                          color: Color(0xFF4DD0E1),
                        ),
                        SizedBox(height: 16),
                        Text(
                          'Authenticating...',
                          style: TextStyle(
                            color: Colors.black54,
                            fontSize: 14,
                          ),
                        ),
                      ],
                    ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}

