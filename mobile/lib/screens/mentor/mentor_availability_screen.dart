// lib/screens/mentor/mentor_availability_screen.dart
import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import '../services/api_service.dart';

class MentorAvailabilityScreen extends StatefulWidget {
  const MentorAvailabilityScreen({super.key});

  @override
  State<MentorAvailabilityScreen> createState() => _MentorAvailabilityScreenState();
}

class _MentorAvailabilityScreenState extends State<MentorAvailabilityScreen> {
  List<dynamic> _slots = [];
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _loadSlots();
  }

  Future<void> _loadSlots() async {
    final slots = await ApiService.getMyAvailabilitySlots();
    if (mounted) {
      setState(() {
        _slots = slots;
        _isLoading = false;
      });
    }
  }

  String _formatDate(String? dateStr) {
    if (dateStr == null) return 'No date';
    try {
      final dt = DateTime.parse(dateStr);
      const days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
      const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
      return '${days[dt.weekday - 1]}, ${dt.day} ${months[dt.month - 1]} ${dt.year}';
    } catch (_) {
      return dateStr;
    }
  }

  String _formatTime(String? time) {
    if (time == null) return '';
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

  Future<void> _deleteSlot(int slotId) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
        title: const Text("Delete Slot?"),
        content: const Text("This will remove the availability slot. Are you sure?"),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: const Text("Cancel"),
          ),
          ElevatedButton(
            onPressed: () => Navigator.pop(ctx, true),
            style: ElevatedButton.styleFrom(backgroundColor: Colors.redAccent),
            child: const Text("Delete", style: TextStyle(color: Colors.white)),
          ),
        ],
      ),
    );

    if (confirmed == true) {
      final success = await ApiService.deleteAvailabilitySlot(slotId);
      if (success && mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text("Slot deleted"), backgroundColor: Color(0xFF2E7D32), behavior: SnackBarBehavior.floating),
        );
        _loadSlots();
      }
    }
  }

  void _showAddSlotDialog() {
    DateTime selectedDate = DateTime.now().add(const Duration(days: 1));
    TimeOfDay startTime = const TimeOfDay(hour: 9, minute: 0);
    TimeOfDay endTime = const TimeOfDay(hour: 10, minute: 0);
    double fee = 50.0;
    final feeController = TextEditingController(text: '50.00');

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) {
        return StatefulBuilder(
          builder: (context, setSheetState) {
            return Container(
              padding: EdgeInsets.only(
                bottom: MediaQuery.of(context).viewInsets.bottom,
              ),
              decoration: const BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.vertical(top: Radius.circular(25)),
              ),
              child: Padding(
                padding: const EdgeInsets.all(24),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Handle
                    Center(
                      child: Container(
                        width: 40, height: 4,
                        decoration: BoxDecoration(color: Colors.grey.shade300, borderRadius: BorderRadius.circular(2)),
                      ),
                    ),
                    const SizedBox(height: 20),

                    // Title
                    const Text("Add Available Slot",
                        style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold, color: Color(0xFF2D2D3A))),
                    const SizedBox(height: 5),
                    Text("Set your available date, time and fee",
                        style: TextStyle(color: Colors.grey.shade600, fontSize: 13)),
                    const SizedBox(height: 24),

                    // Date picker
                    GestureDetector(
                      onTap: () async {
                        final picked = await showDatePicker(
                          context: context,
                          initialDate: selectedDate,
                          firstDate: DateTime.now(),
                          lastDate: DateTime.now().add(const Duration(days: 60)),
                          builder: (ctx, child) => Theme(
                            data: ThemeData.light().copyWith(
                              colorScheme: const ColorScheme.light(primary: Color(0xFF2E7D32)),
                            ),
                            child: child!,
                          ),
                        );
                        if (picked != null) setSheetState(() => selectedDate = picked);
                      },
                      child: Container(
                        padding: const EdgeInsets.all(16),
                        decoration: BoxDecoration(
                          color: const Color(0xFFF0F7F0),
                          borderRadius: BorderRadius.circular(14),
                          border: Border.all(color: const Color(0xFF2E7D32).withOpacity(0.3)),
                        ),
                        child: Row(
                          children: [
                            const Icon(Icons.calendar_today, color: Color(0xFF2E7D32)),
                            const SizedBox(width: 12),
                            Text(_formatDate(selectedDate.toIso8601String().substring(0, 10)),
                                style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 15)),
                            const Spacer(),
                            const Icon(Icons.arrow_drop_down, color: Color(0xFF2E7D32)),
                          ],
                        ),
                      ),
                    ),
                    const SizedBox(height: 16),

                    // Time pickers
                    Row(
                      children: [
                        Expanded(
                          child: GestureDetector(
                            onTap: () async {
                              final picked = await showTimePicker(
                                context: context,
                                initialTime: startTime,
                                builder: (ctx, child) => Theme(
                                  data: ThemeData.light().copyWith(
                                    colorScheme: const ColorScheme.light(primary: Color(0xFF2E7D32)),
                                  ),
                                  child: child!,
                                ),
                              );
                              if (picked != null) setSheetState(() => startTime = picked);
                            },
                            child: Container(
                              padding: const EdgeInsets.all(16),
                              decoration: BoxDecoration(
                                color: const Color(0xFFF0F7F0),
                                borderRadius: BorderRadius.circular(14),
                                border: Border.all(color: const Color(0xFF2E7D32).withOpacity(0.3)),
                              ),
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text("Start", style: TextStyle(fontSize: 11, color: Colors.grey.shade600)),
                                  const SizedBox(height: 4),
                                  Text(startTime.format(context),
                                      style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                                ],
                              ),
                            ),
                          ),
                        ),
                        const Padding(
                          padding: EdgeInsets.symmetric(horizontal: 12),
                          child: Icon(Icons.arrow_forward, color: Color(0xFF2E7D32)),
                        ),
                        Expanded(
                          child: GestureDetector(
                            onTap: () async {
                              final picked = await showTimePicker(
                                context: context,
                                initialTime: endTime,
                                builder: (ctx, child) => Theme(
                                  data: ThemeData.light().copyWith(
                                    colorScheme: const ColorScheme.light(primary: Color(0xFF2E7D32)),
                                  ),
                                  child: child!,
                                ),
                              );
                              if (picked != null) setSheetState(() => endTime = picked);
                            },
                            child: Container(
                              padding: const EdgeInsets.all(16),
                              decoration: BoxDecoration(
                                color: const Color(0xFFF0F7F0),
                                borderRadius: BorderRadius.circular(14),
                                border: Border.all(color: const Color(0xFF2E7D32).withOpacity(0.3)),
                              ),
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text("End", style: TextStyle(fontSize: 11, color: Colors.grey.shade600)),
                                  const SizedBox(height: 4),
                                  Text(endTime.format(context),
                                      style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                                ],
                              ),
                            ),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 16),

                    // Fee input
                    Container(
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        color: const Color(0xFFF0F7F0),
                        borderRadius: BorderRadius.circular(14),
                        border: Border.all(color: const Color(0xFF2E7D32).withOpacity(0.3)),
                      ),
                      child: Row(
                        children: [
                          const Icon(Icons.attach_money, color: Color(0xFF2E7D32)),
                          const SizedBox(width: 8),
                          const Text("Fee (RM)", style: TextStyle(fontWeight: FontWeight.w600)),
                          const Spacer(),
                          SizedBox(
                            width: 80,
                            child: TextField(
                              controller: feeController,
                              keyboardType: const TextInputType.numberWithOptions(decimal: true),
                              textAlign: TextAlign.right,
                              style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 18, color: Color(0xFF2E7D32)),
                              decoration: const InputDecoration(
                                border: InputBorder.none,
                                isDense: true,
                              ),
                              onChanged: (val) => fee = double.tryParse(val) ?? 50.0,
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 24),

                    // Save button
                    InkWell(
                      onTap: () async {
                        fee = double.tryParse(feeController.text) ?? 50.0;
                        final dateStr = selectedDate.toIso8601String().substring(0, 10);
                        final startStr = '${startTime.hour.toString().padLeft(2, '0')}:${startTime.minute.toString().padLeft(2, '0')}';
                        final endStr = '${endTime.hour.toString().padLeft(2, '0')}:${endTime.minute.toString().padLeft(2, '0')}';

                        Navigator.pop(ctx);

                        final result = await ApiService.createAvailabilitySlot(
                          date: dateStr,
                          startTime: startStr,
                          endTime: endStr,
                          fee: fee,
                        );

                        if (mounted) {
                          if (result['success'] == true) {
                            ScaffoldMessenger.of(context).showSnackBar(
                              const SnackBar(
                                content: Text("Slot added successfully!"),
                                backgroundColor: Color(0xFF2E7D32),
                                behavior: SnackBarBehavior.floating,
                              ),
                            );
                            _loadSlots();
                          } else {
                            ScaffoldMessenger.of(context).showSnackBar(
                              SnackBar(
                                content: Text(result['message'] ?? 'Failed to add slot'),
                                backgroundColor: Colors.redAccent,
                                behavior: SnackBarBehavior.floating,
                              ),
                            );
                          }
                        }
                      },
                      borderRadius: BorderRadius.circular(15),
                      child: Container(
                        width: double.infinity,
                        padding: const EdgeInsets.symmetric(vertical: 16),
                        decoration: BoxDecoration(
                          gradient: const LinearGradient(colors: [Color(0xFF2E7D32), Color(0xFF66BB6A)]),
                          borderRadius: BorderRadius.circular(15),
                          boxShadow: [BoxShadow(color: const Color(0xFF2E7D32).withOpacity(0.3), blurRadius: 10, offset: const Offset(0, 5))],
                        ),
                        child: const Center(
                          child: Text("Add Slot",
                              style: TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.bold)),
                        ),
                      ),
                    ),
                    const SizedBox(height: 10),
                  ],
                ),
              ),
            );
          },
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    // Group slots by date
    Map<String, List<dynamic>> grouped = {};
    for (var slot in _slots) {
      final date = slot['date']?.toString() ?? 'Recurring';
      grouped.putIfAbsent(date, () => []);
      grouped[date]!.add(slot);
    }

    // Sort by date
    final sortedDates = grouped.keys.toList()..sort();

    return Scaffold(
      backgroundColor: const Color(0xFFF0F7F0),
      appBar: AppBar(
        title: const Text("My Availability", style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
        backgroundColor: const Color(0xFF2E7D32),
        elevation: 0,
        iconTheme: const IconThemeData(color: Colors.white),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: () {
              setState(() => _isLoading = true);
              _loadSlots();
            },
          ),
        ],
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: _showAddSlotDialog,
        backgroundColor: const Color(0xFF2E7D32),
        icon: const Icon(Icons.add, color: Colors.white),
        label: const Text("Add Slot", style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: Color(0xFF2E7D32)))
          : _slots.isEmpty
              ? Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(Icons.event_note, size: 64, color: Colors.grey.shade300),
                      const SizedBox(height: 12),
                      const Text("No availability slots yet",
                          style: TextStyle(fontSize: 16, fontWeight: FontWeight.w600, color: Colors.grey)),
                      const SizedBox(height: 6),
                      Text("Tap + to add your first slot",
                          style: TextStyle(color: Colors.grey.shade500)),
                    ],
                  ).animate().fadeIn(duration: 500.ms),
                )
              : ListView(
                  padding: const EdgeInsets.all(16),
                  children: [
                    // Summary card
                    Container(
                      padding: const EdgeInsets.all(20),
                      margin: const EdgeInsets.only(bottom: 20),
                      decoration: BoxDecoration(
                        gradient: const LinearGradient(colors: [Color(0xFF2E7D32), Color(0xFF66BB6A)]),
                        borderRadius: BorderRadius.circular(20),
                        boxShadow: [BoxShadow(color: const Color(0xFF2E7D32).withOpacity(0.3), blurRadius: 15)],
                      ),
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.spaceAround,
                        children: [
                          _summaryItem("Total Slots", _slots.length.toString(), Icons.event_available),
                          _summaryItem("Available", _slots.where((s) => s['is_available'] == true || s['is_available'] == 1).length.toString(), Icons.check_circle),
                          _summaryItem("Booked", _slots.where((s) => s['is_available'] == false || s['is_available'] == 0).length.toString(), Icons.book_online),
                        ],
                      ),
                    ).animate().fadeIn(duration: 400.ms),

                    // Slots grouped by date
                    ...sortedDates.map((date) {
                      final dateSlots = grouped[date]!;
                      return Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Padding(
                            padding: const EdgeInsets.symmetric(vertical: 8),
                            child: Text(_formatDate(date),
                                style: const TextStyle(fontSize: 15, fontWeight: FontWeight.bold, color: Color(0xFF2D2D3A))),
                          ),
                          ...dateSlots.map((slot) => _slotCard(slot)),
                          const SizedBox(height: 8),
                        ],
                      );
                    }),

                    const SizedBox(height: 80), // Space for FAB
                  ],
                ),
    );
  }

  Widget _summaryItem(String label, String value, IconData icon) {
    return Column(
      children: [
        Icon(icon, color: Colors.white, size: 24),
        const SizedBox(height: 6),
        Text(value, style: const TextStyle(color: Colors.white, fontSize: 20, fontWeight: FontWeight.bold)),
        Text(label, style: TextStyle(color: Colors.white.withOpacity(0.7), fontSize: 11)),
      ],
    );
  }

  Widget _slotCard(dynamic slot) {
    final bool isAvailable = slot['is_available'] == true || slot['is_available'] == 1;
    final fee = slot['fee'] ?? '50.00';
    final bookedCount = slot['booked_slots'] ?? 0;

    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: isAvailable ? const Color(0xFF2E7D32).withOpacity(0.2) : Colors.red.withOpacity(0.2),
        ),
        boxShadow: [BoxShadow(color: Colors.green.withOpacity(0.03), blurRadius: 8)],
      ),
      child: Row(
        children: [
          // Time badge
          Container(
            padding: const EdgeInsets.all(10),
            decoration: BoxDecoration(
              color: isAvailable ? const Color(0xFFE8F5E9) : Colors.red.shade50,
              borderRadius: BorderRadius.circular(12),
            ),
            child: Icon(
              isAvailable ? Icons.access_time_filled : Icons.block,
              color: isAvailable ? const Color(0xFF2E7D32) : Colors.red,
              size: 22,
            ),
          ),
          const SizedBox(width: 14),
          // Details
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  '${_formatTime(slot['start_time'])} - ${_formatTime(slot['end_time'])}',
                  style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15),
                ),
                const SizedBox(height: 3),
                Row(
                  children: [
                    Text('RM $fee', style: const TextStyle(color: Color(0xFF2E7D32), fontWeight: FontWeight.w600, fontSize: 13)),
                    const SizedBox(width: 10),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                      decoration: BoxDecoration(
                        color: isAvailable ? const Color(0xFFE8F5E9) : Colors.red.shade50,
                        borderRadius: BorderRadius.circular(6),
                      ),
                      child: Text(
                        isAvailable ? 'Available' : 'Booked ($bookedCount)',
                        style: TextStyle(
                          fontSize: 10,
                          fontWeight: FontWeight.w600,
                          color: isAvailable ? const Color(0xFF2E7D32) : Colors.red,
                        ),
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
          // Delete button
          if (isAvailable)
            IconButton(
              icon: Icon(Icons.delete_outline, color: Colors.red.shade300, size: 20),
              onPressed: () => _deleteSlot(slot['id']),
            ),
        ],
      ),
    );
  }
}
