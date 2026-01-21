import 'package:flutter/material.dart';
import 'student.dart';
import 'directus_service.dart';

class StudentAttendanceDetailPage extends StatefulWidget {
  final Student student;

  const StudentAttendanceDetailPage({super.key, required this.student});

  @override
  State<StudentAttendanceDetailPage> createState() =>
      _StudentAttendanceDetailPageState();
}

class _StudentAttendanceDetailPageState
    extends State<StudentAttendanceDetailPage> {
  int _selectedMonth = DateTime.now().month;
  int _selectedYear = DateTime.now().year;

  late Map<int, bool?> _dayStatus;
  bool _loading = false;

  @override
  void initState() {
    super.initState();
    _dayStatus = {};
    _loadMonthAttendance();
  }

  Future<void> _loadMonthAttendance() async {
    setState(() {
      _loading = true;
      _dayStatus.clear();
    });

    final records = await DirectusService.getMonthlyAttendanceForStudent(
      studentId: widget.student.id,
      year: _selectedYear,
      month: _selectedMonth,
    );

    for (final rec in records) {
      final dateStr = rec['date'] as String?;
      final isPresent = rec['is_present'] as bool?;
      if (dateStr == null) continue;

      final d = DateTime.tryParse(dateStr);
      if (d != null && d.year == _selectedYear && d.month == _selectedMonth) {
        _dayStatus[d.day] = isPresent;
      }
    }

    final daysInMonth = DateUtils.getDaysInMonth(_selectedYear, _selectedMonth);
    for (int day = 1; day <= daysInMonth; day++) {
      _dayStatus.putIfAbsent(day, () => null);
    }

    if (mounted) {
      setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    // Uses overall percentage from Student object
    final double overallPct = widget.student.attendancePercentage;
    final bool isSafe = overallPct >= 0.75;

    final statusColor = isSafe ? const Color(0xFF2ECC71) : const Color(0xFFE74C3C);
    final months = ['January','February','March','April','May','June','July','August','September','October','November','December'];

    return Scaffold(
      backgroundColor: const Color(0xFFF8F9FD),
      body: Column(
        children: [
          Container(
            padding: const EdgeInsets.only(top: 50, bottom: 10, left: 10, right: 20),
            color: Colors.white,
            child: Row(
              children: [
                IconButton(
                  icon: const Icon(Icons.arrow_back_ios_new, size: 20),
                  onPressed: () => Navigator.pop(context),
                ),
                const Expanded(
                  child: Text("Attendance Report",
                      style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                ),
              ],
            ),
          ),
          Expanded(
            child: SingleChildScrollView(
              child: Column(
                children: [
                  _buildProfileCard(statusColor, overallPct),
                  _buildDateFocusHeader(months[_selectedMonth - 1], _selectedYear.toString()),
                  _buildFilterControls(months),
                  Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
                    child: _loading
                        ? const Center(child: CircularProgressIndicator())
                        : ListView.builder(
                      shrinkWrap: true,
                      physics: const NeverScrollableScrollPhysics(),
                      itemCount: DateUtils.getDaysInMonth(_selectedYear, _selectedMonth),
                      itemBuilder: (context, index) {
                        return _buildDailyLogTile(index + 1, _dayStatus[index + 1]);
                      },
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

  Widget _buildDateFocusHeader(String month, String year) {
    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
      padding: const EdgeInsets.symmetric(vertical: 20),
      width: double.infinity,
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.02), blurRadius: 10)],
      ),
      child: Column(
        children: [
          Text(month.toUpperCase(),
              style: TextStyle(letterSpacing: 2, fontSize: 14, color: Colors.grey.shade500, fontWeight: FontWeight.bold)),
          const SizedBox(height: 8),
          Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Container(width: 40, height: 1, color: Colors.grey.shade200),
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 20),
                child: Text(
                  DateTime.now().day.toString().padLeft(2, '0'),
                  style: const TextStyle(fontSize: 48, fontWeight: FontWeight.w900, color: Colors.black),
                ),
              ),
              Container(width: 40, height: 1, color: Colors.grey.shade200),
            ],
          ),
          Text(year,
              style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w600, color: Colors.black54)),
        ],
      ),
    );
  }

  Widget _buildProfileCard(Color statusColor, double pct) {
    return Container(
      margin: const EdgeInsets.all(20),
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(24),
        boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.03), blurRadius: 15, offset: const Offset(0, 8))],
      ),
      child: Row(
        children: [
          Stack(
            alignment: Alignment.center,
            children: [
              SizedBox(
                width: 75,
                height: 75,
                child: CircularProgressIndicator(
                  value: pct,
                  strokeWidth: 5,
                  backgroundColor: statusColor.withValues(alpha: 0.1),
                  valueColor: AlwaysStoppedAnimation<Color>(statusColor),
                ),
              ),
              CircleAvatar(radius: 30, backgroundImage: NetworkImage(widget.student.imageUrl)),
            ],
          ),
          const SizedBox(width: 15),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(widget.student.name,
                    style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                const SizedBox(height: 4),
                Text("${widget.student.branch ?? 'N/A'} • Sem ${widget.student.semester ?? 'N/A'}",
                    style: TextStyle(color: Colors.grey.shade600, fontSize: 13)),
                const SizedBox(height: 8),
                Text("${(pct * 100).toStringAsFixed(1)}% Overall",
                    style: TextStyle(color: statusColor, fontWeight: FontWeight.bold, fontSize: 12)),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildFilterControls(List<String> months) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 5),
      child: Row(
        children: [
          Expanded(
            child: _styledDropdown(
              value: _selectedMonth,
              items: List.generate(12, (i) => i + 1),
              display: (m) => months[m - 1],
              onChanged: (v) { setState(() => _selectedMonth = v!); _loadMonthAttendance(); },
            ),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: _styledDropdown(
              value: _selectedYear,
              items: [for (int y = DateTime.now().year - 2; y <= DateTime.now().year + 1; y++) y],
              display: (y) => y.toString(),
              onChanged: (v) { setState(() => _selectedYear = v!); _loadMonthAttendance(); },
            ),
          ),
        ],
      ),
    );
  }

  Widget _styledDropdown({required int value, required List<int> items, required String Function(int) display, required Function(int?) onChanged}) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12),
      decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(12), border: Border.all(color: Colors.grey.shade200)),
      child: DropdownButtonHideUnderline(
        child: DropdownButton<int>(
          value: value,
          isExpanded: true,
          items: items.map((i) => DropdownMenuItem(value: i, child: Text(display(i), style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w500)))).toList(),
          onChanged: onChanged,
        ),
      ),
    );
  }

  Widget _buildDailyLogTile(int day, bool? status) {
    final date = DateTime(_selectedYear, _selectedMonth, day);
    final color = status == true ? const Color(0xFF2ECC71) : (status == false ? const Color(0xFFE74C3C) : Colors.grey.shade300);

    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(16), border: Border.all(color: Colors.grey.shade50)),
      child: Row(
        children: [
          Container(
            width: 45, height: 45,
            decoration: BoxDecoration(color: color.withValues(alpha: 0.1), borderRadius: BorderRadius.circular(12)),
            child: Center(child: Text(day.toString(), style: TextStyle(fontWeight: FontWeight.bold, color: color, fontSize: 16))),
          ),
          const SizedBox(width: 15),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'][date.weekday-1],
                    style: const TextStyle(fontWeight: FontWeight.bold)),
                Text(status == null ? "No Record" : (status ? "Present" : "Absent"),
                    style: TextStyle(color: Colors.grey.shade500, fontSize: 12)),
              ],
            ),
          ),
          Icon(status == true ? Icons.check_circle : (status == false ? Icons.cancel : Icons.help_outline), color: color),
        ],
      ),
    );
  }
}