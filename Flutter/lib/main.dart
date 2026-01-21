import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'branch_semester.dart';
import 'directus_service.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();

  await SystemChrome.setPreferredOrientations([
    DeviceOrientation.portraitUp,
    DeviceOrientation.portraitDown,
  ]);

  SystemChrome.setSystemUIOverlayStyle(const SystemUiOverlayStyle(
    statusBarColor: Colors.transparent,
    statusBarIconBrightness: Brightness.dark,
  ));

  runApp(const MyApp());
}

class MyApp extends StatelessWidget {
  const MyApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Teacher Login',
      theme: ThemeData(
        fontFamily: 'Roboto',
        primarySwatch: Colors.indigo,
      ),
      home: const LoginPage(),
      debugShowCheckedModeBanner: false,
    );
  }
}

// ========================== LOGIN PAGE ==========================

class LoginPage extends StatefulWidget {
  const LoginPage({super.key});
  @override
  State<LoginPage> createState() => _LoginPageState();
}

class _LoginPageState extends State<LoginPage> {
  final TextEditingController _usernameController = TextEditingController();
  final TextEditingController _passwordController = TextEditingController();
  final GlobalKey<FormState> _formKey = GlobalKey<FormState>();

  bool _obscurePassword = true;
  bool _isLoading = false;

  Future<void> _login() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _isLoading = true);

    try {
      final teacher = await DirectusService.login(
        _usernameController.text.trim(),
        _passwordController.text.trim(),
      );

      if (!mounted) return;

      if (teacher != null) {
        // yahan verification check
        final bool isVerified = (teacher['is_verified'] ?? false) == true;

        if (!isVerified) {
          showDialog(
            context: context,
            builder: (_) => const AlertDialog(
              title: Text("Not Verified"),
              content: Text(
                "Your account is waiting for admin verification.\n"
                    "Please try again after admin approves your registration.",
              ),
            ),
          );
          return;
        }

        // Verified teacher → aage jao
        Navigator.pushReplacement(
          context,
          MaterialPageRoute(builder: (context) => const BranchSemesterPage()),
        );
      } else {
        showDialog(
          context: context,
          builder: (_) => const AlertDialog(
            title: Text("Invalid Login"),
            content: Text("Incorrect username or password."),
          ),
        );
      }
    } catch (e) {
      if (!mounted) return;
      showDialog(
        context: context,
        builder: (_) => AlertDialog(
          title: const Text("Error"),
          content: Text("Login failed: $e"),
        ),
      );
    } finally {
      if (mounted) {
        setState(() => _isLoading = false);
      }
    }
  }

  void _goToRegisterPage() {
    Navigator.of(context).push(
      MaterialPageRoute(builder: (context) => const RegistrationPage()),
    );
  }

  void _goToForgotPasswordPage() {
    Navigator.of(context).push(
      MaterialPageRoute(
        builder: (context) => const ForgotPasswordPage(),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Container(
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            colors: [
              Color(0xFFe0eafc),
              Color(0xFFcfdef3),
              Color(0xFFa1c4fd),
              Color(0xFFc2e9fb)
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
              color: Colors.white.withAlpha((0.92 * 255).round()),
              margin: const EdgeInsets.symmetric(horizontal: 32, vertical: 16),
              child: Padding(
                padding: const EdgeInsets.all(36.0),
                child: Form(
                  key: _formKey,
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      CircleAvatar(
                        radius: 42,
                        backgroundColor: Colors.indigo[100],
                        child: const Icon(Icons.school,
                            size: 48, color: Colors.indigo),
                      ),
                      const SizedBox(height: 18),
                      Text(
                        'Teacher Login',
                        style: TextStyle(
                          fontSize: 28,
                          fontWeight: FontWeight.bold,
                          color: Colors.indigo[700],
                          letterSpacing: 1.5,
                        ),
                      ),

                      const SizedBox(height: 8),
                      // Info tag about verification
                      // Info tag about verification (responsive)
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                        decoration: BoxDecoration(
                          color: Colors.orange.shade50,
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: Row(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: const [
                            Icon(
                              Icons.info_outline,
                              size: 18,
                              color: Colors.orange,
                            ),
                            SizedBox(width: 6),
                            // Let text take remaining width and wrap
                            Expanded(
                              child: Text(
                                "New registrations need admin verification before login.",
                                style: TextStyle(
                                  fontSize: 12,
                                  color: Colors.orange,
                                ),
                                softWrap: true,
                              ),
                            ),
                          ],
                        ),
                      ),

                      const SizedBox(height: 24),
                      TextFormField(
                        controller: _usernameController,
                        decoration: InputDecoration(
                          labelText: 'Username',
                          prefixIcon:
                          const Icon(Icons.person, color: Colors.indigo),
                          border: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(16),
                          ),
                        ),
                        validator: (value) {
                          if (value == null || value.isEmpty) {
                            return 'Please enter your username';
                          }
                          return null;
                        },
                      ),
                      const SizedBox(height: 18),
                      TextFormField(
                        controller: _passwordController,
                        obscureText: _obscurePassword,
                        decoration: InputDecoration(
                          labelText: 'Password',
                          prefixIcon:
                          const Icon(Icons.lock, color: Colors.indigo),
                          suffixIcon: IconButton(
                            icon: Icon(
                              _obscurePassword
                                  ? Icons.visibility_off
                                  : Icons.visibility,
                              color: Colors.indigo,
                            ),
                            onPressed: () {
                              setState(() {
                                _obscurePassword = !_obscurePassword;
                              });
                            },
                          ),
                          border: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(16),
                          ),
                        ),
                        validator: (value) {
                          if (value == null || value.isEmpty) {
                            return 'Please enter your password';
                          }
                          return null;
                        },
                      ),

                      const SizedBox(height: 8),

                      Align(
                        alignment: Alignment.centerRight,
                        child: TextButton(
                          onPressed:
                          _isLoading ? null : _goToForgotPasswordPage,
                          child: Text(
                            "Forgot Password?",
                            style: TextStyle(
                              color: Colors.indigo.shade800,
                              fontWeight: FontWeight.w500,
                            ),
                          ),
                        ),
                      ),

                      const SizedBox(height: 12),

                      SizedBox(
                        width: double.infinity,
                        child: ElevatedButton(
                          style: ElevatedButton.styleFrom(
                            padding:
                            const EdgeInsets.symmetric(vertical: 16),
                            backgroundColor: Colors.indigo.shade600,
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(16),
                            ),
                          ),
                          onPressed: _isLoading ? null : _login,
                          child: _isLoading
                              ? const SizedBox(
                            height: 22,
                            width: 22,
                            child: CircularProgressIndicator(
                              strokeWidth: 2.5,
                              valueColor: AlwaysStoppedAnimation(
                                Colors.white,
                              ),
                            ),
                          )
                              : const Text(
                            'Login',
                            style: TextStyle(
                              color: Colors.white,
                              fontSize: 18,
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                        ),
                      ),
                      const SizedBox(height: 12),
                      TextButton(
                        onPressed: _isLoading ? null : _goToRegisterPage,
                        child: Text(
                          "New User? Register",
                          style: TextStyle(
                            color: Colors.indigo.shade800,
                            fontWeight: FontWeight.w500,
                            fontSize: 16,
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

// ====================== REGISTRATION PAGE ======================

class RegistrationPage extends StatefulWidget {
  const RegistrationPage({super.key});
  @override
  State<RegistrationPage> createState() => _RegistrationPageState();
}

class _RegistrationPageState extends State<RegistrationPage> {
  final TextEditingController _usernameController = TextEditingController();
  final TextEditingController _emailController = TextEditingController();
  final TextEditingController _contactController = TextEditingController();
  final TextEditingController _createPasswordController =
  TextEditingController();
  final TextEditingController _confirmPasswordController =
  TextEditingController();
  final GlobalKey<FormState> _formKey = GlobalKey<FormState>();

  bool _obscureCreatePassword = true;
  bool _obscureConfirmPassword = true;
  bool _isRegistering = false;

  String? _validateConfirmPassword(String? value) {
    if (value == null || value.isEmpty) return 'Please confirm your password';
    if (value != _createPasswordController.text) {
      return 'Passwords do not match';
    }
    return null;
  }

  Future<void> _register() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _isRegistering = true);

    try {
      final result = await DirectusService.registerTeacher({
        "username": _usernameController.text.trim(),
        "email": _emailController.text.trim(),
        "contact": _contactController.text.trim(),
        "password": _createPasswordController.text.trim(),
        // "is_verified": false,  // optional, Directus default bhi use ho sakta hai
      });

      if (!mounted) return;

      if (result["success"] == true) {
        showDialog(
          context: context,
          builder: (_) => AlertDialog(
            title: const Text("Registration Successful"),
            content: const Text(
              "Your account has been created.\n"
                  "It is waiting for admin verification.\n"
                  "You will be able to login after approval.",
            ),
            actions: [
              TextButton(
                onPressed: () {
                  Navigator.of(context).pop();
                  Navigator.of(context).pushReplacement(
                    MaterialPageRoute(
                      builder: (context) => const LoginPage(),
                    ),
                  );
                },
                child: const Text("Go to Login"),
              ),
            ],
          ),
        );
      } else {
        final field = result["field"] as String?;
        String errorText = "Failed to register.";

        if (field == 'username') {
          errorText =
          "This username is already registered. Please choose a different username.";
        } else if (field == 'email') {
          errorText =
          "This email is already registered. Please use a different email.";
        } else if (field == 'contact') {
          errorText =
          "This contact number is already registered. Please use a different number.";
        } else if (result["message"] != null) {
          errorText = result["message"].toString();
        }

        showDialog(
          context: context,
          builder: (_) => AlertDialog(
            title: const Text("Registration Error"),
            content: Text(errorText),
          ),
        );
      }
    } catch (e) {
      if (!mounted) return;
      showDialog(
        context: context,
        builder: (_) => AlertDialog(
          title: const Text("Error"),
          content: Text("Registration failed: $e"),
        ),
      );
    } finally {
      if (mounted) {
        setState(() => _isRegistering = false);
      }
    }
  }

  void _goToLoginPage() {
    Navigator.of(context).pushReplacement(
      MaterialPageRoute(builder: (context) => const LoginPage()),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Container(
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            colors: [
              Color(0xFFe0eafc),
              Color(0xFFcfdef3),
              Color(0xFFa1c4fd),
              Color(0xFFc2e9fb)
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
              color: Colors.white.withAlpha((0.92 * 255).round()),
              margin: const EdgeInsets.symmetric(horizontal: 32, vertical: 16),
              child: Padding(
                padding: const EdgeInsets.all(36.0),
                child: Form(
                  key: _formKey,
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      CircleAvatar(
                        radius: 42,
                        backgroundColor: Colors.indigo[100],
                        child: const Icon(Icons.app_registration,
                            size: 48, color: Colors.indigo),
                      ),
                      const SizedBox(height: 18),
                      Text(
                        'Teacher Registration',
                        style: TextStyle(
                          fontSize: 28,
                          fontWeight: FontWeight.bold,
                          color: Colors.indigo[700],
                          letterSpacing: 1.5,
                        ),
                      ),
                      const SizedBox(height: 32),
                      TextFormField(
                        controller: _usernameController,
                        decoration: InputDecoration(
                          labelText: 'Username',
                          prefixIcon:
                          const Icon(Icons.person, color: Colors.indigo),
                          border: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(16),
                          ),
                        ),
                        validator: (value) =>
                        value == null || value.isEmpty
                            ? 'Please enter your username'
                            : null,
                      ),
                      const SizedBox(height: 18),
                      TextFormField(
                        controller: _emailController,
                        decoration: InputDecoration(
                          labelText: 'Email',
                          prefixIcon:
                          const Icon(Icons.email, color: Colors.indigo),
                          border: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(16),
                          ),
                        ),
                        validator: (value) {
                          if (value == null || value.isEmpty) {
                            return 'Please enter email';
                          }
                          if (!RegExp(
                              r"^[a-zA-Z0-9.]+@[a-zA-Z0-9]+\.[a-zA-Z]+")
                              .hasMatch(value)) {
                            return 'Enter a valid email (e.g. abc@gmail.com)';
                          }
                          return null;
                        },
                      ),
                      const SizedBox(height: 18),
                      TextFormField(
                        controller: _contactController,
                        decoration: InputDecoration(
                          labelText: 'Contact Number',
                          prefixIcon:
                          const Icon(Icons.phone, color: Colors.indigo),
                          border: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(16),
                          ),
                        ),
                        keyboardType: TextInputType.number,
                        inputFormatters: [
                          LengthLimitingTextInputFormatter(10),
                        ],
                        validator: (value) {
                          if (value == null || value.isEmpty) {
                            return 'Enter contact number';
                          }
                          if (value.length != 10) {
                            return 'Contact number must be 10 digits';
                          }
                          return null;
                        },
                      ),
                      const SizedBox(height: 18),
                      TextFormField(
                        controller: _createPasswordController,
                        obscureText: _obscureCreatePassword,
                        decoration: InputDecoration(
                          labelText: 'Create Password',
                          prefixIcon: const Icon(Icons.lock_outline,
                              color: Colors.indigo),
                          suffixIcon: IconButton(
                            icon: Icon(
                              _obscureCreatePassword
                                  ? Icons.visibility_off
                                  : Icons.visibility,
                              color: Colors.indigo,
                            ),
                            onPressed: () {
                              setState(() {
                                _obscureCreatePassword =
                                !_obscureCreatePassword;
                              });
                            },
                          ),
                          border: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(16),
                          ),
                        ),
                        validator: (value) =>
                        value == null || value.isEmpty
                            ? 'Please enter password'
                            : null,
                      ),
                      const SizedBox(height: 18),
                      TextFormField(
                        controller: _confirmPasswordController,
                        obscureText: _obscureConfirmPassword,
                        decoration: InputDecoration(
                          labelText: 'Confirm Password',
                          prefixIcon:
                          const Icon(Icons.lock, color: Colors.indigo),
                          suffixIcon: IconButton(
                            icon: Icon(
                              _obscureConfirmPassword
                                  ? Icons.visibility_off
                                  : Icons.visibility,
                              color: Colors.indigo,
                            ),
                            onPressed: () {
                              setState(() {
                                _obscureConfirmPassword =
                                !_obscureConfirmPassword;
                              });
                            },
                          ),
                          border: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(16),
                          ),
                        ),
                        validator: _validateConfirmPassword,
                      ),
                      const SizedBox(height: 28),
                      SizedBox(
                        width: double.infinity,
                        child: ElevatedButton(
                          style: ElevatedButton.styleFrom(
                            padding:
                            const EdgeInsets.symmetric(vertical: 16),
                            backgroundColor: Colors.indigo.shade600,
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(16),
                            ),
                            overlayColor:
                            Colors.indigo.shade800.withAlpha(80),
                          ),
                          onPressed: _isRegistering ? null : _register,
                          child: _isRegistering
                              ? const SizedBox(
                            height: 22,
                            width: 22,
                            child: CircularProgressIndicator(
                              strokeWidth: 2.5,
                              valueColor: AlwaysStoppedAnimation(
                                Colors.white,
                              ),
                            ),
                          )
                              : const Text(
                            'Register',
                            style: TextStyle(
                              color: Colors.white,
                              fontSize: 18,
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                        ),
                      ),
                      const SizedBox(height: 12),
                      TextButton(
                        onPressed: _isRegistering ? null : _goToLoginPage,
                        child: Text(
                          "Registered already? Login",
                          style: TextStyle(
                            color: Colors.indigo.shade800,
                            fontWeight: FontWeight.w500,
                            fontSize: 16,
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

// ===================== FORGOT PASSWORD PAGE =====================

class ForgotPasswordPage extends StatefulWidget {
  const ForgotPasswordPage({super.key});

  @override
  State<ForgotPasswordPage> createState() => _ForgotPasswordPageState();
}

class _ForgotPasswordPageState extends State<ForgotPasswordPage> {
  final _formKey = GlobalKey<FormState>();
  final _usernameController = TextEditingController();
  final _emailController = TextEditingController();

  bool _isSubmitting = false;

  Future<void> _submitForgotPassword() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _isSubmitting = true);

    try {
      // TODO: yahan apna PHP / Directus API call lagao
      // sirf username + email bhejna hai
      // await DirectusService.forgotPasswordRequest(
      //   username: _usernameController.text.trim(),
      //   email: _emailController.text.trim(),
      // );

      await Future.delayed(const Duration(milliseconds: 500));

      if (!mounted) return;
      showDialog(
        context: context,
        builder: (_) => AlertDialog(
          title: const Text('Request Sent'),
          content: const Text(
            'Your reset request has been sent to admin.\n',
          ),
          actions: [
            TextButton(
              onPressed: () {
                Navigator.of(context).pop(); // dialog
                Navigator.of(context).pop(); // back to Login
              },
              child: const Text('OK'),
            ),
          ],
        ),
      );
    } catch (e) {
      if (!mounted) return;
      showDialog(
        context: context,
        builder: (_) => AlertDialog(
          title: const Text('Error'),
          content: Text('Failed to send request: $e'),
        ),
      );
    } finally {
      if (mounted) setState(() => _isSubmitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Forgot Password'),
        backgroundColor: Colors.indigo.shade600,
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
              color: Colors.white.withAlpha((0.92 * 255).round()),
              margin:
              const EdgeInsets.symmetric(horizontal: 32, vertical: 16),
              child: Padding(
                padding: const EdgeInsets.all(36.0),
                child: Form(
                  key: _formKey,
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      CircleAvatar(
                        radius: 42,
                        backgroundColor: Colors.indigo[100],
                        child: const Icon(
                          Icons.key,
                          size: 48,
                          color: Colors.indigo,
                        ),
                      ),
                      const SizedBox(height: 18),
                      Text(
                        'Forgot Password',
                        style: TextStyle(
                          fontSize: 24,
                          fontWeight: FontWeight.bold,
                          color: Colors.indigo[700],
                          letterSpacing: 1.2,
                        ),
                      ),
                      const SizedBox(height: 24),
                      Text(
                        'Enter your username and registered email.\n',
                        textAlign: TextAlign.center,
                        style: TextStyle(
                          color: Colors.grey.shade700,
                          fontSize: 14,
                        ),
                      ),
                      const SizedBox(height: 24),

                      // Username
                      TextFormField(
                        controller: _usernameController,
                        decoration: InputDecoration(
                          labelText: 'Username',
                          prefixIcon: const Icon(
                            Icons.person,
                            color: Colors.indigo,
                          ),
                          border: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(16),
                          ),
                        ),
                        validator: (value) =>
                        value == null || value.isEmpty
                            ? 'Please enter your username'
                            : null,
                      ),
                      const SizedBox(height: 18),

                      // Email
                      TextFormField(
                        controller: _emailController,
                        decoration: InputDecoration(
                          labelText: 'Registered Email',
                          prefixIcon: const Icon(
                            Icons.email,
                            color: Colors.indigo,
                          ),
                          border: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(16),
                          ),
                        ),
                        validator: (value) {
                          if (value == null || value.isEmpty) {
                            return 'Please enter email';
                          }
                          if (!RegExp(
                              r"^[a-zA-Z0-9.]+@[a-zA-Z0-9]+\.[a-zA-Z]+")
                              .hasMatch(value)) {
                            return 'Enter a valid email';
                          }
                          return null;
                        },
                      ),
                      const SizedBox(height: 28),

                      SizedBox(
                        width: double.infinity,
                        child: ElevatedButton(
                          style: ElevatedButton.styleFrom(
                            padding: const EdgeInsets.symmetric(
                              vertical: 16,
                            ),
                            backgroundColor: Colors.indigo.shade600,
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(16),
                            ),
                          ),
                          onPressed:
                          _isSubmitting ? null : _submitForgotPassword,
                          child: _isSubmitting
                              ? const SizedBox(
                            height: 22,
                            width: 22,
                            child: CircularProgressIndicator(
                              strokeWidth: 2.5,
                              valueColor:
                              AlwaysStoppedAnimation(Colors.white),
                            ),
                          )
                              : const Text(
                            'Submit Request',
                            style: TextStyle(
                              color: Colors.white,
                              fontSize: 18,
                              fontWeight: FontWeight.w600,
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
