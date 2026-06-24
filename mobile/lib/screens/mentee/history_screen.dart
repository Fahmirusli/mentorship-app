import 'package:flutter/material.dart';
import '../services/api_service.dart';

class HistoryScreen extends StatefulWidget {
  const HistoryScreen({super.key});

  @override
  State<HistoryScreen> createState() => _HistoryScreenState();
}

class _HistoryScreenState extends State<HistoryScreen> {
  List<dynamic> _history = [];
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _fetchHistory();
  }

  Future<void> _fetchHistory() async {
    final appointments = await ApiService.getMyAppointments();
    if (mounted) {
      setState(() {
        _history = appointments;
        _isLoading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
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
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: Color(0xFF6B4EE6)))
          : _history.isEmpty
              ? const Center(child: Text("No mentorship history found.", style: TextStyle(color: Colors.grey)))
              : ListView.builder(
                  padding: const EdgeInsets.only(left: 20, right: 20, top: 20, bottom: 100),
                  itemCount: _history.length,
                  itemBuilder: (context, index) {
                    var session = _history[index];
                    bool isCompleted = session['status'] == "completed";
                    String topic = session['notes'] ?? "Mentorship Session";
                    if (topic.isEmpty) topic = "Mentorship Session";
                    String otherName = session['other_user_name'] ?? "User";
                    String date = session['date'] ?? "Unknown Date";

                    return Container(
                      margin: const EdgeInsets.only(bottom: 15),
                      padding: const EdgeInsets.all(15),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(20),
                        boxShadow: [BoxShadow(color: Colors.purple.withOpacity(0.05), blurRadius: 10)],
                        border: Border.all(color: isCompleted ? Colors.green.withOpacity(0.3) : Colors.grey.withOpacity(0.3)),
                      ),
                      child: Row(
                        children: [
                          CircleAvatar(
                            backgroundColor: isCompleted ? Colors.green.shade50 : Colors.grey.shade50,
                            child: Icon(isCompleted ? Icons.check_circle : Icons.info, color: isCompleted ? Colors.green : Colors.grey),
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
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                            decoration: BoxDecoration(
                              color: isCompleted ? Colors.green.withOpacity(0.1) : Colors.grey.withOpacity(0.1),
                              borderRadius: BorderRadius.circular(8),
                            ),
                            child: Text(
                              (session['status'] ?? 'unknown').toUpperCase(),
                              style: TextStyle(
                                fontSize: 10,
                                fontWeight: FontWeight.bold,
                                color: isCompleted ? Colors.green : Colors.grey,
                              ),
                            ),
                          ),
                        ],
                      ),
                    );
                  },
                ),
    );
  }
}