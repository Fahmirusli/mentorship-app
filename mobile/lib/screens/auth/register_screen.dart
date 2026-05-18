// lib/screens/auth/register_screen.dart
import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../mentee/mentee_home.dart';
import '../mentor/mentor_home.dart';
import '../auth/verification_screen.dart';

class RegisterScreen extends StatefulWidget {
  const RegisterScreen({super.key});

  @override
  State<RegisterScreen> createState() => _RegisterScreenState();
}

class _RegisterScreenState extends State<RegisterScreen> {
  final TextEditingController _nameController = TextEditingController();
  final TextEditingController _emailController = TextEditingController();
  final TextEditingController _passwordController = TextEditingController();
  final TextEditingController _confirmPasswordController = TextEditingController();

  String _selectedRole = 'mentee';
  bool _isLoading = false;

  Future<void> _handleRegister() async {
    final name = _nameController.text.trim();
    final email = _emailController.text.trim();
    final password = _passwordController.text.trim();
    final confirmPassword = _confirmPasswordController.text.trim();

    if (name.isEmpty || email.isEmpty || password.isEmpty || confirmPassword.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text("Please fill all fields")));
      return;
    }

    if (password != confirmPassword) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text("Passwords do not match")));
      return;
    }

    setState(() => _isLoading = true);

    final result = await ApiService.register(name, email, password, confirmPassword, _selectedRole);

    setState(() => _isLoading = false);

    if (!mounted) return;

    if (result['success'] == true) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text("Code sent! Check your email.", style: TextStyle(color: Colors.white)), backgroundColor: Colors.green));

      // NEW ROUTING: Send them to the TAC Verification Screen
      Navigator.of(context).push(MaterialPageRoute(
        builder: (context) => VerificationScreen(email: email), // We will create this file next!
      ));

    } else {
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(result['message']), backgroundColor: Colors.redAccent));
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF4F3FB),
      body: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.symmetric(horizontal: 30, vertical: 20),
            child: Container(
              padding: const EdgeInsets.all(25),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(25),
                boxShadow: [BoxShadow(color: Colors.purple.withOpacity(0.05), blurRadius: 20)],
              ),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Center(
                    child: Column(
                      children: [
                        Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Container(
                              padding: const EdgeInsets.all(10),
                              decoration: BoxDecoration(color: const Color(0xFF6B4EE6), borderRadius: BorderRadius.circular(10)),
                              child: const Icon(Icons.school, color: Colors.white, size: 24),
                            ),
                            const SizedBox(width: 10),
                            const Text("MentorCore", style: TextStyle(fontSize: 26, fontWeight: FontWeight.bold, color: Color(0xFF6B4EE6))),
                          ],
                        ),
                        const SizedBox(height: 10),
                        Text("Join MentorCore and start your journey", style: TextStyle(color: Colors.grey.shade600, fontSize: 14)),
                      ],
                    ),
                  ),
                  const SizedBox(height: 30),

                  _buildLabel("Full Name", Icons.person_outline),
                  _buildInputField("John Doe", _nameController, false),
                  const SizedBox(height: 15),

                  _buildLabel("Email", Icons.email_outlined),
                  _buildInputField("your@email.com", _emailController, false),
                  const SizedBox(height: 15),

                  _buildLabel("Password", Icons.lock_outline),
                  _buildInputField("••••••••", _passwordController, true),
                  const SizedBox(height: 15),

                  _buildLabel("Confirm Password", Icons.lock_outline),
                  _buildInputField("••••••••", _confirmPasswordController, true),
                  const SizedBox(height: 20),

                  const Text("I want to join as", style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: Color(0xFF2D2D3A))),
                  const SizedBox(height: 10),
                  Row(
                    children: [
                      Expanded(child: _roleButton('mentee', "Mentee")),
                      const SizedBox(width: 15),
                      Expanded(child: _roleButton('mentor', "Mentor")),
                    ],
                  ),
                  const SizedBox(height: 25),

                  InkWell(
                    onTap: _isLoading ? null : _handleRegister,
                    borderRadius: BorderRadius.circular(10),
                    child: Container(
                      width: double.infinity,
                      padding: const EdgeInsets.symmetric(vertical: 16),
                      decoration: BoxDecoration(color: const Color(0xFF6B4EE6), borderRadius: BorderRadius.circular(10)),
                      child: Center(
                        child: _isLoading
                            ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                            : const Text("Create Account", style: TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.bold)),
                      ),
                    ),
                  ),
                  const SizedBox(height: 25),

                  const Row(
                    children: [
                      Expanded(child: Divider(color: Colors.grey)),
                      Padding(padding: EdgeInsets.symmetric(horizontal: 10), child: Text("Or sign up with", style: TextStyle(color: Colors.grey, fontSize: 12))),
                      Expanded(child: Divider(color: Colors.grey)),
                    ],
                  ),
                  const SizedBox(height: 20),

                  Row(
                    children: [
                      Expanded(child: _socialButton("Google", Colors.redAccent, Icons.g_mobiledata_rounded)),
                      const SizedBox(width: 15),
                      Expanded(child: _socialButton("GitHub", Colors.black87, Icons.code)),
                    ],
                  ),
                  const SizedBox(height: 25),

                  Center(
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        const Text("Already have an account? ", style: TextStyle(color: Colors.grey)),
                        InkWell(
                          onTap: () => Navigator.pop(context),
                          child: const Text("Login", style: TextStyle(color: Color(0xFF6B4EE6), fontWeight: FontWeight.bold)),
                        ),
                      ],
                    ),
                  )
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildLabel(String text, IconData icon) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 5),
      child: Row(
        children: [
          Icon(icon, size: 16, color: Colors.black87),
          const SizedBox(width: 5),
          Text(text, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: Color(0xFF2D2D3A))),
        ],
      ),
    );
  }

  Widget _buildInputField(String hint, TextEditingController controller, bool isPassword) {
    return Container(
      decoration: BoxDecoration(border: Border.all(color: Colors.grey.shade300), borderRadius: BorderRadius.circular(10)),
      child: TextField(
        controller: controller,
        obscureText: isPassword,
        decoration: InputDecoration(hintText: hint, hintStyle: TextStyle(color: Colors.grey.shade400), border: InputBorder.none, contentPadding: const EdgeInsets.symmetric(vertical: 15, horizontal: 15)),
      ),
    );
  }

  Widget _roleButton(String roleValue, String title) {
    bool isSelected = _selectedRole == roleValue;
    return InkWell(
      onTap: () => setState(() => _selectedRole = roleValue),
      borderRadius: BorderRadius.circular(10),
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 12),
        decoration: BoxDecoration(
          color: isSelected ? const Color(0xFF6B4EE6).withOpacity(0.1) : Colors.white,
          borderRadius: BorderRadius.circular(10),
          border: Border.all(color: isSelected ? const Color(0xFF6B4EE6) : Colors.grey.shade300, width: isSelected ? 2 : 1),
        ),
        child: Center(child: Text(title, style: TextStyle(fontWeight: FontWeight.bold, color: isSelected ? const Color(0xFF6B4EE6) : Colors.black87))),
      ),
    );
  }

  Widget _socialButton(String platform, Color color, IconData icon) {
    return InkWell(
      onTap: () { ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text("Connecting to $platform API..."))); },
      borderRadius: BorderRadius.circular(10),
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 12),
        decoration: BoxDecoration(borderRadius: BorderRadius.circular(10), border: Border.all(color: Colors.grey.shade300)),
        child: Icon(icon, color: color, size: 28),
      ),
    );
  }
}