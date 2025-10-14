import 'package:flutter/material.dart';
import 'package:flutter/foundation.dart' show kIsWeb;
import 'package:provider/provider.dart';
import 'package:mobile_scanner/mobile_scanner.dart';
import '../providers/auth_provider.dart';
import '../providers/attendance_provider.dart';

class QRScannerScreen extends StatefulWidget {
  const QRScannerScreen({super.key});

  @override
  State<QRScannerScreen> createState() => _QRScannerScreenState();
}

class _QRScannerScreenState extends State<QRScannerScreen> {
  bool isProcessing = false;
  MobileScannerController? cameraController;

  @override
  void initState() {
    super.initState();
    if (!kIsWeb) {
      cameraController = MobileScannerController();
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.black,
      body: Stack(
        children: [
          // Camera/Scanner view or Web fallback
          if (kIsWeb)
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
                if (!isProcessing) {
                  final List<Barcode> barcodes = capture.barcodes;
                  if (barcodes.isNotEmpty && barcodes.first.rawValue != null) {
                    _processAttendance(barcodes.first.rawValue!);
                  }
                }
              },
            ),
          
          // Top bar with back button
          SafeArea(
            child: Padding(
              padding: const EdgeInsets.all(16.0),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  IconButton(
                    icon: const Icon(Icons.arrow_back, color: Colors.white, size: 28),
                    onPressed: () => Navigator.pop(context),
                  ),
                  if (!kIsWeb)
                    IconButton(
                      icon: const Icon(Icons.flash_off, color: Colors.white, size: 28),
                      onPressed: () => cameraController?.toggleTorch(),
                    ),
                ],
              ),
            ),
          ),
          
          // Scanning frame overlay
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
                      decoration: BoxDecoration(
                        border: const Border(
                          top: BorderSide(color: Colors.cyan, width: 5),
                          left: BorderSide(color: Colors.cyan, width: 5),
                        ),
                        borderRadius: const BorderRadius.only(
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
          
          // Bottom instruction text or button
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
    setState(() {
      isProcessing = true;
    });
    
    // Simulate QR scan delay
    await Future.delayed(const Duration(seconds: 1));
    
    _processAttendance('demo_qr_code');
  }

  void _processAttendance(String qrData) async {
    final authProvider = Provider.of<AuthProvider>(context, listen: false);
    final attendanceProvider = Provider.of<AttendanceProvider>(context, listen: false);
    
    if (authProvider.currentUser == null) {
      _showErrorDialog('User not found. Please login again.');
      return;
    }

    // Check if attendance is already marked today
    if (attendanceProvider.isAttendanceMarkedToday()) {
      _showErrorDialog('Attendance already marked for today.');
      setState(() {
        isProcessing = false;
      });
      return;
    }

    // In a real app, you would validate the QR code data
    // For demo purposes, we'll accept any QR code as valid
    final success = await attendanceProvider.markAttendance(
      authProvider.currentUser!['id'], 
      'Present'
    );
    
    if (success) {
      final todayStatus = attendanceProvider.getTodayAttendanceStatus();
      _showSuccessDialog(todayStatus ?? 'Present', DateTime.now().toString().split(' ')[1].substring(0, 5));
    } else {
      _showErrorDialog(attendanceProvider.errorMessage ?? 'Failed to mark attendance');
    }
    
    setState(() {
      isProcessing = false;
    });
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
            Text('Attendance marked successfully!'),
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
    cameraController?.dispose();
    super.dispose();
  }
}
