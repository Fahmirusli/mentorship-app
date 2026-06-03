// lib/screens/auth/login_screen.dart
import 'package:flutter/material.dart';
import 'register_screen.dart';
import 'forgot_password_screen.dart';
import '../services/api_service.dart';

class LoginPage extends StatefulWidget {
  final Function(int) onLogin;
  const LoginPage({super.key, required this.onLogin});

  @override
  State<LoginPage> createState() => _LoginPageState();
}

class _LoginPageState extends State<LoginPage> {
  final TextEditingController _emailController = TextEditingController();
  final TextEditingController _passwordController = TextEditingController();

  bool _isLoading = false;

  Future<void> _handleRealLogin() async {
    setState(() => _isLoading = true); // Optional: you can show a loading spinner with this later

    final email = _emailController.text.trim();
    final password = _passwordController.text.trim();

    // Prevent sending empty requests to Laravel
    if (email.isEmpty || password.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text("Please fill in all fields")));
      setState(() => _isLoading = false);
      return;
    }

    // Ping the Laravel API
    final result = await ApiService.login(email, password);

    setState(() => _isLoading = false);

    if (!mounted) return; // Safety check

    if (result['success'] == true) {
      // It worked! Laravel accepted it. Route to the correct dashboard based on role.
      widget.onLogin(result['role']);
    } else {
      // It failed! Show the error message from Laravel
      ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(result['message']),
            backgroundColor: Colors.redAccent,
          )
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      // Global scaffold background color handles the tint
      body: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.symmetric(horizontal: 30, vertical: 20),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                // App Logo / Header
                Container(
                  padding: const EdgeInsets.all(20),
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    gradient: const LinearGradient(colors: [Color(0xFF6B4EE6), Color(0xFF9B7EFA)]),
                    boxShadow: [BoxShadow(color: const Color(0xFF6B4EE6).withOpacity(0.3), blurRadius: 20, spreadRadius: 5)],
                  ),
                  child: const Icon(Icons.menu_book_rounded, size: 60, color: Colors.white),
                ),
                const SizedBox(height: 20),
                const Text("MentorCore", style: TextStyle(fontSize: 32, fontWeight: FontWeight.bold, color: Color(0xFF2D2D3A))),
                const Text("Accelerate your journey", style: TextStyle(color: Colors.grey, fontSize: 16)),
                const SizedBox(height: 40),

                // Neumorphic Input Fields
                _buildInputField(Icons.email_outlined, "Email Address", _emailController, false),
                const SizedBox(height: 15),
                _buildInputField(Icons.lock_outline, "Password", _passwordController, true),

                Align(
                  alignment: Alignment.centerRight,
                  child: TextButton(
                    onPressed: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const ForgotPasswordScreen())),
                    style: TextButton.styleFrom(foregroundColor: const Color(0xFF6B4EE6)),
                    child: const Text("Forgot Password?", style: TextStyle(fontWeight: FontWeight.bold)),
                  ),
                ),
                const SizedBox(height: 10),

                // Login Button
                InkWell(
                  onTap: _isLoading ? null : _handleRealLogin,
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
                      child: _isLoading
                          ? const SizedBox(width: 22, height: 22, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2.5))
                          : const Text("LOG IN", style: TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.bold, letterSpacing: 1.2)),
                    ),
                  ),
                ),

                const SizedBox(height: 30),
                const Row(
                  children: [
                    Expanded(child: Divider(color: Colors.grey)),
                    Padding(padding: EdgeInsets.symmetric(horizontal: 10), child: Text("Or continue with", style: TextStyle(color: Colors.grey))),
                    Expanded(child: Divider(color: Colors.grey)),
                  ],
                ),
                const SizedBox(height: 30),

                // Social Auth Buttons
                Row(
                  children: [
                    Expanded(child: _socialButton("Google", Colors.redAccent, Icons.g_mobiledata_rounded)),
                    const SizedBox(width: 15),
                    Expanded(child: _socialButton("GitHub", Colors.black87, Icons.code)),
                  ],
                ),

                const SizedBox(height: 30),
                Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    const Text("Don't have an account?", style: TextStyle(color: Colors.grey)),
                    TextButton(
                      onPressed: () => Navigator.push(context, MaterialPageRoute(builder: (context) => const RegisterScreen())),
                      style: TextButton.styleFrom(foregroundColor: const Color(0xFF6B4EE6)),
                      child: const Text("Register Now", style: TextStyle(fontWeight: FontWeight.bold)),
                    ),
                  ],
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildInputField(IconData icon, String hint, TextEditingController controller, bool isPassword) {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(15),
        boxShadow: [BoxShadow(color: Colors.purple.withOpacity(0.03), blurRadius: 15)],
      ),
      child: TextField(
        controller: controller,
        obscureText: isPassword,
        decoration: InputDecoration(
          hintText: hint,
          hintStyle: TextStyle(color: Colors.grey.shade400),
          prefixIcon: Icon(icon, color: const Color(0xFF6B4EE6)),
          border: InputBorder.none,
          contentPadding: const EdgeInsets.symmetric(vertical: 18, horizontal: 15),
        ),
      ),
    );
  }

  Widget _socialButton(String platform, Color color, IconData icon) {
    return InkWell(
      onTap: () {
        final provider = platform.toLowerCase();
        ApiService.startOAuth(provider).then((opened) {
          if (!opened) {
            ScaffoldMessenger.of(context).showSnackBar(
              SnackBar(content: Text("Unable to open $platform login.")),
            );
          }
        });
      },
      borderRadius: BorderRadius.circular(12),
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 12),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: Colors.grey.shade200),
          boxShadow: [BoxShadow(color: Colors.black.withOpacity(0.02), blurRadius: 5)],
        ),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(icon, color: color, size: 28),
            const SizedBox(width: 5),
            Text(platform, style: const TextStyle(fontWeight: FontWeight.bold, color: Color(0xFF2D2D3A))),
          ],
        ),
      ),
    );
  }
}