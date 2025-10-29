import 'package:flutter/material.dart';
import 'package:flutter/foundation.dart' show kDebugMode;
import 'dart:io' show Platform;
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

void main() {
  const devBaseUrl = String.fromEnvironment('DEV_BASE_URL');
  if (kDebugMode && devBaseUrl.isNotEmpty) {
    ApiService.setDevelopmentBaseUrl(devBaseUrl);
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

class _AuthWrapperState extends State<AuthWrapper> {
  bool _initializing = true;

  @override
  void initState() {
    super.initState();
    _initializeApp();
  }
  
  void _initializeApp() async {
    // Initialize auth with a slight delay to ensure everything is ready
    WidgetsBinding.instance.addPostFrameCallback((_) async {
      try {
        final authProvider = Provider.of<AuthProvider>(context, listen: false);
        await authProvider.initializeAuth();
        
        // Give it another moment to settle
        await Future.delayed(const Duration(milliseconds: 200));
        
        if (mounted) {
          setState(() {
            _initializing = false;
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
        return authProvider.isLoggedIn 
            ? const DashboardScreen() 
            : const LoginScreen();
      },
    );
  }
}
