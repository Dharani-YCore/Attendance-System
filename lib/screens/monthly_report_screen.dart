import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../services/api_service.dart';

class MonthlyReportScreen extends StatefulWidget {
  const MonthlyReportScreen({super.key});

  @override
  State<MonthlyReportScreen> createState() => _MonthlyReportScreenState();
}

class _MonthlyReportScreenState extends State<MonthlyReportScreen> {
  bool isLoading = true;
  DateTime selectedMonth = DateTime.now();
  Map<String, dynamic>? reportData;
  List<Map<String, dynamic>> holidays = [];
  String? errorMessage;

  @override
  void initState() {
    super.initState();
    // Schedule data loading after the first frame to avoid build phase conflicts
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _loadMonthlyReport();
    });
  }

  void _loadMonthlyReport() async {
    setState(() {
      isLoading = true;
      errorMessage = null;
    });

    try {
      final user = await ApiService.getCurrentUser();
      if (user != null) {
        // Get first and last day of selected month
        final firstDay = DateTime(selectedMonth.year, selectedMonth.month, 1);
        final lastDay = DateTime(selectedMonth.year, selectedMonth.month + 1, 0);
        
        final startDate = DateFormat('yyyy-MM-dd').format(firstDay);
        final endDate = DateFormat('yyyy-MM-dd').format(lastDay);
        
        // Fetch both attendance report and holidays
        final result = await ApiService.getAttendanceReport(user['id'], startDate, endDate);
        final holidaysResult = await ApiService.getHolidays(startDate, endDate);
        
        setState(() {
          reportData = result;
          print('📊 Monthly Report Data: $result');
          print('📊 Summary exists: ${result['summary'] != null}');
          print('📊 Summary data: ${result['summary']}');
          if (holidaysResult['success'] && holidaysResult['data'] != null) {
            holidays = List<Map<String, dynamic>>.from(holidaysResult['data']);
          }
          isLoading = false;
        });
      } else {
        setState(() {
          errorMessage = 'User not found. Please login again.';
          isLoading = false;
        });
      }
    } catch (e) {
      print('Error loading monthly report: $e');
      setState(() {
        errorMessage = 'Failed to load monthly report. Please check your connection.';
        isLoading = false;
      });
    }
  }

  void _selectMonth() async {
    final DateTime? picked = await showDatePicker(
      context: context,
      initialDate: selectedMonth,
      firstDate: DateTime(2020),
      lastDate: DateTime.now(),
      initialDatePickerMode: DatePickerMode.year,
    );

    if (picked != null && (picked.month != selectedMonth.month || picked.year != selectedMonth.year)) {
      setState(() {
        selectedMonth = picked;
      });
      _loadMonthlyReport();
    }
  }

  // Get attendance status for a specific date
  String? _getAttendanceStatus(DateTime date) {
    if (reportData == null || reportData!['data'] == null) return null;
    
    final dateStr = DateFormat('yyyy-MM-dd').format(date);
    final records = reportData!['data'] as List;
    
    for (var record in records) {
      if (record['date'] == dateStr) {
        return record['status'];
      }
    }
    return null;
  }

  // Check if a date is a holiday
  bool _isHoliday(DateTime date) {
    final dateStr = DateFormat('yyyy-MM-dd').format(date);
    return holidays.any((holiday) => holiday['date'] == dateStr);
  }

  // Get holiday name for a date
  String? _getHolidayName(DateTime date) {
    final dateStr = DateFormat('yyyy-MM-dd').format(date);
    final holiday = holidays.firstWhere(
      (h) => h['date'] == dateStr,
      orElse: () => {},
    );
    return holiday.isNotEmpty ? holiday['name'] : null;
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFE0F7FA),
      appBar: AppBar(
        title: const Text('Monthly Report'),
        backgroundColor: Colors.transparent,
        elevation: 0,
        foregroundColor: Colors.black,
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
                        onPressed: _loadMonthlyReport,
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
                  // Month Selector Card
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.all(16),
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
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text(
                              'Selected Month',
                              style: TextStyle(
                                fontSize: 14,
                                color: Colors.black54,
                              ),
                            ),
                            const SizedBox(height: 4),
                            Text(
                              DateFormat('MMMM yyyy').format(selectedMonth),
                              style: const TextStyle(
                                fontSize: 18,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                          ],
                        ),
                        ElevatedButton.icon(
                          onPressed: _selectMonth,
                          icon: const Icon(Icons.calendar_month, size: 18),
                          label: const Text('Change'),
                          style: ElevatedButton.styleFrom(
                            backgroundColor: const Color(0xFF80DEEA),
                            foregroundColor: Colors.black,
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(8),
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 20),

                  // Calendar View
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
                    child: _buildCalendar(),
                  ),
                  const SizedBox(height: 20),

                  // Summary Stats Card
                  if (reportData != null && reportData!['summary'] != null) ...[
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
                            'Attendance Summary:',
                            style: TextStyle(
                              fontSize: 18,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                          const SizedBox(height: 20),
                          // First Row: Total working days and Days Present
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Expanded(
                                child: _buildSummaryItemCompact(
                                  'Total working days',
                                  _calculateTotalWorkingDays().toString(),
                                ),
                              ),
                              const SizedBox(width: 20),
                              Expanded(
                                child: _buildSummaryItemCompact(
                                  'Days Present',
                                  (reportData!['summary']['present_days'] ?? 0).toString(),
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 16),
                          // Second Row: Attendance Not Marked and Days Absent
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Expanded(
                                child: _buildSummaryItemCompact(
                                  'Attendance Not Marked',
                                  _calculateNotMarkedDays().toString(),
                                ),
                              ),
                              const SizedBox(width: 20),
                              Expanded(
                                child: _buildSummaryItemCompact(
                                  'Days Absent',
                                  (reportData!['summary']['absent_days'] ?? 0).toString(),
                                ),
                              ),
                            ],
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

  Widget _buildCalendar() {
    final firstDay = DateTime(selectedMonth.year, selectedMonth.month, 1);
    final lastDay = DateTime(selectedMonth.year, selectedMonth.month + 1, 0);
    final daysInMonth = lastDay.day;
    final firstWeekday = firstDay.weekday;
    
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text(
          'Attendance Calendar',
          style: TextStyle(
            fontSize: 20,
            fontWeight: FontWeight.bold,
          ),
        ),
        const SizedBox(height: 16),
        
        // Weekday headers
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceAround,
          children: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']
              .map((day) => SizedBox(
                    width: 40,
                    child: Center(
                      child: Text(
                        day,
                        style: TextStyle(
                          fontSize: 12,
                          fontWeight: FontWeight.bold,
                          color: Colors.grey.shade600,
                        ),
                      ),
                    ),
                  ))
              .toList(),
        ),
        const SizedBox(height: 12),
        
        // Calendar grid
        Wrap(
          spacing: 4,
          runSpacing: 8,
          children: List.generate(42, (index) {
            // Calculate actual day number
            final dayOffset = (firstWeekday % 7);
            final dayNumber = index - dayOffset + 1;
            
            // Check if this cell should display a day
            if (dayNumber < 1 || dayNumber > daysInMonth) {
              return SizedBox(
                width: 40,
                height: 40,
                child: Container(),
              );
            }
            
            final date = DateTime(selectedMonth.year, selectedMonth.month, dayNumber);
            final status = _getAttendanceStatus(date);
            final isSunday = date.weekday == DateTime.sunday;
            final isGovernmentHoliday = _isHoliday(date);
            final isToday = date.year == DateTime.now().year &&
                date.month == DateTime.now().month &&
                date.day == DateTime.now().day;
            
            Color circleColor = Colors.transparent;
            Color borderColor = Colors.grey.shade300;
            Color textColor = Colors.black87;
            
            // Priority: Attendance status > Holiday/Sunday
            if (status == 'Present') {
              circleColor = Colors.green;
              textColor = Colors.white;
            } else if (status == 'Absent') {
              circleColor = Colors.red;
              textColor = Colors.white;
            } else if (status == 'Late') {
              circleColor = Colors.orange;
              textColor = Colors.white;
            } else if (isSunday || isGovernmentHoliday) {
              // Show yellow for Sundays and holidays if no attendance is marked
              circleColor = Colors.yellow.shade700;
              textColor = Colors.white;
            }
            
            if (isToday) {
              borderColor = Colors.cyan;
            }
            
            return SizedBox(
              width: 40,
              height: 40,
              child: Container(
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  color: circleColor != Colors.transparent ? circleColor : Colors.transparent,
                  border: Border.all(
                    color: borderColor,
                    width: isToday ? 2 : 1,
                  ),
                ),
                child: Center(
                  child: Text(
                    '$dayNumber',
                    style: TextStyle(
                      fontSize: 14,
                      fontWeight: isToday ? FontWeight.bold : FontWeight.normal,
                      color: textColor,
                    ),
                  ),
                ),
              ),
            );
          }),
        ),
        const SizedBox(height: 20),
        
        // Legend
        Wrap(
          alignment: WrapAlignment.center,
          spacing: 12,
          runSpacing: 8,
          children: [
            _buildLegendItem('Present', Colors.green),
            _buildLegendItem('Absent', Colors.red),
            _buildLegendItem('Late', Colors.orange),
            _buildLegendItem('Holiday', Colors.yellow.shade700),
          ],
        ),
      ],
    );
  }

  Widget _buildLegendItem(String label, Color color) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Container(
          width: 16,
          height: 16,
          decoration: BoxDecoration(
            color: color,
            shape: BoxShape.circle,
          ),
        ),
        const SizedBox(width: 6),
        Text(
          label,
          style: const TextStyle(fontSize: 12),
        ),
      ],
    );
  }

  Widget _buildStatCard(String label, String value, Color color, IconData icon) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: color.withOpacity(0.1),
        borderRadius: BorderRadius.circular(10),
      ),
      child: Column(
        children: [
          Icon(icon, color: color, size: 30),
          const SizedBox(height: 8),
          Text(
            value,
            style: TextStyle(
              fontSize: 24,
              fontWeight: FontWeight.bold,
              color: color,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            label,
            style: TextStyle(
              fontSize: 12,
              color: color.withOpacity(0.8),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSummaryItem(String label, String value) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(
          label,
          style: const TextStyle(
            fontSize: 16,
            color: Colors.black87,
          ),
        ),
        Text(
          value,
          style: const TextStyle(
            fontSize: 16,
            fontWeight: FontWeight.bold,
            color: Colors.black87,
          ),
        ),
      ],
    );
  }

  Widget _buildSummaryItemCompact(String label, String value) {
    return RichText(
      text: TextSpan(
        style: const TextStyle(
          fontSize: 15,
          color: Colors.black87,
        ),
        children: [
          TextSpan(text: label),
          const TextSpan(
            text: ' : ',
            style: TextStyle(fontWeight: FontWeight.normal),
          ),
          TextSpan(
            text: value,
            style: const TextStyle(fontWeight: FontWeight.bold),
          ),
        ],
      ),
    );
  }

  int _calculateNotMarkedDays() {
    final totalWorkingDays = _calculateTotalWorkingDays();
    final presentDays = (reportData?['summary']?['present_days'] ?? 0) as int;
    final absentDays = (reportData?['summary']?['absent_days'] ?? 0) as int;
    final lateDays = (reportData?['summary']?['late_days'] ?? 0) as int;
    
    // Calculate not marked days by subtracting marked days from total working days
    final markedDays = presentDays + absentDays + lateDays;
    return totalWorkingDays - markedDays;
  }

  int _calculateTotalWorkingDays() {
    final firstDay = DateTime(selectedMonth.year, selectedMonth.month, 1);
    final lastDay = DateTime(selectedMonth.year, selectedMonth.month + 1, 0);
    
    int workingDays = 0;
    
    // Iterate through each day of the month
    for (int day = 1; day <= lastDay.day; day++) {
      final currentDate = DateTime(selectedMonth.year, selectedMonth.month, day);
      
      // Skip only Sundays
      if (currentDate.weekday == DateTime.sunday) {
        continue;
      }
      
      // Skip holidays
      if (_isHoliday(currentDate)) {
        continue;
      }
      
      workingDays++;
    }
    
    return workingDays;
  }

}
