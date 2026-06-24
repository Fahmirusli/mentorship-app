import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';
import '../services/api_service.dart';

class MenteeResourcesScreen extends StatefulWidget {
  const MenteeResourcesScreen({super.key});

  @override
  State<MenteeResourcesScreen> createState() => _MenteeResourcesScreenState();
}

class _MenteeResourcesScreenState extends State<MenteeResourcesScreen> {
  List<dynamic> _resources = [];
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _fetchResources();
  }

  Future<void> _fetchResources() async {
    final resources = await ApiService.getMenteeResources();
    if (mounted) {
      setState(() {
        _resources = resources;
        _isLoading = false;
      });
    }
  }

  Future<void> _downloadResource(String url) async {
    try {
      final uri = Uri.parse(url);
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Could not open file URL')),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF4F3FB),
      appBar: AppBar(
        title: const Text('Resources', style: TextStyle(color: Colors.black87, fontWeight: FontWeight.bold)),
        backgroundColor: Colors.transparent,
        elevation: 0,
        iconTheme: const IconThemeData(color: Colors.black87),
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: Color(0xFF6B4EE6)))
          : _resources.isEmpty
              ? const Center(
                  child: Text(
                    "No resources found.\nOnly active mentors can share resources with you.",
                    textAlign: TextAlign.center,
                    style: TextStyle(color: Colors.grey, fontSize: 16),
                  ),
                )
              : ListView.builder(
                  padding: const EdgeInsets.all(20),
                  itemCount: _resources.length,
                  itemBuilder: (context, index) {
                    final res = _resources[index];
                    return _resourceCard(res);
                  },
                ),
    );
  }

  Widget _resourceCard(dynamic res) {
    return Container(
      margin: const EdgeInsets.only(bottom: 15),
      padding: const EdgeInsets.all(15),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(15),
        boxShadow: [BoxShadow(color: Colors.purple.withOpacity(0.05), blurRadius: 10)],
      ),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(color: const Color(0xFFF4F3FB), borderRadius: BorderRadius.circular(12)),
            child: const Icon(Icons.picture_as_pdf, color: Color(0xFF6B4EE6)),
          ),
          const SizedBox(width: 15),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(res['title'] ?? 'Resource', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                const SizedBox(height: 4),
                Text("Shared by: ${res['mentor_name'] ?? 'Mentor'}", style: TextStyle(color: Colors.grey.shade600, fontSize: 13)),
              ],
            ),
          ),
          IconButton(
            icon: const Icon(Icons.download_rounded, color: Color(0xFF6B4EE6)),
            onPressed: () {
              if (res['file_url'] != null) {
                _downloadResource(res['file_url']);
              }
            },
          ),
        ],
      ),
    );
  }
}
