import 'package:flutter/material.dart';


class HistoryScreen extends StatelessWidget {
  const HistoryScreen({super.key});

  @override
  Widget build(BuildContext context) {
    // This is the exact History code we wrote earlier!
    final List<Map<String, String>> history = [
      {"mentor": "Dr. Ali", "topic": "VLAN Design & Routing", "date": "10 Apr 2026", "status": "Completed"},
      {"mentor": "Sarah Lee", "topic": "Next.js Environment Setup", "date": "02 Apr 2026", "status": "Completed"},
      {"mentor": "James Tan", "topic": "Flutter DevTools Debugging", "date": "15 Mar 2026", "status": "Canceled"},
    ];

    return Scaffold(
      backgroundColor: const Color(0xFFF4F3FB),
      appBar: AppBar(
        title: const Text("Mentorship History", style: TextStyle(color: Colors.black87, fontWeight: FontWeight.bold)),
        backgroundColor: Colors.transparent,
        elevation: 0,
        centerTitle: true,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back_ios, color: Colors.black87),
          onPressed: () => Navigator.pop(context), // This handles the back button!
        ),
      ),
      body: ListView.builder(
        padding: const EdgeInsets.all(20),
        itemCount: history.length,
        itemBuilder: (context, index) {
          var session = history[index];
          bool isCompleted = session['status'] == "Completed";

          return Container(
            margin: const EdgeInsets.only(bottom: 15),
            padding: const EdgeInsets.all(15),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(20),
              boxShadow: [BoxShadow(color: Colors.purple.withOpacity(0.05), blurRadius: 10)],
              border: Border.all(color: isCompleted ? Colors.green.withOpacity(0.3) : Colors.red.withOpacity(0.3)),
            ),
            child: Row(
              children: [
                CircleAvatar(
                  backgroundColor: isCompleted ? Colors.green.shade50 : Colors.red.shade50,
                  child: Icon(isCompleted ? Icons.check_circle : Icons.cancel, color: isCompleted ? Colors.green : Colors.red),
                ),
                const SizedBox(width: 15),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(session['topic']!, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                      Text("with ${session['mentor']}", style: TextStyle(color: Colors.grey.shade700, fontSize: 13)),
                      const SizedBox(height: 5),
                      Text(session['date']!, style: const TextStyle(fontSize: 12, color: Colors.grey)),
                    ],
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