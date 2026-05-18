// lib/screens/auth/forgot_password_screen.dart
import 'package:flutter/material.dart';
import '../services/api_service.dart';

class ForgotPasswordScreen extends StatefulWidget {
  const ForgotPasswordScreen({super.key});

  @override
  State<ForgotPasswordScreen> createState() => _ForgotPasswordScreenState();
}

class _ForgotPasswordScreenState extends State<ForgotPasswordScreen> {
  final _emailController = TextEditingController();
  final _tacController = TextEditingController();
  final _passwordController = TextEditingController();
  final _confirmPasswordController = TextEditingController();

  int _step = 1; // 1=email, 2=TAC, 3=new password
  bool _isLoading = false;
  String _email = '';

  Future<void> _requestReset() async {
    final email = _emailController.text.trim();
    if (email.isEmpty) {
      _showSnack('Please enter your email');
      return;
    }

    setState(() => _isLoading = true);
    try {
      final response = await ApiService.forgotPassword(email);
      if (!mounted) return;
      if (response['success'] == true) {
        setState(() {
          _email = email;
          _step = 2;
        });
        _showSnack('Verification code sent to your email', isError: false);
      } else {
        _showSnack(response['message'] ?? 'Failed to send code');
      }
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  Future<void> _verifyCode() async {
    final tac = _tacController.text.trim();
    if (tac.isEmpty || tac.length != 6) {
      _showSnack('Please enter the 6-digit code');
      return;
    }

    setState(() => _isLoading = true);
    try {
      final response = await ApiService.verifyResetCode(_email, tac);
      if (!mounted) return;
      if (response['success'] == true) {
        setState(() => _step = 3);
        _showSnack('Code verified! Set your new password', isError: false);
      } else {
        _showSnack(response['message'] ?? 'Invalid code');
      }
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  Future<void> _resetPassword() async {
    final password = _passwordController.text.trim();
    final confirm = _confirmPasswordController.text.trim();
    final tac = _tacController.text.trim();

    if (password.isEmpty || password.length < 8) {
      _showSnack('Password must be at least 8 characters');
      return;
    }
    if (password != confirm) {
      _showSnack('Passwords do not match');
      return;
    }

    setState(() => _isLoading = true);
    try {
      final response = await ApiService.resetPassword(_email, tac, password, confirm);
      if (!mounted) return;
      if (response['success'] == true) {
        _showSnack('Password reset successfully!', isError: false);
        await Future.delayed(const Duration(seconds: 1));
        if (mounted) Navigator.pop(context);
      } else {
        _showSnack(response['message'] ?? 'Reset failed');
      }
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  void _showSnack(String msg, {bool isError = true}) {
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(
      content: Text(msg),
      backgroundColor: isError ? Colors.redAccent : const Color(0xFF6B4EE6),
    ));
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF4F3FB),
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back_ios, color: Color(0xFF2D2D3A)),
          onPressed: () {
            if (_step > 1) {
              setState(() => _step--);
            } else {
              Navigator.pop(context);
            }
          },
        ),
      ),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.symmetric(horizontal: 30, vertical: 20),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Header icon
              Center(
                child: Container(
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    gradient: const LinearGradient(colors: [Color(0xFF6B4EE6), Color(0xFF9B7EFA)]),
                    boxShadow: [BoxShadow(color: const Color(0xFF6B4EE6).withOpacity(0.3), blurRadius: 15)],
                  ),
                  child: Icon(
                    _step == 1 ? Icons.email_outlined : _step == 2 ? Icons.pin_outlined : Icons.lock_reset_rounded,
                    size: 40,
                    color: Colors.white,
                  ),
                ),
              ),
              const SizedBox(height: 20),
              Center(
                child: Text(
                  _step == 1 ? 'Forgot Password' : _step == 2 ? 'Enter Verification Code' : 'Set New Password',
                  style: const TextStyle(fontSize: 26, fontWeight: FontWeight.bold, color: Color(0xFF2D2D3A)),
                ),
              ),
              const SizedBox(height: 8),
              Center(
                child: Text(
                  _step == 1
                      ? 'Enter your email to receive a verification code'
                      : _step == 2
                          ? 'We sent a 6-digit code to $_email'
                          : 'Create a strong new password',
                  textAlign: TextAlign.center,
                  style: const TextStyle(color: Colors.grey, fontSize: 14),
                ),
              ),
              const SizedBox(height: 30),

              // Step indicator
              Row(
                children: [1, 2, 3].map((s) {
                  final isActive = s <= _step;
                  return Expanded(
                    child: Container(
                      margin: const EdgeInsets.symmetric(horizontal: 4),
                      height: 4,
                      decoration: BoxDecoration(
                        borderRadius: BorderRadius.circular(2),
                        color: isActive ? const Color(0xFF6B4EE6) : Colors.grey.shade300,
                      ),
                    ),
                  );
                }).toList(),
              ),
              const SizedBox(height: 30),

              // Step content
              if (_step == 1) ...[
                _buildField(Icons.email_outlined, 'Email Address', _emailController, false),
                const SizedBox(height: 20),
                _buildButton('Send Code', _requestReset),
              ],
              if (_step == 2) ...[
                _buildField(Icons.pin_outlined, '6-Digit Code', _tacController, false, maxLength: 6, keyboardType: TextInputType.number),
                const SizedBox(height: 20),
                _buildButton('Verify Code', _verifyCode),
              ],
              if (_step == 3) ...[
                _buildField(Icons.lock_outline, 'New Password', _passwordController, true),
                const SizedBox(height: 15),
                _buildField(Icons.lock_outline, 'Confirm Password', _confirmPasswordController, true),
                const SizedBox(height: 20),
                _buildButton('Reset Password', _resetPassword),
              ],
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildField(IconData icon, String hint, TextEditingController controller, bool isPassword, {int? maxLength, TextInputType? keyboardType}) {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(15),
        boxShadow: [BoxShadow(color: Colors.purple.withOpacity(0.03), blurRadius: 15)],
      ),
      child: TextField(
        controller: controller,
        obscureText: isPassword,
        maxLength: maxLength,
        keyboardType: keyboardType,
        decoration: InputDecoration(
          hintText: hint,
          hintStyle: TextStyle(color: Colors.grey.shade400),
          prefixIcon: Icon(icon, color: const Color(0xFF6B4EE6)),
          border: InputBorder.none,
          counterText: '',
          contentPadding: const EdgeInsets.symmetric(vertical: 18, horizontal: 15),
        ),
      ),
    );
  }

  Widget _buildButton(String label, VoidCallback onTap) {
    return InkWell(
      onTap: _isLoading ? null : onTap,
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
              : Text(label, style: const TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.bold, letterSpacing: 1.2)),
        ),
      ),
    );
  }
}
