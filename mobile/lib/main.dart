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
  runApp(const UpliftsApp());
}

class UpliftsApp extends StatefulWidget {
  const UpliftsApp({super.key});

  static void setThemeMode(BuildContext context, ThemeMode mode) {
    final _UpliftsAppState? state = context.findAncestorStateOfType<_UpliftsAppState>();
    state?.changeTheme(mode);
  }

  @override
  State<UpliftsApp> createState() => _UpliftsAppState();
}

class _UpliftsAppState extends State<UpliftsApp> {
  ThemeMode _themeMode = ThemeMode.system;

  @override
  void initState() {
    super.initState();
    _loadTheme();
  }

  Future<void> _loadTheme() async {
    final prefs = await SharedPreferences.getInstance();
    final isDark = prefs.getBool('is_dark_mode');
    if (isDark != null) {
      setState(() {
        _themeMode = isDark ? ThemeMode.dark : ThemeMode.light;
      });
    }
  }

  void changeTheme(ThemeMode mode) async {
    setState(() {
      _themeMode = mode;
    });
    final prefs = await SharedPreferences.getInstance();
    if (mode == ThemeMode.dark) {
      prefs.setBool('is_dark_mode', true);
    } else if (mode == ThemeMode.light) {
      prefs.setBool('is_dark_mode', false);
    } else {
      prefs.remove('is_dark_mode');
    }
  }

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Uplifts',
      debugShowCheckedModeBanner: false,
      themeMode: _themeMode,
      theme: ThemeData(
        useMaterial3: true,
        scaffoldBackgroundColor: const Color(0xFFF4F3FB),
        primaryColor: const Color(0xFF6B4EE6),
        colorScheme: ColorScheme.fromSeed(
          seedColor: const Color(0xFF6B4EE6),
          primary: const Color(0xFF6B4EE6),
          brightness: Brightness.light,
        ),
        textTheme: GoogleFonts.poppinsTextTheme().copyWith(
          displayLarge: const TextStyle(color: Color(0xFF2D2D3A), fontWeight: FontWeight.bold),
          titleLarge: const TextStyle(color: Color(0xFF2D2D3A), fontWeight: FontWeight.bold, fontSize: 18),
          bodyMedium: const TextStyle(color: Color(0xFF2D2D3A), fontSize: 14),
        ),
      ),
      darkTheme: ThemeData(
        useMaterial3: true,
        scaffoldBackgroundColor: const Color(0xFF121212),
        primaryColor: const Color(0xFF6B4EE6),
        colorScheme: ColorScheme.fromSeed(
          seedColor: const Color(0xFF6B4EE6),
          primary: const Color(0xFF6B4EE6),
          brightness: Brightness.dark,
        ),
        textTheme: GoogleFonts.poppinsTextTheme(ThemeData.dark().textTheme).copyWith(
          displayLarge: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
          titleLarge: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 18),
          bodyMedium: const TextStyle(color: Colors.white70, fontSize: 14),
        ),
      ),
      home: const AuthWrapper(),
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