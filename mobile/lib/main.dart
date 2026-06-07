// lib/main.dart
import 'dart:async';
import 'dart:convert';

import 'package:app_links/app_links.dart';
import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'screens/auth/login_screen.dart';
import 'screens/mentee/mentee_home.dart';
import 'screens/mentor/mentor_home.dart';
import 'config.dart';

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
class AuthWrapper extends StatefulWidget {
  const AuthWrapper({super.key});

  @override
  State<AuthWrapper> createState() => _AuthWrapperState();
}

class _AuthWrapperState extends State<AuthWrapper> {
  final AppLinks _appLinks = AppLinks();
  StreamSubscription<Uri>? _sub;

  @override
  void initState() {
    super.initState();
    _initDeepLinks();
  }

  Future<void> _initDeepLinks() async {
    final initial = await _appLinks.getInitialLink();
    if (initial != null) {
      await _handleOAuthLink(initial);
    }

    _sub = _appLinks.uriLinkStream.listen((uri) {
      _handleOAuthLink(uri);
    });
  }

  Future<void> _handleOAuthLink(Uri uri) async {
    if (uri.scheme != AppConfig.oauthRedirectScheme || uri.host != AppConfig.oauthRedirectPath) {
      return;
    }

    final token = uri.queryParameters['token'];
    if (token == null || token.isEmpty) {
      return;
    }

    int role = 1; // mentee by default
    final userJson = uri.queryParameters['user'];
    if (userJson != null && userJson.isNotEmpty) {
      final user = jsonDecode(userJson) as Map<String, dynamic>;
      final roleStr = user['role']?.toString().toLowerCase();
      if (roleStr == 'mentor') {
        role = 2;
      }
    }

    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('auth_token', token);

    if (!mounted) return;
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _navigateToRole(role);
    });
  }

  void _navigateToRole(int role) {
    if (role == 2) {
      Navigator.of(context).pushReplacement(
        MaterialPageRoute(
          builder: (context) => MentorDashboard(
            onLogout: () {
              Navigator.of(context).pushReplacement(
                MaterialPageRoute(builder: (context) => const AuthWrapper()),
              );
            },
          ),
        ),
      );
      return;
    }

    Navigator.of(context).pushReplacement(
      MaterialPageRoute(
        builder: (context) => MenteeDashboard(
          onLogout: () {
            Navigator.of(context).pushReplacement(
              MaterialPageRoute(builder: (context) => const AuthWrapper()),
            );
          },
        ),
      ),
    );
  }

  @override
  void dispose() {
    _sub?.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return LoginPage(onLogin: _navigateToRole);
  }
}