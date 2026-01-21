import 'dart:convert';
import 'dart:typed_data';

import 'package:bcrypt/bcrypt.dart';
import 'package:flutter/foundation.dart' show kIsWeb;
import 'package:http/http.dart' as http;
import 'package:image_picker/image_picker.dart';

import 'student.dart';

class DirectusService {
  // ---------------------------------------------------------------------------
  // CONFIGURATION
  // ---------------------------------------------------------------------------

    // Your Directus URL
  static const String apiKey = 'gk3WLa_wSVuj4YkEedlDpgwmB9ryXtuv';   // From Settings > [User] > Token


  /// Replace with your Directus URL
  static const String baseUrl = "http://10.39.144.231:8055";

  // Collection Names
  static const String teachersCollection = "teachers";
  static const String attendanceCollection = "attendance";
  static const String studentsCollection = "students";
  static const String attendanceTotalsCollection = "attendance_totals";
  static const String subjectsCollection = "subjects";

  // Store logged-in teacher ID for the session
  static int? loggedInTeacherId;

  // OPTIONAL: Directus static token if you use one
  static const String? authToken = null;

  // Common headers (add auth token if available)
  static Map<String, String> _jsonHeaders() {
    final headers = <String, String>{"Content-Type": "application/json"};
    if (authToken != null) {
      headers["Authorization"] = "Bearer $authToken";
    }
    return headers;
  }

  // ---------------------------------------------------------------------------
  // SUBJECTS (Branch + Semester + Type)
  // ---------------------------------------------------------------------------

  // ---------------------------------------------------------------------------
  // SUBJECTS (Flexible Fetch: Handles "3", "Semester 3", "semester 3")
  // ---------------------------------------------------------------------------

  static Future<List<Map<String, dynamic>>> getSubjects({
    required String branch,
    required String semester,
    required String type,
  }) async {
    print("\n🛑 STARTING SUBJECT DEBUG FETCH 🛑");
    print("Goal: Find subjects for Branch: '$branch', Semester: '$semester', Type: '$type'");

    // ---------------------------------------------------------
    // ATTEMPT 1: RAW FETCH (Fetch ALL subjects to test Permissions)
    // ---------------------------------------------------------
    final uriRaw = Uri.parse('$baseUrl/items/$subjectsCollection?limit=100');
    print("👉 Calling API (No Filters): $uriRaw");

    final responseRaw = await http.get(uriRaw, headers: _jsonHeaders());

    print("   Status Code: ${responseRaw.statusCode}");

    if (responseRaw.statusCode == 403) {
      print("❌ PERMISSION ERROR: Teacher role cannot read 'Subjects'.");
      print("👉 FIX: Go to Directus > Settings > Access Policies > Teacher > Subjects > Enable 'Read' (Eye Icon).");
      return [];
    }

    if (responseRaw.statusCode != 200) {
      print("❌ API ERROR: ${responseRaw.body}");
      return [];
    }

    final json = jsonDecode(responseRaw.body);
    final List allSubjects = json['data'] ?? [];
    print("✅ CONNECTION SUCCESS! Found ${allSubjects.length} total subjects in DB.");

    if (allSubjects.isEmpty) {
      print("⚠️ WARNING: The 'Subjects' collection is empty. Add data in Directus.");
      return [];
    }

    // ---------------------------------------------------------
    // ATTEMPT 2: MANUAL FILTERING (Filter in App, not Database)
    // ---------------------------------------------------------
    // We filter manually here to handle "Semester 3" vs "3" vs "semster" typos safely.

    print("👉 Applying Filters in Dart Code...");

    final filtered = allSubjects.where((s) {
      final sBranch = (s['branch'] ?? '').toString().trim().toLowerCase();
      final sType = (s['type'] ?? '').toString().trim().toLowerCase();

      // Handle the "Semester" field spelling (check both spellings just in case)
      final sSem = (s['semester'] ?? s['semster'] ?? '').toString().trim().toLowerCase();

      // Search Inputs
      final searchBranch = branch.trim().toLowerCase();
      final searchType = type.trim().toLowerCase();
      final searchSem = semester.toLowerCase().replaceAll('semester', '').trim(); // Extract "3"

      // Debug each row
      // print("   Checking: ${s['name']} | Br: $sBranch | Sem: $sSem | Type: $sType");

      // LOGIC:
      bool branchMatch = sBranch == searchBranch;
      bool typeMatch = sType == searchType;
      // Checks if DB "semester 3" contains input "3"
      bool semMatch = sSem.contains(searchSem);

      return branchMatch && typeMatch && semMatch;
    }).toList();

    print("🎉 Match Count: ${filtered.length}");

    if (filtered.isEmpty) {
      print("❌ NO MATCHES FOUND. Dumping first 3 rows from DB for comparison:");
      for (var i = 0; i < allSubjects.length && i < 3; i++) {
        print("   Row $i: ${allSubjects[i]}");
      }
    }

    // ---------------------------------------------------------
    // RETURN DATA
    // ---------------------------------------------------------
    return filtered.map((item) {
      final m = Map<String, dynamic>.from(item);
      m['code'] = m['code']?.toString() ?? '';
      m['id'] = m['id']?.toString() ?? '';
      return m;
    }).toList();
  }

  // ---------------------------------------------------------------------------
  // AUTHENTICATION (Register & Login)
  // ---------------------------------------------------------------------------

  static Future<Map<String, dynamic>> registerTeacher(
      Map<String, dynamic> body) async {
    final url = Uri.parse("$baseUrl/items/$teachersCollection");

    try {
      final plainPassword = (body['password'] ?? '').toString();
      final hashedPassword = BCrypt.hashpw(plainPassword, BCrypt.gensalt());

      final sendBody = Map<String, dynamic>.from(body);
      sendBody['password'] = hashedPassword;
      sendBody['is_verified'] = false;

      final response = await http.post(
        url,
        headers: _jsonHeaders(),
        body: jsonEncode(sendBody),
      );

      if (response.statusCode == 201 || response.statusCode == 200) {
        return {"success": true};
      }

      try {
        final bodyJson = jsonDecode(response.body);
        if (bodyJson is Map &&
            bodyJson['errors'] is List &&
            bodyJson['errors'].isNotEmpty) {
          final err = bodyJson['errors'][0];
          return {
            "success": false,
            "message": (err['message'] ?? 'Unknown error').toString(),
          };
        }
      } catch (_) {}

      return {
        "success": false,
        "message": 'Status Code: ${response.statusCode}',
      };
    } catch (e) {
      return {"success": false, "message": 'Network Error: $e'};
    }
  }

  static Future<Map<String, dynamic>?> login(
      String username, String password) async {
    final url = Uri.parse(
      "$baseUrl/items/$teachersCollection"
          "?filter[username][_eq]=$username"
          "&limit=1",
    );

    try {
      final response = await http.get(url, headers: _jsonHeaders());
      if (response.statusCode != 200) return null;

      final json = jsonDecode(response.body);
      if (json["data"] == null ||
          json["data"] is! List ||
          json["data"].isEmpty) {
        return null;
      }

      final Map<String, dynamic> teacher =
      Map<String, dynamic>.from(json["data"][0]);
      final String storedHash = (teacher['password'] ?? '').toString();

      if (storedHash.isNotEmpty && BCrypt.checkpw(password, storedHash)) {
        loggedInTeacherId = teacher["id"] as int?;
        return teacher;
      }
    } catch (e) {
      // ignore: avoid_print
      print("LOGIN ERROR → $e");
    }
    return null;
  }

  // ---------------------------------------------------------------------------
  // FILE UPLOAD
  // ---------------------------------------------------------------------------

  static Future<String?> uploadImageXFile(XFile image,
      {Uint8List? webBytes}) async {
    try {
      final url = Uri.parse("$baseUrl/files");
      final request = http.MultipartRequest('POST', url);

      if (authToken != null) {
        request.headers['Authorization'] = 'Bearer $authToken';
      }

      if (kIsWeb && webBytes != null) {
        request.files.add(
          http.MultipartFile.fromBytes('file', webBytes, filename: image.name),
        );
      } else {
        request.files.add(
          await http.MultipartFile.fromPath('file', image.path,
              filename: image.name),
        );
      }

      final streamed = await request.send();
      final response = await http.Response.fromStream(streamed);

      if (response.statusCode == 200 || response.statusCode == 201) {
        final json = jsonDecode(response.body);
        return json['data']['id']?.toString();
      }
    } catch (e) {
      // ignore: avoid_print
      print("FILE UPLOAD ERROR → $e");
    }
    return null;
  }

  // ---------------------------------------------------------------------------
  // TEACHER SCORECARD (Running Totals)
  // ---------------------------------------------------------------------------

  static Future<List<Map<String, dynamic>>> getTeacherStats(
      int teacherId) async {
    try {
      final url = Uri.parse(
        "$baseUrl/items/$attendanceTotalsCollection"
            "?filter[teacher_id][_eq]=$teacherId"
            "&limit=-1",
      );
      final response = await http.get(url, headers: _jsonHeaders());
      if (response.statusCode == 200) {
        final json = jsonDecode(response.body);
        return List<Map<String, dynamic>>.from(json["data"]);
      }
    } catch (e) {
      // ignore: avoid_print
      print("GET TEACHER STATS ERROR: $e");
    }
    return [];
  }

  static Future<void> updateTeacherScorecard({
    required int teacherId,
    required int studentId,
    required bool isPresent,
  }) async {
    try {
      final checkUrl = Uri.parse(
        "$baseUrl/items/$attendanceTotalsCollection"
            "?filter[teacher_id][_eq]=$teacherId"
            "&filter[student_id][_eq]=$studentId"
            "&limit=1",
      );

      final checkRes = await http.get(checkUrl, headers: _jsonHeaders());
      List existing = [];
      if (checkRes.statusCode == 200) {
        existing = jsonDecode(checkRes.body)['data'];
      }

      if (existing.isEmpty) {
        final createUrl =
        Uri.parse("$baseUrl/items/$attendanceTotalsCollection");
        await http.post(
          createUrl,
          headers: _jsonHeaders(),
          body: jsonEncode({
            "teacher_id": teacherId,
            "student_id": studentId,
            "total_classes": 1,
            "present_classes": isPresent ? 1 : 0,
          }),
        );
      } else {
        final int recordId = existing[0]['id'];
        final int currentTotal = existing[0]['total_classes'] ?? 0;
        final int currentPresent = existing[0]['present_classes'] ?? 0;

        final updateUrl = Uri.parse(
          "$baseUrl/items/$attendanceTotalsCollection/$recordId",
        );

        await http.patch(
          updateUrl,
          headers: _jsonHeaders(),
          body: jsonEncode({
            "total_classes": currentTotal + 1,
            "present_classes":
            isPresent ? (currentPresent + 1) : currentPresent,
          }),
        );
      }
    } catch (e) {
      // ignore: avoid_print
      print("UPDATE SCORECARD ERROR: $e");
    }
  }

  // ---------------------------------------------------------------------------
  // ATTENDANCE OPERATIONS
  // ---------------------------------------------------------------------------

  // ✅ UPDATED: Fixes "No Subject in Database" issue
  static Future<bool> saveAttendanceForStudent({
    required String branch,
    required String semester,
    required String date,
    required dynamic studentId,
    required String studentName,
    required bool isPresent,
    required int teacherId,
    String? subjectId,
    String? classType, // "theory" / "lab"
  }) async {
    final url = Uri.parse("$baseUrl/items/$attendanceCollection");

    final int? subjectIdInt =
    subjectId != null ? int.tryParse(subjectId) : null;

    final int studentIdInt = studentId is int
        ? studentId
        : (int.tryParse(studentId.toString()) ?? 0);

    final body = {
      "teacher_id": teacherId,
      "student_id": studentIdInt,
      "student_name": studentName,
      "branch": branch,
      "semester": semester,
      "date": date,
      "present": isPresent,

      // ✅ RELATION
      "subject_id": subjectIdInt,

      // ✅ STRING FIELD (NOW FIXED IN DIRECTUS)
      "session": classType, // "Theory" / "Lab"
    };

    try {
      final response = await http.post(
        url,
        headers: _jsonHeaders(),
        body: jsonEncode(body),
      );

      if (response.statusCode == 200 || response.statusCode == 201) {
        updateTeacherScorecard(
          teacherId: teacherId,
          studentId: studentIdInt,
          isPresent: isPresent,
        );
        return true;
      } else {
        print("❌ SAVE ERROR: ${response.statusCode} ${response.body}");
        return false;
      }
    } catch (e) {
      print("❌ SAVE EXCEPTION: $e");
      return false;
    }
  }



  static Future<bool> hasTakenAttendanceToday({
    required String branch,
    required String semester,
    required String date,
  }) async {
    if (loggedInTeacherId == null) return false;

    final url = Uri.parse(
      "$baseUrl/items/$attendanceCollection"
          "?filter[branch][_eq]=$branch"
          "&filter[semester][_eq]=$semester"
          "&filter[date][_eq]=$date"
          "&filter[teacher_id][_eq]=$loggedInTeacherId"
          "&limit=1",
    );

    try {
      final response = await http.get(url, headers: _jsonHeaders());
      if (response.statusCode == 200) {
        final data = jsonDecode(response.body)["data"] ?? [];
        return data.isNotEmpty;
      }
    } catch (_) {}
    return false;
  }

  static Future<List<Map<String, dynamic>>> getMonthlyAttendanceForStudent({
    required int studentId,
    required int year,
    required int month,
  }) async {
    try {
      if (loggedInTeacherId == null) return [];

      final from = DateTime(year, month, 1);
      final to = DateTime(year, month + 1, 0);

      final fromStr = from.toIso8601String().split('T').first;
      final toStr = to.toIso8601String().split('T').first;

      final url = Uri.parse(
        '$baseUrl/items/$attendanceCollection'
            '?filter[student_id][_eq]=$studentId'
            '&filter[date][_gte]=$fromStr'
            '&filter[date][_lte]=$toStr'
            '&filter[teacher_id][_eq]=$loggedInTeacherId'
            '&limit=-1',
      );

      final response = await http.get(url, headers: _jsonHeaders());

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        final List list = data['data'] ?? [];
        return list.map<Map<String, dynamic>>((e) {
          final m = Map<String, dynamic>.from(e);
          return {
            'date': m['date'],
            'is_present': m['present'],
          };
        }).toList();
      }
    } catch (e) {
      // ignore: avoid_print
      print('GET MONTHLY ATT EXCEPTION → $e');
    }
    return [];
  }

  // ---------------------------------------------------------------------------
  // STUDENT MANAGEMENT
  // ---------------------------------------------------------------------------

  static Future<bool> addStudent(Map<String, dynamic> body) async {
    final url = Uri.parse("$baseUrl/items/$studentsCollection");
    try {
      final response = await http.post(
        url,
        headers: _jsonHeaders(),
        body: jsonEncode(body),
      );
      return response.statusCode == 200 || response.statusCode == 201;
    } catch (e) {
      return false;
    }
  }

  static Future<List<Map<String, dynamic>>> getStudentsRaw({
    required String branch,
    required String semester,
  }) async {
    final url = Uri.parse(
      "$baseUrl/items/$studentsCollection"
          "?filter[branch][_eq]=$branch"
          "&filter[semester][_eq]=$semester",
    );

    try {
      final response = await http.get(url, headers: _jsonHeaders());
      if (response.statusCode == 200) {
        final json = jsonDecode(response.body);
        if (json["data"] is List) {
          return List<Map<String, dynamic>>.from(json["data"]);
        }
      }
    } catch (e) {
      // ignore: avoid_print
      print("GET STUDENTS RAW ERROR → $e");
    }
    return [];
  }

  static Future<List<Student>> getStudentsAttendanceForDay({
    required String branch,
    required String semester,
    required String date,
  }) async {
    if (loggedInTeacherId == null) return [];

    final rawStudents =
    await getStudentsRaw(branch: branch, semester: semester);

    final todayUrl = Uri.parse(
      "$baseUrl/items/$attendanceCollection"
          "?filter[teacher_id][_eq]=$loggedInTeacherId"
          "&filter[branch][_eq]=$branch"
          "&filter[semester][_eq]=$semester"
          "&filter[date][_eq]=$date",
    );
    Map<int, bool> todayPresentMap = {};
    try {
      final res = await http.get(todayUrl, headers: _jsonHeaders());
      if (res.statusCode == 200) {
        final list = jsonDecode(res.body)['data'] as List;
        for (var row in list) {
          todayPresentMap[row['student_id']] = row['present'];
        }
      }
    } catch (_) {}

    final stats = await getTeacherStats(loggedInTeacherId!);

    final List<Student> result = [];
    for (final raw in rawStudents) {
      try {
        final m = Map<String, dynamic>.from(raw);
        // Safely extract ID as integer
        final int id = m['id'] is int ? m['id'] : int.tryParse(m['id'].toString()) ?? 0;

        final statRow = stats.firstWhere(
              (s) => s['student_id'] == id,
          orElse: () => {},
        );

        final s = Student(
          id: id,
          name: (m['name'] ?? '').toString(),
          rollNumber: (m['roll_number'] ?? '').toString(),
          imageUrl: m['image'] != null
              ? "$baseUrl/assets/${m['image']}"
              : "https://via.placeholder.com/150",
          branch: m['branch'],
          semester: m['semester'],
          totalClasses: statRow.isNotEmpty
              ? (statRow['total_classes'] ?? 0)
              : 0,
          presentClasses: statRow.isNotEmpty
              ? (statRow['present_classes'] ?? 0)
              : 0,
          isPresent: todayPresentMap[id],
        );
        result.add(s);
      } catch (e) {
        // ignore: avoid_print
        print("MAPPING ERROR: $e");
      }
    }
    return result;
  }

  static Future<List<Student>> getStudents({int? limit = 500}) async {
    final response = await http.get(
      Uri.parse('$baseUrl/items/$studentsCollection').replace(
        queryParameters: {
          'fields': '*,branch.*,section.*,image.*',
          if (limit != null) 'limit': limit.toString(),
          'filter[semester][_null]': 'false',
          'sort': '-id',
        },
      ),
      headers: _jsonHeaders(),
    );

    if (response.statusCode != 200) {
      throw Exception('Failed to load students: ${response.statusCode}');
    }

    final data = jsonDecode(response.body);
    final List items = data['data'] ?? [];
    return items.map((json) => Student.fromJson(json)).toList();
  }
}