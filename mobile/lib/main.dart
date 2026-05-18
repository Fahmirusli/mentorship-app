// lib/main.dart
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'screens/auth/login_screen.dart';
import 'screens/mentee/mentee_home.dart';
import 'screens/mentor/mentor_home.dart';

void main() {
  runApp(const MentorCoreApp());
}

class MentorCoreApp extends StatelessWidget {
  const MentorCoreApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'MentorCore',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        useMaterial3: true,
        scaffoldBackgroundColor: const Color(0xFFF4F3FB),
        primaryColor: const Color(0xFF6B4EE6),
        colorScheme: ColorScheme.fromSeed(
          seedColor: const Color(0xFF6B4EE6),
          primary: const Color(0xFF6B4EE6),
        ),
        textTheme: GoogleFonts.poppinsTextTheme().copyWith(
          displayLarge: const TextStyle(color: Color(0xFF2D2D3A), fontWeight: FontWeight.bold),
          titleLarge: const TextStyle(color: Color(0xFF2D2D3A), fontWeight: FontWeight.bold, fontSize: 18),
          bodyMedium: const TextStyle(color: Color(0xFF2D2D3A), fontSize: 14),
        ),
        // We completely removed cardTheme! Our UI uses custom Containers instead, so this is no longer needed.
      ),
      home: const AuthWrapper(), // Uses the clean routing wrapper below
    );
  }
}

// A clean wrapper to handle the routing without the messy Builder hack
class AuthWrapper extends StatelessWidget {
  const AuthWrapper({super.key});

  @override
  Widget build(BuildContext context) {
    return LoginPage(
      onLogin: (role) {
        if (role == 1) {
          // Route to Mentee
          Navigator.of(context).pushReplacement(
            MaterialPageRoute(
              builder: (context) => MenteeDashboard(
                onLogout: () {
                  // Cleanly route back to the AuthWrapper on logout
                  Navigator.of(context).pushReplacement(
                    MaterialPageRoute(builder: (context) => const AuthWrapper()),
                  );
                },
              ),
            ),
          );
        } else if (role == 2) {
          // Route to Mentor
          Navigator.of(context).pushReplacement(
            MaterialPageRoute(
              builder: (context) => MentorDashboard(
                onLogout: () {
                  // Cleanly route back to the AuthWrapper on logout
                  Navigator.of(context).pushReplacement(
                    MaterialPageRoute(builder: (context) => const AuthWrapper()),
                  );
                },
              ),
            ),
          );
        }
      },
    );
  }
}