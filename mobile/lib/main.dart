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
import 'screens/services/api_service.dart';
import 'config.dart';

import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
// import 'firebase_options.dart'; // User needs to generate this using flutterfire configure

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  
  // Initialize Firebase (Requires flutterfire configure)
  try {
    await Firebase.initializeApp(
      // options: DefaultFirebaseOptions.currentPlatform,
    );
    _setupFCM();
  } catch (e) {
    debugPrint('Firebase init failed: $e');
  }

  runApp(const UpliftsApp());
}

Future<void> _setupFCM() async {
  final messaging = FirebaseMessaging.instance;
  await messaging.requestPermission();
  
  // Setup local notifications for foreground audio
  final FlutterLocalNotificationsPlugin flutterLocalNotificationsPlugin = FlutterLocalNotificationsPlugin();
  const AndroidInitializationSettings initializationSettingsAndroid = AndroidInitializationSettings('@mipmap/ic_launcher');
  const InitializationSettings initializationSettings = InitializationSettings(android: initializationSettingsAndroid);
  await flutterLocalNotificationsPlugin.initialize(settings: initializationSettings);

  FirebaseMessaging.onMessage.listen((RemoteMessage message) {
    RemoteNotification? notification = message.notification;
    AndroidNotification? android = message.notification?.android;
    if (notification != null && android != null) {
      flutterLocalNotificationsPlugin.show(
        id: notification.hashCode,
        title: notification.title,
        body: notification.body,
        notificationDetails: const NotificationDetails(
          android: AndroidNotificationDetails(
            'uplifts_channel',
            'Uplifts Notifications',
            importance: Importance.max,
            priority: Priority.high,
            playSound: true,
          ),
        ),
      );
    }
  });
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
  ThemeMode _themeMode = ThemeMode.light;

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
    } else {
      setState(() {
        _themeMode = ThemeMode.light;
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
      // darkTheme is removed to prevent text visibility issues caused by hardcoded black text on dark backgrounds
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
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _checkSavedLogin();
    _initDeepLinks();
  }

  Future<void> _checkSavedLogin() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString('auth_token');
      
      if (token != null && token.isNotEmpty) {
        // Token exists, verify it by trying to get the user profile
        final userProfile = await ApiService.getProfile();
        
        if (userProfile != null && mounted) {
          final roleStr = userProfile['role']?.toString().toLowerCase();
          _navigateToRole(roleStr == 'mentor' ? 2 : 1);
          return;
        } else {
          // Token is likely invalid, clear it
          await prefs.remove('auth_token');
        }
      }
    } catch (e) {
      debugPrint("Auto-login error: $e");
    }
    
    if (mounted) {
      setState(() {
        _isLoading = false;
      });
    }
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
    if (_isLoading) {
      return const Scaffold(
        body: Center(
          child: CircularProgressIndicator(color: Color(0xFF6B4EE6)),
        ),
      );
    }
    return LoginPage(onLogin: _navigateToRole);
  }
}