// lib/screens/mentee/mentee_home.dart
import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import '../services/api_service.dart';
import '../shared/my_schedule.dart';
import '../shared/profile_screen.dart';
import '../shared/notification_screen.dart';
import '../shared/session_detail_screen.dart';
import 'nearby_mentors.dart';
import 'message_list_screen.dart';
import 'job_list_screen.dart';
import 'skill_selection_screen.dart';

class MenteeDashboard extends StatefulWidget {
  final VoidCallback onLogout;
  const MenteeDashboard({super.key, required this.onLogout});

  @override
  State<MenteeDashboard> createState() => _MenteeDashboardState();
}

class _MenteeDashboardState extends State<MenteeDashboard> {
  // Navigation indices:
  // 0 = Schedule (+)
  // 1 = Jobs
  // 2 = Dashboard (center FAB)
  // 3 = Messages
  // 4 = Profile
  int _selectedIndex = 2; // Default to Dashboard

  // --- REAL DATA VARIABLES ---
  String _userName = "Loading...";
  List<dynamic> _skills = [];
  List<dynamic> _schedule = [];
  List<dynamic> _jobs = [];
  bool _isLoadingData = true;
  int _unreadNotifCount = 0;

  late List<Widget> _pages;

  @override
  void initState() {
    super.initState();
    _fetchRealData(); // Call the API when the app opens!

    _pages = [
      const MyScheduleScreen(),          // 0 - Schedule (was the + button)
      const JobListScreen(),             // 1 - Jobs
      _buildPurpleDashboard(),           // 2 - Dashboard (center)
      const MessageListScreen(),         // 3 - Messages
      ProfileScreen(onLogout: widget.onLogout), // 4 - Profile
    ];
  }

  // --- THE DATA FETCHER ---
  Future<void> _fetchRealData() async {
    final dashboardData = await ApiService.getDashboardData();

    // Also fetch notification count
    final notifCount = await ApiService.getUnreadNotificationCount();

    if (dashboardData != null && mounted) {
      setState(() {
        _userName = dashboardData['user']['name'] ?? "Mentee";
        _skills = dashboardData['user']['skills_to_learn'] ?? [];
        _schedule = dashboardData['today_schedule'] ?? [];
        _jobs = dashboardData['job_recommendations'] ?? [];
        _unreadNotifCount = notifCount;
        _isLoadingData = false;

        // Rebuild the dashboard to inject the new data
        _pages[2] = _buildPurpleDashboard();
      });
    } else {
      setState(() {
        _unreadNotifCount = notifCount;
        _isLoadingData = false;
        _pages[2] = _buildPurpleDashboard();
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF4F3FB),
      body: _pages[_selectedIndex],

      // Center FAB = Dashboard
      floatingActionButton: FloatingActionButton(
        onPressed: () => setState(() => _selectedIndex = 2),
        backgroundColor: _selectedIndex == 2
            ? const Color(0xFF6B4EE6)
            : const Color(0xFF9B7EFA),
        elevation: _selectedIndex == 2 ? 8 : 4,
        shape: const CircleBorder(),
        child: Icon(
          Icons.dashboard_rounded,
          color: Colors.white,
          size: _selectedIndex == 2 ? 30 : 26,
        ),
      ),
      floatingActionButtonLocation: FloatingActionButtonLocation.centerDocked,

      bottomNavigationBar: BottomAppBar(
        shape: const CircularNotchedRectangle(),
        notchMargin: 8.0,
        color: Colors.white,
        elevation: 10,
        child: SizedBox(
          height: 64,
          child: Row(
            mainAxisAlignment: MainAxisAlignment.spaceAround,
            children: [
              _navItem(Icons.calendar_month_rounded, "Schedule", 0),
              _navItem(Icons.work_rounded, "Jobs", 1),
              const SizedBox(width: 48), // Space for FAB
              _navItem(Icons.message_rounded, "Messages", 3),
              _navItem(Icons.person_rounded, "Profile", 4),
            ],
          ),
        ),
      ),
    );
  }

  /// Navigation item with icon + label
  Widget _navItem(IconData icon, String label, int index) {
    bool isSelected = _selectedIndex == index;
    return Expanded(
      child: InkWell(
        onTap: () => setState(() => _selectedIndex = index),
        splashColor: const Color(0xFF6B4EE6).withOpacity(0.1),
        highlightColor: Colors.transparent,
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(
              icon,
              color: isSelected ? const Color(0xFF6B4EE6) : Colors.grey.shade400,
              size: 24,
            ),
            const SizedBox(height: 3),
            Text(
              label,
              style: TextStyle(
                fontSize: 10,
                fontWeight: isSelected ? FontWeight.w700 : FontWeight.w500,
                color: isSelected ? const Color(0xFF6B4EE6) : Colors.grey.shade400,
              ),
            ),
          ],
        ),
      ),
    );
  }

  // =======================================================================
  // DYNAMIC DASHBOARD (Now Powered by MySQL)
  // =======================================================================
  Widget _buildPurpleDashboard() {
    if (_isLoadingData) {
      return const Center(child: CircularProgressIndicator(color: Color(0xFF6B4EE6)));
    }

    return Column(
      children: [
        // HEADER
        Container(
          padding: const EdgeInsets.only(top: 60, left: 20, right: 20, bottom: 20),
          decoration: const BoxDecoration(
            gradient: LinearGradient(colors: [Color(0xFF6B4EE6), Color(0xFF9B7EFA)], begin: Alignment.topLeft, end: Alignment.bottomRight),
            borderRadius: BorderRadius.only(bottomLeft: Radius.circular(30), bottomRight: Radius.circular(30)),
          ),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text("Hello, $_userName", style: const TextStyle(color: Colors.white70, fontSize: 16)),
                  const Text("Your Diary", style: TextStyle(color: Colors.white, fontSize: 28, fontWeight: FontWeight.bold)),
                ],
              ),
              GestureDetector(
                onTap: () async {
                  await Navigator.push(context, MaterialPageRoute(builder: (_) => const NotificationScreen()));
                  // Refresh count after returning
                  final count = await ApiService.getUnreadNotificationCount();
                  if (mounted) setState(() { _unreadNotifCount = count; _pages[2] = _buildPurpleDashboard(); });
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

        // SCROLLABLE CONTENT AREA
        Expanded(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(20),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // 1. RECOMMENDED SKILLS (Tappable → Skill Selection → Nearby Mentors)
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    const Text("Recommended Skills", style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                    GestureDetector(
                      onTap: () => _navigateToSkillSelection(),
                      child: const Text("Find Mentor →", style: TextStyle(fontSize: 13, color: Color(0xFF6B4EE6), fontWeight: FontWeight.w600)),
                    ),
                  ],
                ),
                const SizedBox(height: 15),
                _skills.isEmpty
                    ? const Text("No skills listed yet.", style: TextStyle(color: Colors.grey))
                    : Row(
                  children: List.generate(
                    _skills.length > 3 ? 3 : _skills.length, // Show up to 3
                        (index) => Expanded(
                      child: Padding(
                        padding: EdgeInsets.only(right: index < (_skills.length - 1 < 2 ? _skills.length - 1 : 2) ? 10.0 : 0),
                        child: GestureDetector(
                          onTap: () => _navigateToSkillSelection(preselectedSkill: _skills[index].toString()),
                          child: _gradientCard(_skills[index].toString(), _getSkillColors(index)),
                        ),
                      ),
                    ),
                  ),
                ).animate().fadeIn(duration: 500.ms).slideY(begin: 0.2, curve: Curves.easeOutBack),

                const SizedBox(height: 30),

                // 2. TODAY's SCHEDULE
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    const Text("Today's Schedule", style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                    GestureDetector(
                      onTap: () => setState(() => _selectedIndex = 0), // Go to Schedule tab
                      child: const Text("View All →", style: TextStyle(fontSize: 13, color: Color(0xFF6B4EE6), fontWeight: FontWeight.w600)),
                    ),
                  ],
                ),
                const SizedBox(height: 15),
                _schedule.isEmpty
                    ? const Text("No sessions scheduled for today.", style: TextStyle(color: Colors.grey, fontStyle: FontStyle.italic))
                    : Column(
                  children: _schedule.map((apt) => _scheduleCard(apt)).toList(),
                ).animate().fadeIn(delay: 200.ms).slideY(begin: 0.1),

                const SizedBox(height: 30),

                // 3. JOB RECOMMENDATIONS (Tappable → Jobs tab)
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    const Text("Job Recommendations", style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                    GestureDetector(
                      onTap: () => setState(() => _selectedIndex = 1), // Go to Jobs tab
                      child: const Text("View All →", style: TextStyle(fontSize: 13, color: Color(0xFF6B4EE6), fontWeight: FontWeight.w600)),
                    ),
                  ],
                ),
                const SizedBox(height: 15),
                _jobs.isEmpty
                    ? const Text("No job matches found right now.", style: TextStyle(color: Colors.grey, fontStyle: FontStyle.italic))
                    : Column(
                  children: _jobs.map((job) => _jobCard(job)).toList(),
                ).animate().fadeIn(delay: 400.ms).slideY(begin: 0.1),
              ],
            ),
          ),
        ),
      ],
    );
  }

  /// Navigate to Skill Selection screen, optionally with a preselected skill
  void _navigateToSkillSelection({String? preselectedSkill}) {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => SkillSelectionScreen(
          availableSkills: _skills.map((s) => s.toString()).toList(),
        ),
      ),
    );
  }

  // --- DYNAMIC UI CARDS ---

  Widget _gradientCard(String title, List<Color> gradientColors) {
    return Container(
      padding: const EdgeInsets.all(15),
      decoration: BoxDecoration(
        gradient: LinearGradient(colors: gradientColors, begin: Alignment.topLeft, end: Alignment.bottomRight),
        borderRadius: BorderRadius.circular(15),
        boxShadow: [BoxShadow(color: gradientColors[0].withOpacity(0.4), blurRadius: 10, offset: const Offset(0, 5))],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(title, style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 13)),
          const SizedBox(height: 5),
          const Icon(Icons.trending_up, color: Colors.white70, size: 16),
        ],
      ),
    );
  }

  List<Color> _getSkillColors(int index) {
    const colorSets = [
      [Color(0xFFFF9A9E), Color(0xFFFECFEF)], // Pink
      [Color(0xFF667EEA), Color(0xFF764BA2)], // Purple/Blue
      [Color(0xFFFF758C), Color(0xFFFF7EB3)], // Coral
    ];
    return colorSets[index % colorSets.length];
  }

  Widget _scheduleCard(dynamic apt) {
    return GestureDetector(
      onTap: () {
        Navigator.push(
          context,
          MaterialPageRoute(
            builder: (context) => SessionDetailScreen(
              appointment: Map<String, dynamic>.from(apt),
              isMentor: false,
            ),
          ),
        );
      },
      child: Container(
        margin: const EdgeInsets.only(bottom: 15),
        padding: const EdgeInsets.all(15),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(20),
          boxShadow: [BoxShadow(color: Colors.purple.withOpacity(0.05), blurRadius: 10)],
          border: Border.all(color: const Color(0xFF6B4EE6).withOpacity(0.2)),
        ),
        child: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(color: const Color(0xFFF4F3FB), borderRadius: BorderRadius.circular(12)),
              child: const Icon(Icons.access_time_filled, color: Color(0xFF6B4EE6)),
            ),
            const SizedBox(width: 15),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(apt['time'] ?? '00:00', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                  Text("Mentorship with ${apt['mentor_name']}", style: TextStyle(color: Colors.grey.shade600, fontSize: 13)),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _jobCard(dynamic rawJob) {
    // Handle nested format from recommendations: {job: {...}, match_score: X}
    final dynamic job = rawJob is Map && rawJob.containsKey('job') ? rawJob['job'] : rawJob;

    return GestureDetector(
      onTap: () => setState(() => _selectedIndex = 1), // Navigate to Jobs tab
      child: Container(
        margin: const EdgeInsets.only(bottom: 15),
        padding: const EdgeInsets.all(15),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(20),
          boxShadow: [BoxShadow(color: Colors.purple.withOpacity(0.05), blurRadius: 10)],
        ),
        child: Row(
          children: [
            const CircleAvatar(
              backgroundColor: Color(0xFFF4F3FB),
              child: Icon(Icons.work_outline, color: Color(0xFF6B4EE6)),
            ),
            const SizedBox(width: 15),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(job['title'] ?? 'Job Title', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
                  Text(job['company'] ?? 'Company', style: TextStyle(color: Colors.grey.shade600, fontSize: 13)),
                  const SizedBox(height: 5),
                  Text(job['salary'] ?? 'Salary TBA', style: const TextStyle(fontSize: 12, color: Colors.green, fontWeight: FontWeight.bold)),
                ],
              ),
            ),
            const Icon(Icons.arrow_forward_ios, size: 14, color: Colors.grey),
          ],
        ),
      ),
    );
  }
}