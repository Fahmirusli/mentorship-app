// lib/screens/shared/my_schedule.dart
import 'package:flutter/material.dart';
import 'package:table_calendar/table_calendar.dart';
import '../services/api_service.dart';
import 'session_detail_screen.dart';

class MyScheduleScreen extends StatefulWidget {
  const MyScheduleScreen({super.key});

  @override
  State<MyScheduleScreen> createState() => _MyScheduleScreenState();
}

class _MyScheduleScreenState extends State<MyScheduleScreen> {
  DateTime _focusedDay = DateTime.now();
  DateTime? _selectedDay;
  List<dynamic> _allAppointments = [];
  List<dynamic> _dayAppointments = [];
  bool _isLoading = true;
  String? _userRole;

  // Track which dates have appointments for the calendar markers
  Map<DateTime, List<dynamic>> _eventsByDate = {};

  @override
  void initState() {
    super.initState();
    _selectedDay = _focusedDay;
    _loadData();
  }

  Future<void> _loadData() async {
    // Get user role to determine color scheme
    final profile = await ApiService.getProfile();
    final appointments = await ApiService.getMyAppointments();

    if (mounted) {
      setState(() {
        _userRole = profile?['role'] ?? 'mentee';
        _allAppointments = appointments;
        _buildEventMap();
        _filterForSelectedDay();
        _isLoading = false;
      });
    }
  }

  void _buildEventMap() {
    _eventsByDate = {};
    for (var apt in _allAppointments) {
      final dateStr = apt['date'];
      if (dateStr == null) continue;
      try {
        final date = DateTime.parse(dateStr);
        final normalizedDate = DateTime(date.year, date.month, date.day);
        _eventsByDate.putIfAbsent(normalizedDate, () => []);
        _eventsByDate[normalizedDate]!.add(apt);
      } catch (_) {}
    }
  }

  void _filterForSelectedDay() {
    if (_selectedDay == null) {
      _dayAppointments = [];
      return;
    }
    final normalized = DateTime(_selectedDay!.year, _selectedDay!.month, _selectedDay!.day);
    _dayAppointments = _eventsByDate[normalized] ?? [];
  }

  List<dynamic> _getEventsForDay(DateTime day) {
    final normalized = DateTime(day.year, day.month, day.day);
    return _eventsByDate[normalized] ?? [];
  }

  bool get _isMentor => _userRole == 'mentor';
  Color get _primaryColor => _isMentor ? const Color(0xFF2E7D32) : const Color(0xFF6B4EE6);
  Color get _primaryLight => _isMentor ? const Color(0xFFE8F5E9) : const Color(0xFFE2DDF8);

  @override
  Widget build(BuildContext context) {
    final bool showTabs = _isMentor;

    return DefaultTabController(
      length: 2,
      child: Scaffold(
        backgroundColor: _isMentor ? const Color(0xFFF0F7F0) : const Color(0xFFF4F3FB),
        appBar: AppBar(
          title: const Text("My Schedule", style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
          backgroundColor: _primaryColor,
          elevation: 0,
          iconTheme: const IconThemeData(color: Colors.white),
          bottom: showTabs
              ? const TabBar(
                  indicatorColor: Colors.white,
                  tabs: [
                    Tab(text: "Upcoming"),
                    Tab(text: "Verify Past Sessions"),
                  ],
                )
              : null,
        ),
        body: _isLoading
            ? Center(child: CircularProgressIndicator(color: _primaryColor))
            : showTabs
                ? TabBarView(
                    children: [
                      _buildScheduleView(),
                      _buildVerifySessionsView(),
                    ],
                  )
                : _buildScheduleView(),
      ),
    );
  }

  Widget _buildScheduleView() {
    return Column(
      children: [
        // Calendar Card
                Container(
                  margin: const EdgeInsets.all(16),
                  padding: const EdgeInsets.only(bottom: 10),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(20),
                    boxShadow: [BoxShadow(color: _primaryColor.withOpacity(0.05), blurRadius: 15, spreadRadius: 3)],
                  ),
                  child: TableCalendar(
                    firstDay: DateTime.utc(2020, 1, 1),
                    lastDay: DateTime.utc(2030, 12, 31),
                    focusedDay: _focusedDay,
                    selectedDayPredicate: (day) => isSameDay(_selectedDay, day),
                    eventLoader: _getEventsForDay,
                    onDaySelected: (selectedDay, focusedDay) {
                      setState(() {
                        _selectedDay = selectedDay;
                        _focusedDay = focusedDay;
                        _filterForSelectedDay();
                      });
                    },
                    calendarStyle: CalendarStyle(
                      selectedDecoration: BoxDecoration(color: _primaryColor, shape: BoxShape.circle),
                      selectedTextStyle: const TextStyle(color: Colors.white),
                      todayDecoration: BoxDecoration(color: _primaryLight, shape: BoxShape.circle),
                      todayTextStyle: TextStyle(color: _primaryColor, fontWeight: FontWeight.bold),
                      markerDecoration: BoxDecoration(color: _primaryColor, shape: BoxShape.circle),
                      markerSize: 6,
                      markersMaxCount: 3,
                    ),
                    headerStyle: const HeaderStyle(formatButtonVisible: false, titleCentered: true),
                  ),
                ),

                // Sessions Header
                Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 5),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(
                        _selectedDay != null && isSameDay(_selectedDay, DateTime.now())
                            ? "Today's Sessions"
                            : "Sessions",
                        style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                      ),
                      if (_dayAppointments.isNotEmpty)
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                          decoration: BoxDecoration(
                            color: _primaryLight,
                            borderRadius: BorderRadius.circular(12),
                          ),
                          child: Text(
                            '${_dayAppointments.length} session${_dayAppointments.length > 1 ? 's' : ''}',
                            style: TextStyle(fontSize: 12, color: _primaryColor, fontWeight: FontWeight.w600),
                          ),
                        ),
                    ],
                  ),
                ),

                // Sessions List
                Expanded(
                  child: _dayAppointments.isEmpty
                      ? Center(
                          child: Column(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              Icon(Icons.event_busy, size: 56, color: Colors.grey.shade300),
                              const SizedBox(height: 10),
                              Text(
                                "No sessions on this day.",
                                style: TextStyle(color: Colors.grey.shade500),
                              ),
                            ],
                          ),
                        )
                      : ListView.builder(
                          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                          itemCount: _dayAppointments.length,
                          itemBuilder: (context, index) {
                            final apt = _dayAppointments[index];
                            return _buildAppointmentCard(apt);
                          },
                        ),
                ),
              ],
            );
  }

  Widget _buildVerifySessionsView() {
    // Filter past sessions that are still scheduled
    final now = DateTime.now();
    final pastUnverified = _allAppointments.where((apt) {
      if (apt['status'] != 'scheduled') return false;
      final dateStr = apt['date'];
      if (dateStr == null) return false;
      try {
        final date = DateTime.parse(dateStr);
        return date.isBefore(DateTime(now.year, now.month, now.day));
      } catch (_) {
        return false;
      }
    }).toList();

    if (pastUnverified.isEmpty) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.verified, size: 56, color: Colors.grey.shade300),
            const SizedBox(height: 10),
            Text("All past sessions are verified!", style: TextStyle(color: Colors.grey.shade500)),
          ],
        ),
      );
    }

    return ListView.builder(
      padding: const EdgeInsets.all(16),
      itemCount: pastUnverified.length,
      itemBuilder: (context, index) {
        final apt = pastUnverified[index];
        final String date = apt['date'] ?? '';
        final String menteeName = apt['other_user_name'] ?? 'Mentee';
        
        return Container(
          margin: const EdgeInsets.only(bottom: 15),
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(15),
            boxShadow: [BoxShadow(color: _primaryColor.withOpacity(0.05), blurRadius: 10)],
            border: Border.all(color: Colors.orange.withOpacity(0.3)),
          ),
          child: Row(
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text("Mentorship with $menteeName", style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
                    const SizedBox(height: 5),
                    Text(date, style: TextStyle(color: Colors.grey.shade600, fontSize: 13)),
                  ],
                ),
              ),
              ElevatedButton(
                style: ElevatedButton.styleFrom(
                  backgroundColor: Colors.orange,
                  foregroundColor: Colors.white,
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                ),
                onPressed: () => _showVerificationModal(apt),
                child: const Text("Verify", style: TextStyle(fontWeight: FontWeight.bold)),
              ),
            ],
          ),
        );
      },
    );
  }

  void _showVerificationModal(dynamic apt) {
    showDialog(
      context: context,
      builder: (ctx) {
        return Dialog(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
          child: Container(
            padding: const EdgeInsets.all(25),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(20),
            ),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                const Icon(Icons.event_available_rounded, color: Color(0xFF2E7D32), size: 56),
                const SizedBox(height: 20),
                const Text("Verify Session", style: TextStyle(fontSize: 22, fontWeight: FontWeight.bold)),
                const SizedBox(height: 10),
                const Text(
                  "Did this session happen as planned? Marking it missed will refund the mentee.",
                  textAlign: TextAlign.center,
                  style: TextStyle(color: Colors.grey, fontSize: 14),
                ),
                const SizedBox(height: 30),
                Row(
                  children: [
                    Expanded(
                      child: ElevatedButton(
                        style: ElevatedButton.styleFrom(
                          backgroundColor: Colors.red.shade50,
                          foregroundColor: Colors.red,
                          elevation: 0,
                          padding: const EdgeInsets.symmetric(vertical: 12),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                        ),
                        onPressed: () {
                          Navigator.pop(ctx);
                          ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Marked as Missed')));
                          // Re-fetch in real app
                          _loadData();
                        },
                        child: const Text("Mark Missed"),
                      ),
                    ),
                    const SizedBox(width: 15),
                    Expanded(
                      child: ElevatedButton(
                        style: ElevatedButton.styleFrom(
                          backgroundColor: const Color(0xFF2E7D32),
                          foregroundColor: Colors.white,
                          elevation: 0,
                          padding: const EdgeInsets.symmetric(vertical: 12),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                        ),
                        onPressed: () {
                          Navigator.pop(ctx);
                          ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Marked as Completed')));
                          // Re-fetch in real app
                          _loadData();
                        },
                        child: const Text("Completed"),
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
        );
      },
    );
  }

  Widget _buildAppointmentCard(dynamic apt) {
    final String time = apt['time'] ?? '00:00';
    final String otherName = apt['other_user_name'] ?? 'Unknown';
    final String status = apt['status'] ?? 'scheduled';
    final int duration = apt['duration_minutes'] ?? 60;
    final String? notes = apt['notes'];

    Color statusColor;
    String statusLabel;
    switch (status) {
      case 'completed':
        statusColor = Colors.green;
        statusLabel = 'Completed';
        break;
      case 'cancelled':
        statusColor = Colors.red;
        statusLabel = 'Cancelled';
        break;
      case 'rescheduled':
        statusColor = Colors.orange;
        statusLabel = 'Rescheduled';
        break;
      default:
        statusColor = _primaryColor;
        statusLabel = 'Upcoming';
    }

    return GestureDetector(
      onTap: () {
        Navigator.push(
          context,
          MaterialPageRoute(
            builder: (context) => SessionDetailScreen(
              appointment: Map<String, dynamic>.from(apt),
              isMentor: _isMentor,
            ),
          ),
        );
      },
      child: Container(
        margin: const EdgeInsets.only(bottom: 12),
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(18),
          boxShadow: [BoxShadow(color: _primaryColor.withOpacity(0.04), blurRadius: 10)],
          border: Border.all(color: _primaryColor.withOpacity(0.1)),
        ),
        child: Row(
          children: [
            // Time column
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: _primaryLight,
                borderRadius: BorderRadius.circular(14),
              ),
              child: Icon(Icons.access_time_filled, color: _primaryColor, size: 24),
            ),
            const SizedBox(width: 14),
            // Details
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(time, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                  const SizedBox(height: 2),
                  Text(
                    _isMentor ? "Session with $otherName" : "Mentorship with $otherName",
                    style: TextStyle(color: Colors.grey.shade600, fontSize: 13),
                  ),
                  const SizedBox(height: 4),
                  Row(
                    children: [
                      Icon(Icons.timer_outlined, size: 13, color: Colors.grey.shade400),
                      const SizedBox(width: 3),
                      Text('${duration}min', style: TextStyle(fontSize: 11, color: Colors.grey.shade500)),
                      if (notes != null && notes.isNotEmpty) ...[
                        const SizedBox(width: 10),
                        Icon(Icons.note_outlined, size: 13, color: Colors.grey.shade400),
                        const SizedBox(width: 3),
                        Flexible(
                          child: Text(notes, style: TextStyle(fontSize: 11, color: Colors.grey.shade500),
                              maxLines: 1, overflow: TextOverflow.ellipsis),
                        ),
                      ],
                    ],
                  ),
                ],
              ),
            ),
            // Status badge + arrow
            Column(
              crossAxisAlignment: CrossAxisAlignment.end,
              children: [
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                  decoration: BoxDecoration(
                    color: statusColor.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Text(statusLabel,
                      style: TextStyle(fontSize: 10, fontWeight: FontWeight.w600, color: statusColor)),
                ),
                const SizedBox(height: 6),
                Icon(Icons.arrow_forward_ios, size: 12, color: Colors.grey.shade400),
              ],
            ),
          ],
        ),
      ),
    );
  }
}