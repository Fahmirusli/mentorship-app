import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:google_maps_flutter/google_maps_flutter.dart';
import 'package:geolocator/geolocator.dart';
import 'package:url_launcher/url_launcher.dart';
import '../services/api_service.dart';

class NearbyMentorsScreen extends StatefulWidget {
  final String? selectedSkill;
  const NearbyMentorsScreen({super.key, this.selectedSkill});

  @override
  State<NearbyMentorsScreen> createState() => _NearbyMentorsScreenState();
}

class _NearbyMentorsScreenState extends State<NearbyMentorsScreen> {
  LatLng? _currentPosition;
  bool _isLoading = true;
  String _locationMessage = "Locating you...";
  GoogleMapController? _mapController;

  List<dynamic> _mentors = [];

  List<dynamic> get _filteredMentors {
    if (widget.selectedSkill == null || widget.selectedSkill!.isEmpty) {
      return _mentors;
    }
    final filtered = _mentors.where((m) {
      final skills = m['skills'] as List<dynamic>? ?? [];
      return skills.any((s) => s.toString().toLowerCase().contains(widget.selectedSkill!.toLowerCase()));
    }).toList();
    return filtered.isNotEmpty ? filtered : _mentors;
  }

  @override
  void initState() {
    super.initState();
    _getUserLocation();
  }

  Future<void> _getUserLocation() async {
    try {
      LocationPermission permission = await Geolocator.checkPermission();
      if (permission == LocationPermission.denied) {
        permission = await Geolocator.requestPermission();
        if (permission == LocationPermission.denied) {
          setState(() { _locationMessage = "Location permission denied."; _isLoading = false; });
          return;
        }
      }
      Position position = await Geolocator.getCurrentPosition(desiredAccuracy: LocationAccuracy.high);
      
      final mentors = await ApiService.getNearbyMentors(
        lat: position.latitude, 
        lng: position.longitude, 
        radiusKm: 50.0 // Default 50km radius
      );

      setState(() {
        _currentPosition = LatLng(position.latitude, position.longitude);
        _mentors = mentors;
        _isLoading = false;
      });
      if (_mapController != null && _currentPosition != null) {
        _mapController!.animateCamera(CameraUpdate.newLatLng(_currentPosition!));
      }
    } catch (e) {
      setState(() { _locationMessage = "Could not fetch GPS. Ensure location is on."; _isLoading = false; });
    }
  }

  Set<Marker> _createMarkers() {
    Set<Marker> markers = {};
    final mentors = _filteredMentors;
    for (int i = 0; i < mentors.length; i++) {
      final lat = mentors[i]['latitude'];
      final lng = mentors[i]['longitude'];
      if (lat != null && lng != null) {
        final double latitude = lat is double ? lat : double.parse(lat.toString());
        final double longitude = lng is double ? lng : double.parse(lng.toString());
        markers.add(Marker(
          markerId: MarkerId('mentor_${mentors[i]['id']}'),
          position: LatLng(latitude, longitude),
          icon: BitmapDescriptor.defaultMarkerWithHue(BitmapDescriptor.hueViolet),
          infoWindow: InfoWindow(title: mentors[i]['name'], snippet: mentors[i]['title'] ?? 'Mentor'),
        ));
      }
    }
    return markers;
  }

  // =================================================
  // BOOKING FLOW
  // =================================================
  void _showBookingSheet(dynamic mentor) {
    final skills = (mentor['skills'] as List<dynamic>?)?.map((e) => e.toString()).toList() ?? [];
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => _BookingSheet(
        mentorName: mentor['name'] ?? 'Mentor',
        mentorRole: mentor['title'] ?? 'Expert',
        mentorId: mentor['id'] ?? 0,
        skills: skills,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final mentors = _filteredMentors;
    return Scaffold(
      backgroundColor: const Color(0xFFF4F3FB),
      appBar: widget.selectedSkill != null
          ? AppBar(
              title: Text("Mentors for ${widget.selectedSkill}",
                  style: const TextStyle(color: Colors.black87, fontWeight: FontWeight.bold, fontSize: 16)),
              backgroundColor: Colors.transparent,
              elevation: 0,
              iconTheme: const IconThemeData(color: Colors.black87),
              centerTitle: true,
            )
          : null,
      body: Column(
        children: [
          if (widget.selectedSkill != null)
            Container(
              margin: const EdgeInsets.symmetric(horizontal: 20, vertical: 8),
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
              decoration: BoxDecoration(
                gradient: const LinearGradient(colors: [Color(0xFF6B4EE6), Color(0xFF9B7EFA)]),
                borderRadius: BorderRadius.circular(20),
              ),
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  const Icon(Icons.filter_alt_rounded, color: Colors.white, size: 16),
                  const SizedBox(width: 6),
                  Text("Skill: ${widget.selectedSkill}",
                      style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w600, fontSize: 13)),
                ],
              ),
            ),

          Expanded(
            flex: 2,
            child: Container(
              decoration: BoxDecoration(
                boxShadow: [BoxShadow(color: Colors.purple.withOpacity(0.1), blurRadius: 10, offset: const Offset(0, 5))],
              ),
              child: _isLoading
                  ? Center(child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        const CircularProgressIndicator(color: Color(0xFF6B4EE6)),
                        const SizedBox(height: 15),
                        Text(_locationMessage, style: const TextStyle(color: Colors.grey)),
                      ],
                    ))
                  : _currentPosition == null
                      ? Center(child: Text(_locationMessage))
                      : GoogleMap(
                          initialCameraPosition: CameraPosition(target: _currentPosition!, zoom: 14.0),
                          onMapCreated: (GoogleMapController controller) { _mapController = controller; },
                          markers: _createMarkers(),
                          myLocationEnabled: true,
                          myLocationButtonEnabled: true,
                          zoomControlsEnabled: false,
                        ),
            ),
          ),

          Expanded(
            flex: 3,
            child: Container(
              padding: const EdgeInsets.only(top: 20, left: 20, right: 20),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    widget.selectedSkill != null ? "Mentors for ${widget.selectedSkill}" : "Mentors Near You",
                    style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Color(0xFF2D2D3A)),
                  ),
                  const SizedBox(height: 15),
                  Expanded(
                    child: mentors.isEmpty && !_isLoading
                        ? Center(
                            child: Column(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                Icon(Icons.person_search, size: 48, color: Colors.grey.shade300),
                                const SizedBox(height: 8),
                                Text("No mentors found.",
                                    style: TextStyle(color: Colors.grey.shade500)),
                              ],
                            ),
                          )
                        : ListView(
                            padding: EdgeInsets.zero,
                            children: mentors.map((m) => _mentorCard(m)).toList(),
                          ),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _mentorCard(dynamic mentor) {
    return InkWell(
      onTap: () => _showBookingSheet(mentor),
      borderRadius: BorderRadius.circular(20),
      splashColor: const Color(0xFF6B4EE6).withOpacity(0.1),
      child: Container(
        margin: const EdgeInsets.only(bottom: 15),
        padding: const EdgeInsets.all(15),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(20),
          boxShadow: [BoxShadow(color: Colors.purple.withOpacity(0.05), blurRadius: 10)],
        ),
        child: Column(
          children: [
            Row(
              children: [
                const CircleAvatar(radius: 25, backgroundColor: Color(0xFF6B4EE6), child: Icon(Icons.person, color: Colors.white)),
                const SizedBox(width: 15),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(mentor['name'] ?? 'Mentor', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                      Text(mentor['title'] ?? 'Expert', style: TextStyle(color: Colors.grey.shade600, fontSize: 13)),
                      const SizedBox(height: 5),
                      Text("📍 ${mentor['distance_km']} km away", style: const TextStyle(fontSize: 12, color: Color(0xFF6B4EE6), fontWeight: FontWeight.bold)),
                    ],
                  ),
                ),
                Column(
                  children: [
                    const Icon(Icons.arrow_forward_ios, color: Color(0xFF6B4EE6), size: 16),
                    const SizedBox(height: 4),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                      decoration: BoxDecoration(
                        color: const Color(0xFFE8F5E9),
                        borderRadius: BorderRadius.circular(6),
                      ),
                      child: const Text("Book", style: TextStyle(fontSize: 10, color: Color(0xFF2E7D32), fontWeight: FontWeight.w700)),
                    ),
                  ],
                ),
              ],
            ),
            if ((mentor['skills'] as List<dynamic>?)?.isNotEmpty ?? false) ...[
              const SizedBox(height: 10),
              Wrap(
                spacing: 6,
                runSpacing: 4,
                children: (mentor['skills'] as List<dynamic>).map((s) => Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                  decoration: BoxDecoration(
                    color: const Color(0xFFEDE7F6),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Text(s.toString(), style: const TextStyle(fontSize: 10, color: Color(0xFF6B4EE6), fontWeight: FontWeight.w600)),
                )).toList(),
              ),
            ],
          ],
        ),
      ),
    );
  }
}

// =====================================================
// BOOKING BOTTOM SHEET — Shows slots & initiates payment
// =====================================================
class _BookingSheet extends StatefulWidget {
  final String mentorName;
  final String mentorRole;
  final int mentorId;
  final List<String> skills;

  const _BookingSheet({
    required this.mentorName,
    required this.mentorRole,
    required this.mentorId,
    required this.skills,
  });

  @override
  State<_BookingSheet> createState() => _BookingSheetState();
}

class _BookingSheetState extends State<_BookingSheet> {
  List<dynamic> _slots = [];
  bool _isLoadingSlots = true;
  dynamic _selectedSlot;
  bool _isBooking = false;

  @override
  void initState() {
    super.initState();
    _loadSlots();
  }

  Future<void> _loadSlots() async {
    if (widget.mentorId > 0) {
      final slots = await ApiService.getMentorSlots(widget.mentorId);
      if (mounted) {
        setState(() {
          _slots = slots;
          _isLoadingSlots = false;
        });
      }
    } else {
      if (mounted) {
        setState(() {
          _slots = [];
          _isLoadingSlots = false;
        });
      }
    }
  }

  String _formatDate(String? dateStr) {
    if (dateStr == null) return '';
    try {
      final dt = DateTime.parse(dateStr);
      const days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
      const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
      return '${days[dt.weekday - 1]}, ${dt.day} ${months[dt.month - 1]}';
    } catch (_) {
      return dateStr;
    }
  }

  String _formatTime(String? time) {
    if (time == null) return '';
    // "09:00:00" -> "9:00 AM"
    try {
      final parts = time.split(':');
      int hour = int.parse(parts[0]);
      final minute = parts[1];
      final ampm = hour >= 12 ? 'PM' : 'AM';
      if (hour > 12) hour -= 12;
      if (hour == 0) hour = 12;
      return '$hour:$minute $ampm';
    } catch (_) {
      return time;
    }
  }

  Future<void> _bookSlot() async {
    if (_selectedSlot == null) return;

    setState(() => _isBooking = true);

    final dateStr = _selectedSlot['date'] ?? DateTime.now().toIso8601String().substring(0, 10);
    final timeStr = _selectedSlot['start_time'] ?? '10:00:00';
    final scheduledAt = '$dateStr ${timeStr.toString().substring(0, 5)}:00';

    final result = await ApiService.initiatePayment(
      mentorId: widget.mentorId,
      scheduledAt: scheduledAt,
      durationMinutes: 60,
      notes: 'Session for ${widget.skills.isNotEmpty ? widget.skills.first : "mentoring"}',
    );

    if (mounted) {
      setState(() => _isBooking = false);

      if (result['success'] == true && result['payment_url'] != null) {
        // Open ToyyibPay payment page
        final url = Uri.parse(result['payment_url']);
        try {
          await launchUrl(url, mode: LaunchMode.externalApplication);
        } catch (_) {}
        if (mounted) {
          Navigator.pop(context); // Close sheet
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text("Payment initiated! Bill: ${result['bill_code']}"),
              backgroundColor: const Color(0xFF6B4EE6),
              behavior: SnackBarBehavior.floating,
            ),
          );
        }
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(result['message'] ?? 'Payment failed'),
            backgroundColor: Colors.redAccent,
            behavior: SnackBarBehavior.floating,
          ),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    // Group slots by date
    Map<String, List<dynamic>> groupedSlots = {};
    for (var slot in _slots) {
      final date = slot['date'] ?? 'Unknown';
      groupedSlots.putIfAbsent(date, () => []);
      groupedSlots[date]!.add(slot);
    }

    return Container(
      height: MediaQuery.of(context).size.height * 0.75,
      decoration: const BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.vertical(top: Radius.circular(25)),
      ),
      child: Column(
        children: [
          // Handle bar
          Container(
            margin: const EdgeInsets.only(top: 12),
            width: 40,
            height: 4,
            decoration: BoxDecoration(
              color: Colors.grey.shade300,
              borderRadius: BorderRadius.circular(2),
            ),
          ),

          // Mentor header
          Padding(
            padding: const EdgeInsets.all(20),
            child: Row(
              children: [
                const CircleAvatar(
                  radius: 25,
                  backgroundColor: Color(0xFF6B4EE6),
                  child: Icon(Icons.person, color: Colors.white, size: 28),
                ),
                const SizedBox(width: 15),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(widget.mentorName,
                          style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                      Text(widget.mentorRole,
                          style: TextStyle(color: Colors.grey.shade600, fontSize: 13)),
                    ],
                  ),
                ),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                  decoration: BoxDecoration(
                    gradient: const LinearGradient(colors: [Color(0xFF6B4EE6), Color(0xFF9B7EFA)]),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: const Text("Book Session", style: TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.w600)),
                ),
              ],
            ),
          ),

          const Divider(height: 1),

          // Available slots
          Expanded(
            child: _isLoadingSlots
                ? const Center(child: CircularProgressIndicator(color: Color(0xFF6B4EE6)))
                : _slots.isEmpty
                    ? Center(
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Icon(Icons.event_busy, size: 48, color: Colors.grey.shade300),
                            const SizedBox(height: 10),
                            const Text("No available slots", style: TextStyle(color: Colors.grey)),
                          ],
                        ),
                      )
                    : ListView(
                        padding: const EdgeInsets.all(16),
                        children: groupedSlots.entries.map((entry) {
                          return Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              // Date header
                              Padding(
                                padding: const EdgeInsets.symmetric(vertical: 8),
                                child: Text(
                                  _formatDate(entry.key),
                                  style: const TextStyle(
                                    fontSize: 15,
                                    fontWeight: FontWeight.bold,
                                    color: Color(0xFF2D2D3A),
                                  ),
                                ),
                              ),
                              // Time slots
                              Wrap(
                                spacing: 8,
                                runSpacing: 8,
                                children: entry.value.map<Widget>((slot) {
                                  final isSelected = _selectedSlot == slot;
                                  final fee = slot['fee'] ?? '50.00';
                                  final isAvailable = slot['is_available'] == true || slot['is_available'] == 1;

                                  return GestureDetector(
                                    onTap: isAvailable ? () => setState(() => _selectedSlot = slot) : null,
                                    child: AnimatedContainer(
                                      duration: const Duration(milliseconds: 200),
                                      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                                      decoration: BoxDecoration(
                                        gradient: isSelected
                                            ? const LinearGradient(colors: [Color(0xFF6B4EE6), Color(0xFF9B7EFA)])
                                            : null,
                                        color: isSelected
                                            ? null
                                            : isAvailable
                                                ? Colors.white
                                                : Colors.grey.shade100,
                                        borderRadius: BorderRadius.circular(12),
                                        border: Border.all(
                                          color: isSelected
                                              ? Colors.transparent
                                              : isAvailable
                                                  ? const Color(0xFF6B4EE6).withOpacity(0.3)
                                                  : Colors.grey.shade300,
                                        ),
                                        boxShadow: isSelected
                                            ? [BoxShadow(color: const Color(0xFF6B4EE6).withOpacity(0.3), blurRadius: 8)]
                                            : null,
                                      ),
                                      child: Column(
                                        children: [
                                          Text(
                                            _formatTime(slot['start_time']),
                                            style: TextStyle(
                                              fontWeight: FontWeight.bold,
                                              fontSize: 13,
                                              color: isSelected ? Colors.white : (isAvailable ? const Color(0xFF2D2D3A) : Colors.grey),
                                            ),
                                          ),
                                          const SizedBox(height: 2),
                                          Text(
                                            'RM $fee',
                                            style: TextStyle(
                                              fontSize: 10,
                                              fontWeight: FontWeight.w600,
                                              color: isSelected ? Colors.white70 : (isAvailable ? const Color(0xFF6B4EE6) : Colors.grey),
                                            ),
                                          ),
                                        ],
                                      ),
                                    ),
                                  );
                                }).toList(),
                              ),
                              const SizedBox(height: 8),
                            ],
                          );
                        }).toList(),
                      ),
          ),

          // Book & Pay Button
          Container(
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              color: Colors.white,
              boxShadow: [BoxShadow(color: Colors.grey.withOpacity(0.1), blurRadius: 10, offset: const Offset(0, -5))],
            ),
            child: InkWell(
              onTap: _selectedSlot != null && !_isBooking ? _bookSlot : null,
              borderRadius: BorderRadius.circular(15),
              child: AnimatedContainer(
                duration: const Duration(milliseconds: 300),
                width: double.infinity,
                padding: const EdgeInsets.symmetric(vertical: 16),
                decoration: BoxDecoration(
                  gradient: _selectedSlot != null
                      ? const LinearGradient(colors: [Color(0xFF6B4EE6), Color(0xFF9B7EFA)])
                      : null,
                  color: _selectedSlot == null ? Colors.grey.shade300 : null,
                  borderRadius: BorderRadius.circular(15),
                ),
                child: Center(
                  child: _isBooking
                      ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                      : Text(
                          _selectedSlot != null
                              ? "Pay RM ${_selectedSlot['fee'] ?? '50.00'} with ToyyibPay"
                              : "Select a time slot",
                          style: TextStyle(
                            color: _selectedSlot != null ? Colors.white : Colors.grey,
                            fontSize: 16,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                ),
              ),
            ),
          ),
        ],
      ),
    ).animate().slideY(begin: 0.3, duration: 300.ms, curve: Curves.easeOutBack);
  }
}