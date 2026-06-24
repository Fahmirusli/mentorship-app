import 'package:flutter/material.dart';
import '../mentee/history_screen.dart'; // Add this line!
import '../mentee/resources_screen.dart';
import '../services/api_service.dart';
import 'profile_menu_screens.dart';

class ProfileScreen extends StatefulWidget {
  final VoidCallback onLogout;
  const ProfileScreen({super.key, required this.onLogout});

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  String _name = "Loading...";
  String _bio = "";
  String? _profileImageUrl;
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _loadProfile();
  }

  Future<void> _loadProfile() async {
    final userData = await ApiService.getProfile();
    if (userData != null && mounted) {
      setState(() {
        _name = userData['name'] ?? 'User';
        _bio = userData['bio'] ?? '';
        _profileImageUrl = userData['profile_image'];
        _isLoading = false;
      });
    } else if (mounted) {
      setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF4F3FB),
      appBar: AppBar(
        title: const Text("My Profile", style: TextStyle(color: Colors.black87, fontWeight: FontWeight.bold)),
        backgroundColor: Colors.transparent,
        elevation: 0,
        centerTitle: true,
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: Color(0xFF6B4EE6)))
          : ListView(
              padding: const EdgeInsets.all(20),
              children: [
                // Vibrant Profile Header
                Center(
                  child: Container(
                    decoration: BoxDecoration(
                      shape: BoxShape.circle,
                      gradient: LinearGradient(colors: [Color(0xFF6B4EE6), Color(0xFF9B7EFA)]),
                      boxShadow: [BoxShadow(color: Colors.purpleAccent, blurRadius: 15, spreadRadius: 2)],
                    ),
                    child: CircleAvatar(
                      radius: 50,
                      backgroundColor: Colors.transparent,
                      backgroundImage: _profileImageUrl != null && _profileImageUrl!.isNotEmpty
                          ? NetworkImage(_profileImageUrl!)
                          : null,
                      child: _profileImageUrl == null || _profileImageUrl!.isEmpty
                          ? const Icon(Icons.person, size: 50, color: Colors.white)
                          : null,
                    ),
                  ),
                ),
                const SizedBox(height: 15),
                Center(child: Text(_name, style: const TextStyle(fontSize: 24, fontWeight: FontWeight.bold, color: Color(0xFF2D2D3A)))),
                Center(child: Text(_bio.isNotEmpty ? _bio : "No bio set", style: const TextStyle(color: Colors.grey))),
                const SizedBox(height: 40),

                // Elevated Menu Options with Hover/Splash
                Container(
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(20),
                    boxShadow: [BoxShadow(color: Colors.purple.withOpacity(0.05), blurRadius: 15)],
                  ),
                  child: Column(
                    children: [
                      _buildMenuTile(context, Icons.person_outline, "Edit Profile"),
                      const Divider(height: 1, color: Color(0xFFF4F3FB)),
                      _buildMenuTile(context, Icons.history, "Mentorship History"),
                      const Divider(height: 1, color: Color(0xFFF4F3FB)),
                      _buildMenuTile(context, Icons.folder_shared_outlined, "Resources"),
                      const Divider(height: 1, color: Color(0xFFF4F3FB)),
                      _buildMenuTile(context, Icons.settings_outlined, "Account Settings"),
                      const Divider(height: 1, color: Color(0xFFF4F3FB)),
                      _buildMenuTile(context, Icons.help_outline, "Help & Support"),
                    ],
                  ),
                ),

                const SizedBox(height: 40),

                // Stylish Logout Button
                InkWell(
                  onTap: widget.onLogout,
                  borderRadius: BorderRadius.circular(15),
                  splashColor: Colors.red.withOpacity(0.2),
                  child: Container(
                    padding: const EdgeInsets.symmetric(vertical: 15),
                    decoration: BoxDecoration(
                      color: const Color(0xFFFFF0F0),
                      borderRadius: BorderRadius.circular(15),
                      border: Border.all(color: const Color(0xFFFFD6D6)),
                    ),
                    child: const Row(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Icon(Icons.logout, color: Colors.red),
                        SizedBox(width: 10),
                        Text("Log Out", style: TextStyle(color: Colors.red, fontWeight: FontWeight.bold, fontSize: 16)),
                      ],
                    ),
                  ),
                )
              ],
            ),
    );
  }

  Widget _buildMenuTile(BuildContext context, IconData icon, String title) {
    return ListTile(
      leading: Icon(icon, color: const Color(0xFF6B4EE6)),
      title: Text(title, style: const TextStyle(fontWeight: FontWeight.w600, color: Color(0xFF2D2D3A))),
      trailing: const Icon(Icons.arrow_forward_ios, size: 16, color: Colors.grey),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
      onTap: () async {
        // Here is the routing logic for ALL buttons!
        if (title == "Mentorship History") {
          Navigator.push(context, MaterialPageRoute(builder: (context) => const HistoryScreen()));
        } else if (title == "Resources") {
          Navigator.push(context, MaterialPageRoute(builder: (context) => const MenteeResourcesScreen()));
        } else if (title == "Edit Profile") {
          // Wait for the edit screen to return, then reload profile
          await Navigator.push(
            context,
            MaterialPageRoute(builder: (context) => const EditProfileScreen()),
          );
          // Always reload profile when coming back from edit screen
          _loadProfile();
        } else if (title == "Account Settings") {
          Navigator.push(context, MaterialPageRoute(builder: (context) => const AccountSettingsScreen()));
        } else if (title == "Help & Support") {
          Navigator.push(context, MaterialPageRoute(builder: (context) => const HelpSupportScreen()));
        }
      },
    );
  }
}