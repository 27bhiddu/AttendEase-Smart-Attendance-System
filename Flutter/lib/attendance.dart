import 'dart:math';
import 'dart:ui';

import 'package:flutter/material.dart';
import 'package:flutter_card_swiper/flutter_card_swiper.dart';

import 'lists.dart';
import 'add_student_page.dart';
import 'student.dart';
import 'directus_service.dart';
import 'attendance_summary_page.dart';

class AttendancePage extends StatefulWidget {
  final String branch;
  /// Can be "Semester 3" or "3"
  final String semester;
  final int teacherId;

  // New fields to track Subject & Type
  final String? subjectId;
  final String? subjectCode;
  final String? classType; // "theory" / "laboratory"

  const AttendancePage({
    super.key,
    required this.branch,
    required this.semester,
    required this.teacherId,
    this.subjectId,
    this.subjectCode,
    this.classType,
  });

  @override
  State<AttendancePage> createState() => _AttendancePageState();
}

class _AttendancePageState extends State<AttendancePage> {
  final CardSwiperController _controller = CardSwiperController();

  List<Student> students = [];
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _fetchStudents();
  }

  Future<void> _fetchStudents() async {

    setState(() => _isLoading = true);

    try {
      // 🔥 DEBUG 1: API Call
      final allStudents = await DirectusService.getStudents(limit: 500);
      debugPrint('🔥 TOTAL STUDENTS FROM API: ${allStudents.length}');

      if (allStudents.isEmpty) {
        debugPrint('❌ NO STUDENTS IN DIRECTUS - Check getStudents() or add test data');
      }

      final searchBranch = widget.branch.trim().toLowerCase();
      final searchSemester = widget.semester
          .toLowerCase()
          .replaceAll(RegExp(r'[^0-9]'), '');
      debugPrint('🎯 TARGET: branch="$searchBranch" semester="$searchSemester"');

      // Filter the list
      students = allStudents.where((s) {
        final sBranch = (s.branch ?? '').toString().trim().toLowerCase();
        final sSem = (s.semester ?? '')
            .toString()
            .toLowerCase()
            .replaceAll(RegExp(r'[^0-9]'), '');
        final branchMatch = sBranch == searchBranch || searchBranch.contains(sBranch);
        final semMatch = sSem.contains(searchSemester) || searchSemester.contains(sSem);

        return branchMatch && semMatch;
      }).toList();

      // ✅ ADDED: Sort Alphabetically (A-Z)
      students.sort((a, b) => a.name.toLowerCase().compareTo(b.name.toLowerCase()));

      debugPrint('🎉 FINAL MATCHED & SORTED: ${students.length} students');

      setState(() => _isLoading = false);
    } catch (e) {
      debugPrint('💥 FETCH ERROR: $e');
      setState(() => _isLoading = false);
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Load failed: $e'), action: SnackBarAction(
            label: 'Retry',
            onPressed: _fetchStudents,
          )),
        );
      }
    }
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  bool _onSwipe(
      int previousIndex,
      int? currentIndex,
      CardSwiperDirection direction,
      ) {
    final student = students[previousIndex];

    if (direction == CardSwiperDirection.left) {
      setState(() => student.isPresent = true);
    } else if (direction == CardSwiperDirection.top) {
      setState(() => student.isPresent = false);
    } else {
      return false;
    }

    if (currentIndex == null) {
      Future.delayed(const Duration(milliseconds: 200), () {
        _showAttendanceSummary();
      });
      return true;
    }

    return true;
  }

  void _onEnd() {
    if (students.any((s) => s.isPresent != null)) {
      _showAttendanceSummary();
    }
  }

  void _openAddStudentPage() async {
    await Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => AddStudentPage(
          onStudentAdded: (newStudent) {},
        ),
      ),
    );
    await _fetchStudents();  // Refresh after adding student
  }

  void _goToListsPage() {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => Lists(
          students: students,
          teacherId: widget.teacherId,
        ),
      ),
    );
  }

  // ✅ UPDATED: Pass Subject & ClassType to the Summary Page
  void _showAttendanceSummary() {
    if (!mounted) return;

    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => AttendanceSummaryPage(
          students: students,
          teacherId: widget.teacherId,
          // Sending these so they can be saved to DB

        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      extendBodyBehindAppBar: true,
      appBar: AppBar(
        title: Text(
          'Mark Attendance${widget.subjectCode != null ? ' - ${widget.subjectCode}${widget.classType != null ? ' (${widget.classType!.toUpperCase()})' : ''}' : ''}',
          style: const TextStyle(
            color: Colors.white,
            shadows: [Shadow(blurRadius: 4, color: Colors.black)],
          ),
        ),
        backgroundColor: Colors.black.withValues(alpha: 0.4),
        elevation: 0,
        iconTheme: const IconThemeData(color: Colors.white),
        actions: [
          IconButton(
            icon: const Icon(Icons.list_alt, color: Colors.white),
            onPressed: _goToListsPage,
          ),
          IconButton(
            icon: const Icon(Icons.add, color: Colors.white),
            onPressed: _openAddStudentPage,
          ),
        ],
      ),
      body: _isLoading
          ? const Center(
        child: CircularProgressIndicator(color: Colors.white),
      )
          : students.isEmpty
          ? Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const Icon(Icons.school_outlined, size: 64, color: Colors.white54),
            const SizedBox(height: 16),
            Text(
              "No students found for ${widget.branch} • ${widget.semester}",
              style: const TextStyle(color: Colors.white70, fontSize: 16),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 8),
            const Text(
              "Check that branch & semester spellings match exactly.",
              style: TextStyle(color: Colors.white54, fontSize: 14),
            ),
            const SizedBox(height: 20),
            ElevatedButton(
              onPressed: _fetchStudents,
              child: const Text("Retry"),
            )
          ],
        ),
      )
          : Stack(
        children: [
          CardSwiper(
            controller: _controller,
            cardsCount: students.length,
            onSwipe: _onSwipe,
            onEnd: _onEnd,
            numberOfCardsDisplayed:
            students.length < 2 ? students.length : 2,
            backCardOffset: const Offset(20, 20),
            padding: EdgeInsets.zero,
            allowedSwipeDirection:
            const AllowedSwipeDirection.only(
              left: true,
              right: false,
              up: true,
              down: false,
            ),
            cardBuilder: (
                context,
                index,
                horizontalPct,
                verticalPct,
                ) {
              final student = students[index];
              return Stack(
                children: [
                  _buildCardContent(student),
                  _buildSwipeOverlay(horizontalPct, verticalPct),
                  _buildMarkedBadge(student),
                ],
              );
            },
          ),
          Positioned(
            bottom: 40,
            left: 0,
            right: 0,
            child: Container(
              padding:
              const EdgeInsets.symmetric(vertical: 10),
              decoration: const BoxDecoration(
                gradient: LinearGradient(
                  begin: Alignment.bottomCenter,
                  end: Alignment.topCenter,
                  colors: [
                    Colors.black87,
                    Colors.transparent
                  ],
                ),
              ),
              child: const Text(
                "Swipe Left ⬅ Present\n"
                    "Swipe Up ⬆ Absent",
                textAlign: TextAlign.center,
                style: TextStyle(
                  color: Colors.white70,
                  fontWeight: FontWeight.bold,
                  fontSize: 14,
                  height: 1.4,
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildCardContent(Student student) {
    return Stack(
      fit: StackFit.expand,
      children: [
        Positioned.fill(
          child: Image.network(
            student.imageUrl,
            fit: BoxFit.cover,
            loadingBuilder:
                (context, child, loadingProgress) {
              if (loadingProgress == null) return child;
              return const Center(
                child:
                CircularProgressIndicator(color: Colors.white),
              );
            },
            errorBuilder: (context, error, stackTrace) => Container(
              color: Colors.grey.shade900,
              child: const Center(
                child: Icon(Icons.person,
                    size: 120, color: Colors.white54),
              ),
            ),
          ),
        ),
        Positioned(
          bottom: 110,
          left: 20,
          right: 20,
          child: ClipRRect(
            borderRadius: BorderRadius.circular(20),
            child: BackdropFilter(
              filter: ImageFilter.blur(sigmaX: 10, sigmaY: 10),
              child: Container(
                padding: const EdgeInsets.all(20),
                decoration: BoxDecoration(
                  color:
                  Colors.black.withValues(alpha: 0.6),
                  border: Border.all(
                    color: Colors.white.withValues(alpha: 0.2),
                  ),
                  borderRadius: BorderRadius.circular(20),
                ),
                child: Column(
                  crossAxisAlignment:
                  CrossAxisAlignment.start,
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Text(
                      student.name,
                      style: const TextStyle(
                        fontSize: 32,
                        fontWeight: FontWeight.bold,
                        color: Colors.white,
                      ),
                    ),
                    const SizedBox(height: 12),
                    Row(
                      children: [
                        const Icon(Icons.badge_outlined,
                            color: Colors.white70, size: 20),
                        const SizedBox(width: 8),
                        Text(
                          'Roll No: ${student.rollNumber}',
                          style: const TextStyle(
                            fontSize: 18,
                            color: Colors.white,
                            fontWeight: FontWeight.w500,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 8),
                    Row(
                      children: [
                        const Icon(Icons.school_outlined,
                            color: Colors.white70, size: 20),
                        const SizedBox(width: 8),
                        Text(
                          '${student.branch ?? widget.branch} • '
                              '${student.semester ?? widget.semester}',
                          style:
                          const TextStyle(
                            fontSize: 18,
                            color: Colors.white70,
                          ),
                        ),
                      ],
                    ),
                    if (widget.subjectCode != null)
                      const SizedBox(height: 8),
                    if (widget.subjectCode != null)
                      Row(
                        children: [
                          const Icon(Icons.book_outlined,
                              color: Colors.white70,
                              size: 20),
                          const SizedBox(width: 8),
                          Text(
                            widget.subjectCode!,
                            style: const TextStyle(
                              fontSize: 16,
                              color: Colors.white70,
                            ),
                          ),
                        ],
                      ),
                  ],
                ),
              ),
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildSwipeOverlay(
      int horizontalPercentage, int verticalPercentage) {
    if (horizontalPercentage < -20) {
      return Center(
        child: Transform.rotate(
          angle: -pi / 6,
          child: Container(
            padding: const EdgeInsets.symmetric(
                horizontal: 20, vertical: 8),
            decoration: BoxDecoration(
              border:
              Border.all(color: Colors.green, width: 4),
              borderRadius: BorderRadius.circular(12),
            ),
            child: const Text(
              'PRESENT',
              style: TextStyle(
                color: Colors.green,
                fontSize: 48,
                fontWeight: FontWeight.bold,
                letterSpacing: 4,
              ),
            ),
          ),
        ),
      );
    } else if (verticalPercentage < -20) {
      return Center(
        child: Container(
          padding: const EdgeInsets.symmetric(
              horizontal: 20, vertical: 8),
          decoration: BoxDecoration(
            border: Border.all(color: Colors.red, width: 4),
            borderRadius: BorderRadius.circular(12),
          ),
          child: const Text(
            'ABSENT',
            style: TextStyle(
              color: Colors.red,
              fontSize: 48,
              fontWeight: FontWeight.bold,
              letterSpacing: 4,
            ),
          ),
        ),
      );
    }

    return const SizedBox.shrink();
  }

  Widget _buildMarkedBadge(Student student) {
    if (student.isPresent == null) {
      return const SizedBox.shrink();
    }

    final bool present = student.isPresent == true;
    final Color color = present ? Colors.green : Colors.red;
    final String text = present ? 'Marked P' : 'Marked A';

    return Positioned(
      top: 40,
      right: 20,
      child: Container(
        padding: const EdgeInsets.symmetric(
            horizontal: 10, vertical: 4),
        decoration: BoxDecoration(
          color: color.withValues(alpha: 0.9),
          borderRadius: BorderRadius.circular(12),
        ),
        child: Text(
          text,
          style: const TextStyle(
            color: Colors.white,
            fontSize: 12,
            fontWeight: FontWeight.w600,
          ),
        ),
      ),
    );
  }
}