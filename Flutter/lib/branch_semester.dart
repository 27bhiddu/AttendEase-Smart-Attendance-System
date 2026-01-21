import 'package:flutter/material.dart';

import 'main.dart';
import 'attendance.dart';
import 'directus_service.dart';
import 'lists.dart';
import 'student.dart';

class BranchSemesterPage extends StatefulWidget {
  const BranchSemesterPage({super.key});

  @override
  State<BranchSemesterPage> createState() => _BranchSemesterPageState();
}

class _BranchSemesterPageState extends State<BranchSemesterPage> {
  final List<String> _branches = ['CSE', 'IT', 'AI', 'MCA'];

  // UI label; can be "Semester 3" or "3"
  String _selectedBranch = 'MCA';
  String _selectedSemesterLabel = 'Semester 3';
  String _selectedType = 'theory'; // 'theory' or 'laboratory'

  // subjects from Directus
  List<Map<String, dynamic>> _subjects = [];
  String? _selectedSubjectId;
  String? _selectedSubjectCode;

  bool _isLoading = false;
  bool _isSubjectsLoading = false;

  // ---------- HELPERS ----------

  /// Normalize any semester label to numeric string:
  /// "Semester 3" -> "3", "3" -> "3"
  String _normalizeSemesterForApi(String label) {
    final lower = label.toLowerCase();
    if (lower.startsWith('semester ')) {
      return label.split(' ').last.trim();
    }
    return label.trim();
  }

  List<String> getSemesterLabels() {
    // labels for dropdown UI
    if (_selectedBranch == 'MCA') {
      return [
        'Semester 1',
        'Semester 2',
        'Semester 3',
        'Semester 4',
      ];
    } else {
      return [
        'Semester 1',
        'Semester 2',
        'Semester 3',
        'Semester 4',
        'Semester 5',
        'Semester 6',
        'Semester 7',
        'Semester 8',
      ];
    }
  }

  Future<void> _loadSubjects() async {
    setState(() {
      _isSubjectsLoading = true;
      _subjects = [];
      _selectedSubjectId = null;
      _selectedSubjectCode = null;
    });

    try {
      final apiSemester = _normalizeSemesterForApi(_selectedSemesterLabel);

      final result = await DirectusService.getSubjects(
        branch: _selectedBranch,
        semester: apiSemester,
        type: _selectedType,
      );

      if (!mounted) return;
      setState(() {
        _subjects = result;
        if (_subjects.isNotEmpty) {
          _selectedSubjectId = _subjects.first['id'].toString();
          _selectedSubjectCode = _subjects.first['code']?.toString();
        }
      });
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Failed to load subjects: $e')),
      );
    } finally {
      if (mounted) {
        setState(() => _isSubjectsLoading = false);
      }
    }
  }

  @override
  void initState() {
    super.initState();
    _loadSubjects();
  }

  // ---------- SUBMIT ----------

  Future<void> _onSubmit() async {
    if (_selectedBranch.isEmpty ||
        _selectedSemesterLabel.isEmpty ||
        _selectedSubjectId == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Please select branch, semester and subject.'),
        ),
      );
      return;
    }

    setState(() => _isLoading = true);

    try {
      if (DirectusService.loggedInTeacherId == null) {
        if (!mounted) return;
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content:
            Text("Error: Teacher ID not found. Please login again."),
          ),
        );
        setState(() => _isLoading = false);
        return;
      }

      final now = DateTime.now();
      final dateStr =
          '${now.year}-${now.month.toString().padLeft(2, '0')}-${now.day.toString().padLeft(2, '0')}';

      final apiSemester = _normalizeSemesterForApi(_selectedSemesterLabel);

      final alreadyTaken = await DirectusService.hasTakenAttendanceToday(
        branch: _selectedBranch,
        semester: apiSemester,
        date: dateStr,
      );

      if (alreadyTaken) {
        final studentsForToday =
        await DirectusService.getStudentsAttendanceForDay(
          branch: _selectedBranch,
          semester: apiSemester,
          date: dateStr,
        );

        if (!mounted) return;
        setState(() => _isLoading = false);
        _showDuplicateDialog(studentsForToday);
        return;
      }

      if (!mounted) return;
      setState(() => _isLoading = false);

      // Pass UI label to AttendancePage; it will normalize itself for API
      Navigator.push(
        context,
        MaterialPageRoute(
          builder: (context) => AttendancePage(
            branch: _selectedBranch,
            semester: _selectedSemesterLabel,
            teacherId: DirectusService.loggedInTeacherId!,
            subjectId: _selectedSubjectId,
            subjectCode: _selectedSubjectCode,
            classType: _selectedType,
          ),
        ),
      );
    } catch (e) {
      if (mounted) setState(() => _isLoading = false);
      // ignore: avoid_print
      print('SUBMIT ERROR: $e');
    }
  }

  void _showDuplicateDialog(List<Student> studentsForToday) {
    final selectedSubject = _subjects.firstWhere(
          (s) => s['id'].toString() == _selectedSubjectId,
      orElse: () => {},
    );
    final subjectLabel = selectedSubject.isEmpty
        ? '-'
        : '${selectedSubject['name']} (${selectedSubject['code']})';

    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (context) => AlertDialog(
        title: const Row(
          children: [
            Icon(Icons.warning_amber_rounded, color: Colors.orange),
            SizedBox(width: 10),
            Text("Attendance Exists"),
          ],
        ),
        content: Text(
          "Attendance for $_selectedBranch - $_selectedSemesterLabel\n"
              "Subject: $subjectLabel\n"
              "Type: ${_selectedType == 'theory' ? 'Theory' : 'Lab'}\n\n"
              "has already been marked for today.\n"
              "You can review or edit the list, but you cannot create a new record.",
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(context).pop(),
            child:
            const Text("Cancel", style: TextStyle(color: Colors.grey)),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: Colors.indigo),
            onPressed: () {
              Navigator.of(context).pop();
              Navigator.push(
                context,
                MaterialPageRoute(
                  builder: (context) => Lists(
                    students: studentsForToday,
                    teacherId: DirectusService.loggedInTeacherId!,
                  ),
                ),
              );
            },
            child: const Text(
              "Edit List",
              style: TextStyle(color: Colors.white),
            ),
          ),
        ],
      ),
    );
  }

  // ---------- UI ----------

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        backgroundColor: Colors.indigo.shade600,
        elevation: 6,
        shadowColor: Colors.indigoAccent.withValues(alpha: 0.4),
        leading: IconButton(
          icon: const Icon(Icons.arrow_back, color: Colors.white),
          onPressed: () {
            Navigator.pushReplacement(
              context,
              MaterialPageRoute(
                builder: (context) => const LoginPage(),
              ),
            );
          },
        ),
        centerTitle: true,
        title: const Text(
          'Select Class',
          style: TextStyle(
            fontWeight: FontWeight.bold,
            letterSpacing: 1,
            color: Colors.white,
          ),
        ),
      ),
      body: Container(
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            colors: [
              Color(0xFFe0eafc),
              Color(0xFFcfdef3),
              Color(0xFFa1c4fd),
              Color(0xFFc2e9fb),
            ],
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
          ),
        ),
        child: Center(
          child: SingleChildScrollView(
            child: Card(
              elevation: 14,
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(32),
              ),
              color: Colors.white.withValues(alpha: 0.92),
              margin:
              const EdgeInsets.symmetric(horizontal: 32, vertical: 16),
              child: Padding(
                padding: const EdgeInsets.all(36.0),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    CircleAvatar(
                      radius: 42,
                      backgroundColor: Colors.indigo[100],
                      child: const Icon(
                        Icons.account_tree,
                        size: 48,
                        color: Colors.indigo,
                      ),
                    ),
                    const SizedBox(height: 18),
                    Text(
                      'Select Branch, Semester & Subject',
                      style: TextStyle(
                        fontSize: 20,
                        fontWeight: FontWeight.bold,
                        color: Colors.indigo[700],
                        letterSpacing: 1.0,
                      ),
                      textAlign: TextAlign.center,
                    ),
                    const SizedBox(height: 32),

                    // Branch
                    DropdownButtonFormField<String>(
                      decoration: InputDecoration(
                        labelText: 'Branch',
                        prefixIcon: const Icon(
                          Icons.school,
                          color: Colors.indigo,
                        ),
                        focusedBorder: OutlineInputBorder(
                          borderSide: const BorderSide(
                              color: Colors.indigo, width: 2),
                          borderRadius: BorderRadius.circular(16),
                        ),
                        border: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(16),
                        ),
                      ),
                      initialValue: _selectedBranch,
                      items: _branches
                          .map(
                            (branch) => DropdownMenuItem(
                          value: branch,
                          child: Text(branch),
                        ),
                      )
                          .toList(),
                      onChanged: (value) {
                        setState(() {
                          _selectedBranch = value!;
                          final labels = getSemesterLabels();
                          if (!labels.contains(_selectedSemesterLabel)) {
                            _selectedSemesterLabel = labels.first;
                          }
                        });
                        _loadSubjects();
                      },
                    ),
                    const SizedBox(height: 24),

                    // Semester
                    DropdownButtonFormField<String>(
                      decoration: InputDecoration(
                        labelText: 'Semester',
                        prefixIcon: const Icon(
                          Icons.calendar_month,
                          color: Colors.indigo,
                        ),
                        focusedBorder: OutlineInputBorder(
                          borderSide: const BorderSide(
                              color: Colors.indigo, width: 2),
                          borderRadius: BorderRadius.circular(16),
                        ),
                        border: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(16),
                        ),
                      ),
                      initialValue: _selectedSemesterLabel,
                      items: getSemesterLabels()
                          .map(
                            (label) => DropdownMenuItem(
                          value: label,
                          child: Text(label),
                        ),
                      )
                          .toList(),
                      onChanged: (value) {
                        setState(() {
                          _selectedSemesterLabel = value!;
                        });
                        _loadSubjects();
                      },
                    ),
                    const SizedBox(height: 24),

                    // Type (Theory / Laboratory)
                    Align(
                      alignment: Alignment.centerLeft,
                      child: Text(
                        'Class Type',
                        style: TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.w600,
                          color: Colors.indigo[700],
                        ),
                      ),
                    ),
                    Row(
                      children: [
                        Expanded(
                          child: RadioListTile<String>(
                            title: const Text('Theory'),
                            value: 'theory',
                            groupValue: _selectedType,
                            activeColor: Colors.indigo,
                            onChanged: (value) {
                              setState(() => _selectedType = value!);
                              _loadSubjects();
                            },
                          ),
                        ),
                        Expanded(
                          child: RadioListTile<String>(
                            title: const Text('Lab'),
                            value: 'laboratory',
                            groupValue: _selectedType,
                            activeColor: Colors.indigo,
                            onChanged: (value) {
                              setState(() => _selectedType = value!);
                              _loadSubjects();
                            },
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 24),

                    // Subject dropdown
                    Container(
                      padding: EdgeInsets.only(bottom: 24),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          // Loading/Empty states
                          if (_isSubjectsLoading)
                            Padding(
                              padding: EdgeInsets.all(20),
                              child: Column(
                                children: [
                                  CircularProgressIndicator(color: Colors.indigo),
                                  SizedBox(height: 12),
                                  Text('Loading subjects...', style: TextStyle(fontSize: 16, color: Colors.indigo[600])),
                                ],
                              ),
                            )
                          else if (_subjects.isEmpty)
                            Container(
                              padding: EdgeInsets.all(20),
                              decoration: BoxDecoration(
                                color: Colors.orange.shade50,
                                borderRadius: BorderRadius.circular(16),
                                border: Border.all(color: Colors.orange.shade200),
                              ),
                              child: Row(
                                children: [
                                  Icon(Icons.info_outline, color: Colors.orange.shade700, size: 28),
                                  SizedBox(width: 12),
                                  Expanded(
                                    child: Text.rich(
                                      TextSpan(
                                        children: [
                                          TextSpan(text: 'No subjects for ', style: TextStyle(fontWeight: FontWeight.w500)),
                                          TextSpan(text: '$_selectedBranch • ${_normalizeSemesterForApi(_selectedSemesterLabel)} • $_selectedType',
                                              style: TextStyle(fontWeight: FontWeight.bold)),
                                          TextSpan(text: '\nTry different branch/semester/type'),
                                        ],
                                        style: TextStyle(color: Colors.orange.shade800, height: 1.4),
                                      ),
                                    ),
                                  ),
                                  TextButton(
                                    onPressed: _loadSubjects,
                                    child: Text('Retry'),
                                  ),
                                ],
                              ),
                            )
                          else
                          // ✅ POPULATED DROPDOWN - FIXED SELECTION
                            DropdownButtonFormField<String>(
                              decoration: InputDecoration(
                                labelText: 'Subject (${_subjects.length})',
                                prefixIcon: Icon(Icons.book_outlined, color: Colors.indigo),
                                focusedBorder: OutlineInputBorder(
                                  borderSide: BorderSide(color: Colors.indigo, width: 2),
                                  borderRadius: BorderRadius.circular(16),
                                ),
                                border: OutlineInputBorder(borderRadius: BorderRadius.circular(16)),
                                // Visual feedback
                                suffixIcon: _selectedSubjectId != null
                                    ? Icon(Icons.check_circle, color: Colors.green)
                                    : null,
                              ),
                              initialValue: _selectedSubjectId,  // ✅ FIXED: 'value' not 'initialValue'
                              isExpanded: true,  // ✅ FIXED: Full width, tappable
                              validator: (value) => value == null ? 'Select subject' : null,
                              items: _subjects.map((s) {
                                return DropdownMenuItem<String>(
                                  value: s['id'].toString(),
                                  child: Text(
                                    '${s['name']} (${s['code']})',
                                    overflow: TextOverflow.ellipsis,
                                  ),
                                );
                              }).toList(),
                              onChanged: (value) {
                                print('✅ SELECTED: $value');  // Debug
                                setState(() {
                                  _selectedSubjectId = value;
                                  final sub = _subjects.firstWhere((s) => s['id'].toString() == value);
                                  _selectedSubjectCode = sub['code']?.toString();
                                });
                              },
                            ),
                        ],
                      ),
                    ),

                    // Continue button
                    SizedBox(
                      width: double.infinity,
                      height: 55,
                      child: ElevatedButton(
                        style: ElevatedButton.styleFrom(
                          backgroundColor: Colors.indigo.shade600,
                          elevation: 4,
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(16),
                          ),
                          disabledBackgroundColor: Colors.indigo.shade300,
                        ),
                        onPressed: _isLoading ? null : _onSubmit,
                        child: _isLoading
                            ? const SizedBox(
                          height: 24,
                          width: 24,
                          child: CircularProgressIndicator(
                            color: Colors.white,
                            strokeWidth: 3,
                          ),
                        )
                            : const Text(
                          'Continue',
                          style: TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.w600,
                            letterSpacing: 0.6,
                            color: Colors.white,
                          ),
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }
}
