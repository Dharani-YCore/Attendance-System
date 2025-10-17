import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../services/api_service.dart';

class DailyReportScreen extends StatefulWidget {
  const DailyReportScreen({super.key});

  @override
  State<DailyReportScreen> createState() => _DailyReportScreenState();
}

class _DailyReportScreenState extends State<DailyReportScreen> {
  bool isLoading = true;
  Map<String, dynamic>? attendanceData;
  DateTime selectedDate = DateTime.now();
  bool _initialized = false;
  String? errorMessage;

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    if (!_initialized) {
      // Get date from route arguments if provided
      final args = ModalRoute.of(context)?.settings.arguments as Map<String, dynamic>?;
      if (args != null && args['date'] != null) {
        selectedDate = DateTime.parse(args['date']);
      }
      // Schedule data loading after the first frame to avoid build phase conflicts
      WidgetsBinding.instance.addPostFrameCallback((_) {
        _loadAttendance();
      });
      _initialized = true;
    }
  }

  void _loadAttendance() async {
    setState(() {
      isLoading = true;
      errorMessage = null;
    });

    try {
      final user = await ApiService.getCurrentUser();
      if (user != null) {
        final result = await ApiService.getAttendanceHistory(user['id'], limit: 30);
        
        final records = List<Map<String, dynamic>>.from(result['data'] ?? []);
        final targetDate = DateFormat('yyyy-MM-dd').format(selectedDate);
        
        // Find attendance for selected date
        attendanceData = records.firstWhere(
          (record) => record['date'] == targetDate,
          orElse: () => {},
        );
        
        setState(() {
          isLoading = false;
        });
      } else {
        setState(() {
          errorMessage = 'User not found. Please login again.';
          isLoading = false;
        });
      }
    } catch (e) {
      print('Error loading daily attendance: $e');
      setState(() {
        errorMessage = 'Failed to load attendance data. Please check your connection.';
        isLoading = false;
      });
    }
  }

  void _selectDate() async {
    final DateTime? picked = await showDatePicker(
      context: context,
      initialDate: selectedDate,
      firstDate: DateTime(2020),
      lastDate: DateTime.now(),
    );

    if (picked != null && picked != selectedDate) {
      setState(() {
        selectedDate = picked;
      });
      _loadAttendance();
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFE0F7FA),
      appBar: AppBar(
        title: const Text('Daily Report'),
        backgroundColor: Colors.transparent,
        elevation: 0,
        foregroundColor: Colors.black,
        actions: [
          IconButton(
            icon: const Icon(Icons.calendar_month),
            onPressed: _selectDate,
            tooltip: 'Select Date',
          ),
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: _loadAttendance,
            tooltip: 'Refresh',
          ),
        ],
      ),
      body: isLoading
          ? const Center(child: CircularProgressIndicator())
          : errorMessage != null
              ? Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(
                        Icons.error_outline,
                        size: 64,
                        color: Colors.red.shade300,
                      ),
                      const SizedBox(height: 16),
                      Text(
                        errorMessage!,
                        textAlign: TextAlign.center,
                        style: const TextStyle(
                          fontSize: 16,
                          color: Colors.black54,
                        ),
                      ),
                      const SizedBox(height: 16),
                      ElevatedButton(
                        onPressed: _loadAttendance,
                        child: const Text('Retry'),
                      ),
                    ],
                  ),
                )
              : SingleChildScrollView(
              padding: const EdgeInsets.all(20),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Today's Date Header
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.all(20),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(12),
                      boxShadow: [
                        BoxShadow(
                          color: Colors.grey.withOpacity(0.1),
                          spreadRadius: 1,
                          blurRadius: 5,
                        ),
                      ],
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            Icon(
                              Icons.calendar_today,
                              color: Colors.cyan.shade700,
                              size: 24,
                            ),
                            const SizedBox(width: 12),
                            Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  selectedDate.day == DateTime.now().day &&
                                  selectedDate.month == DateTime.now().month &&
                                  selectedDate.year == DateTime.now().year
                                    ? 'Today\'s Attendance'
                                    : 'Attendance Details',
                                  style: const TextStyle(
                                    fontSize: 16,
                                    color: Colors.black54,
                                  ),
                                ),
                                const SizedBox(height: 4),
                                Text(
                                  DateFormat('EEEE, MMMM dd, yyyy').format(selectedDate),
                                  style: const TextStyle(
                                    fontSize: 18,
                                    fontWeight: FontWeight.bold,
                                  ),
                                ),
                              ],
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 20),

                  // Attendance Status Card
                  if (attendanceData != null && attendanceData!.isNotEmpty) ...[
                    Container(
                      width: double.infinity,
                      padding: const EdgeInsets.all(20),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(12),
                        boxShadow: [
                          BoxShadow(
                            color: Colors.grey.withOpacity(0.1),
                            spreadRadius: 1,
                            blurRadius: 5,
                          ),
                        ],
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text(
                            'Attendance Status',
                            style: TextStyle(
                              fontSize: 20,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                          const SizedBox(height: 20),
                          _buildStatusRow(
                            'Check-in Time',
                            attendanceData!['check_in_time'] ?? attendanceData!['time'] ?? 'N/A',
                            Icons.login,
                            Colors.green,
                          ),
                          const Divider(height: 30),
                          _buildStatusRow(
                            'Check-out Time',
                            attendanceData!['check_out_time'] ?? 'Not checked out',
                            Icons.logout,
                            attendanceData!['check_out_time'] != null ? Colors.blue : Colors.grey,
                          ),
                          const Divider(height: 30),
                          _buildStatusRow(
                            'Total Hours',
                            attendanceData!['total_hours'] != null 
                                ? '${attendanceData!['total_hours']} hours'
                                : 'Not calculated',
                            Icons.schedule,
                            attendanceData!['total_hours'] != null ? Colors.purple : Colors.grey,
                          ),
                          const Divider(height: 30),
                          _buildStatusRow(
                            'Status',
                            attendanceData!['status'] ?? 'N/A',
                            Icons.check_circle,
                            _getStatusColor(attendanceData!['status']),
                          ),
                          const Divider(height: 30),
                          _buildStatusRow(
                            'Date',
                            DateFormat('MMMM dd, yyyy').format(selectedDate),
                            Icons.calendar_today,
                            Colors.teal,
                          ),
                        ],
                      ),
                    ),
                  ] else ...[
                    Container(
                      width: double.infinity,
                      padding: const EdgeInsets.all(40),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(12),
                        boxShadow: [
                          BoxShadow(
                            color: Colors.grey.withOpacity(0.1),
                            spreadRadius: 1,
                            blurRadius: 5,
                          ),
                        ],
                      ),
                      child: Column(
                        children: [
                          Icon(
                            Icons.event_busy,
                            size: 80,
                            color: Colors.grey.shade300,
                          ),
                          const SizedBox(height: 16),
                          const Text(
                            'No Attendance Record',
                            style: TextStyle(
                              fontSize: 18,
                              fontWeight: FontWeight.bold,
                              color: Colors.black54,
                            ),
                          ),
                          const SizedBox(height: 8),
                          const Text(
                            'No attendance was marked on this date',
                            textAlign: TextAlign.center,
                            style: TextStyle(
                              fontSize: 14,
                              color: Colors.black38,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ],
              ),
            ),
    );
  }

  Widget _buildStatusRow(String label, String value, IconData icon, Color color) {
    return Row(
      children: [
        Container(
          padding: const EdgeInsets.all(10),
          decoration: BoxDecoration(
            color: color.withOpacity(0.1),
            borderRadius: BorderRadius.circular(8),
          ),
          child: Icon(icon, color: color, size: 24),
        ),
        const SizedBox(width: 16),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                label,
                style: const TextStyle(
                  fontSize: 14,
                  color: Colors.black54,
                ),
              ),
              const SizedBox(height: 4),
              Text(
                value,
                style: const TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.bold,
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }

  Color _getStatusColor(String? status) {
    switch (status) {
      case 'Present':
        return Colors.green;
      case 'Late':
        return Colors.orange;
      case 'Absent':
        return Colors.red;
      default:
        return Colors.grey;
    }
  }
}
