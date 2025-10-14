import 'package:flutter/material.dart';
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
import 'screens/reports_screen.dart';
import 'screens/daily_report_screen.dart';
import 'screens/monthly_report_screen.dart';

void main() {
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
          '/reports': (context) => const ReportsScreen(),
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
    // Initialize auth after the widget is built
    WidgetsBinding.instance.addPostFrameCallback((_) async {
      await Provider.of<AuthProvider>(context, listen: false).initializeAuth();
      if (mounted) {
        setState(() {
          _initializing = false;
        });
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
