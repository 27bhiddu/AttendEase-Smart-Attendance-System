import 'package:flutter/material.dart';
import 'student_list.dart'; // Contains StudentAttendanceDetailPage
import 'student.dart';
import 'directus_service.dart';
import 'branch_semester.dart';
import 'add_student_page.dart';

class Lists extends StatefulWidget {
  final List<Student> students;
  final int teacherId;

  const Lists({
    super.key,
    required this.students,
    required this.teacherId,
  });

  @override
  State<Lists> createState() => _ListsState();
}

class _ListsState extends State<Lists> {
  late List<Student> _students;
  String? _selectedFilter;
  DateTime _selectedDate = DateTime.now();

  @override
  void initState() {
    super.initState();
    // Create a mutable copy of the student list so we can modify 'isPresent' locally
    _students = List<Student>.from(widget.students);
  }

  // --- Logic Methods ---

  void _markAttendance(Student student, bool isPresent) {
    setState(() {
      // Toggle logic: If clicking the same status again, reset to null (Pending)
      if (student.isPresent == isPresent) {
        student.isPresent = null;
      } else {
        student.isPresent = isPresent;
      }
    });
  }

  List<Student> _getFilteredStudents() {
    return _students.where((student) {
      if (_selectedFilter == 'Present') return student.isPresent == true;
      if (_selectedFilter == 'Absent') return student.isPresent == false;
      if (_selectedFilter == 'Pending') return student.isPresent == null;
      return true;
    }).toList();
  }

  Map<String, int> _getStats() {
    int p = 0, a = 0, pending = 0;
    for (var s in _students) {
      if (s.isPresent == true) {
        p++;
      } else if (s.isPresent == false) {
        a++;
      } else {
        pending++;
      }
    }
    return {'p': p, 'a': a, 'pending': pending};
  }

  Future<void> _pickDate(BuildContext context) async {
    final DateTime? picked = await showDatePicker(
      context: context,
      initialDate: _selectedDate,
      firstDate: DateTime(2000),
      lastDate: DateTime(2101),
      builder: (context, child) {
        return Theme(
          data: Theme.of(context).copyWith(
            colorScheme: const ColorScheme.light(
              primary: Colors.indigo,
              onPrimary: Colors.white,
            ),
          ),
          child: child!,
        );
      },
    );

    if (picked != null && picked != _selectedDate) {
      setState(() => _selectedDate = picked);
    }
  }

  void _saveAttendance() {
    // Basic validation: Ensure at least one student is marked
    final toSave = _students.where((s) => s.isPresent != null).toList();
    if (toSave.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text("Please mark attendance for at least one student.")),
      );
      return;
    }

    showDialog(
      context: context,
      builder: (dialogContext) => AlertDialog(
        title: const Text('Finalize Attendance'),
        content: Text(
          'Are you sure you want to save records for '
              '${_selectedDate.day}/${_selectedDate.month}/${_selectedDate.year}?',
        ),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(dialogContext),
            child: const Text('Cancel'),
          ),
          ElevatedButton(
            onPressed: () => _processSave(dialogContext),
            style: ElevatedButton.styleFrom(backgroundColor: Colors.indigo),
            child: const Text('Confirm', style: TextStyle(color: Colors.white)),
          ),
        ],
      ),
    );
  }

  Future<void> _processSave(BuildContext dialogContext) async {
    Navigator.pop(dialogContext); // Close confirmation dialog

    // 1. Show Loading SnackBar
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(
        content: Text('Processing API request...'),
        duration: Duration(milliseconds: 800),
      ),
    );

    try {
      final toSave = _students.where((s) => s.isPresent != null).toList();
      final dateStr =
          '${_selectedDate.year}-${_selectedDate.month.toString().padLeft(2, '0')}-${_selectedDate.day.toString().padLeft(2, '0')}';

      // Validation check for Teacher ID (Required for API)
      if (widget.teacherId == 0) {
        throw Exception("Teacher ID is missing (0). Cannot save.");
      }

      for (final s in toSave) {
        // A. Save to Server via API
        final bool success = await DirectusService.saveAttendanceForStudent(
          branch: s.branch ?? '',
          semester: s.semester ?? '',
          date: dateStr,
          studentId: s.id,
          studentName: s.name,
          isPresent: s.isPresent == true,
          teacherId: widget.teacherId,
        );

        // 🛑 STOP IF FAILED
        if (!success) {
          throw Exception("API Error: Failed to save data for ${s.name}.");
        }

        // B. Update Local State (Only if API success is true)
        setState(() {
          s.totalClasses += 1;
          if (s.isPresent == true) s.presentClasses += 1;

          // C. Reset Selection (Clear pending state)
          s.isPresent = null;
        });
      }

      if (!mounted) return;

      // 2. Show SUCCESS DIALOG
      showDialog(
        context: context,
        barrierDismissible: false,
        builder: (ctx) => AlertDialog(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
          title: const Row(
            children: [
              Icon(Icons.check_circle, color: Colors.green),
              SizedBox(width: 10),
              Text("Saved", style: TextStyle(color: Colors.green, fontWeight: FontWeight.bold)),
            ],
          ),
          content: const Text("Attendance records uploaded successfully."),
          actions: [
            TextButton(
              onPressed: () {
                Navigator.pop(ctx); // Close Dialog
              },
              child: const Text("OK", style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Colors.indigo)),
            ),
          ],
        ),
      );

    } catch (e) {
      if (!mounted) return;
      // 🚨 SHOW ERROR DIALOG
      showDialog(
        context: context,
        builder: (ctx) => AlertDialog(
          title: const Text("API Error", style: TextStyle(color: Colors.red)),
          content: Text(e.toString()),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(ctx),
              child: const Text("Close"),
            )
          ],
        ),
      );
    }
  }

  void _navigateToBranchSemester() {
    Navigator.pushReplacement(
      context,
      MaterialPageRoute(builder: (_) => const BranchSemesterPage()),
    );
  }

  void _openStudentDetails(Student student) {
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (_) => StudentAttendanceDetailPage(student: student),
      ),
    );
  }

  // --- UI Building Methods ---

  @override
  Widget build(BuildContext context) {
    final filteredStudents = _getFilteredStudents();
    final stats = _getStats();

    final months = [
      'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
      'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
    ];
    final dateStr =
        "${_selectedDate.day} ${months[_selectedDate.month - 1]} ${_selectedDate.year}";

    // Force user to go back to Branch Selection on 'Back'
    return PopScope(
      canPop: false,
      onPopInvokedWithResult: (didPop, result) {
        if (didPop) return;
        _navigateToBranchSemester();
      },
      child: Scaffold(
        backgroundColor: const Color(0xFFF5F7FA),

        appBar: AppBar(
          title: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Text('Mark Attendance',
                  style: TextStyle(
                      color: Colors.black87,
                      fontWeight: FontWeight.bold,
                      fontSize: 18)),
              Text(dateStr,
                  style: TextStyle(
                      color: Colors.indigo.shade400,
                      fontSize: 13,
                      fontWeight: FontWeight.w600)),
            ],
          ),
          backgroundColor: Colors.white,
          elevation: 0,
          leading: IconButton(
            icon: const Icon(Icons.arrow_back_ios_new,
                color: Colors.black87, size: 20),
            onPressed: _navigateToBranchSemester,
          ),
          actions: [
            IconButton(
              onPressed: () {
                Navigator.push(
                  context,
                  MaterialPageRoute(
                    builder: (context) => AddStudentPage(
                      onStudentAdded: (Student newStudent) {
                        setState(() {
                          _students.add(newStudent);
                        });
                      },
                    ),
                  ),
                );
              },
              icon: const Icon(Icons.person_add_alt_1_outlined, color: Colors.indigo),
              tooltip: "Add Student",
            ),
            /*IconButton(
              onPressed: () => _pickDate(context),
              icon: const Icon(Icons.calendar_month_outlined,
                  color: Colors.indigo),
              tooltip: "Change Date",
            ),*/
            const SizedBox(width: 8),
          ],
        ),

        body: Column(
          children: [
            // 1. Dashboard & Filters
            Container(
              padding: const EdgeInsets.fromLTRB(16, 10, 16, 20),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: const BorderRadius.only(
                  bottomLeft: Radius.circular(24),
                  bottomRight: Radius.circular(24),
                ),
                boxShadow: [
                  BoxShadow(
                      color: Colors.black.withValues(alpha: 0.03),
                      blurRadius: 10,
                      offset: const Offset(0, 5)),
                ],
              ),
              child: Column(
                children: [
                  Row(
                    children: [
                      _buildStatItem("Present", stats['p']!, Colors.green),
                      Container(width: 1, height: 30, color: Colors.grey.shade200),
                      _buildStatItem("Absent", stats['a']!, Colors.red),
                      Container(width: 1, height: 30, color: Colors.grey.shade200),
                      _buildStatItem("Pending", stats['pending']!, Colors.orange),
                    ],
                  ),
                  const SizedBox(height: 16),
                  SingleChildScrollView(
                    scrollDirection: Axis.horizontal,
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        _buildFilterChip('All', null),
                        const SizedBox(width: 8),
                        _buildFilterChip('Present', 'Present',
                            color: Colors.green),
                        const SizedBox(width: 8),
                        _buildFilterChip('Absent', 'Absent', color: Colors.red),
                        const SizedBox(width: 8),
                        _buildFilterChip('Pending', 'Pending',
                            color: Colors.orange),
                      ],
                    ),
                  ),
                ],
              ),
            ),

            // 2. Student List
            Expanded(
              child: ListView.builder(
                padding: const EdgeInsets.all(16),
                itemCount: filteredStudents.length,
                itemBuilder: (context, index) {
                  return _buildStudentCard(filteredStudents[index]);
                },
              ),
            ),

            // 3. Save Button
            Container(
              padding: const EdgeInsets.all(20),
              decoration: const BoxDecoration(
                color: Colors.white,
                boxShadow: [
                  BoxShadow(
                      color: Colors.black12,
                      blurRadius: 10,
                      offset: Offset(0, -5))
                ],
              ),
              child: SizedBox(
                width: double.infinity,
                height: 55,
                child: ElevatedButton(
                  onPressed: _saveAttendance,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: Colors.indigo,
                    elevation: 5,
                    shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(16)),
                  ),
                  child: const Text(
                    'Save Attendance',
                    style: TextStyle(
                        fontSize: 18,
                        color: Colors.white,
                        fontWeight: FontWeight.bold),
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  // --- Helper Widgets ---

  Widget _buildStatItem(String label, int count, Color color) {
    return Expanded(
      child: Column(
        children: [
          Text(count.toString(),
              style: TextStyle(
                  fontSize: 20, fontWeight: FontWeight.bold, color: color)),
          Text(label, style: const TextStyle(fontSize: 12, color: Colors.grey)),
        ],
      ),
    );
  }

  Widget _buildFilterChip(String label, String? value,
      {Color color = Colors.indigo}) {
    final bool isSelected = _selectedFilter == value;
    return ChoiceChip(
      label: Text(label),
      selected: isSelected,
      onSelected: (bool selected) {
        setState(() => _selectedFilter = selected ? value : null);
      },
      backgroundColor: Colors.white,
      selectedColor: color.withValues(alpha: 0.15),
      labelStyle: TextStyle(
        color: isSelected ? color : Colors.grey.shade700,
        fontWeight: isSelected ? FontWeight.bold : FontWeight.normal,
      ),
      side: BorderSide(color: isSelected ? color : Colors.grey.shade300),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
      showCheckmark: false,
    );
  }

  Widget _buildStudentCard(Student student) {
    final bool? isPresent = student.isPresent;
    Color cardColor = Colors.white;
    Color borderColor = Colors.transparent;

    if (isPresent == true) {
      cardColor = Colors.green.withValues(alpha: 0.05);
      borderColor = Colors.green;
    } else if (isPresent == false) {
      cardColor = Colors.red.withValues(alpha: 0.05);
      borderColor = Colors.red;
    }

    // --- FIX: Manual Calculation to ignore model dummy data ---
    // If we have 0 classes, we treat it as 0.0% (N/A) to avoid dividing by zero
    double pct = 0.0;
    bool isNewStudent = (student.totalClasses == 0);

    if (!isNewStudent) {
      pct = (student.presentClasses / student.totalClasses) * 100;
    }

    // Determine Color based on Safe/Unsafe/New
    Color healthColor;
    if (isNewStudent) {
      healthColor = Colors.grey; // Grey for no data
    } else {
      healthColor = pct >= 75.0 ? const Color(0xFF2ECC71) : const Color(0xFFE74C3C);
    }

    return AnimatedContainer(
      duration: const Duration(milliseconds: 200),
      margin: const EdgeInsets.only(bottom: 12),
      decoration: BoxDecoration(
        color: cardColor,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
            color: isPresent != null ? borderColor : Colors.transparent,
            width: 2),
        boxShadow: [
          BoxShadow(
              color: Colors.grey.shade200,
              blurRadius: 8,
              offset: const Offset(0, 4)),
        ],
      ),
      child: Column(
        children: [
          Padding(
            padding: const EdgeInsets.all(12),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                GestureDetector(
                  onTap: () => _openStudentDetails(student),
                  child: Hero(
                    tag: 'student_${student.id}',
                    child: CircleAvatar(
                      radius: 24,
                      backgroundImage: NetworkImage(student.imageUrl),
                      backgroundColor: Colors.grey.shade200,
                    ),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Expanded(
                            child: GestureDetector(
                              onTap: () => _openStudentDetails(student),
                              child: Text(
                                student.name,
                                style: const TextStyle(
                                  fontSize: 16,
                                  fontWeight: FontWeight.bold,
                                ),
                                overflow: TextOverflow.ellipsis,
                              ),
                            ),
                          ),
                          InkWell(
                            onTap: () => _openStudentDetails(student),
                            child: const Padding(
                              padding: EdgeInsets.all(4.0),
                              child: Icon(Icons.info_outline,
                                  size: 20, color: Colors.grey),
                            ),
                          ),
                        ],
                      ),
                      Text("Roll: ${student.rollNumber}",
                          style: TextStyle(
                              fontSize: 12, color: Colors.grey.shade600)),
                      const SizedBox(height: 8),
                      // Progress Bar Row (Uses Manually Calculated 'pct')
                      Row(
                        children: [
                          Expanded(
                            child: ClipRRect(
                              borderRadius: BorderRadius.circular(4),
                              child: LinearProgressIndicator(
                                value: isNewStudent ? 0.0 : pct / 100,
                                minHeight: 6,
                                backgroundColor: Colors.grey.shade200,
                                valueColor:
                                AlwaysStoppedAnimation<Color>(healthColor),
                              ),
                            ),
                          ),
                          const SizedBox(width: 8),
                          Text(
                            isNewStudent ? "N/A" : "${pct.toStringAsFixed(1)}%",
                            style: TextStyle(
                              fontSize: 12,
                              fontWeight: FontWeight.bold,
                              color: healthColor,
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
          Divider(height: 1, color: Colors.grey.shade200),
          SizedBox(
            height: 50,
            child: Row(
              children: [
                Expanded(
                  child: InkWell(
                    onTap: () => _markAttendance(student, true),
                    borderRadius: const BorderRadius.only(
                        bottomLeft: Radius.circular(14)),
                    child: Container(
                      decoration: BoxDecoration(
                        color: isPresent == true
                            ? Colors.green
                            : Colors.transparent,
                        borderRadius: const BorderRadius.only(
                            bottomLeft: Radius.circular(14)),
                      ),
                      alignment: Alignment.center,
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(Icons.check_circle_outline,
                              color: isPresent == true
                                  ? Colors.white
                                  : Colors.green,
                              size: 20),
                          const SizedBox(width: 8),
                          Text(
                            "Present",
                            style: TextStyle(
                              color: isPresent == true
                                  ? Colors.white
                                  : Colors.green,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                ),
                Container(width: 1, color: Colors.grey.shade200),
                Expanded(
                  child: InkWell(
                    onTap: () => _markAttendance(student, false),
                    borderRadius: const BorderRadius.only(
                        bottomRight: Radius.circular(14)),
                    child: Container(
                      decoration: BoxDecoration(
                        color: isPresent == false
                            ? Colors.red
                            : Colors.transparent,
                        borderRadius: const BorderRadius.only(
                            bottomRight: Radius.circular(14)),
                      ),
                      alignment: Alignment.center,
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(Icons.cancel_outlined,
                              color: isPresent == false
                                  ? Colors.white
                                  : Colors.red,
                              size: 20),
                          const SizedBox(width: 8),
                          Text(
                            "Absent",
                            style: TextStyle(
                              color: isPresent == false
                                  ? Colors.white
                                  : Colors.red,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}