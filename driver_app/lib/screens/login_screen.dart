import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../providers/auth_provider.dart';
import 'main_nav_screen.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  final _formKey = GlobalKey<FormState>();

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();

    return Scaffold(
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(20),
          child: Form(
            key: _formKey,
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Text('تسجيل دخول السائق', style: Theme.of(context).textTheme.headlineSmall),
                const SizedBox(height: 20),
                TextFormField(
                  controller: _emailController,
                  decoration: const InputDecoration(labelText: 'البريد الإلكتروني'),
                  validator: (v) => (v == null || v.isEmpty) ? 'مطلوب' : null,
                ),
                const SizedBox(height: 12),
                TextFormField(
                  controller: _passwordController,
                  obscureText: true,
                  decoration: const InputDecoration(labelText: 'كلمة المرور'),
                  validator: (v) => (v == null || v.isEmpty) ? 'مطلوب' : null,
                ),
                const SizedBox(height: 20),
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton(
                    onPressed: auth.isLoading
                        ? null
                        : () async {
                            if (!_formKey.currentState!.validate()) return;
                            final navigator = Navigator.of(context);
                            final authProvider = context.read<AuthProvider>();
                            final ok = await authProvider.login(
                                  _emailController.text.trim(),
                                  _passwordController.text,
                                );
                            if (!mounted || !ok) return;
                            navigator.pushReplacement(
                              MaterialPageRoute(builder: (_) => const MainNavScreen()),
                            );
                          },
                    child: auth.isLoading
                        ? const CircularProgressIndicator()
                        : const Text('تسجيل الدخول'),
                  ),
                ),
                if (auth.error != null) ...[
                  const SizedBox(height: 10),
                  Text(auth.error!, style: const TextStyle(color: Colors.red)),
                ]
              ],
            ),
          ),
        ),
      ),
    );
  }
}
