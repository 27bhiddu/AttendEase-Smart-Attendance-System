import 'directus_service.dart';

class Student {
  final int id;
  final String name;
  final String rollNumber;
  final String imageUrl;
  final String? branch;
  final String? semester;

  // Runtime attendance state
  int totalClasses;
  int presentClasses;
  bool? isPresent;

  Student({
    required this.id,
    required this.name,
    required this.rollNumber,
    required this.imageUrl,
    this.branch,
    this.semester,
    this.totalClasses = 0,
    this.presentClasses = 0,
    this.isPresent,
  });

  factory Student.fromJson(Map<String, dynamic> json) {
    // ✅ DEFAULT PLACEHOLDER
    String imageUrl = "https://via.placeholder.com/300x400.png?text=No+Image";

    // ✅ DIRECTUS FILE HANDLING WITH API KEY (UPDATED)
    final image = json['image'];
    if (image != null) {
      if (image is Map && image['id'] != null) {
        imageUrl = "${DirectusService.baseUrl}/assets/${image['id']}?access_token=${DirectusService.apiKey}&download";
      } else if (image is String) {
        imageUrl = "${DirectusService.baseUrl}/assets/$image?access_token=${DirectusService.apiKey}&download";
      }
    }

    // Safe extractor for relations
    String? extract(dynamic v) {
      if (v == null) return null;
      if (v is Map) {
        return v['name']?.toString() ?? v['code']?.toString();
      }
      return v.toString();
    }

    return Student(
      id: json['id'] is int
          ? json['id']
          : int.tryParse(json['id'].toString()) ?? 0,
      name: json['name']?.toString() ?? 'Unknown',
      rollNumber: json['roll_number']?.toString() ?? 'N/A',
      imageUrl: imageUrl,
      branch: extract(json['branch']),
      semester: extract(json['semester']),
      totalClasses: int.tryParse(json['total_classes']?.toString() ?? '0') ?? 0,
      presentClasses: int.tryParse(json['present_classes']?.toString() ?? '0') ?? 0,
    );
  }

  // Attendance percentage (used in UI)
  double get attendancePercentage {
    final int totalAdj = totalClasses + (isPresent != null ? 1 : 0);
    final int presentAdj = presentClasses + (isPresent == true ? 1 : 0);
    if (totalAdj == 0) return 0.0;
    return presentAdj / totalAdj;
  }

  // Used only if you update student stats back to Directus
  Map<String, dynamic> toJson() => {
    'id': id,
    'name': name,
    'roll_number': rollNumber,
    'image': imageUrl.split('/').last.split('?').first, // file id only
    'branch': branch,
    'semester': semester,
    'total_classes': totalClasses,
    'present_classes': presentClasses,
  };
}
