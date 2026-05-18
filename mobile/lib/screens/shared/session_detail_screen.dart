// lib/screens/shared/session_detail_screen.dart
import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'chat_room_screen.dart';

class SessionDetailScreen extends StatelessWidget {
  final Map<String, dynamic> appointment;
  final bool isMentor;

  const SessionDetailScreen({
    super.key,
    required this.appointment,
    this.isMentor = false,
  });

  Color get _primaryColor => isMentor ? const Color(0xFF2E7D32) : const Color(0xFF6B4EE6);
  Color get _primaryLight => isMentor ? const Color(0xFFE8F5E9) : const Color(0xFFEDE7F6);
  Color get _bgColor => isMentor ? const Color(0xFFF0F7F0) : const Color(0xFFF4F3FB);
  List<Color> get _gradientColors => isMentor
      ? [const Color(0xFF2E7D32), const Color(0xFF66BB6A)]
      : [const Color(0xFF6B4EE6), const Color(0xFF9B7EFA)];

  String _formatDate(String? dateStr) {
    if (dateStr == null || dateStr.isEmpty) return 'No date';
    try {
      final dt = DateTime.parse(dateStr);
      const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
      const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
      return '${days[dt.weekday - 1]}, ${dt.day} ${months[dt.month - 1]} ${dt.year}';
    } catch (_) {
      return dateStr;
    }
  }

  String _getStatusLabel(String status) {
    switch (status) {
      case 'completed': return 'Completed';
      case 'cancelled': return 'Cancelled';
      case 'rescheduled': return 'Rescheduled';
      case 'pending_payment': return 'Pending Payment';
      default: return 'Upcoming';
    }
  }

  Color _getStatusColor(String status) {
    switch (status) {
      case 'completed': return Colors.green;
      case 'cancelled': return Colors.red;
      case 'rescheduled': return Colors.orange;
      case 'pending_payment': return Colors.amber;
      default: return _primaryColor;
    }
  }

  IconData _getStatusIcon(String status) {
    switch (status) {
      case 'completed': return Icons.check_circle_rounded;
      case 'cancelled': return Icons.cancel_rounded;
      case 'rescheduled': return Icons.update_rounded;
      case 'pending_payment': return Icons.hourglass_top_rounded;
      default: return Icons.event_available_rounded;
    }
  }

  @override
  Widget build(BuildContext context) {
    final String time = appointment['time'] ?? '00:00';
    final String date = appointment['date'] ?? '';
    final String mentorName = appointment['mentor_name'] ?? 'Mentor';
    final String menteeName = appointment['mentee_name'] ?? 'Mentee';
    final String otherName = appointment['other_user_name'] ?? (isMentor ? menteeName : mentorName);
    final String status = appointment['status'] ?? 'scheduled';
    final int duration = appointment['duration_minutes'] ?? 60;
    final String? notes = appointment['notes'];
    final String? meetingLink = appointment['meeting_link'];
    final int? appointmentId = appointment['id'];

    // Determine other user's ID for chat
    // We don't have the ID directly, so we pass the name for now
    // The chat will be routed via the message_list_screen or directly

    final statusColor = _getStatusColor(status);
    final statusLabel = _getStatusLabel(status);
    final statusIcon = _getStatusIcon(status);

    return Scaffold(
      backgroundColor: _bgColor,
      body: CustomScrollView(
        slivers: [
          // HEADER
          SliverAppBar(
            expandedHeight: 200,
            pinned: true,
            backgroundColor: _primaryColor,
            iconTheme: const IconThemeData(color: Colors.white),
            flexibleSpace: FlexibleSpaceBar(
              background: Container(
                decoration: BoxDecoration(
                  gradient: LinearGradient(
                    colors: _gradientColors,
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                  ),
                ),
                child: SafeArea(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      const SizedBox(height: 30),
                      // Status icon
                      Container(
                        padding: const EdgeInsets.all(16),
                        decoration: BoxDecoration(
                          color: Colors.white.withOpacity(0.2),
                          shape: BoxShape.circle,
                        ),
                        child: Icon(statusIcon, color: Colors.white, size: 36),
                      ).animate().scale(duration: 500.ms, curve: Curves.elasticOut),
                      const SizedBox(height: 12),
                      Text(
                        "Session Details",
                        style: TextStyle(
                          color: Colors.white.withOpacity(0.8),
                          fontSize: 14,
                        ),
                      ),
                      const SizedBox(height: 4),
                      // Status badge
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 6),
                        decoration: BoxDecoration(
                          color: Colors.white.withOpacity(0.2),
                          borderRadius: BorderRadius.circular(20),
                        ),
                        child: Text(
                          statusLabel,
                          style: const TextStyle(
                            color: Colors.white,
                            fontWeight: FontWeight.w700,
                            fontSize: 13,
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ),

          // CONTENT
          SliverToBoxAdapter(
            child: Padding(
              padding: const EdgeInsets.all(20),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // ===========================
                  // PARTICIPANTS CARD
                  // ===========================
                  Container(
                    padding: const EdgeInsets.all(20),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(20),
                      boxShadow: [BoxShadow(color: _primaryColor.withOpacity(0.06), blurRadius: 15)],
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            Icon(Icons.people_alt_rounded, color: _primaryColor, size: 20),
                            const SizedBox(width: 8),
                            Text("Participants",
                                style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: _primaryColor)),
                          ],
                        ),
                        const SizedBox(height: 16),
                        // Mentor row
                        _participantRow(
                          label: "Mentor",
                          name: mentorName,
                          icon: Icons.school_rounded,
                          isCurrentRole: isMentor,
                        ),
                        Divider(height: 24, color: Colors.grey.shade100),
                        // Mentee row
                        _participantRow(
                          label: "Mentee",
                          name: menteeName,
                          icon: Icons.person_rounded,
                          isCurrentRole: !isMentor,
                        ),
                      ],
                    ),
                  ).animate().fadeIn(duration: 400.ms).slideY(begin: 0.1),

                  const SizedBox(height: 16),

                  // ===========================
                  // DATE & TIME CARD
                  // ===========================
                  Container(
                    padding: const EdgeInsets.all(20),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(20),
                      boxShadow: [BoxShadow(color: _primaryColor.withOpacity(0.06), blurRadius: 15)],
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            Icon(Icons.calendar_month_rounded, color: _primaryColor, size: 20),
                            const SizedBox(width: 8),
                            Text("Schedule",
                                style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: _primaryColor)),
                          ],
                        ),
                        const SizedBox(height: 16),
                        _infoRow(Icons.calendar_today_rounded, "Date", _formatDate(date)),
                        const SizedBox(height: 12),
                        _infoRow(Icons.access_time_rounded, "Time", time),
                        const SizedBox(height: 12),
                        _infoRow(Icons.timer_outlined, "Duration", "$duration minutes"),
                      ],
                    ),
                  ).animate().fadeIn(delay: 150.ms, duration: 400.ms).slideY(begin: 0.1),

                  const SizedBox(height: 16),

                  // ===========================
                  // NOTES CARD (if notes exist)
                  // ===========================
                  if (notes != null && notes.isNotEmpty)
                    Container(
                      padding: const EdgeInsets.all(20),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(20),
                        boxShadow: [BoxShadow(color: _primaryColor.withOpacity(0.06), blurRadius: 15)],
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              Icon(Icons.note_alt_rounded, color: _primaryColor, size: 20),
                              const SizedBox(width: 8),
                              Text("Notes",
                                  style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: _primaryColor)),
                            ],
                          ),
                          const SizedBox(height: 12),
                          Text(
                            notes.replaceAll('[Seeded] ', ''),
                            style: TextStyle(color: Colors.grey.shade700, fontSize: 14, height: 1.5),
                          ),
                        ],
                      ),
                    ).animate().fadeIn(delay: 300.ms, duration: 400.ms).slideY(begin: 0.1),

                  if (notes != null && notes.isNotEmpty) const SizedBox(height: 16),

                  // ===========================
                  // MEETING LINK CARD (if exists)
                  // ===========================
                  if (meetingLink != null && meetingLink.isNotEmpty)
                    Container(
                      padding: const EdgeInsets.all(20),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(20),
                        boxShadow: [BoxShadow(color: _primaryColor.withOpacity(0.06), blurRadius: 15)],
                      ),
                      child: Row(
                        children: [
                          Container(
                            padding: const EdgeInsets.all(10),
                            decoration: BoxDecoration(
                              color: const Color(0xFFE3F2FD),
                              borderRadius: BorderRadius.circular(12),
                            ),
                            child: const Icon(Icons.video_call_rounded, color: Color(0xFF1976D2), size: 22),
                          ),
                          const SizedBox(width: 14),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                const Text("Meeting Link",
                                    style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                                const SizedBox(height: 2),
                                Text(meetingLink,
                                    style: const TextStyle(color: Color(0xFF1976D2), fontSize: 12),
                                    maxLines: 1, overflow: TextOverflow.ellipsis),
                              ],
                            ),
                          ),
                        ],
                      ),
                    ).animate().fadeIn(delay: 450.ms, duration: 400.ms).slideY(begin: 0.1),

                  const SizedBox(height: 30),

                  // ===========================
                  // CHAT BUTTON
                  // ===========================
                  InkWell(
                    onTap: () {
                      Navigator.push(
                        context,
                        MaterialPageRoute(
                          builder: (context) => ChatRoomScreen(
                            mentorName: otherName,
                            otherUserId: _getOtherUserId(),
                          ),
                        ),
                      );
                    },
                    borderRadius: BorderRadius.circular(18),
                    child: Container(
                      width: double.infinity,
                      padding: const EdgeInsets.symmetric(vertical: 18),
                      decoration: BoxDecoration(
                        gradient: LinearGradient(colors: _gradientColors),
                        borderRadius: BorderRadius.circular(18),
                        boxShadow: [
                          BoxShadow(
                            color: _primaryColor.withOpacity(0.3),
                            blurRadius: 12,
                            offset: const Offset(0, 6),
                          ),
                        ],
                      ),
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          const Icon(Icons.chat_bubble_rounded, color: Colors.white, size: 22),
                          const SizedBox(width: 10),
                          Text(
                            "Chat with $otherName",
                            style: const TextStyle(
                              color: Colors.white,
                              fontSize: 16,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ).animate().fadeIn(delay: 600.ms, duration: 400.ms).slideY(begin: 0.15),

                  const SizedBox(height: 40),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  int _getOtherUserId() {
    // Try to extract the other user's ID from the appointment data
    // The appointment may have mentor_id/mentee_id, or we fall back to a default
    if (isMentor) {
      return appointment['mentee_id'] ?? 0;
    } else {
      return appointment['mentor_id'] ?? 0;
    }
  }

  Widget _participantRow({
    required String label,
    required String name,
    required IconData icon,
    required bool isCurrentRole,
  }) {
    return Row(
      children: [
        Container(
          padding: const EdgeInsets.all(10),
          decoration: BoxDecoration(
            color: _primaryLight,
            borderRadius: BorderRadius.circular(12),
          ),
          child: Icon(icon, color: _primaryColor, size: 22),
        ),
        const SizedBox(width: 14),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(label, style: TextStyle(fontSize: 11, color: Colors.grey.shade500, fontWeight: FontWeight.w500)),
              const SizedBox(height: 2),
              Text(name, style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
            ],
          ),
        ),
        if (isCurrentRole)
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
            decoration: BoxDecoration(
              color: _primaryLight,
              borderRadius: BorderRadius.circular(8),
            ),
            child: Text("You", style: TextStyle(fontSize: 10, fontWeight: FontWeight.w700, color: _primaryColor)),
          ),
      ],
    );
  }

  Widget _infoRow(IconData icon, String label, String value) {
    return Row(
      children: [
        Icon(icon, size: 18, color: Colors.grey.shade400),
        const SizedBox(width: 10),
        Text("$label: ", style: TextStyle(color: Colors.grey.shade500, fontSize: 13)),
        Expanded(
          child: Text(value, style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 14)),
        ),
      ],
    );
  }
}
