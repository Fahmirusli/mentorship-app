// lib/services/api_service.dart
import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../config.dart';


class ApiService {
  static String get baseUrl => AppConfig.apiBaseUrl;
  static Duration get _timeout => Duration(seconds: AppConfig.httpTimeoutSeconds);

  // OAuth (Google/GitHub) - opens external browser for authentication
  static Future<bool> startOAuth(String provider, {String? role}) async {
    final redirect = Uri.encodeComponent(AppConfig.oauthRedirectUri);
    final roleQuery = role != null ? '&role=${Uri.encodeComponent(role)}' : '';
    final url = '${AppConfig.apiRootUrl}/api/auth/$provider?redirect=$redirect$roleQuery';

    return launchUrl(Uri.parse(url), mode: LaunchMode.externalApplication);
  }

  // 1. LOGIN FUNCTION
  static Future<Map<String, dynamic>> login(String email, String password) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/login'), // Your Laravel login endpoint
        headers: {'Content-Type': 'application/json', 'Accept': 'application/json',},
        body: jsonEncode({'email': email, 'password': password,}),
      );

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);

        // 1. Save the Sanctum token securely
        if (data.containsKey('token')) {
          SharedPreferences prefs = await SharedPreferences.getInstance();
          await prefs.setString('auth_token', data['token']);
        }

        // 2. Extract the user role from the nested 'user' object
        int userRole = 1; // Default: mentee
        if (data.containsKey('user') && data['user'] != null && data['user']['role'] != null) {
          String roleStr = data['user']['role'].toString().toLowerCase();
          if (roleStr == 'mentor') {
            userRole = 2;
          } else {
            userRole = 1; // mentee or any other role defaults to mentee view
          }
        }

        return {
          'success': true,
          'message': data['message'] ?? 'Login successful',
          'role': userRole,
          'profile_incomplete': data['profile_incomplete'] == true,
        };
      } else {
        // Handle Laravel validation errors (401, 422)
        final errorData = jsonDecode(response.body);
        return {'success': false, 'message': errorData['message'] ?? 'Login failed'};
      }
    } catch (e) {
      return {'success': false, 'message': 'Could not connect to server.'};
    }
  }

  // 2. LOGOUT FUNCTION
  static Future<void> logout() async {
    SharedPreferences prefs = await SharedPreferences.getInstance();
    String? token = prefs.getString('auth_token');

    if (token != null) {
      await http.post(
        Uri.parse('$baseUrl/logout'),
        headers: {'Accept': 'application/json', 'Authorization': 'Bearer $token',},
      );
    }
    // Delete token from phone regardless of server response
    await prefs.remove('auth_token');
  }

  // 3. FETCH USER PROFILE FUNCTION
  static Future<Map<String, dynamic>?> getUserProfile() async {
    try {
      // Get the saved VIP pass (Token)
      SharedPreferences prefs = await SharedPreferences.getInstance();
      String? token = prefs.getString('auth_token');

      if (token == null) return null; // Not logged in

      final response = await http.get(
        Uri.parse('$baseUrl/user'), // Standard Laravel Sanctum route
        headers: {'Accept': 'application/json', 'Authorization': 'Bearer $token', // Showing the VIP pass
        },
      );

      if (response.statusCode == 200) {
        return jsonDecode(response.body); // Return the real database user data!
      } else {
        return null;
      }
    } catch (e) {
      return null;
    }
  }

  // 4. FETCH MASTER DASHBOARD DATA
  static Future<Map<String, dynamic>?> getDashboardData() async {
    try {
      SharedPreferences prefs = await SharedPreferences.getInstance();
      String? token = prefs.getString('auth_token');

      if (token == null) return null;

      final response = await http.get(
        Uri.parse('$baseUrl/mentee/dashboard'), // Our new Laravel route!
        headers: {'Accept': 'application/json', 'Authorization': 'Bearer $token',},
      );

      if (response.statusCode == 200) {
        final decoded = jsonDecode(response.body);
        return decoded['data']; // Returns the combined user, schedule, jobs, and mentors!
      } else {
        print('Dashboard API Error: ${response.body}');
        return null;
      }
    } catch (e) {
      print('Network Error: $e');
      return null;
    }
  }

  // 5. REGISTER FUNCTION
// 5. REGISTER (Step 1: Create Account & Request TAC)
  static Future<Map<String, dynamic>> register(String name, String email, String password, String confirmPassword, String role) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/register'),
        headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
        body: jsonEncode({
          'name': name,
          'email': email,
          'password': password,
          'password_confirmation': confirmPassword,
          'role': role,
        }),
      );

      if (response.statusCode == 200 || response.statusCode == 201) {
        // We DO NOT get a token here anymore. Just success.
        return {'success': true, 'message': 'Please check your email for the code.'};
      } else {
        final errorData = jsonDecode(response.body);
        return {'success': false, 'message': errorData['message'] ?? 'Registration failed'};
      }
    } catch (e) {
      return {'success': false, 'message': 'Could not connect to server.'};
    }
  }

  // 6. VERIFY EMAIL (Step 2: Submit TAC & Get Token)
  static Future<Map<String, dynamic>> verifyEmail(String email, String tac) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/verify-email'),
        headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
        body: jsonEncode({
          'email': email,
          'tac': tac,
        }),
      );

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);

        // NOW we get the token and save it!
        if (data.containsKey('token')) {
          SharedPreferences prefs = await SharedPreferences.getInstance();
          await prefs.setString('auth_token', data['token']);
        }

        // Figure out their role so we know which dashboard to load
        int userRole = 1;
        if (data.containsKey('user') && data['user'] != null && data['user']['role'] != null) {
          userRole = data['user']['role'] == 'mentor' ? 2 : 1;
        }

        return {
          'success': true,
          'message': 'Verified successfully!',
          'role': userRole,
          'profile_incomplete': data['profile_incomplete'] == true,
        };
      } else {
        final errorData = jsonDecode(response.body);
        return {'success': false, 'message': errorData['message'] ?? 'Invalid code.'};
      }
    } catch (e) {
      return {'success': false, 'message': 'Could not connect to server.'};
    }
  }

  // 7. UPDATE PROFILE (Name, Bio, Phone, Email)
  static Future<Map<String, dynamic>> updateProfile({
    String? name,
    String? bio,
    String? phone,
    String? address,
    String? email,
    List<String>? skills,
  }) async {
    try {
      SharedPreferences prefs = await SharedPreferences.getInstance();
      String? token = prefs.getString('auth_token');
      if (token == null) return {'success': false, 'message': 'Not logged in'};

      final body = <String, dynamic>{};
      if (name != null) body['name'] = name;
      if (bio != null) body['bio'] = bio;
      if (phone != null) body['phone'] = phone;
      if (address != null) body['address'] = address;
      if (email != null) body['email'] = email;
      if (skills != null) body['skills'] = skills;

      final response = await http.put(
        Uri.parse('$baseUrl/user/profile'),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'Authorization': 'Bearer $token',
        },
        body: jsonEncode(body),
      );

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        return {'success': true, 'message': data['message'] ?? 'Profile updated', 'user': data['user']};
      } else {
        final errorData = jsonDecode(response.body);
        return {'success': false, 'message': errorData['message'] ?? 'Update failed'};
      }
    } catch (e) {
      return {'success': false, 'message': 'Could not connect to server.'};
    }
  }

  static Future<Map<String, dynamic>> uploadResume(String filePath) async {
    try {
      SharedPreferences prefs = await SharedPreferences.getInstance();
      String? token = prefs.getString('auth_token');
      if (token == null) return {'success': false, 'message': 'Not logged in'};

      var request = http.MultipartRequest(
        'POST',
        Uri.parse('$baseUrl/upload/resume'),
      );
      request.headers['Authorization'] = 'Bearer $token';
      request.headers['Accept'] = 'application/json';
      request.files.add(await http.MultipartFile.fromPath('resume', filePath));

      final streamedResponse = await request.send();
      final response = await http.Response.fromStream(streamedResponse);

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        return {
          'success': true,
          'resume_url': data['resume_url'],
          'message': data['message'] ?? 'Resume uploaded'
        };
      }

      final errorData = jsonDecode(response.body);
      return {'success': false, 'message': errorData['message'] ?? 'Upload failed'};
    } catch (e) {
      return {'success': false, 'message': 'Could not connect to server.'};
    }
  }

  // 8. UPLOAD PROFILE IMAGE
  static Future<Map<String, dynamic>> uploadProfileImage(String filePath) async {
    try {
      SharedPreferences prefs = await SharedPreferences.getInstance();
      String? token = prefs.getString('auth_token');
      if (token == null) return {'success': false, 'message': 'Not logged in'};

      var request = http.MultipartRequest(
        'POST',
        Uri.parse('$baseUrl/user/profile-image'),
      );
      request.headers['Authorization'] = 'Bearer $token';
      request.headers['Accept'] = 'application/json';
      request.files.add(await http.MultipartFile.fromPath('image', filePath));

      final streamedResponse = await request.send();
      final response = await http.Response.fromStream(streamedResponse);

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        return {'success': true, 'image_url': data['image_url'], 'message': 'Image uploaded'};
      } else {
        final errorData = jsonDecode(response.body);
        return {'success': false, 'message': errorData['message'] ?? 'Upload failed'};
      }
    } catch (e) {
      return {'success': false, 'message': 'Could not connect to server.'};
    }
  }

  // 9. GET USER PROFILE (Fresh data)
  static Future<Map<String, dynamic>?> getProfile() async {
    try {
      SharedPreferences prefs = await SharedPreferences.getInstance();
      String? token = prefs.getString('auth_token');
      if (token == null) return null;

      final response = await http.get(
        Uri.parse('$baseUrl/user'),
        headers: {
          'Accept': 'application/json',
          'Authorization': 'Bearer $token',
        },
      );

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        // The /user route returns the user object directly (with loaded relations)
        // but it might also be nested under 'user' key depending on the route
        if (data is Map<String, dynamic>) {
          if (data.containsKey('user')) {
            return data['user'] as Map<String, dynamic>;
          }
          return data;
        }
        return null;
      } else {
        return null;
      }
    } catch (e) {
      return null;
    }
  }

  // 10. GET ALL JOBS (with optional search)
  static Future<List<dynamic>> getJobs({String? search}) async {
    try {
      SharedPreferences prefs = await SharedPreferences.getInstance();
      String? token = prefs.getString('auth_token');
      if (token == null) return [];

      String url = '$baseUrl/jobs';
      if (search != null && search.isNotEmpty) {
        url += '?search=${Uri.encodeComponent(search)}';
      }

      final response = await http.get(
        Uri.parse(url),
        headers: {
          'Accept': 'application/json',
          'Authorization': 'Bearer $token',
        },
      );

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        // The jobs endpoint returns paginated data
        if (data is Map && data.containsKey('data')) {
          return data['data'] as List<dynamic>;
        }
        if (data is List) return data;
        return [];
      } else {
        return [];
      }
    } catch (e) {
      print('Jobs fetch error: $e');
      return [];
    }
  }

  // 11. GET JOB RECOMMENDATIONS (NLP matched)
  static Future<List<dynamic>> getJobRecommendations() async {
    try {
      SharedPreferences prefs = await SharedPreferences.getInstance();
      String? token = prefs.getString('auth_token');
      if (token == null) return [];

      final response = await http.get(
        Uri.parse('$baseUrl/jobs/recommendations'),
        headers: {
          'Accept': 'application/json',
          'Authorization': 'Bearer $token',
        },
      );

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data is Map && data.containsKey('recommendations')) {
          return data['recommendations'] as List<dynamic>;
        }
        return [];
      } else {
        return [];
      }
    } catch (e) {
      print('Job recommendations error: $e');
      return [];
    }
  }

  // ========================================
  // MESSAGING APIs
  // ========================================

  // 12. GET CONVERSATIONS
  static Future<List<dynamic>> getConversations() async {
    try {
      SharedPreferences prefs = await SharedPreferences.getInstance();
      String? token = prefs.getString('auth_token');
      if (token == null) return [];

      final response = await http.get(
        Uri.parse('$baseUrl/conversations'),
        headers: {'Accept': 'application/json', 'Authorization': 'Bearer $token'},
      );

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        return data['conversations'] ?? [];
      }
      return [];
    } catch (e) {
      print('Conversations error: $e');
      return [];
    }
  }

  // 13. GET MESSAGES WITH A USER
  static Future<Map<String, dynamic>> getMessages(int otherUserId) async {
    try {
      SharedPreferences prefs = await SharedPreferences.getInstance();
      String? token = prefs.getString('auth_token');
      if (token == null) return {'conversation_id': null, 'messages': []};

      final response = await http.get(
        Uri.parse('$baseUrl/messages/$otherUserId'),
        headers: {'Accept': 'application/json', 'Authorization': 'Bearer $token'},
      );

      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      }
      return {'conversation_id': null, 'messages': []};
    } catch (e) {
      print('Messages error: $e');
      return {'conversation_id': null, 'messages': []};
    }
  }

  // 14. SEND A MESSAGE
  static Future<Map<String, dynamic>?> sendMessage(int receiverId, String body) async {
    try {
      SharedPreferences prefs = await SharedPreferences.getInstance();
      String? token = prefs.getString('auth_token');
      if (token == null) return null;

      final response = await http.post(
        Uri.parse('$baseUrl/messages/send'),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'Authorization': 'Bearer $token',
        },
        body: jsonEncode({'receiver_id': receiverId, 'body': body}),
      );

      if (response.statusCode == 201) {
        return jsonDecode(response.body);
      }
      return null;
    } catch (e) {
      print('Send message error: $e');
      return null;
    }
  }

  // 15. POLL FOR NEW MESSAGES
  static Future<List<dynamic>> pollMessages(int conversationId, {int afterId = 0}) async {
    try {
      SharedPreferences prefs = await SharedPreferences.getInstance();
      String? token = prefs.getString('auth_token');
      if (token == null) return [];

      final response = await http.get(
        Uri.parse('$baseUrl/messages/poll/$conversationId?after_id=$afterId'),
        headers: {'Accept': 'application/json', 'Authorization': 'Bearer $token'},
      );

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        return data['messages'] ?? [];
      }
      return [];
    } catch (e) {
      return [];
    }
  }

  // ========================================
  // NOTIFICATION APIs
  // ========================================

  // 16. GET NOTIFICATIONS
  static Future<List<dynamic>> getNotifications() async {
    try {
      SharedPreferences prefs = await SharedPreferences.getInstance();
      String? token = prefs.getString('auth_token');
      if (token == null) return [];

      final response = await http.get(
        Uri.parse('$baseUrl/notifications'),
        headers: {'Accept': 'application/json', 'Authorization': 'Bearer $token'},
      );

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        return data['notifications'] ?? [];
      }
      return [];
    } catch (e) {
      return [];
    }
  }

  // 17. GET UNREAD NOTIFICATION COUNT
  static Future<int> getUnreadNotificationCount() async {
    try {
      SharedPreferences prefs = await SharedPreferences.getInstance();
      String? token = prefs.getString('auth_token');
      if (token == null) return 0;

      final response = await http.get(
        Uri.parse('$baseUrl/notifications/unread-count'),
        headers: {'Accept': 'application/json', 'Authorization': 'Bearer $token'},
      );

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        return data['unread_count'] ?? 0;
      }
      return 0;
    } catch (e) {
      return 0;
    }
  }

  // 18. MARK ALL NOTIFICATIONS AS READ
  static Future<bool> markNotificationsRead() async {
    try {
      SharedPreferences prefs = await SharedPreferences.getInstance();
      String? token = prefs.getString('auth_token');
      if (token == null) return false;

      final response = await http.post(
        Uri.parse('$baseUrl/notifications/read-all'),
        headers: {'Accept': 'application/json', 'Authorization': 'Bearer $token'},
      );

      return response.statusCode == 200;
    } catch (e) {
      return false;
    }
  }

  // ========================================
  // APPOINTMENT APIs
  // ========================================

  // 19. GET MY APPOINTMENTS
  static Future<List<dynamic>> getMyAppointments({bool todayOnly = false}) async {
    try {
      SharedPreferences prefs = await SharedPreferences.getInstance();
      String? token = prefs.getString('auth_token');
      if (token == null) return [];

      String url = '$baseUrl/appointments';
      if (todayOnly) {
        final today = DateTime.now().toIso8601String().substring(0, 10);
        url += '?from_date=$today&to_date=$today 23:59:59&status=scheduled';
      }

      final response = await http.get(
        Uri.parse(url),
        headers: {'Accept': 'application/json', 'Authorization': 'Bearer $token'},
      );

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        // Response is a direct list of appointments
        final List<dynamic> appointments = data is List ? data : (data['data'] ?? data['appointments'] ?? []);

        return appointments.map((apt) {
          // Extract mentor/mentee names from nested mentorship relation
          final mentorship = apt['mentorship'];
          final mentor = mentorship?['mentor'];
          final mentee = mentorship?['mentee'];

          final scheduledAt = apt['scheduled_at'];
          String time = '';
          String date = '';
          if (scheduledAt != null) {
            try {
              final dt = DateTime.parse(scheduledAt).toLocal();
              time = '${dt.hour.toString().padLeft(2, '0')}:${dt.minute.toString().padLeft(2, '0')}';
              date = dt.toIso8601String().substring(0, 10);
            } catch (_) {}
          }

          return {
            'id': apt['id'],
            'time': time,
            'date': date,
            'mentor_name': mentor?['name'] ?? 'Mentor',
            'mentee_name': mentee?['name'] ?? 'Mentee',
            'mentor_id': mentor?['id'] ?? mentorship?['mentor_id'] ?? 0,
            'mentee_id': mentee?['id'] ?? mentorship?['mentee_id'] ?? 0,
            'other_user_name': mentor?['name'] ?? mentee?['name'] ?? 'User',
            'status': apt['status'] ?? 'scheduled',
            'duration_minutes': apt['duration_minutes'] ?? 60,
            'meeting_link': apt['meeting_link'],
            'notes': apt['notes'],
            'fee': apt['fee'],
          };
        }).toList();
      }
      return [];
    } catch (e) {
      print('Appointments error: $e');
      return [];
    }
  }

  // ========================================
  // MENTOR SKILLS & BOOKING APIs
  // ========================================

  // 20. GET ALL MENTOR SKILLS (for skill selection)
  static Future<List<String>> getMentorSkills() async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/mentors/all-skills'),
        headers: {'Accept': 'application/json'},
      );
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        final skills = data['skills'] as List<dynamic>? ?? [];
        return skills.map((s) => s.toString()).toList();
      }
      return [];
    } catch (e) {
      print('Skills fetch error: $e');
      return [];
    }
  }

  // 21. GET MENTOR AVAILABLE SLOTS
  static Future<List<dynamic>> getMentorSlots(int mentorId) async {
    try {
      SharedPreferences prefs = await SharedPreferences.getInstance();
      String? token = prefs.getString('auth_token');

      final response = await http.get(
        Uri.parse('$baseUrl/schedules/mentor/$mentorId?start_date=${DateTime.now().toIso8601String().substring(0, 10)}&end_date=${DateTime.now().add(const Duration(days: 14)).toIso8601String().substring(0, 10)}'),
        headers: {
          'Accept': 'application/json',
          if (token != null) 'Authorization': 'Bearer $token',
        },
      );
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        return data['schedules'] ?? [];
      }
      return [];
    } catch (e) {
      print('Mentor slots error: $e');
      return [];
    }
  }

  // 22. INITIATE PAYMENT (ToyyibPay)
  static Future<Map<String, dynamic>> initiatePayment({
    required int mentorId,
    required String scheduledAt,
    required int durationMinutes,
    String? notes,
  }) async {
    try {
      SharedPreferences prefs = await SharedPreferences.getInstance();
      String? token = prefs.getString('auth_token');
      if (token == null) return {'success': false, 'message': 'Not logged in'};

      final response = await http.post(
        Uri.parse('$baseUrl/payment/initiate'),
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'Authorization': 'Bearer $token',
        },
        body: jsonEncode({
          'mentor_id': mentorId,
          'scheduled_at': scheduledAt,
          'duration_minutes': durationMinutes,
          'notes': notes ?? '',
        }),
      );

      final data = jsonDecode(response.body);
      if (response.statusCode == 200) {
        return {
          'success': true,
          'payment_url': data['payment_url'],
          'bill_code': data['bill_code'],
          'amount': data['amount'],
          'appointment_id': data['appointment_id'],
        };
      }
      return {'success': false, 'message': data['message'] ?? 'Payment failed'};
    } catch (e) {
      return {'success': false, 'message': 'Connection error: $e'};
    }
  }

  // 23. CREATE MENTOR AVAILABILITY SLOT
  static Future<Map<String, dynamic>> createAvailabilitySlot({
    required String date,
    required String startTime,
    required String endTime,
    required double fee,
  }) async {
    try {
      SharedPreferences prefs = await SharedPreferences.getInstance();
      String? token = prefs.getString('auth_token');
      if (token == null) return {'success': false, 'message': 'Not logged in'};

      final response = await http.post(
        Uri.parse('$baseUrl/schedules'),
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'Authorization': 'Bearer $token',
        },
        body: jsonEncode({
          'date': date,
          'start_time': startTime,
          'end_time': endTime,
          'is_available': true,
          'fee': fee,
        }),
      );

      final data = jsonDecode(response.body);
      if (response.statusCode == 201 || response.statusCode == 200) {
        return {'success': true, 'schedule': data['schedule']};
      }
      return {'success': false, 'message': data['message'] ?? 'Failed to create slot'};
    } catch (e) {
      return {'success': false, 'message': 'Connection error: $e'};
    }
  }

  // 24. GET MY AVAILABILITY SLOTS (for mentors)
  static Future<List<dynamic>> getMyAvailabilitySlots() async {
    try {
      SharedPreferences prefs = await SharedPreferences.getInstance();
      String? token = prefs.getString('auth_token');
      if (token == null) return [];

      final response = await http.get(
        Uri.parse('$baseUrl/schedules/my-schedule'),
        headers: {
          'Accept': 'application/json',
          'Authorization': 'Bearer $token',
        },
      );

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        return data['schedules'] ?? [];
      }
      return [];
    } catch (e) {
      print('My slots error: $e');
      return [];
    }
  }

  // 25. DELETE AVAILABILITY SLOT
  static Future<bool> deleteAvailabilitySlot(int slotId) async {
    try {
      SharedPreferences prefs = await SharedPreferences.getInstance();
      String? token = prefs.getString('auth_token');
      if (token == null) return false;

      final response = await http.delete(
        Uri.parse('$baseUrl/schedules/$slotId'),
        headers: {
          'Accept': 'application/json',
          'Authorization': 'Bearer $token',
        },
      );
      return response.statusCode == 200;
    } catch (e) {
      return false;
    }
  }

  // ========================================
  // FORGOT PASSWORD APIs
  // ========================================

  // 26. REQUEST PASSWORD RESET (send TAC)
  static Future<Map<String, dynamic>> forgotPassword(String email) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/forgot-password'),
        headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
        body: jsonEncode({'email': email}),
      );

      final data = jsonDecode(response.body);
      if (response.statusCode == 200) {
        return {'success': true, 'message': data['message'] ?? 'Code sent'};
      }
      return {'success': false, 'message': data['message'] ?? 'Failed to send code'};
    } catch (e) {
      return {'success': false, 'message': 'Could not connect to server.'};
    }
  }

  // 27. VERIFY RESET CODE
  static Future<Map<String, dynamic>> verifyResetCode(String email, String tac) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/verify-reset-code'),
        headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
        body: jsonEncode({'email': email, 'tac': tac}),
      );

      final data = jsonDecode(response.body);
      if (response.statusCode == 200) {
        return {'success': true, 'message': data['message'] ?? 'Code verified'};
      }
      return {'success': false, 'message': data['message'] ?? 'Invalid code'};
    } catch (e) {
      return {'success': false, 'message': 'Could not connect to server.'};
    }
  }

  // 28. RESET PASSWORD
  static Future<Map<String, dynamic>> resetPassword(String email, String tac, String password, String confirmPassword) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/reset-password'),
        headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
        body: jsonEncode({
          'email': email,
          'tac': tac,
          'password': password,
          'password_confirmation': confirmPassword,
        }),
      );

      final data = jsonDecode(response.body);
      if (response.statusCode == 200) {
        return {'success': true, 'message': data['message'] ?? 'Password reset successfully'};
      }
      return {'success': false, 'message': data['message'] ?? 'Reset failed'};
    } catch (e) {
      return {'success': false, 'message': 'Could not connect to server.'};
    }
  }

  // 29. CHANGE PASSWORD (Authenticated)
  static Future<Map<String, dynamic>> changePassword(String currentPassword, String newPassword, String confirmPassword) async {
    try {
      SharedPreferences prefs = await SharedPreferences.getInstance();
      String? token = prefs.getString('auth_token');
      if (token == null) return {'success': false, 'message': 'Not logged in'};

      final response = await http.put(
        Uri.parse('$baseUrl/user/profile'),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'Authorization': 'Bearer $token',
        },
        body: jsonEncode({
          'current_password': currentPassword,
          'password': newPassword,
          'password_confirmation': confirmPassword,
        }),
      );

      final data = jsonDecode(response.body);
      if (response.statusCode == 200) {
        return {'success': true, 'message': 'Password changed successfully'};
      }
      return {'success': false, 'message': data['message'] ?? 'Change failed'};
    } catch (e) {
      return {'success': false, 'message': 'Could not connect to server.'};
    }
  }

  // 30. GET MENTOR STATS
  static Future<Map<String, dynamic>> getMentorStats() async {
    try {
      SharedPreferences prefs = await SharedPreferences.getInstance();
      String? token = prefs.getString('auth_token');
      if (token == null) return {};

      final response = await http.get(
        Uri.parse('$baseUrl/mentor/stats'),
        headers: {'Accept': 'application/json', 'Authorization': 'Bearer $token'},
      );

      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      }
      return {};
    } catch (e) {
      return {};
    }
  }

  // 31. GET MENTORSHIPS (for mentor's mentee list)
  static Future<List<dynamic>> getMentorships() async {
    try {
      SharedPreferences prefs = await SharedPreferences.getInstance();
      String? token = prefs.getString('auth_token');
      if (token == null) return [];

      final response = await http.get(
        Uri.parse('$baseUrl/mentorships'),
        headers: {'Accept': 'application/json', 'Authorization': 'Bearer $token'},
      );

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data is List) return data;
        return data['data'] ?? [];
      }
      return [];
    } catch (e) {
      return [];
    }
  }

  // 32. SUBMIT FEEDBACK
  static Future<Map<String, dynamic>> submitFeedback({
    required int mentorshipId,
    required int toUserId,
    required int rating,
    String? comment,
  }) async {
    try {
      SharedPreferences prefs = await SharedPreferences.getInstance();
      String? token = prefs.getString('auth_token');
      if (token == null) return {'success': false, 'message': 'Not logged in'};

      final response = await http.post(
        Uri.parse('$baseUrl/feedback'),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'Authorization': 'Bearer $token',
        },
        body: jsonEncode({
          'mentorship_id': mentorshipId,
          'to_user_id': toUserId,
          'rating': rating,
          'comment': comment ?? '',
        }),
      );

      final data = jsonDecode(response.body);
      if (response.statusCode == 200 || response.statusCode == 201) {
        return {'success': true, 'message': 'Feedback submitted'};
      }
      return {'success': false, 'message': data['message'] ?? 'Failed'};
    } catch (e) {
      return {'success': false, 'message': 'Could not connect to server.'};
    }
  }

  // ========================================
  // STATS & RESOURCES APIs
  // ========================================

  static Future<Map<String, dynamic>?> getMenteeStats() async {
    try {
      SharedPreferences prefs = await SharedPreferences.getInstance();
      String? token = prefs.getString('auth_token');
      if (token == null) return null;

      final response = await http.get(
        Uri.parse('$baseUrl/mentee/stats'),
        headers: {'Accept': 'application/json', 'Authorization': 'Bearer $token'},
      );

      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      }
      return null;
    } catch (e) {
      print('Mentee stats error: $e');
      return null;
    }
  }

  static Future<List<dynamic>> getMenteeResources() async {
    try {
      SharedPreferences prefs = await SharedPreferences.getInstance();
      String? token = prefs.getString('auth_token');
      if (token == null) return [];

      final response = await http.get(
        Uri.parse('$baseUrl/mentee/resources'),
        headers: {'Accept': 'application/json', 'Authorization': 'Bearer $token'},
      );

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        return data is List ? data : [];
      }
      return [];
    } catch (e) {
      print('Mentee resources error: $e');
      return [];
    }
  }

}