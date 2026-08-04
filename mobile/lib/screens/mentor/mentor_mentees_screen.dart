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
        title: const Text('My Mentees', style: TextStyle(color: Color(0xFF2E7D32), fontWeight: FontWeight.w800, fontSize: 22)),
        backgroundColor: Colors.transparent,
        elevation: 0,
        centerTitle: false,
        iconTheme: const IconThemeData(color: Color(0xFF2E7D32)),
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: Color(0xFF2E7D32)))
          : _mentorships.isEmpty
              ? _buildEmptyState()
              : RefreshIndicator(
                  onRefresh: _fetchMentorships,
                  color: const Color(0xFF2E7D32),
                  child: ListView.builder(
                    padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 10).copyWith(bottom: 100), // padding for bottom nav
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
      child: Container(
        margin: const EdgeInsets.all(30),
        padding: const EdgeInsets.all(40),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(30),
          border: Border.all(color: Colors.grey.shade100),
          boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.02), blurRadius: 20)],
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(color: const Color(0xFFE8F5E9), shape: BoxShape.circle),
              child: const Icon(Icons.people_alt_rounded, size: 60, color: Color(0xFF2E7D32)),
            ),
            const SizedBox(height: 24),
            const Text('No mentees yet', style: TextStyle(fontSize: 22, fontWeight: FontWeight.w800, color: Color(0xFF2E7D32))),
            const SizedBox(height: 12),
            const Text('Your active mentees will appear here once they book a mentorship session.', style: TextStyle(color: Colors.grey, fontSize: 15, height: 1.5), textAlign: TextAlign.center),
          ],
        ),
      ),
    );
  }

  Widget _menteeCard(Map<String, dynamic> mentee, String status, int mentorshipId) {
    final name = mentee['name'] ?? 'Mentee';
    final email = mentee['email'] ?? '';
    final menteeId = mentee['id'] ?? 0;

    Color statusColor;
    Color statusBgColor;
    switch (status) {
      case 'active':
        statusColor = const Color(0xFF059669);
        statusBgColor = const Color(0xFFECFDF5);
        break;
      case 'completed':
        statusColor = const Color(0xFF2563EB);
        statusBgColor = const Color(0xFFEFF6FF);
        break;
      case 'pending':
        statusColor = const Color(0xFFD97706);
        statusBgColor = const Color(0xFFFFFBEB);
        break;
      default:
        statusColor = const Color(0xFF64748B);
        statusBgColor = const Color(0xFFF1F5F9);
    }

    return Container(
      margin: const EdgeInsets.only(bottom: 16),
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(24),
        border: Border.all(color: Colors.grey.shade100),
        boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.02), blurRadius: 15, offset: const Offset(0, 5))],
      ),
      child: Column(
        children: [
          Row(
            children: [
              Container(
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  boxShadow: [BoxShadow(color: const Color(0xFF2E7D32).withOpacity(0.1), blurRadius: 10, offset: const Offset(0, 4))],
                ),
                child: CircleAvatar(
                  radius: 28,
                  backgroundColor: const Color(0xFF2E7D32),
                  child: Text(name.isNotEmpty ? name[0].toUpperCase() : 'M', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 22, color: Colors.white)),
                ),
              ),
              const SizedBox(width: 16),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(name, style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 17, color: Color(0xFF2E7D32)), maxLines: 1, overflow: TextOverflow.ellipsis),
                    const SizedBox(height: 4),
                    Text(email, style: const TextStyle(color: Colors.grey, fontSize: 14, fontWeight: FontWeight.w500), maxLines: 1, overflow: TextOverflow.ellipsis),
                  ],
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                decoration: BoxDecoration(
                  color: statusBgColor,
                  borderRadius: BorderRadius.circular(10),
                ),
                child: Text(status.toUpperCase(), style: TextStyle(fontSize: 10, fontWeight: FontWeight.w800, color: statusColor, letterSpacing: 0.5)),
              ),
            ],
          ),
          const SizedBox(height: 20),
          SizedBox(
            width: double.infinity,
            child: ElevatedButton.icon(
              onPressed: () {
                if (menteeId > 0) {
                  Navigator.push(context, MaterialPageRoute(
                    builder: (_) => ChatRoomScreen(otherUserId: menteeId, mentorName: name),
                  ));
                }
              },
              icon: const Icon(Icons.message_rounded, size: 18),
              label: const Text('Send Message', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15)),
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFFE8F5E9),
                foregroundColor: const Color(0xFF2E7D32),
                elevation: 0,
                padding: const EdgeInsets.symmetric(vertical: 14),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
              ),
            ),
          ),
        ],
      ),
    );
  }
}
