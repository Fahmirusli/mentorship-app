// lib/screens/auth/verification_screen.dart
import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../mentee/mentee_home.dart';
import '../mentor/mentor_home.dart';
import '../shared/profile_menu_screens.dart';

class VerificationScreen extends StatefulWidget {
  final String email; // We pass the email from the register screen
  const VerificationScreen({super.key, required this.email});

  @override
  State<VerificationScreen> createState() => _VerificationScreenState();
}

class _VerificationScreenState extends State<VerificationScreen> {
  final TextEditingController _tacController = TextEditingController();
  bool _isLoading = false;

  Future<void> _handleVerify() async {
    final tac = _tacController.text.trim();

    if (tac.length != 6) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text("Please enter the 6-digit code.")));
      return;
    }

    setState(() => _isLoading = true);

    // Call the new verify API
    final result = await ApiService.verifyEmail(widget.email, tac);

    setState(() => _isLoading = false);

    if (!mounted) return;

    if (result['success'] == true) {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text("Email Verified!", style: TextStyle(color: Colors.white)), backgroundColor: Colors.green));

      if (result['profile_incomplete'] == true) {
        Navigator.of(context).pushAndRemoveUntil(
          MaterialPageRoute(
            builder: (context) => EditProfileScreen(
              requireCompletion: true,
              roleAfterCompletion: result['role'] ?? 1,
            ),
          ),
          (route) => false,
        );
        return;
      }

      // Now we push them to the dashboard, and remove all previous screens so they can't hit "back" to register again
      if (result['role'] == 1) { // Mentee
        Navigator.of(context).pushAndRemoveUntil(
            MaterialPageRoute(builder: (context) => MenteeDashboard(onLogout: () {})),
                (route) => false
        );
      } else { // Mentor
        Navigator.of(context).pushAndRemoveUntil(
            MaterialPageRoute(builder: (context) => MentorDashboard(onLogout: () {})),
                (route) => false
        );
      }
    } else {
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(result['message']), backgroundColor: Colors.redAccent));
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF4F3FB),
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        iconTheme: const IconThemeData(color: Colors.black87),
      ),
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
                children: [
                  const Icon(Icons.mark_email_read_outlined, size: 60, color: Color(0xFF6B4EE6)),
                  const SizedBox(height: 20),
                  const Text("Verify Your Email", style: TextStyle(fontSize: 24, fontWeight: FontWeight.bold, color: Color(0xFF2D2D3A))),
                  const SizedBox(height: 10),
                  Text("We sent a 6-digit TAC code to\n${widget.email}", textAlign: TextAlign.center, style: TextStyle(color: Colors.grey.shade600, height: 1.5)),
                  const SizedBox(height: 30),

                  // TAC Input Field
                  Container(
                    decoration: BoxDecoration(
                      border: Border.all(color: Colors.grey.shade300),
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: TextField(
                      controller: _tacController,
                      keyboardType: TextInputType.number,
                      textAlign: TextAlign.center,
                      maxLength: 6,
                      style: const TextStyle(fontSize: 24, letterSpacing: 8, fontWeight: FontWeight.bold),
                      decoration: InputDecoration(
                        counterText: "", // Hides the "0/6" counter
                        hintText: "000000",
                        hintStyle: TextStyle(color: Colors.grey.shade300, letterSpacing: 8),
                        border: InputBorder.none,
                        contentPadding: const EdgeInsets.symmetric(vertical: 15),
                      ),
                    ),
                  ),
                  const SizedBox(height: 30),

                  // Verify Button
                  InkWell(
                    onTap: _isLoading ? null : _handleVerify,
                    borderRadius: BorderRadius.circular(10),
                    child: Container(
                      width: double.infinity,
                      padding: const EdgeInsets.symmetric(vertical: 16),
                      decoration: BoxDecoration(color: const Color(0xFF6B4EE6), borderRadius: BorderRadius.circular(10)),
                      child: Center(
                        child: _isLoading
                            ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                            : const Text("Verify & Login", style: TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.bold)),
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}