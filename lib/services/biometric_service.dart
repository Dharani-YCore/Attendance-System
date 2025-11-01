import 'package:flutter/services.dart';
import 'package:local_auth/local_auth.dart';

class BiometricService {
  static final LocalAuthentication _localAuth = LocalAuthentication();

  /// Check if device supports biometric authentication
  static Future<bool> isAvailable() async {
    try {
      final bool isAvailable = await _localAuth.canCheckBiometrics;
      final bool isDeviceSupported = await _localAuth.isDeviceSupported();
      
      return isAvailable || isDeviceSupported;
    } catch (e) {
      print('Error checking biometric availability: $e');
      return false;
    }
  }

  /// Get available biometric types
  static Future<List<BiometricType>> getAvailableBiometrics() async {
    try {
      return await _localAuth.getAvailableBiometrics();
    } catch (e) {
      print('Error getting available biometrics: $e');
      return [];
    }
  }

  /// Authenticate user using biometric or device passcode
  static Future<bool> authenticate() async {
    try {
      // Check if biometric is available
      final bool canAuthenticateWithBiometrics = await isAvailable();
      
      if (!canAuthenticateWithBiometrics) {
        print('Biometric authentication not available on this device');
        return false;
      }

      final bool didAuthenticate = await _localAuth.authenticate(
        localizedReason: 'Please authenticate to access the app',
        options: const AuthenticationOptions(
          biometricOnly: false, // Allow device passcode as fallback
          stickyAuth: true, // Keep authentication dialog visible
          useErrorDialogs: true, // Show system error dialogs
        ),
      );

      return didAuthenticate;
    } on PlatformException catch (e) {
      print('Platform exception during authentication: $e');
      
      if (e.code == 'NotAvailable') {
        print('Biometric authentication is not available');
      } else if (e.code == 'NotEnrolled') {
        print('No biometrics enrolled');
      } else if (e.code == 'LockedOut' || e.code == 'PermanentlyLockedOut') {
        print('Biometric is locked out');
      }
      
      return false;
    } catch (e) {
      print('Error during authentication: $e');
      return false;
    }
  }

  /// Stop any ongoing authentication
  static Future<void> stopAuthentication() async {
    try {
      await _localAuth.stopAuthentication();
    } catch (e) {
      print('Error stopping authentication: $e');
    }
  }

  /// Check if device is secured (has biometric or passcode)
  static Future<bool> isDeviceSecure() async {
    try {
      return await _localAuth.isDeviceSupported();
    } catch (e) {
      print('Error checking device security: $e');
      return false;
    }
  }
}

