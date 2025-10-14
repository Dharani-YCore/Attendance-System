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

  @override
  void initState() {
    super.initState();
    _loadMonthlyReport();
  }

  void _loadMonthlyReport() async {
    setState(() {
      isLoading = true;
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
        
        if (result['success']) {
          setState(() {
            reportData = result;
            if (holidaysResult['success'] && holidaysResult['data'] != null) {
              holidays = List<Map<String, dynamic>>.from(holidaysResult['data']);
            }
            isLoading = false;
          });
        }
      }
    } catch (e) {
      setState(() {
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
                            'Attendance Summary',
                            style: TextStyle(
                              fontSize: 20,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                          const SizedBox(height: 20),
                          Row(
                            children: [
                              Expanded(
                                child: _buildStatCard(
                                  'Total',
                                  (reportData!['summary']['total_days'] ?? 0).toString(),
                                  Colors.blue,
                                  Icons.calendar_today,
                                ),
                              ),
                              const SizedBox(width: 12),
                              Expanded(
                                child: _buildStatCard(
                                  'Present',
                                  (reportData!['summary']['present_days'] ?? 0).toString(),
                                  Colors.green,
                                  Icons.check_circle,
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 12),
                          Row(
                            children: [
                              Expanded(
                                child: _buildStatCard(
                                  'Late',
                                  (reportData!['summary']['late_days'] ?? 0).toString(),
                                  Colors.orange,
                                  Icons.access_time,
                                ),
                              ),
                              const SizedBox(width: 12),
                              Expanded(
                                child: _buildStatCard(
                                  'Absent',
                                  (reportData!['summary']['absent_days'] ?? 0).toString(),
                                  Colors.red,
                                  Icons.cancel,
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 20),
                          // Attendance Rate
                          Container(
                            padding: const EdgeInsets.all(16),
                            decoration: BoxDecoration(
                              color: const Color(0xFF80DEEA).withOpacity(0.2),
                              borderRadius: BorderRadius.circular(10),
                            ),
                            child: Row(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                const Icon(Icons.analytics, color: Color(0xFF00BCD4)),
                                const SizedBox(width: 10),
                                Text(
                                  'Attendance Rate: ${((reportData!['summary']['attendance_percentage'] ?? 0.0) as num).toStringAsFixed(1)}%',
                                  style: const TextStyle(
                                    fontSize: 16,
                                    fontWeight: FontWeight.bold,
                                    color: Color(0xFF00838F),
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 20),

                    // Recent Records
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
                            'Recent Records',
                            style: TextStyle(
                              fontSize: 20,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                          const SizedBox(height: 15),
                          ..._buildRecordsList(),
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

  List<Widget> _buildRecordsList() {
    if (reportData == null || reportData!['data'] == null) {
      return [
        const Center(
          child: Padding(
            padding: EdgeInsets.all(20.0),
            child: Text(
              'No data available',
              style: TextStyle(color: Colors.grey),
            ),
          ),
        ),
      ];
    }

    final data = reportData!['data'] as List;
    final recentRecords = data.take(5).toList();

    if (recentRecords.isEmpty) {
      return [
        const Center(
          child: Padding(
            padding: EdgeInsets.all(20.0),
            child: Text(
              'No records found',
              style: TextStyle(color: Colors.grey),
            ),
          ),
        ),
      ];
    }

    return recentRecords.map((record) {
      final date = DateTime.parse(record['date']);
      final formattedDate = DateFormat('MMM dd, yyyy').format(date);
      
      return Column(
        children: [
          ListTile(
            contentPadding: EdgeInsets.zero,
            leading: _getStatusIcon(record['status']),
            title: Text(formattedDate),
            subtitle: Text('Time: ${record['time']}'),
            trailing: Container(
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
              decoration: BoxDecoration(
                color: _getStatusColor(record['status']),
                borderRadius: BorderRadius.circular(12),
              ),
              child: Text(
                record['status'],
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 12,
                  fontWeight: FontWeight.bold,
                ),
              ),
            ),
          ),
          if (record != recentRecords.last) const Divider(),
        ],
      );
    }).toList();
  }

  Widget _getStatusIcon(String status) {
    switch (status) {
      case 'Present':
        return const Icon(Icons.check_circle, color: Colors.green);
      case 'Late':
        return const Icon(Icons.access_time, color: Colors.orange);
      case 'Absent':
        return const Icon(Icons.cancel, color: Colors.red);
      default:
        return const Icon(Icons.help, color: Colors.grey);
    }
  }

  Color _getStatusColor(String status) {
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
