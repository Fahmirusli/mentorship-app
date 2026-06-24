// lib/screens/mentee/message_list_screen.dart
import 'package:flutter/material.dart';
import '../shared/chat_room_screen.dart';
import '../services/api_service.dart';
import 'package:flutter_animate/flutter_animate.dart';

class MessageListScreen extends StatefulWidget {
  const MessageListScreen({super.key});

  @override
  State<MessageListScreen> createState() => _MessageListScreenState();
}

class _MessageListScreenState extends State<MessageListScreen> {
  List<dynamic> _conversations = [];
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _loadConversations();
  }

  Future<void> _loadConversations() async {
    final convs = await ApiService.getConversations();
    if (mounted) {
      setState(() {
        _conversations = convs;
        _isLoading = false;
      });
    }
  }

  String _formatTime(String? isoDate) {
    if (isoDate == null) return '';
    try {
      final date = DateTime.parse(isoDate).toLocal();
      final now = DateTime.now();
      final diff = now.difference(date);
      if (diff.inMinutes < 60) return '${diff.inMinutes}m ago';
      if (diff.inHours < 24) {
        final hour = date.hour.toString().padLeft(2, '0');
        final min = date.minute.toString().padLeft(2, '0');
        return '$hour:$min';
      }
      if (diff.inDays < 7) return '${diff.inDays}d ago';
      return '${date.day}/${date.month}';
    } catch (_) {
      return '';
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF4F3FB),
      appBar: AppBar(
        title: const Text("My Messages",
            style: TextStyle(color: Colors.black87, fontWeight: FontWeight.bold)),
        backgroundColor: Colors.transparent,
        elevation: 0,
        centerTitle: true,
      ),
      body: Column(
        children: [
          // Search bar
          Padding(
            padding: const EdgeInsets.all(20),
            child: Container(
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(15),
                boxShadow: [BoxShadow(color: Colors.purple.withOpacity(0.03), blurRadius: 15)],
              ),
              child: TextField(
                decoration: InputDecoration(
                  hintText: "Search mentors or messages...",
                  hintStyle: TextStyle(color: Colors.grey.shade400),
                  prefixIcon: const Icon(Icons.search, color: Color(0xFF6B4EE6)),
                  border: InputBorder.none,
                  contentPadding: const EdgeInsets.symmetric(vertical: 18, horizontal: 15),
                ),
              ),
            ),
          ),

          // Conversation list
          Expanded(
            child: _isLoading
                ? const Center(child: CircularProgressIndicator(color: Color(0xFF6B4EE6)))
                : _conversations.isEmpty
                    ? Center(
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Icon(Icons.chat_bubble_outline, size: 56, color: Colors.grey.shade300),
                            const SizedBox(height: 12),
                            Text("No conversations yet",
                                style: TextStyle(color: Colors.grey.shade500, fontSize: 15)),
                            const SizedBox(height: 4),
                            Text("Start chatting with a mentor or mentee!",
                                style: TextStyle(color: Colors.grey.shade400, fontSize: 13)),
                          ],
                        ),
                      )
                    : RefreshIndicator(
                        onRefresh: _loadConversations,
                        color: const Color(0xFF6B4EE6),
                        child: ListView.builder(
                          padding: const EdgeInsets.symmetric(horizontal: 20),
                          itemCount: _conversations.length,
                          itemBuilder: (context, index) {
                            final conv = _conversations[index];
                            final otherUser = conv['other_user'];
                            final lastMsg = conv['last_message'];
                            final int unread = conv['unread_count'] ?? 0;
                            final String name = otherUser?['name'] ?? 'User';
                            final String role = otherUser?['role'] ?? '';
                            final int otherUserId = otherUser?['id'] ?? 0;
                            final String? profileImage = otherUser?['profile_image'];

                            return InkWell(
                              onTap: () async {
                                await Navigator.push(
                                  context,
                                  MaterialPageRoute(
                                    builder: (context) => ChatRoomScreen(
                                      mentorName: name,
                                      otherUserId: otherUserId,
                                    ),
                                  ),
                                );
                                // Refresh conversations when coming back
                                _loadConversations();
                              },
                              borderRadius: BorderRadius.circular(20),
                              splashColor: const Color(0xFF6B4EE6).withOpacity(0.1),
                              child: Container(
                                margin: const EdgeInsets.only(bottom: 12),
                                padding: const EdgeInsets.all(14),
                                decoration: BoxDecoration(
                                  color: Colors.white,
                                  borderRadius: BorderRadius.circular(20),
                                  boxShadow: [BoxShadow(color: Colors.purple.withOpacity(0.05), blurRadius: 10)],
                                ),
                                child: Row(
                                  children: [
                                    // Avatar
                                    CircleAvatar(
                                      radius: 25,
                                      backgroundColor: const Color(0xFF6B4EE6),
                                      backgroundImage: ApiService.getProfileImageProvider(profileImage),
                                      child: profileImage == null || profileImage.isEmpty
                                          ? const Icon(Icons.person, color: Colors.white)
                                          : null,
                                    ),
                                    const SizedBox(width: 14),
                                    Expanded(
                                      child: Column(
                                        crossAxisAlignment: CrossAxisAlignment.start,
                                        children: [
                                          Text(name,
                                              style: const TextStyle(
                                                  fontWeight: FontWeight.bold, fontSize: 15)),
                                          Text(role.isNotEmpty ? role[0].toUpperCase() + role.substring(1) : '',
                                              style: TextStyle(color: Colors.grey.shade600, fontSize: 12)),
                                          if (lastMsg != null) ...[
                                            const SizedBox(height: 4),
                                            Text(lastMsg['body'] ?? '',
                                                style: TextStyle(
                                                    fontSize: 12,
                                                    color: unread > 0 ? const Color(0xFF2D2D3A) : Colors.grey,
                                                    fontWeight: unread > 0 ? FontWeight.w600 : FontWeight.normal),
                                                maxLines: 1,
                                                overflow: TextOverflow.ellipsis),
                                          ],
                                        ],
                                      ),
                                    ),
                                    const SizedBox(width: 8),
                                    Column(
                                      crossAxisAlignment: CrossAxisAlignment.end,
                                      children: [
                                        if (lastMsg != null)
                                          Text(
                                            _formatTime(lastMsg['created_at']),
                                            style: TextStyle(fontSize: 10, color: Colors.grey.shade400),
                                          ),
                                        const SizedBox(height: 5),
                                        if (unread > 0)
                                          Container(
                                            padding: const EdgeInsets.all(6),
                                            decoration: const BoxDecoration(
                                                color: Color(0xFF6B4EE6), shape: BoxShape.circle),
                                            child: Text('$unread',
                                                style: const TextStyle(
                                                    color: Colors.white,
                                                    fontSize: 10,
                                                    fontWeight: FontWeight.bold)),
                                          ),
                                      ],
                                    ),
                                  ],
                                ),
                              ),
                            ).animate()
                                .fadeIn(delay: Duration(milliseconds: index * 80))
                                .slideX(begin: 0.08);
                          },
                        ),
                      ),
          ),
        ],
      ),
    );
  }
}