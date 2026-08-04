// lib/screens/mentor/mentor_home.dart
import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import '../services/api_service.dart';
import '../shared/my_schedule.dart';
import '../shared/profile_screen.dart';
import '../shared/notification_screen.dart';
import '../mentee/message_list_screen.dart';
import '../shared/session_detail_screen.dart';
import 'mentor_availability_screen.dart';
import 'mentor_mentees_screen.dart';
import 'earnings_screen.dart';
import 'resources_screen.dart';

class MentorDashboard extends StatefulWidget {
  final VoidCallback onLogout;
  const MentorDashboard({super.key, required this.onLogout});

  @override
  State<MentorDashboard> createState() => _MentorDashboardState();
}

class _MentorDashboardState extends State<MentorDashboard> {
  // Navigation indices:
  // 0 = Schedule
  // 1 = Slots
  // 2 = Wallet
  // 3 = Dashboard (center FAB)
  // 4 = Mentees
  // 5 = Messages
  // 6 = Profile
  int _selectedIndex = 3; // Default to Dashboard

  // --- REAL DATA VARIABLES ---
  String _userName = "Loading...";
  List<dynamic> _todaySchedule = [];
  int _pendingRequests = 0;
  int _upcomingSessions = 0;
  int _totalMentees = 0;
  double _totalEarnings = 0.0;
  bool _isLoadingData = true;
  int _unreadNotifCount = 0;

  late List<Widget> _pages;

  @override
  void initState() {
    super.initState();
    _fetchRealData();

    _pages = [
      const MyScheduleScreen(),                     // 0 - Schedule
      const MentorAvailabilityScreen(),             // 1 - Availability Slots
      const MentorEarningsScreen(),                 // 2 - Wallet/Earnings
      _buildMentorDashboard(),                      // 3 - Dashboard (center)
      const MentorMenteesScreen(),                  // 4 - Mentees
      const MessageListScreen(),                    // 5 - Messages
      ProfileScreen(onLogout: widget.onLogout),     // 6 - Profile
    ];
  }

  Future<void> _fetchRealData() async {
    // Start all API calls concurrently
    final profileFuture = ApiService.getProfile();
    final notifCountFuture = ApiService.getUnreadNotificationCount();
    final apptsFuture = ApiService.getMyAppointments(todayOnly: true).catchError((_) => <dynamic>[]);
    final statsFuture = ApiService.getMentorStats();

    try {
      // Wait for all to complete
      final profile = await profileFuture;
      final notifCount = await notifCountFuture;
      final todayAppts = await apptsFuture;
      final statsData = await statsFuture;

      if (mounted) {
        setState(() {
          if (profile != null) {
            _userName = profile['name'] ?? 'Mentor';
          }
          _todaySchedule = todayAppts;
          _upcomingSessions = todayAppts.length;
          if (statsData != null) {
            final earnings = statsData['total_earnings'];
            if (earnings is num) {
              _totalEarnings = earnings.toDouble();
            } else if (earnings is String) {
              _totalEarnings = double.tryParse(earnings) ?? 0.0;
            } else {
              _totalEarnings = 0.0;
            }
          }
          _unreadNotifCount = notifCount;
          _isLoadingData = false;
          _pages[3] = _buildMentorDashboard();
        });
      }
    } catch (e) {
      print('Error loading mentor dashboard: $e');
      if (mounted) {
        setState(() {
          _isLoadingData = false;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF0F7F0),
      extendBody: true, // Needed for floating nav bar effect
      body: _pages[_selectedIndex],

      // Center FAB = Dashboard
      floatingActionButton: FloatingActionButton(
        onPressed: () => setState(() => _selectedIndex = 3),
        backgroundColor: _selectedIndex == 3
            ? const Color(0xFF2E7D32)
            : const Color(0xFF66BB6A),
        elevation: _selectedIndex == 3 ? 8 : 4,
        shape: const CircleBorder(),
        child: Icon(
          Icons.dashboard_rounded,
          color: Colors.white,
          size: _selectedIndex == 3 ? 30 : 26,
        ),
      ),
      floatingActionButtonLocation: FloatingActionButtonLocation.centerDocked,

      bottomNavigationBar: Container(
        margin: const EdgeInsets.only(left: 20, right: 20, bottom: 20),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(30),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withOpacity(0.1),
              blurRadius: 20,
              offset: const Offset(0, 10),
            )
          ],
        ),
        child: BottomAppBar(
          color: Colors.transparent,
          elevation: 0,
          shape: const CircularNotchedRectangle(),
          notchMargin: 8.0,
          child: SizedBox(
            height: 60,
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceAround,
              children: [
                _navItem(Icons.calendar_month_rounded, "Schedule", 0),
                _navItem(Icons.event_available_rounded, "Slots", 1),
                _navItem(Icons.account_balance_wallet_rounded, "Wallet", 2),
                const SizedBox(width: 48), // Space for FAB
                _navItem(Icons.people_alt_rounded, "Mentees", 4),
                _navItem(Icons.message_rounded, "Messages", 5),
                _navItem(Icons.person_rounded, "Profile", 6),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _navItem(IconData icon, String label, int index, {bool disabled = false}) {
    bool isSelected = _selectedIndex == index;
    return Expanded(
      child: InkWell(
        onTap: disabled ? null : () => setState(() => _selectedIndex = index),
        splashColor: const Color(0xFF2E7D32).withOpacity(0.1),
        highlightColor: Colors.transparent,
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(
              icon,
              color: isSelected
                  ? const Color(0xFF2E7D32)
                  : disabled
                      ? Colors.grey.shade300
                      : Colors.grey.shade400,
              size: 24,
            ),
            const SizedBox(height: 3),
            Text(
              label,
              style: TextStyle(
                fontSize: 10,
                fontWeight: isSelected ? FontWeight.w700 : FontWeight.w500,
                color: isSelected
                    ? const Color(0xFF2E7D32)
                    : disabled
                        ? Colors.grey.shade300
                        : Colors.grey.shade400,
              ),
            ),
          ],
        ),
      ),
    );
  }

  // =======================================================================
  // MENTOR DASHBOARD
  // =======================================================================
  Widget _buildMentorDashboard() {
    if (_isLoadingData) {
      return const Center(child: CircularProgressIndicator(color: Color(0xFF2E7D32)));
    }

    return Column(
      children: [
        // GREEN HEADER for Mentor
        Container(
          padding: const EdgeInsets.only(top: 60, left: 20, right: 20, bottom: 20),
          decoration: const BoxDecoration(
            gradient: LinearGradient(
                colors: [Color(0xFF2E7D32), Color(0xFF66BB6A)],
                begin: Alignment.topLeft,
                end: Alignment.bottomRight),
            borderRadius: BorderRadius.only(
                bottomLeft: Radius.circular(30),
                bottomRight: Radius.circular(30)),
          ),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text("Welcome back,", style: TextStyle(color: Colors.white.withOpacity(0.7), fontSize: 16)),
                  Text(_userName, style: const TextStyle(color: Colors.white, fontSize: 26, fontWeight: FontWeight.bold)),
                ],
              ),
              GestureDetector(
                onTap: () async {
                  await Navigator.push(context, MaterialPageRoute(builder: (_) => const NotificationScreen()));
                  final count = await ApiService.getUnreadNotificationCount();
                  if (mounted) setState(() { _unreadNotifCount = count; _pages[3] = _buildMentorDashboard(); });
                },
                child: Stack(
                  children: [
                    const CircleAvatar(backgroundColor: Colors.white24, child: Icon(Icons.notifications_none, color: Colors.white)),
                    if (_unreadNotifCount > 0)
                      Positioned(
                        right: 0, top: 0,
                        child: Container(
                          padding: const EdgeInsets.all(4),
                          decoration: const BoxDecoration(color: Colors.redAccent, shape: BoxShape.circle),
                          child: Text('$_unreadNotifCount',
                              style: const TextStyle(color: Colors.white, fontSize: 9, fontWeight: FontWeight.bold)),
                        ),
                      ),
                  ],
                ),
              ),
            ],
          ),
        ),

        // SCROLLABLE CONTENT
        Expanded(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(20),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // STAT CARDS
                Row(
                  children: [
                    Expanded(child: GestureDetector(
                      onTap: () => setState(() => _selectedIndex = 2),
                      child: _statCard("Earnings", "RM ${_totalEarnings.toStringAsFixed(0)}", const Color(0xFFFFA726), Icons.account_balance_wallet_rounded),
                    )),
                    const SizedBox(width: 12),
                    Expanded(child: _statCard("Upcoming", "$_upcomingSessions", const Color(0xFF66BB6A), Icons.event_available_rounded)),
                    const SizedBox(width: 12),
                    Expanded(child: GestureDetector(
                      onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const MentorMenteesScreen())),
                      child: _statCard("Mentees", "$_totalMentees", const Color(0xFF42A5F5), Icons.people_rounded),
                    )),
                  ],
                ).animate().fadeIn(duration: 400.ms).slideY(begin: 0.15),

                const SizedBox(height: 30),

                // TODAY's SCHEDULE
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    const Text("Today's Schedule", style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                    GestureDetector(
                      onTap: () => setState(() => _selectedIndex = 0),
                      child: const Text("View All →",
                          style: TextStyle(fontSize: 13, color: Color(0xFF2E7D32), fontWeight: FontWeight.w600)),
                    ),
                  ],
                ),
                const SizedBox(height: 15),
                _todaySchedule.isEmpty
                    ? Container(
                        padding: const EdgeInsets.all(30),
                        decoration: BoxDecoration(
                          color: Colors.white,
                          borderRadius: BorderRadius.circular(20),
                          boxShadow: [BoxShadow(color: Colors.green.withOpacity(0.05), blurRadius: 10)],
                        ),
                        child: Column(
                          children: [
                            Icon(Icons.event_busy, size: 48, color: Colors.grey.shade300),
                            const SizedBox(height: 8),
                            const Text("No sessions scheduled for today.",
                                style: TextStyle(color: Colors.grey, fontStyle: FontStyle.italic)),
                          ],
                        ),
                      )
                    : Column(
                        children: _todaySchedule.map((apt) => _scheduleCard(apt)).toList(),
                      ).animate().fadeIn(delay: 200.ms).slideY(begin: 0.1),

                const SizedBox(height: 30),

                // RECENT MESSAGES
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    const Text("Recent Messages", style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                    GestureDetector(
                      onTap: () => setState(() => _selectedIndex = 3),
                      child: const Text("View All →",
                          style: TextStyle(fontSize: 13, color: Color(0xFF2E7D32), fontWeight: FontWeight.w600)),
                    ),
                  ],
                ),
                const SizedBox(height: 15),
                GestureDetector(
                  onTap: () => setState(() => _selectedIndex = 3),
                  child: Container(
                    padding: const EdgeInsets.all(20),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(20),
                      boxShadow: [BoxShadow(color: Colors.green.withOpacity(0.05), blurRadius: 10)],
                    ),
                    child: Row(
                      children: [
                        Container(
                          padding: const EdgeInsets.all(12),
                          decoration: BoxDecoration(
                            color: const Color(0xFFE8F5E9),
                            borderRadius: BorderRadius.circular(12),
                          ),
                          child: const Icon(Icons.message_rounded, color: Color(0xFF2E7D32)),
                        ),
                        const SizedBox(width: 15),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const Text("Chat with your mentees",
                                  style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
                              Text("Tap to view your conversations",
                                  style: TextStyle(color: Colors.grey.shade600, fontSize: 13)),
                            ],
                          ),
                        ),
                        const Icon(Icons.arrow_forward_ios, size: 14, color: Color(0xFF2E7D32)),
                      ],
                    ),
                  ),
                ).animate().fadeIn(delay: 400.ms).slideY(begin: 0.1),

                const SizedBox(height: 30),

                // MY RESOURCES
                const Text("My Resources", style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                const SizedBox(height: 15),
                GestureDetector(
                  onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const MentorResourcesScreen())),
                  child: Container(
                    padding: const EdgeInsets.all(20),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(20),
                      boxShadow: [BoxShadow(color: Colors.green.withOpacity(0.05), blurRadius: 10)],
                    ),
                    child: Row(
                      children: [
                        Container(
                          padding: const EdgeInsets.all(12),
                          decoration: BoxDecoration(
                            color: const Color(0xFFE8F5E9),
                            borderRadius: BorderRadius.circular(12),
                          ),
                          child: const Icon(Icons.folder_shared, color: Color(0xFF2E7D32)),
                        ),
                        const SizedBox(width: 15),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const Text("Manage Resources",
                                  style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
                              Text("Upload and share files with mentees",
                                  style: TextStyle(color: Colors.grey.shade600, fontSize: 13)),
                            ],
                          ),
                        ),
                        const Icon(Icons.arrow_forward_ios, size: 14, color: Color(0xFF2E7D32)),
                      ],
                    ),
                  ),
                ).animate().fadeIn(delay: 500.ms).slideY(begin: 0.1),
              ],
            ),
          ),
        ),
      ],
    );
  }

  Widget _statCard(String title, String count, Color color, IconData icon) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(18),
        boxShadow: [BoxShadow(color: color.withOpacity(0.1), blurRadius: 10, offset: const Offset(0, 4))],
      ),
      child: Column(
        children: [
          Container(
            padding: const EdgeInsets.all(8),
            decoration: BoxDecoration(
              color: color.withOpacity(0.1),
              borderRadius: BorderRadius.circular(10),
            ),
            child: Icon(icon, color: color, size: 22),
          ),
          const SizedBox(height: 8),
          Text(count, style: TextStyle(fontSize: 22, fontWeight: FontWeight.bold, color: color)),
          const SizedBox(height: 2),
          Text(title, style: TextStyle(color: Colors.grey.shade600, fontSize: 11, fontWeight: FontWeight.w500)),
        ],
      ),
    );
  }

  Widget _scheduleCard(dynamic apt) {
    final String time = apt['time'] ?? '00:00';
    final String otherName = apt['other_user_name'] ?? apt['mentor_name'] ?? apt['mentee_name'] ?? 'Session';
    final String status = apt['status'] ?? 'scheduled';

    return GestureDetector(
      onTap: () {
        Navigator.push(
          context,
          MaterialPageRoute(
            builder: (context) => SessionDetailScreen(
              appointment: Map<String, dynamic>.from(apt),
              isMentor: true,
            ),
          ),
        );
      },
      child: Container(
        margin: const EdgeInsets.only(bottom: 12),
        padding: const EdgeInsets.all(15),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(18),
          boxShadow: [BoxShadow(color: Colors.green.withOpacity(0.05), blurRadius: 10)],
          border: Border.all(color: const Color(0xFF2E7D32).withOpacity(0.15)),
        ),
        child: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: const Color(0xFFE8F5E9),
                borderRadius: BorderRadius.circular(12),
              ),
              child: const Icon(Icons.access_time_filled, color: Color(0xFF2E7D32)),
            ),
            const SizedBox(width: 15),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(time, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                  Text("Session with $otherName",
                      style: TextStyle(color: Colors.grey.shade600, fontSize: 13)),
                ],
              ),
            ),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
              decoration: BoxDecoration(
                color: status == 'scheduled' ? const Color(0xFFE8F5E9) : Colors.orange.withOpacity(0.1),
                borderRadius: BorderRadius.circular(8),
              ),
              child: Text(
                status == 'scheduled' ? 'Upcoming' : status,
                style: TextStyle(
                  fontSize: 10,
                  fontWeight: FontWeight.w600,
                  color: status == 'scheduled' ? const Color(0xFF2E7D32) : Colors.orange,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}