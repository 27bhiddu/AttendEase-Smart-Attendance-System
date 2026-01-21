class Subject {
  final String id;        // Safe for Dropdown/Navigator
  final String name;
  final String code;
  final String branch;
  final String semester;  // FIXED spelling
  final String type;      // "theory" or "lab"

  Subject({
    required this.id,
    required this.name,
    required this.code,
    required this.branch,
    required this.semester,
    required this.type,
  });

  factory Subject.fromMap(Map<String, dynamic> map) {
    return Subject(
      id: map['id']?.toString() ?? '',
      name: map['name']?.toString() ?? '',
      code: map['code']?.toString() ?? '',
      branch: map['branch']?.toString() ?? '',
      semester: map['semester']?.toString() ?? '',
      type: (map['type'] ?? '').toString().toLowerCase(),
    );
  }

  @override
  String toString() => '$name ($code - ${type.toUpperCase()})';
}
