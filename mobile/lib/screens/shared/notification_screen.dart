// lib/screens/shared/notification_screen.dart
import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import '../services/api_service.dart';

class NotificationScreen extends StatefulWidget {
  const NotificationScreen({super.key});

  @override
  State<NotificationScreen> createState() => _NotificationScreenState();
}

class _NotificationScreenState extends State<NotificationScreen> {
  List<dynamic> _notifications = [];
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _loadNotifications();
  }

  Future<void> _loadNotifications() async {
    final notifs = await ApiService.getNotifications();
    if (mounted) {
      setState(() {
        _notifications = notifs;
        _isLoading = false;
      });
    }
  }

  Future<void> _markAllRead() async {
    await ApiService.markNotificationsRead();
    _loadNotifications();
  }

  IconData _getIconForType(String type) {
    switch (type) {
      case 'message':
        return Icons.message_rounded;
      case 'appointment':
        return Icons.calendar_month_rounded;
      case 'system':
        return Icons.info_outline_rounded;
      default:
        return Icons.notifications_rounded;
    }
  }

  Color _getColorForType(String type) {
    switch (type) {
      case 'message':
        return const Color(0xFF6B4EE6);
      case 'appointment':
        return Colors.green;
      case 'system':
        return Colors.orange;
      default:
        return Colors.grey;
    }
  }

  String _formatTime(String isoDate) {
    try {
      final date = DateTime.parse(isoDate);
      final now = DateTime.now();
      final diff = now.difference(date);

      if (diff.inMinutes < 1) return 'Just now';
      if (diff.inMinutes < 60) return '${diff.inMinutes}m ago';
      if (diff.inHours < 24) return '${diff.inHours}h ago';
      if (diff.inDays < 7) return '${diff.inDays}d ago';
      return '${date.day}/${date.month}/${date.year}';
    } catch (_) {
      return '';
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF4F3FB),
      appBar: AppBar(
        title: const Text("Notifications",
            style: TextStyle(color: Colors.black87, fontWeight: FontWeight.bold)),
        backgroundColor: Colors.transparent,
        elevation: 0,
        iconTheme: const IconThemeData(color: Colors.black87),
        centerTitle: true,
        actions: [
          if (_notifications.any((n) => n['is_read'] == false))
            TextButton(
              onPressed: _markAllRead,
              child: const Text("Mark all read",
                  style: TextStyle(color: Color(0xFF6B4EE6), fontWeight: FontWeight.w600, fontSize: 12)),
            ),
        ],
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: Color(0xFF6B4EE6)))
          : _notifications.isEmpty
              ? Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(Icons.notifications_off_outlined, size: 64, color: Colors.grey.shade300),
                      const SizedBox(height: 12),
                      Text("No notifications yet",
                          style: TextStyle(color: Colors.grey.shade500, fontSize: 16)),
                    ],
                  ),
                )
              : RefreshIndicator(
                  onRefresh: _loadNotifications,
                  color: const Color(0xFF6B4EE6),
                  child: ListView.builder(
                    padding: const EdgeInsets.all(16),
                    itemCount: _notifications.length,
                    itemBuilder: (context, index) {
                      final notif = _notifications[index];
                      final bool isRead = notif['is_read'] ?? false;
                      final String type = notif['type'] ?? 'system';

                      return Container(
                        margin: const EdgeInsets.only(bottom: 10),
                        padding: const EdgeInsets.all(14),
                        decoration: BoxDecoration(
                          color: isRead ? Colors.white : const Color(0xFFF0EBFF),
                          borderRadius: BorderRadius.circular(16),
                          border: isRead ? null : Border.all(color: const Color(0xFF6B4EE6).withOpacity(0.2)),
                          boxShadow: [
                            BoxShadow(color: Colors.purple.withOpacity(0.03), blurRadius: 8),
                          ],
                        ),
                        child: Row(
                          children: [
                            Container(
                              padding: const EdgeInsets.all(10),
                              decoration: BoxDecoration(
                                color: _getColorForType(type).withOpacity(0.1),
                                borderRadius: BorderRadius.circular(12),
                              ),
                              child: Icon(_getIconForType(type),
                                  color: _getColorForType(type), size: 22),
                            ),
                            const SizedBox(width: 12),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(notif['title'] ?? '',
                                      style: TextStyle(
                                          fontWeight: isRead ? FontWeight.w500 : FontWeight.bold,
                                          fontSize: 14,
                                          color: const Color(0xFF2D2D3A))),
                                  const SizedBox(height: 3),
                                  Text(notif['body'] ?? '',
                                      style: TextStyle(fontSize: 12, color: Colors.grey.shade600),
                                      maxLines: 2,
                                      overflow: TextOverflow.ellipsis),
                                ],
                              ),
                            ),
                            const SizedBox(width: 8),
                            Column(
                              crossAxisAlignment: CrossAxisAlignment.end,
                              children: [
                                Text(_formatTime(notif['created_at'] ?? ''),
                                    style: TextStyle(fontSize: 10, color: Colors.grey.shade400)),
                                if (!isRead) ...[
                                  const SizedBox(height: 6),
                                  Container(
                                    width: 8,
                                    height: 8,
                                    decoration: const BoxDecoration(
                                      color: Color(0xFF6B4EE6),
                                      shape: BoxShape.circle,
                                    ),
                                  ),
                                ],
                              ],
                            ),
                          ],
                        ),
                      ).animate()
                          .fadeIn(delay: Duration(milliseconds: index * 50))
                          .slideX(begin: 0.05);
                    },
                  ),
                ),
    );
  }
}
