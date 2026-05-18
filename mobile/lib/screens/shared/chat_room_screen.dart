// lib/screens/shared/chat_room_screen.dart
import 'dart:async';
import 'package:flutter/material.dart';
import '../services/api_service.dart';

class ChatRoomScreen extends StatefulWidget {
  final String mentorName;
  final int otherUserId;

  const ChatRoomScreen({
    super.key,
    required this.mentorName,
    required this.otherUserId,
  });

  @override
  State<ChatRoomScreen> createState() => _ChatRoomScreenState();
}

class _ChatRoomScreenState extends State<ChatRoomScreen> {
  final TextEditingController _msgController = TextEditingController();
  final ScrollController _scrollController = ScrollController();
  List<Map<String, dynamic>> _messages = [];
  int? _conversationId;
  int? _currentUserId;
  bool _isLoading = true;
  bool _isSending = false;
  Timer? _pollTimer;

  @override
  void initState() {
    super.initState();
    _loadCurrentUser();
    _loadMessages();
  }

  @override
  void dispose() {
    _pollTimer?.cancel();
    _msgController.dispose();
    _scrollController.dispose();
    super.dispose();
  }

  Future<void> _loadCurrentUser() async {
    final profile = await ApiService.getProfile();
    if (profile != null && mounted) {
      setState(() => _currentUserId = profile['id']);
    }
  }

  Future<void> _loadMessages() async {
    final data = await ApiService.getMessages(widget.otherUserId);
    if (mounted) {
      setState(() {
        _conversationId = data['conversation_id'];
        _messages = List<Map<String, dynamic>>.from(data['messages'] ?? []);
        _isLoading = false;
      });
      _scrollToBottom();
      _startPolling();
    }
  }

  void _startPolling() {
    _pollTimer?.cancel();
    _pollTimer = Timer.periodic(const Duration(seconds: 3), (_) async {
      if (_conversationId == null) return;
      final lastId = _messages.isNotEmpty ? _messages.last['id'] as int : 0;
      final newMessages = await ApiService.pollMessages(_conversationId!, afterId: lastId);
      if (newMessages.isNotEmpty && mounted) {
        setState(() {
          for (var msg in newMessages) {
            // Avoid duplicates
            if (!_messages.any((m) => m['id'] == msg['id'])) {
              _messages.add(Map<String, dynamic>.from(msg));
            }
          }
        });
        _scrollToBottom();
      }
    });
  }

  void _scrollToBottom() {
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (_scrollController.hasClients) {
        _scrollController.animateTo(
          _scrollController.position.maxScrollExtent,
          duration: const Duration(milliseconds: 300),
          curve: Curves.easeOut,
        );
      }
    });
  }

  Future<void> _sendMessage() async {
    final text = _msgController.text.trim();
    if (text.isEmpty || _isSending) return;

    setState(() => _isSending = true);
    _msgController.clear();

    final result = await ApiService.sendMessage(widget.otherUserId, text);

    if (result != null && mounted) {
      final sentMsg = result['message'];
      setState(() {
        if (!_messages.any((m) => m['id'] == sentMsg['id'])) {
          _messages.add(Map<String, dynamic>.from(sentMsg));
        }
        _conversationId ??= sentMsg['conversation_id'];
        _isSending = false;
      });
      _scrollToBottom();
    } else {
      setState(() => _isSending = false);
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text("Failed to send message"),
            backgroundColor: Colors.redAccent,
            behavior: SnackBarBehavior.floating,
          ),
        );
      }
    }
  }

  String _formatTime(String? isoDate) {
    if (isoDate == null) return '';
    try {
      final date = DateTime.parse(isoDate).toLocal();
      final hour = date.hour.toString().padLeft(2, '0');
      final min = date.minute.toString().padLeft(2, '0');
      return '$hour:$min';
    } catch (_) {
      return '';
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF4F3FB),
      appBar: AppBar(
        title: Text(widget.mentorName,
            style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
        backgroundColor: const Color(0xFF6B4EE6),
        elevation: 0,
        iconTheme: const IconThemeData(color: Colors.white),
      ),
      body: Column(
        children: [
          // Chat area
          Expanded(
            child: _isLoading
                ? const Center(child: CircularProgressIndicator(color: Color(0xFF6B4EE6)))
                : _messages.isEmpty
                    ? Center(
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Icon(Icons.chat_bubble_outline, size: 48, color: Colors.grey.shade300),
                            const SizedBox(height: 8),
                            Text("No messages yet. Say hello!",
                                style: TextStyle(color: Colors.grey.shade500)),
                          ],
                        ),
                      )
                    : ListView.builder(
                        controller: _scrollController,
                        padding: const EdgeInsets.all(16),
                        itemCount: _messages.length,
                        itemBuilder: (context, index) {
                          final msg = _messages[index];
                          final bool isMe = msg['sender_id'] == _currentUserId;
                          return _buildBubble(msg, isMe);
                        },
                      ),
          ),

          // Input area
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: const BorderRadius.only(
                  topLeft: Radius.circular(24), topRight: Radius.circular(24)),
              boxShadow: [BoxShadow(color: Colors.purple.withOpacity(0.05), blurRadius: 15)],
            ),
            child: Row(
              children: [
                Expanded(
                  child: Container(
                    decoration: BoxDecoration(
                      color: const Color(0xFFF4F3FB),
                      borderRadius: BorderRadius.circular(20),
                    ),
                    child: TextField(
                      controller: _msgController,
                      textInputAction: TextInputAction.send,
                      onSubmitted: (_) => _sendMessage(),
                      decoration: InputDecoration(
                        hintText: "Type a message...",
                        hintStyle: TextStyle(color: Colors.grey.shade400),
                        prefixIcon: const Icon(Icons.mood, color: Color(0xFF6B4EE6)),
                        border: InputBorder.none,
                        contentPadding:
                            const EdgeInsets.symmetric(vertical: 14, horizontal: 12),
                      ),
                    ),
                  ),
                ),
                const SizedBox(width: 10),
                GestureDetector(
                  onTap: _sendMessage,
                  child: Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: const Color(0xFF6B4EE6),
                      borderRadius: BorderRadius.circular(14),
                    ),
                    child: _isSending
                        ? const SizedBox(
                            width: 22,
                            height: 22,
                            child: CircularProgressIndicator(
                                color: Colors.white, strokeWidth: 2))
                        : const Icon(Icons.send_rounded, color: Colors.white, size: 22),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildBubble(Map<String, dynamic> msg, bool isMe) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: Column(
        crossAxisAlignment: isMe ? CrossAxisAlignment.end : CrossAxisAlignment.start,
        children: [
          Container(
            constraints: BoxConstraints(
                maxWidth: MediaQuery.of(context).size.width * 0.75),
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
            decoration: BoxDecoration(
              color: isMe ? const Color(0xFF6B4EE6) : Colors.white,
              borderRadius: BorderRadius.only(
                topLeft: const Radius.circular(16),
                topRight: const Radius.circular(16),
                bottomLeft: isMe ? const Radius.circular(16) : Radius.zero,
                bottomRight: isMe ? Radius.zero : const Radius.circular(16),
              ),
              boxShadow: [
                BoxShadow(color: Colors.purple.withOpacity(0.05), blurRadius: 6),
              ],
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(msg['body'] ?? '',
                    style: TextStyle(
                        color: isMe ? Colors.white : const Color(0xFF2D2D3A),
                        fontSize: 14)),
                const SizedBox(height: 4),
                Text(_formatTime(msg['created_at']),
                    style: TextStyle(
                        color: isMe ? Colors.white60 : Colors.grey.shade400,
                        fontSize: 10)),
              ],
            ),
          ),
        ],
      ),
    );
  }
}