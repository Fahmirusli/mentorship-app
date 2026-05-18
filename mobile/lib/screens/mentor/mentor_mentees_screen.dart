// lib/screens/mentor/mentor_mentees_screen.dart
import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import '../services/api_service.dart';
import '../shared/chat_room_screen.dart';

class MentorMenteesScreen extends StatefulWidget {
  const MentorMenteesScreen({super.key});

  @override
  State<MentorMenteesScreen> createState() => _MentorMenteesScreenState();
}

class _MentorMenteesScreenState extends State<MentorMenteesScreen> {
  List<dynamic> _mentorships = [];
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _fetchMentorships();
  }

  Future<void> _fetchMentorships() async {
    final data = await ApiService.getMentorships();
    if (mounted) {
      setState(() {
        _mentorships = data;
        _isLoading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF0F7F0),
      appBar: AppBar(
        title: const Text('My Mentees', style: TextStyle(color: Color(0xFF2D2D3A), fontWeight: FontWeight.bold)),
        backgroundColor: Colors.transparent,
        elevation: 0,
        iconTheme: const IconThemeData(color: Color(0xFF2D2D3A)),
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: Color(0xFF2E7D32)))
          : _mentorships.isEmpty
              ? _buildEmptyState()
              : RefreshIndicator(
                  onRefresh: _fetchMentorships,
                  color: const Color(0xFF2E7D32),
                  child: ListView.builder(
                    padding: const EdgeInsets.all(20),
                    itemCount: _mentorships.length,
                    itemBuilder: (context, index) {
                      final m = _mentorships[index];
                      final mentee = m['mentee'] ?? {};
                      final status = m['status'] ?? 'active';
                      return _menteeCard(mentee, status, m['id'] ?? 0).animate(delay: (index * 100).ms).fadeIn().slideY(begin: 0.1);
                    },
                  ),
                ),
    );
  }

  Widget _buildEmptyState() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(Icons.people_outline, size: 80, color: Colors.grey.shade300),
          const SizedBox(height: 16),
          const Text('No mentees yet', style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold, color: Colors.grey)),
          const SizedBox(height: 8),
          Text('Your mentees will appear here once they book a session.', style: TextStyle(color: Colors.grey.shade500), textAlign: TextAlign.center),
        ],
      ),
    );
  }

  Widget _menteeCard(Map<String, dynamic> mentee, String status, int mentorshipId) {
    final name = mentee['name'] ?? 'Mentee';
    final email = mentee['email'] ?? '';
    final menteeId = mentee['id'] ?? 0;

    Color statusColor;
    switch (status) {
      case 'active':
        statusColor = const Color(0xFF2E7D32);
        break;
      case 'completed':
        statusColor = Colors.blue;
        break;
      case 'pending':
        statusColor = Colors.orange;
        break;
      default:
        statusColor = Colors.grey;
    }

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(18),
        boxShadow: [BoxShadow(color: Colors.green.withOpacity(0.05), blurRadius: 10)],
      ),
      child: Column(
        children: [
          Row(
            children: [
              CircleAvatar(
                radius: 24,
                backgroundColor: const Color(0xFFE8F5E9),
                child: Text(name.isNotEmpty ? name[0].toUpperCase() : 'M', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 18, color: Color(0xFF2E7D32))),
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(name, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                    Text(email, style: TextStyle(color: Colors.grey.shade500, fontSize: 13)),
                  ],
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                decoration: BoxDecoration(
                  color: statusColor.withOpacity(0.1),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Text(status.toUpperCase(), style: TextStyle(fontSize: 10, fontWeight: FontWeight.w700, color: statusColor)),
              ),
            ],
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(
                child: OutlinedButton.icon(
                  onPressed: () {
                    if (menteeId > 0) {
                      Navigator.push(context, MaterialPageRoute(
                        builder: (_) => ChatRoomScreen(otherUserId: menteeId, mentorName: name),
                      ));
                    }
                  },
                  icon: const Icon(Icons.message_rounded, size: 16),
                  label: const Text('Message'),
                  style: OutlinedButton.styleFrom(
                    foregroundColor: const Color(0xFF2E7D32),
                    side: const BorderSide(color: Color(0xFF2E7D32)),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                  ),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}
