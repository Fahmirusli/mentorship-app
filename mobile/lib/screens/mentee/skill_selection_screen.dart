// lib/screens/mentee/skill_selection_screen.dart
import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import '../services/api_service.dart';
import 'nearby_mentors.dart';

class SkillSelectionScreen extends StatefulWidget {
  /// Optional list of skills pre-loaded from dashboard data
  final List<String> availableSkills;

  const SkillSelectionScreen({super.key, this.availableSkills = const []});

  @override
  State<SkillSelectionScreen> createState() => _SkillSelectionScreenState();
}

class _SkillSelectionScreenState extends State<SkillSelectionScreen> {
  String? _selectedSkill;
  List<String> _skills = [];
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _loadSkills();
  }

  Future<void> _loadSkills() async {
    // If skills were already passed in, use those
    if (widget.availableSkills.isNotEmpty) {
      setState(() {
        _skills = widget.availableSkills;
        _isLoading = false;
      });
      return;
    }

    // Otherwise fetch from database
    final dbSkills = await ApiService.getMentorSkills();
    if (mounted) {
      setState(() {
        _skills = dbSkills.isNotEmpty ? dbSkills : _defaultSkills;
        _isLoading = false;
      });
    }
  }

  // Fallback skills only used if API returns empty
  final List<String> _defaultSkills = [
    'Flutter', 'React', 'Laravel', 'Python', 'Node.js', 'AWS',
    'Machine Learning', 'Data Science', 'UI/UX Design',
    'Cybersecurity', 'DevOps', 'System Architecture',
  ];

  // Gradient colors for skill chips
  List<Color> _getChipColors(int index) {
    const colorSets = [
      [Color(0xFFFF9A9E), Color(0xFFFECFEF)],
      [Color(0xFF667EEA), Color(0xFF764BA2)],
      [Color(0xFFFF758C), Color(0xFFFF7EB3)],
      [Color(0xFF43E97B), Color(0xFF38F9D7)],
      [Color(0xFFFFA726), Color(0xFFFF7043)],
      [Color(0xFF5C6BC0), Color(0xFF7E57C2)],
    ];
    return colorSets[index % colorSets.length];
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF4F3FB),
      appBar: AppBar(
        title: const Text("Choose a Skill",
            style: TextStyle(color: Colors.black87, fontWeight: FontWeight.bold)),
        backgroundColor: Colors.transparent,
        elevation: 0,
        iconTheme: const IconThemeData(color: Colors.black87),
        centerTitle: true,
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: Color(0xFF6B4EE6)))
          : Column(
              children: [
                // Header illustration
                Container(
                  margin: const EdgeInsets.all(20),
                  padding: const EdgeInsets.all(24),
                  decoration: BoxDecoration(
                    gradient: const LinearGradient(
                        colors: [Color(0xFF6B4EE6), Color(0xFF9B7EFA)]),
                    borderRadius: BorderRadius.circular(20),
                    boxShadow: [
                      BoxShadow(
                          color: const Color(0xFF6B4EE6).withOpacity(0.3),
                          blurRadius: 15,
                          offset: const Offset(0, 5)),
                    ],
                  ),
                  child: Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.all(12),
                        decoration: BoxDecoration(
                          color: Colors.white.withOpacity(0.2),
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: const Icon(Icons.school_rounded,
                            color: Colors.white, size: 32),
                      ),
                      const SizedBox(width: 16),
                      const Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text("What do you want to learn?",
                                style: TextStyle(
                                    color: Colors.white,
                                    fontSize: 18,
                                    fontWeight: FontWeight.bold)),
                            SizedBox(height: 4),
                            Text(
                                "Select a skill to find mentors who can guide you",
                                style: TextStyle(
                                    color: Colors.white70, fontSize: 13)),
                          ],
                        ),
                      ),
                    ],
                  ),
                ).animate().fadeIn(duration: 400.ms).slideY(begin: -0.1),

                // Skills count badge
                Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 24),
                  child: Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                        decoration: BoxDecoration(
                          color: const Color(0xFFEDE7F6),
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: Text(
                          '${_skills.length} skills from mentors',
                          style: const TextStyle(fontSize: 11, color: Color(0xFF6B4EE6), fontWeight: FontWeight.w600),
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 10),

                // Skill Grid
                Expanded(
                  child: Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 20),
                    child: GridView.builder(
                      gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                        crossAxisCount: 2,
                        childAspectRatio: 2.2,
                        crossAxisSpacing: 12,
                        mainAxisSpacing: 12,
                      ),
                      itemCount: _skills.length,
                      itemBuilder: (context, index) {
                        final skill = _skills[index];
                        final isSelected = _selectedSkill == skill;
                        final colors = _getChipColors(index);

                        return GestureDetector(
                          onTap: () => setState(() => _selectedSkill = skill),
                          child: AnimatedContainer(
                            duration: const Duration(milliseconds: 250),
                            curve: Curves.easeOutCubic,
                            decoration: BoxDecoration(
                              gradient: isSelected
                                  ? LinearGradient(colors: colors)
                                  : null,
                              color: isSelected ? null : Colors.white,
                              borderRadius: BorderRadius.circular(16),
                              border: isSelected
                                  ? null
                                  : Border.all(
                                      color: Colors.grey.shade200, width: 1.5),
                              boxShadow: isSelected
                                  ? [
                                      BoxShadow(
                                          color: colors[0].withOpacity(0.4),
                                          blurRadius: 12,
                                          offset: const Offset(0, 4))
                                    ]
                                  : [
                                      BoxShadow(
                                          color: Colors.purple.withOpacity(0.03),
                                          blurRadius: 8)
                                    ],
                            ),
                            child: Center(
                              child: Row(
                                mainAxisAlignment: MainAxisAlignment.center,
                                children: [
                                  if (isSelected)
                                    const Padding(
                                      padding: EdgeInsets.only(right: 6),
                                      child: Icon(Icons.check_circle,
                                          color: Colors.white, size: 18),
                                    ),
                                  Flexible(
                                    child: Text(
                                      skill,
                                      style: TextStyle(
                                        fontWeight: FontWeight.w600,
                                        fontSize: 14,
                                        color: isSelected
                                            ? Colors.white
                                            : const Color(0xFF2D2D3A),
                                      ),
                                      textAlign: TextAlign.center,
                                      overflow: TextOverflow.ellipsis,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          ),
                        )
                            .animate()
                            .fadeIn(delay: Duration(milliseconds: index * 60))
                            .slideY(begin: 0.15, curve: Curves.easeOutBack);
                      },
                    ),
                  ),
                ),

                // Continue Button (With extra bottom padding for floating nav bar)
                Padding(
                  padding: const EdgeInsets.only(left: 20, right: 20, top: 20, bottom: 130),
                  child: InkWell(
                    onTap: _selectedSkill != null
                        ? () {
                            Navigator.push(
                              context,
                              MaterialPageRoute(
                                builder: (context) => NearbyMentorsScreen(
                                  selectedSkill: _selectedSkill,
                                ),
                              ),
                            );
                          }
                        : null,
                    borderRadius: BorderRadius.circular(15),
                    child: AnimatedContainer(
                      duration: const Duration(milliseconds: 300),
                      width: double.infinity,
                      padding: const EdgeInsets.symmetric(vertical: 16),
                      decoration: BoxDecoration(
                        gradient: _selectedSkill != null
                            ? const LinearGradient(
                                colors: [Color(0xFF6B4EE6), Color(0xFF9B7EFA)])
                            : null,
                        color: _selectedSkill == null ? Colors.grey.shade300 : null,
                        borderRadius: BorderRadius.circular(15),
                        boxShadow: _selectedSkill != null
                            ? [
                                BoxShadow(
                                    color: const Color(0xFF6B4EE6).withOpacity(0.3),
                                    blurRadius: 10,
                                    offset: const Offset(0, 5))
                              ]
                            : [],
                      ),
                      child: Center(
                        child: Text(
                          _selectedSkill != null
                              ? "Find Mentors for $_selectedSkill"
                              : "Select a Skill to Continue",
                          style: TextStyle(
                            color:
                                _selectedSkill != null ? Colors.white : Colors.grey,
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
    );
  }
}
