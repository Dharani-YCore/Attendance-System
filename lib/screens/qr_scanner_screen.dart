import 'dart:convert';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:mobile_scanner/mobile_scanner.dart';
import '../providers/auth_provider.dart';
import '../providers/attendance_provider.dart';
import '../services/api_service.dart';

class QRScannerScreen extends StatefulWidget {
  const QRScannerScreen({super.key});

  @override
  State<QRScannerScreen> createState() => _QRScannerScreenState();
}

class _QRScannerScreenState extends State<QRScannerScreen> {
  bool isProcessing = false;
  bool isScanned = false;
  String? scanResult;
  String? checkInTime;
  MobileScannerController? cameraController;

  @override
  void initState() {
    super.initState();
    _checkAuthenticationOnInit();
    if (!kIsWeb) {
      cameraController = MobileScannerController();
    }
  }
  
  void _checkAuthenticationOnInit() async {
    // Don't do aggressive auth check here - let the user try to scan first
    // The auth check will happen in _processAttendance when they actually scan
    print('🔍 QR Scanner initialized');
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.black,
      body: Stack(
        children: [
          // Camera/Scanner view, Web fallback, or Success screen
          if (isScanned)
            // Success screen after scan
            Container(
              color: Colors.green.shade800,
              child: Center(
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Container(
                      width: 120,
                      height: 120,
                      decoration: BoxDecoration(
                        color: Colors.white,
                        shape: BoxShape.circle,
                        boxShadow: [
                          BoxShadow(
                            color: Colors.black.withOpacity(0.2),
                            blurRadius: 10,
                            offset: const Offset(0, 4),
                          ),
                        ],
                      ),
                      child: Icon(
                        Icons.check,
                        size: 60,
                        color: Colors.green.shade800,
                      ),
                    ),
                    const SizedBox(height: 30),
                    const Text(
                      'CHECKED IN',
                      style: TextStyle(
                        color: Colors.white,
                        fontSize: 28,
                        fontWeight: FontWeight.bold,
                        letterSpacing: 2,
                      ),
                    ),
                    const SizedBox(height: 10),
                    if (checkInTime != null)
                      Text(
                        'Time: $checkInTime',
                        style: TextStyle(
                          color: Colors.white.withOpacity(0.9),
                          fontSize: 18,
                        ),
                      ),
                    const SizedBox(height: 20),
                    Text(
                      'Attendance marked successfully!',
                      style: TextStyle(
                        color: Colors.white.withOpacity(0.8),
                        fontSize: 16,
                      ),
                    ),
                    const SizedBox(height: 40),
                    ElevatedButton(
                      onPressed: () => Navigator.pop(context),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: Colors.white,
                        foregroundColor: Colors.green.shade800,
                        padding: const EdgeInsets.symmetric(horizontal: 40, vertical: 16),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(25),
                        ),
                        elevation: 4,
                      ),
                      child: const Text(
                        'Back to Dashboard',
                        style: TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            )
          else if (kIsWeb)
            // Web fallback - show a simulated scanner interface
            Container(
              color: Colors.black,
              child: Center(
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Icon(
                      Icons.qr_code_scanner,
                      size: 100,
                      color: Colors.white.withOpacity(0.3),
                    ),
                    const SizedBox(height: 20),
                    Text(
                      'Camera not available on web',
                      style: TextStyle(
                        color: Colors.white.withOpacity(0.7),
                        fontSize: 16,
                      ),
                    ),
                  ],
                ),
              ),
            )
          else
            MobileScanner(
              controller: cameraController!,
              onDetect: (capture) {
                if (!isProcessing && !isScanned) {
                  final List<Barcode> barcodes = capture.barcodes;
                  if (barcodes.isNotEmpty && barcodes.first.rawValue != null) {
                    _processAttendance(barcodes.first.rawValue!);
                  }
                }
              },
            ),
          
          // Top bar with back/close button
          SafeArea(
            child: Padding(
              padding: const EdgeInsets.all(16.0),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  IconButton(
                    icon: Icon(
                      isScanned ? Icons.close : Icons.arrow_back, 
                      color: Colors.white, 
                      size: 28
                    ),
                    onPressed: () => Navigator.pop(context),
                  ),
                  if (!kIsWeb && !isScanned)
                    IconButton(
                      icon: const Icon(Icons.flash_off, color: Colors.white, size: 28),
                      onPressed: () => cameraController?.toggleTorch(),
                    ),
                ],
              ),
            ),
          ),
          
          // Scanning frame overlay (only show when not scanned)
          if (!isScanned)
            Center(
              child: Container(
                width: 280,
                height: 280,
                decoration: BoxDecoration(
                  border: Border.all(color: Colors.white, width: 3),
                  borderRadius: BorderRadius.circular(20),
                ),
              child: Stack(
                children: [
                  // Corner decorations
                  Positioned(
                    top: -3,
                    left: -3,
                    child: Container(
                      width: 60,
                      height: 60,
                      decoration: const BoxDecoration(
                        border: Border(
                          top: BorderSide(color: Colors.cyan, width: 5),
                          left: BorderSide(color: Colors.cyan, width: 5),
                        ),
                        borderRadius: BorderRadius.only(
                          topLeft: Radius.circular(20),
                        ),
                      ),
                    ),
                  ),
                  Positioned(
                    top: -3,
                    right: -3,
                    child: Container(
                      width: 60,
                      height: 60,
                      decoration: const BoxDecoration(
                        border: Border(
                          top: BorderSide(color: Colors.cyan, width: 5),
                          right: BorderSide(color: Colors.cyan, width: 5),
                        ),
                        borderRadius: BorderRadius.only(
                          topRight: Radius.circular(20),
                        ),
                      ),
                    ),
                  ),
                  Positioned(
                    bottom: -3,
                    left: -3,
                    child: Container(
                      width: 60,
                      height: 60,
                      decoration: const BoxDecoration(
                        border: Border(
                          bottom: BorderSide(color: Colors.cyan, width: 5),
                          left: BorderSide(color: Colors.cyan, width: 5),
                        ),
                        borderRadius: BorderRadius.only(
                          bottomLeft: Radius.circular(20),
                        ),
                      ),
                    ),
                  ),
                  Positioned(
                    bottom: -3,
                    right: -3,
                    child: Container(
                      width: 60,
                      height: 60,
                      decoration: const BoxDecoration(
                        border: Border(
                          bottom: BorderSide(color: Colors.cyan, width: 5),
                          right: BorderSide(color: Colors.cyan, width: 5),
                        ),
                        borderRadius: BorderRadius.only(
                          bottomRight: Radius.circular(20),
                        ),
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),
          
          // Bottom instruction text or button (only show when not scanned)
          if (!isScanned)
            Positioned(
              bottom: 100,
              left: 0,
              right: 0,
              child: kIsWeb
                ? Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 40),
                    child: Column(
                      children: [
                        Text(
                          'For demo purposes on web',
                          style: TextStyle(
                            color: Colors.white.withOpacity(0.7),
                            fontSize: 14,
                          ),
                        ),
                        const SizedBox(height: 16),
                        ElevatedButton(
                          onPressed: isProcessing ? null : _simulateQRScan,
                          style: ElevatedButton.styleFrom(
                            backgroundColor: const Color(0xFF80DEEA),
                            foregroundColor: Colors.black,
                            padding: const EdgeInsets.symmetric(horizontal: 32, vertical: 16),
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(8),
                            ),
                          ),
                          child: isProcessing
                              ? const SizedBox(
                                  width: 20,
                                  height: 20,
                                  child: CircularProgressIndicator(
                                    color: Colors.black,
                                    strokeWidth: 2,
                                  ),
                                )
                              : const Text(
                                  'Mark Attendance',
                                  style: TextStyle(
                                    fontSize: 16,
                                    fontWeight: FontWeight.w600,
                                  ),
                                ),
                        ),
                      ],
                    ),
                  )
                : Column(
                    children: [
                      const Text(
                        'Scan QR code',
                        style: TextStyle(
                          color: Colors.white,
                          fontSize: 20,
                          fontWeight: FontWeight.w500,
                        ),
                      ),
                      const SizedBox(height: 8),
                      Text(
                        'Position the QR code within the frame',
                        style: TextStyle(
                          color: Colors.white.withOpacity(0.7),
                          fontSize: 14,
                        ),
                      ),
                    ],
                  ),
            ),
        ],
      ),
    );
  }

  void _simulateQRScan() async {
    if (mounted) {
      setState(() {
        isProcessing = true;
      });
    }
    
    // Simulate QR scan delay
    await Future.delayed(const Duration(seconds: 1));
    
    // For web, directly show success screen
    if (mounted) {
      setState(() {
        isScanned = true;
        checkInTime = DateTime.now().toString().split(' ')[1].substring(0, 5);
        isProcessing = false;
      });
    }
  }

  void _processAttendance(String qrData) async {
    if (isProcessing) return; // Prevent multiple scans
    
    setState(() {
      isProcessing = true;
    });
    
    final authProvider = Provider.of<AuthProvider>(context, listen: false);
    final attendanceProvider = Provider.of<AttendanceProvider>(context, listen: false);
    
    // Debug authentication state
    print('🔍 Auth State Check:');
    print('  - isLoggedIn: ${authProvider.isLoggedIn}');
    print('  - currentUser: ${authProvider.currentUser}');
    
    
    // Always try to refresh auth state from storage
    await authProvider.initializeAuth();
    
    print('🔍 After initializeAuth:');
    print('  - isLoggedIn: ${authProvider.isLoggedIn}');
    print('  - currentUser: ${authProvider.currentUser}');
    
    final tokenAfterInit = await ApiService.getStoredToken();
    print('🔍 Token after init: ${tokenAfterInit?.substring(0, 20)}...');
    
    // Check if user data is available - give it one more chance
    if (!authProvider.isLoggedIn || authProvider.currentUser == null) {
      print('🔄 First auth check failed, trying once more...');
      
      // Wait a bit and try again
      await Future.delayed(const Duration(milliseconds: 500));
      await authProvider.initializeAuth();
      
      // Final check
      if (!authProvider.isLoggedIn || authProvider.currentUser == null) {
        print('🚨 No valid authentication after retry - redirecting to login');
        if (mounted) {
          _showErrorDialog('Your session has expired. Please login again.');
        }
        await authProvider.logout();
        if (mounted) {
          setState(() {
            isProcessing = false;
          });
          Navigator.of(context).pushNamedAndRemoveUntil('/login', (route) => false);
        }
        return;
      } else {
        print('✅ Authentication recovered on retry!');
      }
    }

    // Check today's attendance status to determine if this is check-in or check-out
    final hasCompletedToday = attendanceProvider.hasCompletedAttendanceToday();
    final hasCheckedInToday = attendanceProvider.hasCheckedInToday();
    
    if (hasCompletedToday) {
      if (mounted) {
        final totalHours = attendanceProvider.getTodayTotalHours();
        _showAlreadyCompletedDialog(totalHours);
        setState(() {
          isProcessing = false;
        });
      }
      return;
    }
    
    String actionType;
    if (!hasCheckedInToday) {
      actionType = 'check_in';
      print('✅ This will be a CHECK-IN');
    } else {
      actionType = 'check_out';
      print('🚪 This will be a CHECK-OUT');
    }

    // Handle different types of QR codes
    String attendanceData = qrData;
    
    // Check if it's a static office QR code
    try {
      final qrJson = json.decode(qrData);
      
      if (qrJson['type'] == 'office_attendance' && qrJson['static'] == true) {
        // This is a static office QR code - create attendance data with current date
        attendanceData = json.encode({
          'type': 'office_attendance',
          'location': qrJson['location'],
          'company': qrJson['company'],
          'date': DateTime.now().toIso8601String().split('T')[0], // Current date
          'time': DateTime.now().toIso8601String().split('T')[1].split('.')[0], // Current time
          'static': true // Keep the original static field
        });
      }
    } catch (e) {
      // If JSON parsing fails, treat as regular QR code
    }
    
    try {
      final success = await attendanceProvider.markAttendance(
        authProvider.currentUser!['id'], 
        'Present',
        qrData: attendanceData
      );
      
      if (success) {
        if (mounted) {
          // Stop camera and show success screen
          await cameraController?.stop();
          
          setState(() {
            isScanned = true;
            checkInTime = DateTime.now().toString().split(' ')[1].substring(0, 5);
            isProcessing = false;
          });
          
          // Refresh attendance data to get updated status
          await attendanceProvider.loadAttendanceHistory(authProvider.currentUser!['id'], limit: 30);
        }
      } else {
        String errorMessage = attendanceProvider.errorMessage ?? 'Failed to mark attendance';
        
        // Handle authentication-related errors
        if (errorMessage.contains('401') || 
            errorMessage.contains('Authentication failed') ||
            errorMessage.contains('Invalid token') ||
            errorMessage.contains('Access denied') ||
            errorMessage.toLowerCase().contains('session expired')) {
          print('🚨 Server returned auth error: $errorMessage');
          if (mounted) {
            _showErrorDialog('Your session has expired. Please login again.');
          }
          await authProvider.logout();
          if (mounted) {
            setState(() {
              isProcessing = false;
            });
            Navigator.of(context).pushNamedAndRemoveUntil('/login', (route) => false);
          }
          return;
        }
        
        if (mounted) {
          _showErrorDialog(errorMessage);
        }
      }
    } catch (e) {
      print('🚨 Exception during attendance marking: $e');
      if (mounted) {
        _showErrorDialog('Network error. Please check your connection and try again.');
      }
    }
    
    if (mounted) {
      setState(() {
        isProcessing = false;
      });
    }
  }

  void _showCheckInSuccessDialog(String time) {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (context) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(15)),
        backgroundColor: Colors.white,
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 80,
              height: 80,
              decoration: const BoxDecoration(
                color: Colors.green,
                shape: BoxShape.circle,
              ),
              child: const Icon(
                Icons.login,
                color: Colors.white,
                size: 40,
              ),
            ),
            const SizedBox(height: 20),
            const Text(
              'Check-in Successful!',
              style: TextStyle(
                fontSize: 20,
                fontWeight: FontWeight.bold,
                color: Colors.green,
              ),
            ),
            const SizedBox(height: 10),
            Text(
              'Time: $time',
              style: const TextStyle(
                fontSize: 16,
                color: Colors.grey,
              ),
            ),
            const SizedBox(height: 10),
            const Text(
              'Remember to check out when you leave!',
              style: TextStyle(
                fontSize: 14,
                color: Colors.orange,
                fontWeight: FontWeight.w500,
              ),
              textAlign: TextAlign.center,
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () {
              Navigator.of(context).pop();
              Navigator.of(context).pop(); // Go back to dashboard
            },
            child: const Text(
              'OK',
              style: TextStyle(
                color: Colors.green,
                fontWeight: FontWeight.bold,
              ),
            ),
          ),
        ],
      ),
    );
  }

  void _showAlreadyCompletedDialog(double? totalHours) {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (context) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(15)),
        backgroundColor: Colors.white,
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 80,
              height: 80,
              decoration: const BoxDecoration(
                color: Colors.orange,
                shape: BoxShape.circle,
              ),
              child: const Icon(
                Icons.schedule,
                color: Colors.white,
                size: 40,
              ),
            ),
            const SizedBox(height: 20),
            const Text(
              'Attendance Complete',
              style: TextStyle(
                fontSize: 20,
                fontWeight: FontWeight.bold,
                color: Colors.orange,
              ),
            ),
            const SizedBox(height: 10),
            const Text(
              'You have already completed your attendance for today.',
              style: TextStyle(
                fontSize: 16,
                color: Colors.grey,
              ),
              textAlign: TextAlign.center,
            ),
            if (totalHours != null) ...[
              const SizedBox(height: 15),
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: Colors.green.withOpacity(0.1),
                  borderRadius: BorderRadius.circular(8),
                  border: Border.all(color: Colors.green.withOpacity(0.3)),
                ),
                child: Column(
                  children: [
                    const Text(
                      'Total Hours Worked',
                      style: TextStyle(
                        fontSize: 14,
                        fontWeight: FontWeight.w500,
                        color: Colors.green,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      '${totalHours.toStringAsFixed(1)} hours',
                      style: const TextStyle(
                        fontSize: 18,
                        fontWeight: FontWeight.bold,
                        color: Colors.green,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ],
        ),
        actions: [
          TextButton(
            onPressed: () {
              Navigator.of(context).pop();
              Navigator.of(context).pop(); // Go back to dashboard
            },
            child: const Text(
              'OK',
              style: TextStyle(
                color: Colors.orange,
                fontWeight: FontWeight.bold,
              ),
            ),
          ),
        ],
      ),
    );
  }

  void _showCheckOutSuccessDialog(double? totalHours) {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (context) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(15)),
        backgroundColor: Colors.white,
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 80,
              height: 80,
              decoration: const BoxDecoration(
                color: Colors.blue,
                shape: BoxShape.circle,
              ),
              child: const Icon(
                Icons.logout,
                color: Colors.white,
                size: 40,
              ),
            ),
            const SizedBox(height: 20),
            const Text(
              'Check-out Successful!',
              style: TextStyle(
                fontSize: 20,
                fontWeight: FontWeight.bold,
                color: Colors.blue,
              ),
            ),
            const SizedBox(height: 10),
            Text(
              'Time: ${DateTime.now().toString().split(' ')[1].substring(0, 5)}',
              style: const TextStyle(
                fontSize: 16,
                color: Colors.grey,
              ),
            ),
            if (totalHours != null) ...[
              const SizedBox(height: 15),
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: Colors.blue.withOpacity(0.1),
                  borderRadius: BorderRadius.circular(8),
                  border: Border.all(color: Colors.blue.withOpacity(0.3)),
                ),
                child: Column(
                  children: [
                    const Text(
                      'Total Hours Worked Today',
                      style: TextStyle(
                        fontSize: 14,
                        fontWeight: FontWeight.w500,
                        color: Colors.blue,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      '${totalHours.toStringAsFixed(1)} hours',
                      style: const TextStyle(
                        fontSize: 18,
                        fontWeight: FontWeight.bold,
                        color: Colors.blue,
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 10),
            ],
            const Text(
              'Have a great day!',
              style: TextStyle(
                fontSize: 14,
                color: Colors.green,
                fontWeight: FontWeight.w500,
              ),
              textAlign: TextAlign.center,
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () {
              Navigator.of(context).pop();
              Navigator.of(context).pop(); // Go back to dashboard
            },
            child: const Text(
              'OK',
              style: TextStyle(
                color: Colors.blue,
                fontWeight: FontWeight.bold,
              ),
            ),
          ),
        ],
      ),
    );
  }

  void _showSuccessDialog(String status, String time) {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (context) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(15)),
        title: const Row(
          children: [
            Icon(Icons.check_circle, color: Colors.green, size: 30),
            SizedBox(width: 10),
            Text('Success!'),
          ],
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('Attendance marked successfully!'),
            const SizedBox(height: 10),
            Text('Status: $status'),
            Text('Time: $time'),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () {
              Navigator.of(context).pop();
              Navigator.of(context).pop(); // Go back to dashboard
            },
            child: const Text('OK'),
          ),
        ],
      ),
    );
  }

  void _showErrorDialog(String message) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(15)),
        title: const Row(
          children: [
            Icon(Icons.error, color: Colors.red, size: 30),
            SizedBox(width: 10),
            Text('Error'),
          ],
        ),
        content: Text(message),
        actions: [
          TextButton(
            onPressed: () {
              Navigator.of(context).pop();
              setState(() {
                isProcessing = false;
              });
            },
            child: const Text('OK'),
          ),
        ],
      ),
    );
  }

  @override
  void dispose() {
    if (!isScanned) {
      cameraController?.stop();
    }
    cameraController?.dispose();
    super.dispose();
  }
}