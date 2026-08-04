import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:flutter_animate/flutter_animate.dart';
import '../services/api_service.dart';

class MenteeResourcesScreen extends StatefulWidget {
  const MenteeResourcesScreen({super.key});

  @override
  State<MenteeResourcesScreen> createState() => _MenteeResourcesScreenState();
}

class _MenteeResourcesScreenState extends State<MenteeResourcesScreen> with SingleTickerProviderStateMixin {
  List<dynamic> _resources = [];
  List<dynamic> _courses = [];
  bool _isLoading = true;
  late TabController _tabController;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
    _fetchData();
  }

  Future<void> _fetchData() async {
    final resourcesFuture = ApiService.getMentorResources();
    final coursesFuture = ApiService.getAllCourses();

    final results = await Future.wait([resourcesFuture, coursesFuture]);

    if (mounted) {
      setState(() {
        _resources = results[0];
        _courses = results[1];
        _isLoading = false;
      });
    }
  }

  Future<void> _downloadResource(String url) async {
    try {
      final uri = Uri.parse(url);
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Could not open file URL')),
        );
      }
    }
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF4F3FB),
      body: SafeArea(
        child: Column(
          children: [
            // Header Section
            Container(
              padding: const EdgeInsets.only(top: 20, left: 24, right: 24, bottom: 10),
              child: Row(
                children: [
                  const Text(
                    "Learning",
                    style: TextStyle(
                      fontSize: 24,
                      fontWeight: FontWeight.bold,
                      color: Color(0xFF2D2D3A),
                    ),
                  ),
                ],
              ),
            ).animate().fadeIn(duration: 400.ms).slideY(begin: -0.1),
            
            // TabBar Segmented Control
            Container(
              margin: const EdgeInsets.symmetric(horizontal: 24, vertical: 10),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(15),
                boxShadow: [
                  BoxShadow(
                    color: Colors.purple.withOpacity(0.05),
                    blurRadius: 10,
                    offset: const Offset(0, 5),
                  )
                ],
              ),
              child: TabBar(
                controller: _tabController,
                indicator: BoxDecoration(
                  borderRadius: BorderRadius.circular(15),
                  color: const Color(0xFF6B4EE6),
                  boxShadow: [
                    BoxShadow(
                      color: const Color(0xFF6B4EE6).withOpacity(0.3),
                      blurRadius: 8,
                      offset: const Offset(0, 4),
                    ),
                  ],
                ),
                labelColor: Colors.white,
                unselectedLabelColor: Colors.grey.shade500,
                labelStyle: const TextStyle(fontWeight: FontWeight.w600, fontSize: 14),
                indicatorSize: TabBarIndicatorSize.tab,
                dividerColor: Colors.transparent,
                tabs: const [
                  Tab(text: "Courses"),
                  Tab(text: "Resources"),
                ],
              ),
            ),
            
            // Content Section
            Expanded(
              child: _isLoading
                  ? const Center(child: CircularProgressIndicator(color: Color(0xFF6B4EE6)))
                  : TabBarView(
                      controller: _tabController,
                      children: [
                        _buildCoursesTab(),
                        _buildResourcesTab(),
                      ],
                    ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildCoursesTab() {
    if (_courses.isEmpty) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.video_library_outlined, size: 48, color: Colors.grey.shade300),
            const SizedBox(height: 10),
            Text("No courses available.", style: TextStyle(color: Colors.grey.shade500)),
          ],
        ),
      );
    }
    return ListView.builder(
      padding: const EdgeInsets.only(left: 20, right: 20, top: 10, bottom: 90), // Added bottom padding for nav bar
      itemCount: _courses.length,
      itemBuilder: (context, index) {
        final course = _courses[index];
        return _courseCard(course).animate().fadeIn(delay: Duration(milliseconds: index * 60)).slideY(begin: 0.1);
      },
    );
  }

  Widget _buildResourcesTab() {
    if (_resources.isEmpty) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.folder_open, size: 48, color: Colors.grey.shade300),
            const SizedBox(height: 10),
            Text("No resources found.", style: TextStyle(color: Colors.grey.shade500)),
          ],
        ),
      );
    }
    return ListView.builder(
      padding: const EdgeInsets.only(left: 20, right: 20, top: 10, bottom: 90), // Added bottom padding for nav bar
      itemCount: _resources.length,
      itemBuilder: (context, index) {
        final res = _resources[index];
        return _resourceCard(res).animate().fadeIn(delay: Duration(milliseconds: index * 60)).slideY(begin: 0.1);
      },
    );
  }

  Widget _courseCard(dynamic course) {
    final mentorName = course['mentor']?['name'] ?? 'Instructor';
    final dynamic priceRaw = course['price'];
    final double price = priceRaw != null ? double.tryParse(priceRaw.toString()) ?? 0.0 : 0.0;
    
    return Container(
      margin: const EdgeInsets.only(bottom: 15),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: Colors.purple.withOpacity(0.04),
            blurRadius: 15,
            offset: const Offset(0, 5),
          )
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                width: 60,
                height: 60,
                decoration: BoxDecoration(
                  gradient: const LinearGradient(colors: [Color(0xFF6B4EE6), Color(0xFF9B7EFA)]),
                  borderRadius: BorderRadius.circular(15),
                ),
                child: const Icon(Icons.play_lesson_rounded, color: Colors.white, size: 30),
              ),
              const SizedBox(width: 15),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      course['title'] ?? 'Course Title',
                      style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: Color(0xFF2D2D3A)),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      "By $mentorName",
                      style: TextStyle(color: Colors.grey.shade500, fontSize: 13),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 15),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                price > 0 ? "RM $price" : "Free",
                style: const TextStyle(
                  color: Color(0xFF6B4EE6),
                  fontWeight: FontWeight.w700,
                  fontSize: 15,
                ),
              ),
              InkWell(
                onTap: () {
                  // TODO: View course details
                },
                borderRadius: BorderRadius.circular(10),
                child: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                  decoration: BoxDecoration(
                    color: const Color(0xFFF4F3FB),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: const Text(
                    "View details",
                    style: TextStyle(color: Color(0xFF6B4EE6), fontWeight: FontWeight.w600, fontSize: 12),
                  ),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _resourceCard(dynamic res) {
    return Container(
      margin: const EdgeInsets.only(bottom: 15),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: Colors.purple.withOpacity(0.04),
            blurRadius: 15,
            offset: const Offset(0, 5),
          )
        ],
      ),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: const Color(0xFFF4F3FB),
              borderRadius: BorderRadius.circular(12),
            ),
            child: const Icon(Icons.picture_as_pdf, color: Color(0xFF6B4EE6)),
          ),
          const SizedBox(width: 15),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  res['title'] ?? 'Resource',
                  style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15, color: Color(0xFF2D2D3A)),
                ),
                const SizedBox(height: 4),
                Text(
                  "Shared by: ${res['mentor_name'] ?? 'Mentor'}",
                  style: TextStyle(color: Colors.grey.shade500, fontSize: 12),
                ),
              ],
            ),
          ),
          IconButton(
            icon: const Icon(Icons.download_rounded, color: Color(0xFF6B4EE6)),
            onPressed: () {
              if (res['file_url'] != null) {
                _downloadResource(res['file_url']);
              }
            },
          ),
        ],
      ),
    );
  }
}
