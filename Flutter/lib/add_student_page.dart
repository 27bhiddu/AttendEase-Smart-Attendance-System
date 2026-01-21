import 'dart:typed_data';
// import 'dart:io'; // REMOVED
// import 'package:flutter/foundation.dart'; // REMOVED (No longer needed)

import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';

import 'student.dart';
import 'directus_service.dart';

class AddStudentPage extends StatefulWidget {
  final void Function(Student) onStudentAdded;

  const AddStudentPage({super.key, required this.onStudentAdded});

  @override
  State<AddStudentPage> createState() => _AddStudentPageState();
}

class _AddStudentPageState extends State<AddStudentPage> {
  final GlobalKey<FormState> _formKey = GlobalKey<FormState>();

  final TextEditingController _nameController = TextEditingController();
  final TextEditingController _rollNoController = TextEditingController();
  final TextEditingController _emailController = TextEditingController();

  String _selectedBranch = 'CSE';
  String _selectedSemester = 'Semester 1';

  // ✅ Cross-platform image holder
  XFile? _pickedImage;
  Uint8List? _imageBytes;

  bool _isLoading = false;

  final ImagePicker _picker = ImagePicker();
  final List<String> _branches = ['CSE', 'IT', 'AI', 'MCA'];

  List<String> get _semesters {
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

  Future<void> _pickImage(ImageSource source) async {
    final pickedFile =
    await _picker.pickImage(source: source, imageQuality: 75);

    if (pickedFile != null) {
      // ✅ Read bytes on ALL platforms (Mobile & Web) to avoid dart:io dependency
      final bytes = await pickedFile.readAsBytes();

      setState(() {
        _pickedImage = pickedFile;
        _imageBytes = bytes;
      });
    }
  }

  void _showImagePickerOptions() {
    showModalBottomSheet(
      context: context,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) => Container(
        padding: const EdgeInsets.all(20),
        height: 150,
        child: Column(
          children: [
            const Text(
              "Select Image From",
              style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 16),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceEvenly,
              children: [
                ElevatedButton.icon(
                  icon: const Icon(Icons.camera_alt),
                  label: const Text("Camera"),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: Colors.indigo,
                  ),
                  onPressed: () {
                    Navigator.pop(context);
                    _pickImage(ImageSource.camera);
                  },
                ),
                ElevatedButton.icon(
                  icon: const Icon(Icons.photo_library),
                  label: const Text("Gallery"),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: Colors.indigo,
                  ),
                  onPressed: () {
                    Navigator.pop(context);
                    _pickImage(ImageSource.gallery);
                  },
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _addStudent() async {
    if (!_formKey.currentState!.validate()) return;

    if (_pickedImage == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text("Please select a student photo")),
      );
      return;
    }

    setState(() => _isLoading = true);

    // 1) Upload image to Directus
    // Pass the bytes directly since we loaded them earlier
    final String? fileId = await DirectusService.uploadImageXFile(
      _pickedImage!,
      webBytes: _imageBytes,
    );

    if (fileId == null) {
      if (!mounted) return;
      setState(() => _isLoading = false);
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text("Failed to upload image")),
      );
      return;
    }

    // 2) Body for Directus "students" collection
    final Map<String, dynamic> body = {
      "name": _nameController.text,
      "roll_number": _rollNoController.text,
      "email": _emailController.text,
      "branch": _selectedBranch,
      "semester": _selectedSemester,
      "image": fileId,
      "total_classes": 0,
      "present_classes": 0,
    };

    final bool ok = await DirectusService.addStudent(body);
    if (!mounted) return;

    setState(() => _isLoading = false);

    if (!ok) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text("Failed to add student to server")),
      );
      return;
    }

    // 3) Locally create Student model
    final String imageUrl = "http://10.195.122.231:8055/assets/$fileId";

    final Student newStudent = Student(
      id: 0,
      name: _nameController.text,
      rollNumber: _rollNoController.text,
      imageUrl: imageUrl,
      branch: _selectedBranch,
      semester: _selectedSemester,
      totalClasses: 0,
      presentClasses: 0,
    );

    widget.onStudentAdded(newStudent);

    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text(
          "Student Added",
          style: TextStyle(color: Colors.indigo),
        ),
        content: Text(
          "${_nameController.text} has been added successfully.\nWould you like to mark attendance now?",
        ),
        actions: [
          TextButton(
            onPressed: () {
              Navigator.of(context).pop();
              Navigator.of(context).pop();
            },
            child: const Text("Later"),
          ),
          TextButton(
            onPressed: () {
              Navigator.of(context).pop();
              Navigator.of(context).pop();
            },
            child: const Text(
              "Mark Now",
              style: TextStyle(fontWeight: FontWeight.bold),
            ),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    // Universal image widget using memory bytes
    final imageWidget = () {
      if (_imageBytes != null) {
        return Image.memory(_imageBytes!, fit: BoxFit.cover);
      }
      return null;
    }();

    return Scaffold(
      appBar: AppBar(
        title: const Text('Add Student'),
        backgroundColor: Colors.indigo,
        elevation: 2,
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
            padding: const EdgeInsets.all(20),
            child: Card(
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(20),
              ),
              elevation: 8,
              color: Colors.white.withAlpha(240),
              child: Padding(
                padding: const EdgeInsets.all(20),
                child: Form(
                  key: _formKey,
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      GestureDetector(
                        onTap: _showImagePickerOptions,
                        child: CircleAvatar(
                          radius: 50,
                          backgroundColor: Colors.indigo[100],
                          child: ClipOval(
                            child: SizedBox(
                              width: 100,
                              height: 100,
                              child: imageWidget ??
                                  const Icon(
                                    Icons.camera_alt,
                                    size: 40,
                                    color: Colors.indigo,
                                  ),
                            ),
                          ),
                        ),
                      ),
                      const SizedBox(height: 20),
                      TextFormField(
                        controller: _nameController,
                        decoration: InputDecoration(
                          labelText: "Full Name",
                          prefixIcon: const Icon(
                            Icons.person,
                            color: Colors.indigo,
                          ),
                          border: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(16),
                          ),
                        ),
                        validator: (v) =>
                        v == null || v.isEmpty ? "Enter name" : null,
                      ),
                      const SizedBox(height: 14),
                      TextFormField(
                        controller: _rollNoController,
                        decoration: InputDecoration(
                          labelText: "Roll Number",
                          prefixIcon: const Icon(
                            Icons.confirmation_number,
                            color: Colors.indigo,
                          ),
                          border: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(16),
                          ),
                        ),
                        validator: (v) =>
                        v == null || v.isEmpty ? "Enter roll number" : null,
                      ),
                      const SizedBox(height: 14),
                      TextFormField(
                        controller: _emailController,
                        decoration: InputDecoration(
                          labelText: "Email",
                          prefixIcon: const Icon(
                            Icons.email,
                            color: Colors.indigo,
                          ),
                          border: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(16),
                          ),
                        ),
                        keyboardType: TextInputType.emailAddress,
                        validator: (v) {
                          if (v == null || v.isEmpty) {
                            return "Enter email";
                          }
                          if (!RegExp(r'\S+@\S+\.\S+').hasMatch(v)) {
                            return "Enter valid email";
                          }
                          return null;
                        },
                      ),
                      const SizedBox(height: 14),
                      DropdownButtonFormField<String>(
                        decoration: InputDecoration(
                          labelText: 'Branch',
                          prefixIcon: const Icon(
                            Icons.school,
                            color: Colors.indigo,
                          ),
                          border: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(16),
                          ),
                        ),
                        initialValue: _selectedBranch,
                        items: _branches
                            .map(
                              (branch) => DropdownMenuItem<String>(
                            value: branch,
                            child: Text(branch),
                          ),
                        )
                            .toList(),
                        onChanged: (val) {
                          setState(() {
                            _selectedBranch = val!;
                            _selectedSemester = 'Semester 1';
                          });
                        },
                      ),
                      const SizedBox(height: 14),
                      DropdownButtonFormField<String>(
                        decoration: InputDecoration(
                          labelText: 'Semester',
                          prefixIcon: const Icon(
                            Icons.calendar_month,
                            color: Colors.indigo,
                          ),
                          border: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(16),
                          ),
                        ),
                        initialValue: _selectedSemester,
                        items: _semesters
                            .map(
                              (sem) => DropdownMenuItem<String>(
                            value: sem,
                            child: Text(sem),
                          ),
                        )
                            .toList(),
                        onChanged: (val) {
                          setState(() {
                            _selectedSemester = val!;
                          });
                        },
                      ),
                      const SizedBox(height: 24),
                      _isLoading
                          ? const CircularProgressIndicator()
                          : SizedBox(
                        width: double.infinity,
                        child: ElevatedButton(
                          style: ElevatedButton.styleFrom(
                            backgroundColor: Colors.indigo,
                            elevation: 3,
                            padding: const EdgeInsets.symmetric(
                              vertical: 16,
                            ),
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(16),
                            ),
                          ),
                          onPressed: _addStudent,
                          child: const Text(
                            "Add Student",
                            style: TextStyle(
                              fontSize: 18,
                              fontWeight: FontWeight.bold,
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
      ),
    );
  }
}