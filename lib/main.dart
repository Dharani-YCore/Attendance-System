import 'package:flutter/material.dart';
import 'package:flutter/foundation.dart' show kDebugMode;
import 'services/api_service.dart';
import 'package:provider/provider.dart';
import 'providers/auth_provider.dart';
import 'providers/attendance_provider.dart';
import 'providers/user_provider.dart';
import 'screens/login_screen.dart';
import 'screens/set_password_screen.dart';
import 'screens/forget_password_screen.dart';
import 'screens/verification_screen.dart';
import 'screens/new_password_screen.dart';
import 'screens/dashboard_screen.dart';
import 'screens/profile_screen.dart';
import 'screens/qr_scanner_screen.dart';
import 'screens/attendance_history_screen.dart';
import 'screens/daily_report_screen.dart';
import 'screens/monthly_report_screen.dart';
import 'screens/biometric_auth_screen.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  
  // Initialize API service base URL
  try {
    const devBaseUrl = String.fromEnvironment('DEV_BASE_URL');
    if (kDebugMode && devBaseUrl.isNotEmpty) {
      ApiService.setDevelopmentBaseUrl(devBaseUrl);
      print('🔧 Using custom dev base URL: $devBaseUrl');
    } else {
      // Initialize with auto-detection
      await ApiService.initializeBaseUrl();
    }
  } catch (e) {
    print('⚠️ Error initializing base URL: $e');
  }
  
  runApp(const MyApp());
}

class MyApp extends StatelessWidget {
  const MyApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MultiProvider(
      providers: [
        ChangeNotifierProvider(create: (_) => AuthProvider()),
        ChangeNotifierProvider(create: (_) => AttendanceProvider()),
        ChangeNotifierProvider(create: (_) => UserProvider()),
      ],
      child: MaterialApp(
        title: 'Smart Attendance System',
        debugShowCheckedModeBanner: false,
        theme: ThemeData(
          fontFamily: 'Roboto',
          primarySwatch: Colors.cyan,
          useMaterial3: true,
        ),
        home: const AuthWrapper(),
        routes: {
          '/login': (context) => const LoginScreen(),
          '/setPassword': (context) => const SetPasswordScreen(),
          '/forgetPassword': (context) => const ForgetPasswordScreen(),
          '/verification': (context) => const VerificationScreen(),
          '/newPassword': (context) => const NewPasswordScreen(),
          '/dashboard': (context) => const DashboardScreen(),
          '/profile': (context) => const ProfileScreen(),
          '/qr_scanner': (context) => const QRScannerScreen(),
          '/history': (context) => const AttendanceHistoryScreen(),
          '/daily_report': (context) => const DailyReportScreen(),
          '/monthly_report': (context) => const MonthlyReportScreen(),
        },
      ),
    );
  }
}

class AuthWrapper extends StatefulWidget {
  const AuthWrapper({super.key});

  @override
  State<AuthWrapper> createState() => _AuthWrapperState();
}

class _AuthWrapperState extends State<AuthWrapper> with WidgetsBindingObserver {
  bool _initializing = true;
  bool _isAuthenticated = false;
  bool _needsBiometricAuth = false;
  AppLifecycleState? _lastLifecycleState;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _initializeApp();
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    super.didChangeAppLifecycleState(state);
    
    // When app resumes from background, require biometric authentication again
    // Only trigger if transitioning from paused/inactive to resumed (not from resumed to resumed)
    if (state == AppLifecycleState.resumed && 
        _lastLifecycleState != null && 
        _lastLifecycleState != AppLifecycleState.resumed) {
      final authProvider = Provider.of<AuthProvider>(context, listen: false);
      if (authProvider.isLoggedIn && _isAuthenticated) {
        setState(() {
          _isAuthenticated = false;
          _needsBiometricAuth = true;
        });
      }
    }
    _lastLifecycleState = state;
  }
  
  void _initializeApp() async {
    // Initialize auth with a slight delay to ensure everything is ready
    WidgetsBinding.instance.addPostFrameCallback((_) async {
      try {
        // Ensure API service is initialized (in case it wasn't in main)
        try {
          await ApiService.initializeBaseUrl();
        } catch (e) {
          print('⚠️ API service already initialized or error: $e');
        }
        
        final authProvider = Provider.of<AuthProvider>(context, listen: false);
        await authProvider.initializeAuth();
        
        // Give it another moment to settle
        await Future.delayed(const Duration(milliseconds: 200));
        
        if (mounted) {
          setState(() {
            _initializing = false;
            // If user is logged in, require biometric authentication
            if (authProvider.isLoggedIn) {
              _needsBiometricAuth = true;
            }
          });
        }
      } catch (e) {
        print('🚨 App initialization error: $e');
        if (mounted) {
          setState(() {
            _initializing = false;
          });
        }
      }
    });
  }

  void _onBiometricAuthenticated() {
    if (mounted) {
      // Use a post frame callback to ensure state is updated after widget rebuild
      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (mounted) {
          setState(() {
            _isAuthenticated = true;
            _needsBiometricAuth = false;
          });
        }
      });
    }
  }

  void _onBiometricFailed() {
    // If biometric fails, user can try again or we could log them out
    // For now, just allow retry
    print('Biometric authentication failed');
    // Don't change state on failure - let user try again
  }

  @override
  Widget build(BuildContext context) {
    // Show loading only during initial auth check
    if (_initializing) {
      return const Scaffold(
        body: Center(
          child: CircularProgressIndicator(),
        ),
      );
    }

    return Consumer<AuthProvider>(
      builder: (context, authProvider, child) {
        // If user is not logged in, show login screen
        if (!authProvider.isLoggedIn) {
          // Reset authentication state when logged out
          if (_isAuthenticated || _needsBiometricAuth) {
            WidgetsBinding.instance.addPostFrameCallback((_) {
              if (mounted) {
                setState(() {
                  _isAuthenticated = false;
                  _needsBiometricAuth = false;
                });
              }
            });
          }
          return const LoginScreen();
        }

        // If user is logged in but needs biometric authentication
        // Only show if we actually need it and haven't authenticated yet
        if (_needsBiometricAuth && !_isAuthenticated) {
          return BiometricAuthScreen(
            key: const ValueKey('biometric_auth'), // Add key to prevent recreation
            onAuthenticated: _onBiometricAuthenticated,
            onFailed: _onBiometricFailed,
          );
        }

        // User is authenticated, show dashboard
        return const DashboardScreen();
      },
    );
  }
}
