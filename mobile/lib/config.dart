// lib/config.dart
// ============================================
// CENTRAL CONFIGURATION FILE
// ============================================
// Change this ONE value to switch between local dev and production.

class AppConfig {
  // =============================================
  // HOW TO USE:
  // =============================================
  // For Android EMULATOR:  'http://10.0.2.2:8000/api'
  // For REAL PHONE (same WiFi as PC): 'http://YOUR_PC_IP:8000/api'
  //    → Find your PC IP: open CMD → type 'ipconfig' → look for IPv4 Address
  //    → Example: 'http://192.168.1.100:8000/api'
  // For PRODUCTION server: 'https://your-domain.com/api'
  // =============================================

  // For REAL PHONE (same WiFi): Use your PC's IP address
  // For Android EMULATOR: Change to 'http://10.0.2.2:8000/api'
  // For PRODUCTION: Change to 'https://your-domain.com/api'
  static const String apiBaseUrl = 'https://api.uplifts.dev/api';
  static const String apiRootUrl = 'https://api.uplifts.dev';

  // OAuth redirect settings (mobile deep link)
  static const String oauthRedirectScheme = 'uplifts';
  static const String oauthRedirectPath = 'oauth';
  static String get oauthRedirectUri => '$oauthRedirectScheme://$oauthRedirectPath';

  // App-wide constants
  static const String appName = 'MentorCore';
  static const String appVersion = '1.0.0';
  static const int httpTimeoutSeconds = 15;
}
