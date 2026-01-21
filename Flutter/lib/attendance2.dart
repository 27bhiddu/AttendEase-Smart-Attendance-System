import 'package:flutter/material.dart';

class AttendancePage extends StatefulWidget {
  const AttendancePage({super.key});
  @override
  State<AttendancePage> createState() => _AttendancePageState();
}

class _AttendancePageState extends State<AttendancePage> {
  final List<Student> students = [
    Student(id: 1, name: 'Abhisek', rollNumber: '1', isPresent: null),
    Student(id: 2, name: 'Aadil', rollNumber: '2', isPresent: null),
    Student(id: 3, name: 'Akshi', rollNumber: '3', isPresent: null),
    Student(id: 4, name: 'Baby', rollNumber: '4', isPresent: null),
    Student(id: 5, name: 'Bhavesh', rollNumber: '5', isPresent: null),
    Student(id: 6, name: 'Divyanshi', rollNumber: '6', isPresent: null),
    Student(id: 7, name: 'Himani', rollNumber: '7', isPresent: null),
    Student(id: 8, name: 'Harshita', rollNumber: '8', isPresent: null),
    Student(id: 9, name: 'Kavita', rollNumber: '9', isPresent: null),
    Student(id: 10, name: 'Lokesh', rollNumber: '10', isPresent: null),
    Student(id: 11, name: 'Mayank', rollNumber: '11', isPresent: null),
    Student(id: 12, name: 'Mustafa', rollNumber: '12', isPresent: null),
    Student(id: 13, name: 'Rahul', rollNumber: '13', isPresent: null),
  ];

  int currentIndex = 0;

  void _markPresent() {
    setState(() {
      students[currentIndex].isPresent = true;
      if (currentIndex < students.length - 1) {
        currentIndex++;
      } else {
        _showAttendanceSummary();
      }
    });
  }

  void _markAbsent() {
    setState(() {
      students[currentIndex].isPresent = false;
      if (currentIndex < students.length - 1) {
        currentIndex++;
      } else {
        _showAttendanceSummary();
      }
    });
  }

  void _showAttendanceSummary() {
    int presentCount = students.where((s) => s.isPresent == true).length;
    int absentCount = students.where((s) => s.isPresent == false).length;

    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text("Attendance Summary", style: TextStyle(color: Colors.indigo)),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text("Total Students: ${students.length}"),
            Text("Present: $presentCount", style: const TextStyle(color: Colors.green, fontWeight: FontWeight.bold)),
            Text("Absent: $absentCount", style: const TextStyle(color: Colors.red, fontWeight: FontWeight.bold)),
          ],
        ),
        actions: [
          TextButton(
            child: const Text("OK"),
            onPressed: () => Navigator.of(context).pop(),
          ),
        ],
      ),
    );
  }

  void _resetAttendance() {
    setState(() {
      for (var student in students) {
        student.isPresent = null;
      }
      currentIndex = 0;
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Mark Attendance'),
        backgroundColor: Colors.indigo,
        actions: [
          TextButton(
            onPressed: _resetAttendance,
            child: const Text('Reset', style: TextStyle(color: Colors.white)),
          ),
        ],
      ),
      body: Container(
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            colors: [Color(0xFFe0eafc), Color(0xFFcfdef3), Color(0xFFa1c4fd), Color(0xFFc2e9fb)],
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
          ),
        ),
        child: Column(
          children: [
            Padding(
              padding: const EdgeInsets.all(16.0),
              child: Text(
                'Student ${currentIndex + 1} of ${students.length}',
                style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
              ),
            ),
            Expanded(
              child: GestureDetector(
                onHorizontalDragEnd: (details) {
                  if (details.primaryVelocity! > 0) {
                    // Swiping right - Mark Present
                    _markPresent();
                  } else if (details.primaryVelocity! < 0) {
                    // Swiping left - Mark Absent
                    _markAbsent();
                  }
                },
                child: Card(
                  elevation: 12,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(24),
                  ),
                  margin: const EdgeInsets.symmetric(horizontal: 24, vertical: 16),
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      // Student Picture/Avatar
                      CircleAvatar(
                        radius: 80,
                        backgroundColor: Colors.indigo[200],
                        child: Icon(
                          Icons.person,
                          size: 100,
                          color: Colors.indigo[700],
                        ),
                      ),
                      const SizedBox(height: 28),
                      // Student Name
                      Text(
                        students[currentIndex].name,
                        style: const TextStyle(
                          fontSize: 28,
                          fontWeight: FontWeight.bold,
                          color: Colors.indigo,
                        ),
                      ),
                      const SizedBox(height: 12),
                      // Roll Number
                      Text(
                        'Roll No: ${students[currentIndex].rollNumber}',
                        style: TextStyle(
                          fontSize: 18,
                          color: Colors.indigo[600],
                        ),
                      ),
                      const SizedBox(height: 28),
                      // Status Badge
                      if (students[currentIndex].isPresent != null)
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
                          decoration: BoxDecoration(
                            color: students[currentIndex].isPresent! ? Colors.green : Colors.red,
                            borderRadius: BorderRadius.circular(20),
                          ),
                          child: Text(
                            students[currentIndex].isPresent! ? 'PRESENT' : 'ABSENT',
                            style: const TextStyle(
                              color: Colors.white,
                              fontWeight: FontWeight.bold,
                              fontSize: 16,
                            ),
                          ),
                        ),
                    ],
                  ),
                ),
              ),
            ),
            Padding(
              padding: const EdgeInsets.all(24.0),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                children: [
                  // Absent Button (Left)
                  ElevatedButton.icon(
                    style: ElevatedButton.styleFrom(
                      backgroundColor: Colors.red,
                      padding: const EdgeInsets.symmetric(horizontal: 28, vertical: 14),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(16),
                      ),
                    ),
                    onPressed: _markAbsent,
                    icon: const Icon(Icons.close, size: 24),
                    label: const Text(
                      'Absent',
                      style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                    ),
                  ),
                  // Present Button (Right)
                  ElevatedButton.icon(
                    style: ElevatedButton.styleFrom(
                      backgroundColor: Colors.green,
                      padding: const EdgeInsets.symmetric(horizontal: 28, vertical: 14),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(16),
                      ),
                    ),
                    onPressed: _markPresent,
                    icon: const Icon(Icons.check, size: 24),
                    label: const Text(
                      'Present',
                      style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class Student {
  final int id;
  final String name;
  final String rollNumber;
  bool? isPresent;

  Student({
    required this.id,
    required this.name,
    required this.rollNumber,
    this.isPresent,
  });
}
