// lib/screens/mentee/job_list_screen.dart
import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import '../services/api_service.dart';

class JobListScreen extends StatefulWidget {
  const JobListScreen({super.key});

  @override
  State<JobListScreen> createState() => _JobListScreenState();
}

class _JobListScreenState extends State<JobListScreen> {
  List<dynamic> _jobs = [];
  List<dynamic> _recommendedJobs = [];
  bool _isLoading = true;
  bool _showRecommended = true;
  final TextEditingController _searchController = TextEditingController();
  final Set<int> _favoriteJobIds = {};

  @override
  void initState() {
    super.initState();
    _fetchJobs();
  }

  Future<void> _fetchJobs() async {
    setState(() => _isLoading = true);

    // Fetch both recommended and all jobs in parallel
    final results = await Future.wait([
      ApiService.getJobRecommendations(),
      ApiService.getJobs(),
    ]);

    if (mounted) {
      setState(() {
        _recommendedJobs = results[0];
        _jobs = results[1];
        _isLoading = false;
      });
    }
  }

  Future<void> _searchJobs(String query) async {
    setState(() => _isLoading = true);
    final results = await ApiService.getJobs(search: query);
    if (mounted) {
      setState(() {
        _jobs = results;
        _showRecommended = false;
        _isLoading = false;
      });
    }
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF4F3FB),
      body: Column(
        children: [
          // Purple Header
          Container(
            padding:
                const EdgeInsets.only(top: 60, left: 20, right: 20, bottom: 20),
            decoration: const BoxDecoration(
              gradient: LinearGradient(
                  colors: [Color(0xFF6B4EE6), Color(0xFF9B7EFA)],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight),
              borderRadius: BorderRadius.only(
                  bottomLeft: Radius.circular(30),
                  bottomRight: Radius.circular(30)),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text("Job Opportunities",
                    style: TextStyle(
                        color: Colors.white,
                        fontSize: 26,
                        fontWeight: FontWeight.bold)),
                const SizedBox(height: 4),
                const Text("Matched to your skills & interests",
                    style: TextStyle(color: Colors.white70, fontSize: 14)),
                const SizedBox(height: 16),
                // Search Bar
                Container(
                  decoration: BoxDecoration(
                    color: Colors.white.withOpacity(0.2),
                    borderRadius: BorderRadius.circular(15),
                  ),
                  child: TextField(
                    controller: _searchController,
                    style: const TextStyle(color: Colors.white),
                    decoration: InputDecoration(
                      hintText: "Search jobs, companies...",
                      hintStyle: TextStyle(color: Colors.white.withOpacity(0.6)),
                      prefixIcon: const Icon(Icons.search, color: Colors.white70),
                      suffixIcon: _searchController.text.isNotEmpty
                          ? IconButton(
                              icon: const Icon(Icons.clear, color: Colors.white70),
                              onPressed: () {
                                _searchController.clear();
                                _fetchJobs();
                                setState(() => _showRecommended = true);
                              },
                            )
                          : null,
                      border: InputBorder.none,
                      contentPadding: const EdgeInsets.symmetric(
                          vertical: 15, horizontal: 15),
                    ),
                    onSubmitted: (value) {
                      if (value.trim().isNotEmpty) {
                        _searchJobs(value.trim());
                      }
                    },
                  ),
                ),
              ],
            ),
          ),

          // Tab Chips
          Padding(
            padding: const EdgeInsets.all(16),
            child: Row(
              children: [
                _buildFilterChip("For You", _showRecommended, () {
                  setState(() => _showRecommended = true);
                }),
                const SizedBox(width: 10),
                _buildFilterChip("All Jobs", !_showRecommended, () {
                  setState(() => _showRecommended = false);
                }),
              ],
            ),
          ),

          // Job List
          Expanded(
            child: _isLoading
                ? const Center(
                    child: CircularProgressIndicator(color: Color(0xFF6B4EE6)))
                : RefreshIndicator(
                    onRefresh: _fetchJobs,
                    color: const Color(0xFF6B4EE6),
                    child: _buildJobList(),
                  ),
          ),
        ],
      ),
    );
  }

  Widget _buildFilterChip(String label, bool isActive, VoidCallback onTap) {
    return GestureDetector(
      onTap: onTap,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 200),
        padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
        decoration: BoxDecoration(
          gradient: isActive
              ? const LinearGradient(
                  colors: [Color(0xFF6B4EE6), Color(0xFF9B7EFA)])
              : null,
          color: isActive ? null : Colors.white,
          borderRadius: BorderRadius.circular(25),
          border: isActive ? null : Border.all(color: Colors.grey.shade300),
          boxShadow: isActive
              ? [
                  BoxShadow(
                      color: const Color(0xFF6B4EE6).withOpacity(0.2),
                      blurRadius: 8,
                      offset: const Offset(0, 2))
                ]
              : [],
        ),
        child: Text(
          label,
          style: TextStyle(
            color: isActive ? Colors.white : Colors.grey.shade600,
            fontWeight: FontWeight.w600,
            fontSize: 13,
          ),
        ),
      ),
    );
  }

  Widget _buildJobList() {
    final jobs = _showRecommended ? _recommendedJobs : _jobs;

    if (jobs.isEmpty) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.work_off_outlined, size: 64, color: Colors.grey.shade300),
            const SizedBox(height: 12),
            Text(
              _showRecommended
                  ? "No recommended jobs yet.\nUpdate your skills to get matches!"
                  : "No jobs found.",
              textAlign: TextAlign.center,
              style: TextStyle(color: Colors.grey.shade500, fontSize: 14),
            ),
          ],
        ),
      );
    }

    return ListView.builder(
      padding: const EdgeInsets.symmetric(horizontal: 16),
      itemCount: jobs.length,
      itemBuilder: (context, index) {
        final job = jobs[index];
        return _buildJobCard(job, index);
      },
    );
  }

  Widget _buildJobCard(dynamic rawJob, int index) {
    // Handle nested format from recommendations: {job: {...}, match_score: X}
    final dynamic job = rawJob is Map && rawJob.containsKey('job') ? rawJob['job'] : rawJob;
    final double? matchScore = rawJob is Map && rawJob.containsKey('match_score')
        ? (rawJob['match_score'] as num).toDouble()
        : (job is Map && job.containsKey('match_score') ? (job['match_score'] as num).toDouble() : null);

    final String title = job['title'] ?? 'Job Title';
    final String company = job['company'] ?? 'Company';
    final String salary = job['salary'] ?? 'Salary TBA';
    final String location = job['location'] ?? 'Location TBA';
    final String jobType = job['job_type'] ?? '';

    return GestureDetector(
      onTap: () => _showJobDetail(context, job),
      child: Container(
        margin: const EdgeInsets.only(bottom: 12),
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(18),
          boxShadow: [
            BoxShadow(
                color: Colors.purple.withOpacity(0.04), blurRadius: 12)
          ],
        ),
        child: Row(
          children: [
            // Company icon
            Container(
              width: 48,
              height: 48,
              decoration: BoxDecoration(
                color: const Color(0xFFF4F3FB),
                borderRadius: BorderRadius.circular(12),
              ),
              child: const Icon(Icons.business_rounded,
                  color: Color(0xFF6B4EE6), size: 24),
            ),
            const SizedBox(width: 14),
            // Job info
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(title,
                      style: const TextStyle(
                          fontWeight: FontWeight.bold, fontSize: 15)),
                  const SizedBox(height: 3),
                  Text(company,
                      style: TextStyle(
                          color: Colors.grey.shade600, fontSize: 13)),
                  const SizedBox(height: 6),
                  Row(
                    children: [
                      Text(salary,
                          style: const TextStyle(
                              fontSize: 12,
                              color: Colors.green,
                              fontWeight: FontWeight.bold)),
                      if (location.isNotEmpty) ...[
                        const SizedBox(width: 10),
                        Icon(Icons.location_on,
                            size: 12, color: Colors.grey.shade400),
                        const SizedBox(width: 2),
                        Flexible(
                          child: Text(location,
                              style: TextStyle(
                                  fontSize: 11,
                                  color: Colors.grey.shade500),
                              overflow: TextOverflow.ellipsis),
                        ),
                      ],
                    ],
                  ),
                  if (jobType.isNotEmpty) ...[
                    const SizedBox(height: 6),
                    Container(
                      padding: const EdgeInsets.symmetric(
                          horizontal: 8, vertical: 3),
                      decoration: BoxDecoration(
                        color: const Color(0xFFEDE7F6),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: Text(jobType,
                          style: const TextStyle(
                              fontSize: 10,
                              color: Color(0xFF6B4EE6),
                              fontWeight: FontWeight.w600)),
                    ),
                  ],
                ],
              ),
            ),
            Column(
              crossAxisAlignment: CrossAxisAlignment.end,
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                IconButton(
                  icon: Icon(
                    _favoriteJobIds.contains(job['id']) ? Icons.favorite : Icons.favorite_border,
                    color: _favoriteJobIds.contains(job['id']) ? Colors.red : Colors.grey,
                    size: 22,
                  ),
                  onPressed: () async {
                    final jobId = job['id'];
                    if (jobId == null) return;
                    setState(() {
                      if (_favoriteJobIds.contains(jobId)) {
                        _favoriteJobIds.remove(jobId);
                      } else {
                        _favoriteJobIds.add(jobId);
                      }
                    });
                    final success = await ApiService.toggleFavoriteJob(jobId);
                    if (!success && mounted) {
                      // Revert on failure
                      setState(() {
                        if (_favoriteJobIds.contains(jobId)) {
                          _favoriteJobIds.remove(jobId);
                        } else {
                          _favoriteJobIds.add(jobId);
                        }
                      });
                      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Failed to update favorite')));
                    }
                  },
                ),
                if (matchScore != null)
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                    decoration: BoxDecoration(
                      gradient: const LinearGradient(
                          colors: [Color(0xFF43E97B), Color(0xFF38F9D7)]),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Text(
                      "${matchScore.toStringAsFixed(0)}%",
                      style: const TextStyle(
                          color: Colors.white,
                          fontSize: 12,
                          fontWeight: FontWeight.bold),
                    ),
                  )
                else
                  const Padding(
                    padding: EdgeInsets.only(right: 8.0),
                    child: Icon(Icons.arrow_forward_ios, size: 14, color: Colors.grey),
                  ),
              ],
            ),
          ],
        ),
      ),
    ).animate()
        .fadeIn(delay: Duration(milliseconds: index * 80))
        .slideX(begin: 0.08, curve: Curves.easeOutCubic);
  }

  void _showJobDetail(BuildContext context, dynamic job) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (context) => Container(
        height: MediaQuery.of(context).size.height * 0.7,
        decoration: const BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.vertical(top: Radius.circular(25)),
        ),
        padding: const EdgeInsets.all(24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Handle bar
            Center(
              child: Container(
                width: 40,
                height: 4,
                decoration: BoxDecoration(
                  color: Colors.grey.shade300,
                  borderRadius: BorderRadius.circular(2),
                ),
              ),
            ),
            const SizedBox(height: 20),
            // Title
            Text(job['title'] ?? 'Job Title',
                style: const TextStyle(
                    fontSize: 22, fontWeight: FontWeight.bold)),
            const SizedBox(height: 8),
            Text(job['company'] ?? 'Company',
                style: TextStyle(
                    fontSize: 16, color: Colors.grey.shade600)),
            const SizedBox(height: 16),
            // Tags row
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: [
                if (job['salary'] != null)
                  _detailChip(Icons.attach_money, job['salary'], Colors.green),
                if (job['location'] != null)
                  _detailChip(
                      Icons.location_on, job['location'], Colors.blue),
                if (job['job_type'] != null)
                  _detailChip(Icons.work, job['job_type'],
                      const Color(0xFF6B4EE6)),
                if (job['experience_level'] != null)
                  _detailChip(Icons.trending_up, job['experience_level'],
                      Colors.orange),
              ],
            ),
            const SizedBox(height: 20),
            const Text("Description",
                style: TextStyle(
                    fontWeight: FontWeight.bold, fontSize: 16)),
            const SizedBox(height: 8),
            Expanded(
              child: SingleChildScrollView(
                child: Text(
                  job['description'] ?? 'No description available.',
                  style: TextStyle(
                      color: Colors.grey.shade700, height: 1.6, fontSize: 14),
                ),
              ),
            ),
            const SizedBox(height: 16),
            // Apply button
            InkWell(
              onTap: () {
                Navigator.pop(context);
                ScaffoldMessenger.of(context).showSnackBar(
                  const SnackBar(
                    content: Text("Application submitted!"),
                    backgroundColor: Color(0xFF6B4EE6),
                    behavior: SnackBarBehavior.floating,
                  ),
                );
              },
              borderRadius: BorderRadius.circular(15),
              child: Container(
                width: double.infinity,
                padding: const EdgeInsets.symmetric(vertical: 16),
                decoration: BoxDecoration(
                  gradient: const LinearGradient(
                      colors: [Color(0xFF6B4EE6), Color(0xFF9B7EFA)]),
                  borderRadius: BorderRadius.circular(15),
                  boxShadow: [
                    BoxShadow(
                        color: const Color(0xFF6B4EE6).withOpacity(0.3),
                        blurRadius: 10,
                        offset: const Offset(0, 5))
                  ],
                ),
                child: const Center(
                  child: Text("Apply Now",
                      style: TextStyle(
                          color: Colors.white,
                          fontSize: 16,
                          fontWeight: FontWeight.bold)),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _detailChip(IconData icon, String text, Color color) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(
        color: color.withOpacity(0.1),
        borderRadius: BorderRadius.circular(10),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 14, color: color),
          const SizedBox(width: 4),
          Text(text,
              style: TextStyle(
                  fontSize: 12, color: color, fontWeight: FontWeight.w600)),
        ],
      ),
    );
  }
}
