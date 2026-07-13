import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:flutter_rating_bar/flutter_rating_bar.dart';
import '../services/api_service.dart';

class HistoryScreen extends StatefulWidget {
  const HistoryScreen({super.key});

  @override
  State<HistoryScreen> createState() => _HistoryScreenState();
}

class _HistoryScreenState extends State<HistoryScreen> {
  List<dynamic> _allHistory = [];
  bool _isLoading = true;
  String _filter = 'all'; // 'all', 'completed', 'missed'

  @override
  void initState() {
    super.initState();
    _fetchHistory();
  }

  Future<void> _fetchHistory() async {
    setState(() => _isLoading = true);
    final appointments = await ApiService.getMyAppointments();
    if (mounted) {
      setState(() {
        _allHistory = appointments;
        _isLoading = false;
      });
    }
  }

  Future<void> _deleteAppointment(int id) async {
    // Show confirmation dialog first
    final bool? confirm = await showDialog<bool>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Delete History'),
        content: const Text('Are you sure you want to delete this session from your history?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Cancel'),
          ),
          TextButton(
            onPressed: () => Navigator.pop(context, true),
            child: const Text('Delete', style: TextStyle(color: Colors.red)),
          ),
        ],
      ),
    );

    if (confirm != true) return;

    final success = await ApiService.deleteAppointment(id);
    if (success) {
      setState(() {
        _allHistory.removeWhere((item) => item['id'] == id);
      });
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('History deleted successfully')),
        );
      }
    } else {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Failed to delete history')),
        );
      }
    }
  }

  void _showRatingDialog(dynamic session) {
    int rating = 5;
    String comment = '';
    
    showDialog(
      context: context,
      builder: (context) {
        return StatefulBuilder(
          builder: (context, setDialogState) {
            return AlertDialog(
              title: const Text('Rate Mentor'),
              content: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: List.generate(5, (index) {
                      return IconButton(
                        icon: Icon(
                          index < rating ? Icons.star : Icons.star_border,
                          color: Colors.amber,
                          size: 32,
                        ),
                        onPressed: () {
                          setDialogState(() => rating = index + 1);
                        },
                      );
                    }),
                  ),
                  const SizedBox(height: 10),
                  TextField(
                    decoration: const InputDecoration(
                      hintText: 'Write a review...',
                      border: OutlineInputBorder(),
                    ),
                    maxLines: 3,
                    onChanged: (val) => comment = val,
                  ),
                ],
              ),
              actions: [
                TextButton(onPressed: () => Navigator.pop(context), child: const Text('Cancel')),
                ElevatedButton(
                  style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFF6B4EE6)),
                  onPressed: () async {
                    Navigator.pop(context); // Close dialog
                    final toUserId = session['mentorship_detail']?['other_party_id'] ?? session['mentor_id'];
                    final result = await ApiService.submitFeedback(
                      mentorshipId: session['mentorship_id'],
                      appointmentId: session['id'],
                      toUserId: toUserId,
                      rating: rating,
                      comment: comment,
                    );
                    if (result['success'] == true) {
                      if (mounted) {
                        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Feedback submitted!')));
                        _fetchHistory(); // Refresh the list
                      }
                    } else {
                      if (mounted) {
                        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(result['message'] ?? 'Failed to submit feedback')));
                      }
                    }
                  },
                  child: const Text('Submit', style: TextStyle(color: Colors.white)),
                ),
              ],
            );
          }
        );
      }
    );
  }

  void _showReviewDialog(int appointmentId) {
    double _rating = 5.0;
    final _commentsController = TextEditingController();

    showDialog(
      context: context,
      builder: (context) {
        bool _isSubmitting = false;
        return StatefulBuilder(
          builder: (context, setDialogState) {
            return AlertDialog(
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
              title: const Text("Leave a Review", style: TextStyle(fontWeight: FontWeight.bold)),
              content: SingleChildScrollView(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    const Text("How was your mentorship session?", style: TextStyle(color: Colors.grey)),
                    const SizedBox(height: 20),
                    RatingBar.builder(
                      initialRating: 5,
                      minRating: 1,
                      direction: Axis.horizontal,
                      allowHalfRating: true,
                      itemCount: 5,
                      itemPadding: const EdgeInsets.symmetric(horizontal: 4.0),
                      itemBuilder: (context, _) => const Icon(
                        Icons.star,
                        color: Colors.amber,
                      ),
                      onRatingUpdate: (rating) {
                        _rating = rating;
                      },
                    ),
                    const SizedBox(height: 20),
                    TextField(
                      controller: _commentsController,
                      maxLines: 3,
                      decoration: InputDecoration(
                        hintText: "Share your experience...",
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(15)),
                        contentPadding: const EdgeInsets.all(15),
                      ),
                    ),
                  ],
                ),
              ),
              actions: [
                TextButton(
                  onPressed: () => Navigator.pop(context),
                  child: const Text("Cancel", style: TextStyle(color: Colors.grey)),
                ),
                ElevatedButton(
                  onPressed: _isSubmitting ? null : () async {
                    setDialogState(() => _isSubmitting = true);
                    final success = await ApiService.submitFeedback(
                      appointmentId: appointmentId,
                      rating: _rating,
                      comments: _commentsController.text,
                    );
                    if (mounted) {
                      Navigator.pop(context);
                      ScaffoldMessenger.of(context).showSnackBar(SnackBar(
                        content: Text(success ? "Review submitted successfully!" : "Failed to submit review."),
                        backgroundColor: success ? Colors.green : Colors.red,
                      ));
                    }
                  },
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFF6B4EE6),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                  ),
                  child: _isSubmitting 
                    ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                    : const Text("Submit", style: TextStyle(color: Colors.white)),
                ),
              ],
            );
          },
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    // Apply filter
    List<dynamic> displayedHistory = _allHistory.where((item) {
      if (_filter == 'all') return true;
      return (item['status'] ?? '').toLowerCase() == _filter;
    }).toList();

    return Scaffold(
      backgroundColor: const Color(0xFFF4F3FB),
      appBar: AppBar(
        title: const Text("Mentorship History", style: TextStyle(color: Colors.black87, fontWeight: FontWeight.bold)),
        backgroundColor: Colors.transparent,
        elevation: 0,
        centerTitle: true,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back_ios, color: Colors.black87),
          onPressed: () => Navigator.pop(context),
        ),
      ),
      body: Column(
        children: [
          // Filter Chips
          SingleChildScrollView(
            scrollDirection: Axis.horizontal,
            padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
            child: Row(
              children: [
                _buildFilterChip('All', 'all'),
                const SizedBox(width: 10),
                _buildFilterChip('Completed', 'completed'),
                const SizedBox(width: 10),
                _buildFilterChip('Missed', 'missed'),
              ],
            ),
          ),
          
          // List View
          Expanded(
            child: _isLoading
                ? const Center(child: CircularProgressIndicator(color: Color(0xFF6B4EE6)))
                : displayedHistory.isEmpty
                    ? const Center(child: Text("No history found for this category.", style: TextStyle(color: Colors.grey)))
                    : ListView.builder(
                        padding: const EdgeInsets.only(left: 20, right: 20, top: 10, bottom: 100),
                        itemCount: displayedHistory.length,
                        itemBuilder: (context, index) {
                          var session = displayedHistory[index];
                          bool isCompleted = session['status'] == "completed";
                          bool isMissed = session['status'] == "missed";
                          String topic = session['notes'] ?? "Mentorship Session";
                          if (topic.isEmpty) topic = "Mentorship Session";
                          String otherName = session['other_user_name'] ?? "User";
                          String date = session['date'] ?? "Unknown Date";
                          
                          Color statusColor = isCompleted 
                              ? Colors.green 
                              : isMissed ? Colors.red : Colors.grey;
                          IconData statusIcon = isCompleted 
                              ? Icons.check_circle 
                              : isMissed ? Icons.cancel : Icons.info;

                          return Container(
                            margin: const EdgeInsets.only(bottom: 15),
                            padding: const EdgeInsets.all(15),
                            decoration: BoxDecoration(
                              color: Colors.white,
                              borderRadius: BorderRadius.circular(20),
                              boxShadow: [BoxShadow(color: Colors.purple.withOpacity(0.05), blurRadius: 10)],
                              border: Border.all(color: statusColor.withOpacity(0.3)),
                            ),
                            child: Row(
                              children: [
                                CircleAvatar(
                                  backgroundColor: statusColor.withOpacity(0.1),
                                  child: Icon(statusIcon, color: statusColor),
                                ),
                                const SizedBox(width: 15),
                                Expanded(
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Text(topic, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16), maxLines: 1, overflow: TextOverflow.ellipsis),
                                      Text("with $otherName", style: TextStyle(color: Colors.grey.shade700, fontSize: 13)),
                                      const SizedBox(height: 5),
                                      Text(date, style: const TextStyle(fontSize: 12, color: Colors.grey)),
                                    ],
                                  ),
                                ),
                                Column(
                                  crossAxisAlignment: CrossAxisAlignment.end,
                                    children: [
                                      Container(
                                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                        decoration: BoxDecoration(
                                          color: statusColor.withOpacity(0.1),
                                          borderRadius: BorderRadius.circular(8),
                                        ),
                                        child: Text(
                                          (session['status'] ?? 'unknown').toUpperCase(),
                                          style: TextStyle(
                                            fontSize: 10,
                                            fontWeight: FontWeight.bold,
                                            color: statusColor,
                                          ),
                                        ),
                                      ),
                                      if (isCompleted && session['id'] != null) ...[
                                        const SizedBox(height: 8),
                                        InkWell(
                                          onTap: () => _showReviewDialog(session['id']),
                                          child: const Text("Leave Review", style: TextStyle(color: Color(0xFF6B4EE6), fontSize: 12, fontWeight: FontWeight.bold, decoration: TextDecoration.underline)),
                                        ),
                                      ],
                                      const SizedBox(height: 8),
                                      
                                      if (isCompleted && session['has_feedback'] != true)
                                        InkWell(
                                          onTap: () => _showRatingDialog(session),
                                          child: Container(
                                            margin: const EdgeInsets.only(bottom: 8),
                                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                            decoration: BoxDecoration(
                                              color: Colors.amber.withOpacity(0.1),
                                              borderRadius: BorderRadius.circular(8),
                                            ),
                                            child: const Row(
                                              children: [
                                                Icon(Icons.star, size: 14, color: Colors.amber),
                                                SizedBox(width: 4),
                                                Text("Rate", style: TextStyle(color: Colors.amber, fontSize: 10, fontWeight: FontWeight.bold)),
                                              ],
                                            ),
                                          ),
                                        ),

                                      // Delete Button
                                      InkWell(
                                        onTap: () => _deleteAppointment(session['id']),
                                        child: Container(
                                          padding: const EdgeInsets.all(4),
                                          child: const Icon(Icons.delete_outline, size: 20, color: Colors.redAccent),
                                        ),
                                      )
                                    ],
                                ),
                              ],
                            ),
                          );
                        },
                      ),
          ),
        ],
      ),
    );
  }

  Widget _buildFilterChip(String label, String value) {
    bool isSelected = _filter == value;
    return ChoiceChip(
      label: Text(label),
      selected: isSelected,
      onSelected: (selected) {
        if (selected) {
          setState(() => _filter = value);
        }
      },
      selectedColor: const Color(0xFF6B4EE6).withOpacity(0.2),
      labelStyle: TextStyle(
        color: isSelected ? const Color(0xFF6B4EE6) : Colors.black87,
        fontWeight: isSelected ? FontWeight.bold : FontWeight.normal,
      ),
      backgroundColor: Colors.white,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(20),
        side: BorderSide(
          color: isSelected ? const Color(0xFF6B4EE6) : Colors.grey.shade300,
        ),
      ),
    );
  }
}