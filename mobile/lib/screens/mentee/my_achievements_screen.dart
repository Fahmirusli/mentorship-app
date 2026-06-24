import 'package:flutter/material.dart';
import '../services/api_service.dart';

class MyAchievementsScreen extends StatefulWidget {
  const MyAchievementsScreen({super.key});

  @override
  State<MyAchievementsScreen> createState() => _MyAchievementsScreenState();
}

class _MyAchievementsScreenState extends State<MyAchievementsScreen> {
  List<dynamic> _badges = [];
  List<dynamic> _learningProgress = [];
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _fetchStats();
  }

  Future<void> _fetchStats() async {
    final statsData = await ApiService.getMenteeStats();
    if (mounted) {
      setState(() {
        if (statsData != null) {
          _badges = statsData['badges'] ?? [];
          _learningProgress = statsData['learning_progress'] ?? [];
        }
        _isLoading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF4F3FB),
      appBar: AppBar(
        title: const Text("My Achievements", style: TextStyle(color: Colors.black87, fontWeight: FontWeight.bold)),
        backgroundColor: Colors.transparent,
        elevation: 0,
        centerTitle: true,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back_ios, color: Colors.black87),
          onPressed: () => Navigator.pop(context),
        ),
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: Color(0xFF6B4EE6)))
          : SingleChildScrollView(
              padding: const EdgeInsets.all(20),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text("Badges Earned", style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                  const SizedBox(height: 15),
                  if (_badges.isEmpty)
                    Container(
                      padding: const EdgeInsets.all(20),
                      width: double.infinity,
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(15),
                        border: Border.all(color: Colors.grey.shade200),
                      ),
                      child: const Column(
                        children: [
                          Icon(Icons.military_tech_outlined, size: 48, color: Colors.grey),
                          SizedBox(height: 10),
                          Text("No badges yet. Keep learning!", style: TextStyle(color: Colors.grey)),
                        ],
                      ),
                    )
                  else
                    Wrap(
                      spacing: 15,
                      runSpacing: 15,
                      children: _badges.map((badge) {
                        return Column(
                          children: [
                            Container(
                              padding: const EdgeInsets.all(15),
                              decoration: BoxDecoration(
                                color: Colors.white,
                                shape: BoxShape.circle,
                                boxShadow: [BoxShadow(color: Colors.purple.withOpacity(0.1), blurRadius: 10)],
                              ),
                              child: Icon(Icons.emoji_events, size: 40, color: Colors.amber.shade600),
                            ),
                            const SizedBox(height: 5),
                            Text(badge['name'] ?? 'Badge', style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 12)),
                          ],
                        );
                      }).toList(),
                    ),
                  
                  const SizedBox(height: 30),
                  
                  const Text("Learning Progress", style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                  const SizedBox(height: 15),
                  if (_learningProgress.isEmpty)
                    Container(
                      padding: const EdgeInsets.all(20),
                      width: double.infinity,
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(15),
                        border: Border.all(color: Colors.grey.shade200),
                      ),
                      child: const Text("No progress recorded yet.", style: TextStyle(color: Colors.grey)),
                    )
                  else
                    ..._learningProgress.map((item) {
                      final title = item['topic'] ?? 'Topic';
                      final progress = (item['progress'] ?? 0.0) / 100.0;
                      return Container(
                        margin: const EdgeInsets.only(bottom: 15),
                        padding: const EdgeInsets.all(15),
                        decoration: BoxDecoration(
                          color: Colors.white,
                          borderRadius: BorderRadius.circular(15),
                          boxShadow: [BoxShadow(color: Colors.purple.withOpacity(0.05), blurRadius: 10)],
                        ),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                Text(title, style: const TextStyle(fontWeight: FontWeight.bold)),
                                Text("${(progress * 100).toInt()}%", style: const TextStyle(color: Color(0xFF6B4EE6), fontWeight: FontWeight.bold)),
                              ],
                            ),
                            const SizedBox(height: 8),
                            LinearProgressIndicator(
                              value: progress,
                              backgroundColor: Colors.grey.shade200,
                              valueColor: const AlwaysStoppedAnimation<Color>(Color(0xFF6B4EE6)),
                              minHeight: 8,
                              borderRadius: BorderRadius.circular(10),
                            ),
                          ],
                        ),
                      );
                    }).toList(),
                ],
              ),
            ),
    );
  }
}
