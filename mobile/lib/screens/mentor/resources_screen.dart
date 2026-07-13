import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:file_picker/file_picker.dart';
import '../services/api_service.dart';

class MentorResourcesScreen extends StatefulWidget {
  const MentorResourcesScreen({super.key});

  @override
  State<MentorResourcesScreen> createState() => _MentorResourcesScreenState();
}

class _MentorResourcesScreenState extends State<MentorResourcesScreen> {
  List<dynamic> _resources = [];
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _fetchResources();
  }

  Future<void> _fetchResources() async {
    final resources = await ApiService.getMentorResources();
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

  void _showUploadDialog() {
    final _titleController = TextEditingController();
    final _descController = TextEditingController();
    String _visibility = 'public';
    String? _selectedFilePath;
    String? _selectedFileName;

    showDialog(
      context: context,
      builder: (context) {
        bool _isUploading = false;
        return StatefulBuilder(
          builder: (context, setDialogState) {
            return AlertDialog(
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
              title: const Text('Upload Resource', style: TextStyle(fontWeight: FontWeight.bold)),
              content: SingleChildScrollView(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    TextField(
                      controller: _titleController,
                      decoration: const InputDecoration(labelText: 'Title'),
                    ),
                    const SizedBox(height: 10),
                    TextField(
                      controller: _descController,
                      decoration: const InputDecoration(labelText: 'Description'),
                      maxLines: 2,
                    ),
                    const SizedBox(height: 15),
                    DropdownButtonFormField<String>(
                      value: _visibility,
                      items: const [
                        DropdownMenuItem(value: 'public', child: Text('Public (All Mentees)')),
                        DropdownMenuItem(value: 'mentees_only', child: Text('Mentees Only')),
                        DropdownMenuItem(value: 'private', child: Text('Private')),
                      ],
                      onChanged: (val) {
                        if (val != null) setDialogState(() => _visibility = val);
                      },
                      decoration: const InputDecoration(labelText: 'Visibility'),
                    ),
                    const SizedBox(height: 15),
                    OutlinedButton.icon(
                      icon: const Icon(Icons.attach_file),
                      label: Text(_selectedFileName ?? 'Select File (PDF/Image)'),
                      onPressed: () async {
                        FilePickerResult? result = await FilePicker.platform.pickFiles();
                        if (result != null && result.files.single.path != null) {
                          setDialogState(() {
                            _selectedFilePath = result.files.single.path;
                            _selectedFileName = result.files.single.name;
                          });
                        }
                      },
                    )
                  ],
                ),
              ),
              actions: [
                TextButton(
                  onPressed: () => Navigator.pop(context),
                  child: const Text('Cancel', style: TextStyle(color: Colors.grey)),
                ),
                ElevatedButton(
                  onPressed: _isUploading ? null : () async {
                    if (_titleController.text.isEmpty || _selectedFilePath == null) {
                      ScaffoldMessenger.of(context).showSnackBar(
                        const SnackBar(content: Text('Please provide title and file')),
                      );
                      return;
                    }
                    setDialogState(() => _isUploading = true);
                    final success = await ApiService.uploadMentorResource(
                      title: _titleController.text,
                      description: _descController.text,
                      visibility: _visibility,
                      filePath: _selectedFilePath!,
                    );
                    if (mounted) {
                      Navigator.pop(context);
                      if (success) {
                        _fetchResources();
                        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Uploaded successfully!'), backgroundColor: Colors.green));
                      } else {
                        ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Upload failed'), backgroundColor: Colors.red));
                      }
                    }
                  },
                  style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFF2E7D32)),
                  child: _isUploading 
                    ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                    : const Text('Upload', style: TextStyle(color: Colors.white)),
                ),
              ],
            );
          }
        );
      }
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF0F7F0),
      appBar: AppBar(
        title: const Text('My Resources', style: TextStyle(color: Colors.black87, fontWeight: FontWeight.bold)),
        backgroundColor: Colors.transparent,
        elevation: 0,
        iconTheme: const IconThemeData(color: Colors.black87),
        actions: [
          IconButton(
            icon: const Icon(Icons.add_circle, color: Color(0xFF2E7D32), size: 28),
            onPressed: _showUploadDialog,
          )
        ],
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: Color(0xFF2E7D32)))
          : _resources.isEmpty
              ? const Center(
                  child: Text(
                    "You haven't uploaded any resources yet.",
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
        boxShadow: [BoxShadow(color: Colors.green.withOpacity(0.05), blurRadius: 10)],
      ),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(color: const Color(0xFFE8F5E9), borderRadius: BorderRadius.circular(12)),
            child: const Icon(Icons.picture_as_pdf, color: Color(0xFF2E7D32)),
          ),
          const SizedBox(width: 15),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(res['title'] ?? 'Resource', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                const SizedBox(height: 4),
                Text("Visibility: ${res['visibility'] ?? 'public'}", style: TextStyle(color: Colors.grey.shade600, fontSize: 13)),
              ],
            ),
          ),
          IconButton(
            icon: const Icon(Icons.download, color: Color(0xFF2E7D32)),
            onPressed: () {
              if (res['file_path'] != null) {
                final url = '${ApiService.baseUrl.replaceAll('/api', '')}/storage/${res['file_path']}';
                _downloadResource(url);
              }
            },
          )
        ],
      ),
    );
  }
}
