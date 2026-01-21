import 'package:flutter/material.dart';
import 'lists.dart';
import 'student.dart';

class AttendanceSummaryPage extends StatelessWidget {
  final List<Student> students;
  final int teacherId;

  const AttendanceSummaryPage({
    super.key,
    required this.students,
    required this.teacherId
   ,
  });

  Widget build(BuildContext context) {
    // 1. Logic Separation
    final presentStudents = students.where((s) => s.isPresent == true).toList();
    final absentStudents = students.where((s) => s.isPresent == false).toList();
    final notMarkedStudents = students.where((s) => s.isPresent == null).toList();

    final total = students.length;
    final presentCount = presentStudents.length;
    final absentCount = absentStudents.length;
    final notMarkedCount = notMarkedStudents.length;

    // Calculate percentage for the progress bar
    final double attendancePercentage = total == 0 ? 0 : presentCount / total;

    return DefaultTabController(
      length: 3, // Present, Absent, Not Marked
      child: Scaffold(
        backgroundColor: Colors.grey[100], // Light background for contrast
        appBar: AppBar(
          title: const Text("Attendance Summary"),
          centerTitle: true,
          elevation: 0,
          leading: IconButton(
            icon: const Icon(Icons.close),
            onPressed: () => _navigateToLists(context),
          ),
        ),
        body: Column(
          children: [
            // 2. Summary Header Section
            Container(
              color: Theme.of(context).primaryColor.withValues(alpha: 0.05),
              padding: const EdgeInsets.all(20.0),
              child: Column(
                children: [
                  // Percentage Indicator
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(
                        "Overall Attendance",
                        style: Theme.of(context).textTheme.titleMedium,
                      ),
                      Text(
                        "${(attendancePercentage * 100).toStringAsFixed(1)}%",
                        style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 18),
                      ),
                    ],
                  ),
                  const SizedBox(height: 10),
                  ClipRRect(
                    borderRadius: BorderRadius.circular(10),
                    child: LinearProgressIndicator(
                      value: attendancePercentage,
                      minHeight: 12,
                      backgroundColor: Colors.grey[300],
                      valueColor: const AlwaysStoppedAnimation<Color>(Colors.green),
                    ),
                  ),
                  const SizedBox(height: 20),

                  // Stat Cards Row
                  Row(
                    children: [
                      _buildStatCard(
                          context, "Present", presentCount, Colors.green),
                      const SizedBox(width: 10),
                      _buildStatCard(
                          context, "Absent", absentCount, Colors.red),
                      const SizedBox(width: 10),
                      _buildStatCard(
                          context, "Pending", notMarkedCount, Colors.orange),
                    ],
                  ),
                ],
              ),
            ),

            // 3. Tab Bar
            Container(
              color: Colors.white,
              child: const TabBar(
                labelColor: Colors.black87,
                unselectedLabelColor: Colors.grey,
                indicatorColor: Colors.blueAccent,
                tabs: [
                  Tab(text: "Present"),
                  Tab(text: "Absent"),
                  Tab(text: "Not Marked"),
                ],
              ),
            ),

            // 4. Tab Views (The Lists)
            Expanded(
              child: TabBarView(
                children: [
                  _buildStudentList(presentStudents, Colors.green),
                  _buildStudentList(absentStudents, Colors.red),
                  _buildStudentList(notMarkedStudents, Colors.orange),
                ],
              ),
            ),
          ],
        ),

        // Floating Action Button is often better for primary actions than a bottom bar
        floatingActionButton: FloatingActionButton.extended(
          onPressed: () => _navigateToLists(context),
          label: const Text("Done"),
          icon: const Icon(Icons.check),
          backgroundColor: Theme.of(context).primaryColor,
        ),
      ),
    );
  }

  // Helper Widget for the Top Stats
  Widget _buildStatCard(BuildContext context, String label, int count, Color color) {
    return Expanded(
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 16),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(12),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.05),
              blurRadius: 5,
              offset: const Offset(0, 2),
            )
          ],
          border: Border.all(color: color.withValues(alpha: 0.3), width: 1),
        ),
        child: Column(
          children: [
            Text(
              count.toString(),
              style: TextStyle(
                fontSize: 22,
                fontWeight: FontWeight.bold,
                color: color,
              ),
            ),
            const SizedBox(height: 4),
            Text(
              label,
              style: TextStyle(
                fontSize: 12,
                color: Colors.grey[600],
                fontWeight: FontWeight.w500,
              ),
            ),
          ],
        ),
      ),
    );
  }

  // Helper Widget for the Lists
  Widget _buildStudentList(List<Student> list, Color statusColor) {
    if (list.isEmpty) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(Icons.person_off_outlined, size: 60, color: Colors.grey[300]),
            const SizedBox(height: 10),
            Text("No students in this list", style: TextStyle(color: Colors.grey[500])),
          ],
        ),
      );
    }

    return ListView.builder(
      padding: const EdgeInsets.all(12),
      itemCount: list.length,
      itemBuilder: (context, index) {
        final student = list[index];
        return Card(
          margin: const EdgeInsets.only(bottom: 10),
          elevation: 0,
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(12),
            side: BorderSide(color: Colors.grey.shade200),
          ),
          child: ListTile(
            leading: CircleAvatar(
              backgroundColor: statusColor.withValues(alpha: 0.1),
              child: Text(
                student.name.substring(0, 1).toUpperCase(),
                style: TextStyle(color: statusColor, fontWeight: FontWeight.bold),
              ),
            ),
            title: Text(
              student.name,
              style: const TextStyle(fontWeight: FontWeight.w600),
            ),
            subtitle: Text("Roll No: ${student.rollNumber}"),
            trailing: Container(
              width: 12,
              height: 12,
              decoration: BoxDecoration(
                color: statusColor,
                shape: BoxShape.circle,
              ),
            ),
          ),
        );
      },
    );
  }

  void _navigateToLists(BuildContext context) {
    Navigator.pushReplacement(
      context,
      MaterialPageRoute(
        builder: (context) => Lists(
          students: students,
          teacherId: teacherId,
        ),
      ),
    );
  }
}