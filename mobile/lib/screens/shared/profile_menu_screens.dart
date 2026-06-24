// lib/screens/shared/profile_menu_screens.dart
import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:file_picker/file_picker.dart';
import 'dart:io';
import '../services/api_service.dart';
import '../mentee/mentee_home.dart';
import '../mentor/mentor_home.dart';

// ==========================================
// 1. EDIT PROFILE SCREEN
// ==========================================
class EditProfileScreen extends StatefulWidget {
  final bool requireCompletion;
  final int? roleAfterCompletion;

  const EditProfileScreen({
    super.key,
    this.requireCompletion = false,
    this.roleAfterCompletion,
  });

  @override
  State<EditProfileScreen> createState() => _EditProfileScreenState();
}

class _EditProfileScreenState extends State<EditProfileScreen> {
  final TextEditingController _nameController = TextEditingController();
  final TextEditingController _bioController = TextEditingController();
  final TextEditingController _emailController = TextEditingController();
  final TextEditingController _phoneController = TextEditingController();
  final TextEditingController _addressController = TextEditingController();
  final TextEditingController _skillsController = TextEditingController();

  String? _profileImageUrl;
  String? _resumeUrl;
  File? _selectedImageFile;
  String? _selectedResumePath;
  bool _isLoading = true;
  bool _isSaving = false;

  @override
  void initState() {
    super.initState();
    _loadProfile();
  }

  Future<void> _loadProfile() async {
    final userData = await ApiService.getProfile();
    if (userData != null && mounted) {
      setState(() {
        _nameController.text = userData['name'] ?? '';
        _bioController.text = userData['bio'] ?? '';
        _emailController.text = userData['email'] ?? '';
        _phoneController.text = userData['phone'] ?? '';
        _addressController.text = userData['address'] ?? '';
        _skillsController.text = (userData['skills'] as List<dynamic>? ?? []).map((s) => s.toString()).join(', ');
        _profileImageUrl = userData['profile_image'];
        if (userData['resume_path'] != null && userData['resume_path'].toString().isNotEmpty) {
          _resumeUrl = userData['resume_path'].toString();
        }
        _isLoading = false;
      });
    } else if (mounted) {
      setState(() => _isLoading = false);
    }
  }

  Future<void> _pickImage() async {
    final ImagePicker picker = ImagePicker();
    final XFile? image = await picker.pickImage(
      source: ImageSource.gallery,
      maxWidth: 800,
      maxHeight: 800,
      imageQuality: 85,
    );
    if (image != null && mounted) {
      setState(() {
        _selectedImageFile = File(image.path);
      });
    }
  }

  Future<void> _saveProfile() async {
    if (widget.requireCompletion &&
        (_nameController.text.trim().isEmpty ||
            _phoneController.text.trim().isEmpty ||
            _addressController.text.trim().isEmpty ||
            _bioController.text.trim().isEmpty ||
            _skillsController.text.trim().isEmpty)) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Please complete all required fields before continuing.'),
          backgroundColor: Colors.redAccent,
          behavior: SnackBarBehavior.floating,
        ),
      );
      return;
    }

    setState(() => _isSaving = true);

    // 1. Upload image if a new one was selected
    if (_selectedImageFile != null) {
      final imageResult = await ApiService.uploadProfileImage(_selectedImageFile!.path);
      if (imageResult['success'] == true) {
        _profileImageUrl = imageResult['image_url'];
      } else {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(imageResult['message'] ?? 'Image upload failed'),
              backgroundColor: Colors.redAccent,
              behavior: SnackBarBehavior.floating,
            ),
          );
        }
        setState(() => _isSaving = false);
        return;
      }
    }

    if (_selectedResumePath != null) {
      final resumeResult = await ApiService.uploadResume(_selectedResumePath!);
      if (resumeResult['success'] != true) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(resumeResult['message'] ?? 'Resume upload failed'),
              backgroundColor: Colors.redAccent,
              behavior: SnackBarBehavior.floating,
            ),
          );
        }
        setState(() => _isSaving = false);
        return;
      }
      _resumeUrl = resumeResult['resume_url'];
    }

    final parsedSkills = _skillsController.text
        .split(',')
        .map((s) => s.trim())
        .where((s) => s.isNotEmpty)
        .toSet()
        .toList();

    // 2. Update profile text fields
    final result = await ApiService.updateProfile(
      name: _nameController.text.trim(),
      bio: _bioController.text.trim(),
      email: _emailController.text.trim(),
      phone: _phoneController.text.trim(),
      address: _addressController.text.trim(),
      skills: parsedSkills,
    );

    setState(() => _isSaving = false);

    if (mounted) {
      if (result['success'] == true) {
        if (widget.requireCompletion && widget.roleAfterCompletion != null) {
          if (widget.roleAfterCompletion == 2) {
            Navigator.of(context).pushAndRemoveUntil(
              MaterialPageRoute(builder: (context) => MentorDashboard(onLogout: () {})),
              (route) => false,
            );
          } else {
            Navigator.of(context).pushAndRemoveUntil(
              MaterialPageRoute(builder: (context) => MenteeDashboard(onLogout: () {})),
              (route) => false,
            );
          }
        } else {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text("Profile Updated!"),
              backgroundColor: Color(0xFF6B4EE6),
              behavior: SnackBarBehavior.floating,
            ),
          );
          Navigator.pop(context, true); // Return true to indicate data changed
        }
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(result['message'] ?? 'Update failed'),
            backgroundColor: Colors.redAccent,
            behavior: SnackBarBehavior.floating,
          ),
        );
      }
    }
  }

  @override
  void dispose() {
    _nameController.dispose();
    _bioController.dispose();
    _emailController.dispose();
    _phoneController.dispose();
    _addressController.dispose();
    _skillsController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF4F3FB),
      appBar: AppBar(
        title: const Text("Edit Profile", style: TextStyle(color: Colors.black87, fontWeight: FontWeight.bold)),
        backgroundColor: Colors.transparent,
        elevation: 0,
        iconTheme: const IconThemeData(color: Colors.black87),
        centerTitle: true,
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: Color(0xFF6B4EE6)))
          : SingleChildScrollView(
              padding: const EdgeInsets.all(20),
              child: Column(
                children: [
                  // Profile Picture Editor
                  Center(
                    child: GestureDetector(
                      onTap: _pickImage,
                      child: Stack(
                        children: [
                          Container(
                            decoration: BoxDecoration(
                              shape: BoxShape.circle,
                              gradient: const LinearGradient(colors: [Color(0xFF6B4EE6), Color(0xFF9B7EFA)]),
                              boxShadow: [BoxShadow(color: Colors.purpleAccent.withOpacity(0.3), blurRadius: 15, spreadRadius: 2)],
                            ),
                            child: CircleAvatar(
                              radius: 50,
                              backgroundColor: Colors.transparent,
                              backgroundImage: _selectedImageFile != null
                                  ? FileImage(_selectedImageFile!)
                                  : ApiService.getProfileImageProvider(_profileImageUrl),
                              child: (_selectedImageFile == null && (_profileImageUrl == null || _profileImageUrl!.isEmpty))
                                  ? const Icon(Icons.person, size: 50, color: Colors.white)
                                  : null,
                            ),
                          ),
                          Positioned(
                            bottom: 0,
                            right: 0,
                            child: Container(
                              padding: const EdgeInsets.all(8),
                              decoration: const BoxDecoration(color: Colors.white, shape: BoxShape.circle),
                              child: const Icon(Icons.camera_alt, color: Color(0xFF6B4EE6), size: 20),
                            ),
                          )
                        ],
                      ),
                    ),
                  ),
                  const SizedBox(height: 30),

                  // Input Fields
                  _buildEditField("Full Name", _nameController, Icons.person_outline),
                  const SizedBox(height: 15),
                  _buildEditField("Title / Bio", _bioController, Icons.work_outline),
                  const SizedBox(height: 15),
                  _buildEditField("Email Address", _emailController, Icons.email_outlined),
                  const SizedBox(height: 15),
                  _buildEditField("Phone Number", _phoneController, Icons.phone_outlined),
                  const SizedBox(height: 15),
                  _buildEditField("Address", _addressController, Icons.home_outlined),
                  const SizedBox(height: 15),
                  _buildEditField("Skills (comma separated)", _skillsController, Icons.psychology_outlined),
                  const SizedBox(height: 15),

                  InkWell(
                    onTap: () async {
                      final result = await FilePicker.platform.pickFiles(
                        type: FileType.custom,
                        allowedExtensions: ['pdf', 'doc', 'docx'],
                      );
                      if (result != null && result.files.single.path != null && mounted) {
                        setState(() {
                          _selectedResumePath = result.files.single.path;
                        });
                      }
                    },
                    borderRadius: BorderRadius.circular(12),
                    child: Container(
                      width: double.infinity,
                      padding: const EdgeInsets.symmetric(vertical: 14, horizontal: 16),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: Colors.grey.shade200),
                      ),
                      child: Text(
                        _selectedResumePath != null
                            ? 'Resume selected: ${_selectedResumePath!.split(Platform.pathSeparator).last}'
                            : (_resumeUrl != null && _resumeUrl!.isNotEmpty
                                ? 'Resume uploaded (tap to replace)'
                                : 'Upload Resume (optional)'),
                        style: const TextStyle(fontWeight: FontWeight.w600, color: Color(0xFF2D2D3A)),
                      ),
                    ),
                  ),

                  const SizedBox(height: 40),

                  // Save Button
                  InkWell(
                    onTap: _isSaving ? null : _saveProfile,
                    borderRadius: BorderRadius.circular(15),
                    child: Container(
                      width: double.infinity,
                      padding: const EdgeInsets.symmetric(vertical: 16),
                      decoration: BoxDecoration(
                        gradient: const LinearGradient(colors: [Color(0xFF6B4EE6), Color(0xFF9B7EFA)]),
                        borderRadius: BorderRadius.circular(15),
                        boxShadow: [BoxShadow(color: const Color(0xFF6B4EE6).withOpacity(0.3), blurRadius: 10, offset: const Offset(0, 5))],
                      ),
                      child: Center(
                        child: _isSaving
                            ? const SizedBox(
                                width: 24,
                                height: 24,
                                child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2.5),
                              )
                            : const Text("Save Changes", style: TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.bold)),
                      ),
                    ),
                  ),
                ],
              ),
            ),
    );
  }

  Widget _buildEditField(String label, TextEditingController controller, IconData icon) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.only(left: 5, bottom: 8),
          child: Text(label, style: const TextStyle(fontWeight: FontWeight.bold, color: Color(0xFF2D2D3A), fontSize: 14)),
        ),
        Container(
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(15),
            boxShadow: [BoxShadow(color: Colors.purple.withOpacity(0.03), blurRadius: 15)],
          ),
          child: TextField(
            controller: controller,
            decoration: InputDecoration(
              prefixIcon: Icon(icon, color: const Color(0xFF6B4EE6)),
              border: InputBorder.none,
              contentPadding: const EdgeInsets.symmetric(vertical: 18, horizontal: 15),
            ),
          ),
        ),
      ],
    );
  }
}

// ==========================================
// 2. ACCOUNT SETTINGS SCREEN
// ==========================================
class AccountSettingsScreen extends StatefulWidget {
  const AccountSettingsScreen({super.key});
  @override
  State<AccountSettingsScreen> createState() => _AccountSettingsScreenState();
}

class _AccountSettingsScreenState extends State<AccountSettingsScreen> {
  bool _notificationsEnabled = true;
  bool _locationEnabled = true;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF4F3FB),
      appBar: AppBar(title: const Text("Account Settings", style: TextStyle(color: Colors.black87, fontWeight: FontWeight.bold)), backgroundColor: Colors.transparent, elevation: 0, iconTheme: const IconThemeData(color: Colors.black87), centerTitle: true),
      body: ListView(
        padding: const EdgeInsets.all(20),
        children: [
          const Text("Preferences", style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Colors.grey)),
          const SizedBox(height: 10),
          _buildToggleCard("Push Notifications", Icons.notifications_active_outlined, _notificationsEnabled, (val) => setState(() => _notificationsEnabled = val)),
          const SizedBox(height: 15),
          _buildToggleCard("Location Services (Nearby Mentors)", Icons.location_on_outlined, _locationEnabled, (val) => setState(() => _locationEnabled = val)),

          const SizedBox(height: 30),
          const Text("Security", style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Colors.grey)),
          const SizedBox(height: 10),

          Container(
            decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(20), boxShadow: [BoxShadow(color: Colors.purple.withOpacity(0.05), blurRadius: 15)]),
            child: ListTile(
              leading: const Icon(Icons.lock_outline, color: Color(0xFF6B4EE6)),
              title: const Text("Change Password", style: TextStyle(fontWeight: FontWeight.w600, color: Color(0xFF2D2D3A))),
              trailing: const Icon(Icons.arrow_forward_ios, size: 16, color: Colors.grey),
              onTap: () => ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text("Password reset link sent to email."))),
            ),
          )
        ],
      ),
    );
  }

  Widget _buildToggleCard(String title, IconData icon, bool value, Function(bool) onChanged) {
    return Container(
      decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(20), boxShadow: [BoxShadow(color: Colors.purple.withOpacity(0.05), blurRadius: 15)]),
      child: SwitchListTile(
        secondary: Icon(icon, color: const Color(0xFF6B4EE6)),
        title: Text(title, style: const TextStyle(fontWeight: FontWeight.w600, color: Color(0xFF2D2D3A))),
        value: value,
        activeColor: const Color(0xFF6B4EE6),
        onChanged: onChanged,
      ),
    );
  }
}

// ==========================================
// 3. HELP & SUPPORT SCREEN
// ==========================================
class HelpSupportScreen extends StatelessWidget {
  const HelpSupportScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF4F3FB),
      appBar: AppBar(title: const Text("Help & Support", style: TextStyle(color: Colors.black87, fontWeight: FontWeight.bold)), backgroundColor: Colors.transparent, elevation: 0, iconTheme: const IconThemeData(color: Colors.black87), centerTitle: true),
      body: ListView(
        padding: const EdgeInsets.all(20),
        children: [
          Container(
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              gradient: const LinearGradient(colors: [Color(0xFF6B4EE6), Color(0xFF9B7EFA)]),
              borderRadius: BorderRadius.circular(20),
              boxShadow: [BoxShadow(color: const Color(0xFF6B4EE6).withOpacity(0.3), blurRadius: 10, offset: const Offset(0, 5))],
            ),
            child: const Row(
              children: [
                Icon(Icons.support_agent, color: Colors.white, size: 40),
                SizedBox(width: 15),
                Expanded(child: Text("How can we help you today?", style: TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.bold))),
              ],
            ),
          ),
          const SizedBox(height: 30),
          const Text("Frequently Asked Questions", style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Colors.grey)),
          const SizedBox(height: 10),

          _buildFaqCard("How do I book a session?", "Go to the Dashboard or Nearby Mentors tab, select a mentor, and click the scheduling button to pick an available date."),
          const SizedBox(height: 10),
          _buildFaqCard("How does NLP matching work?", "Our system reads your skills and active projects from your profile, comparing them against mentor expertise to find the highest percentage match."),
          const SizedBox(height: 10),
          _buildFaqCard("Can I change my location?", "Yes, you can toggle location services in Account Settings or manually select a region in the map view."),

          const SizedBox(height: 30),
          OutlinedButton.icon(
            onPressed: () => ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text("Opening email client..."))),
            icon: const Icon(Icons.email_outlined),
            label: const Text("Contact Support Team"),
            style: OutlinedButton.styleFrom(
              padding: const EdgeInsets.symmetric(vertical: 15),
              foregroundColor: const Color(0xFF6B4EE6),
              side: const BorderSide(color: Color(0xFF6B4EE6)),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(15)),
            ),
          )
        ],
      ),
    );
  }

  Widget _buildFaqCard(String question, String answer) {
    return Container(
      decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(15), boxShadow: [BoxShadow(color: Colors.purple.withOpacity(0.03), blurRadius: 10)]),
      child: ExpansionTile(
        title: Text(question, style: const TextStyle(fontWeight: FontWeight.w600, color: Color(0xFF2D2D3A), fontSize: 14)),
        iconColor: const Color(0xFF6B4EE6),
        children: [
          Padding(
            padding: const EdgeInsets.only(left: 15, right: 15, bottom: 15),
            child: Text(answer, style: TextStyle(color: Colors.grey.shade600, fontSize: 13, height: 1.5)),
          )
        ],
      ),
    );
  }
}