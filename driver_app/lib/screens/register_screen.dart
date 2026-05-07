import 'dart:typed_data';

import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:provider/provider.dart';

import '../core/theme/app_colors.dart';
import '../providers/auth_provider.dart';
import 'email_verification_screen.dart';

class RegisterScreen extends StatefulWidget {
  const RegisterScreen({super.key});

  @override
  State<RegisterScreen> createState() => _RegisterScreenState();
}

class _RegisterScreenState extends State<RegisterScreen> {
  final _formKey = GlobalKey<FormState>();
  final _nameController = TextEditingController();
  final _nationalIdController = TextEditingController();
  final _phoneController = TextEditingController();
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  final _confirmPasswordController = TextEditingController();
  final _plateController = TextEditingController();
  final _cityController = TextEditingController();
  final _emergencyController = TextEditingController();

  String _vehicleType = 'motorcycle';
  String? _profileImagePath;
  Uint8List? _profileImageBytes;
  String? _nationalIdImagePath;
  Uint8List? _nationalIdImageBytes;
  String? _vehicleImagePath;
  Uint8List? _vehicleImageBytes;
  bool _obscurePassword = true;

  static const _vehicleLabels = {
    'bicycle': 'دراجة هوائية',
    'electric_bicycle': 'دراجة كهربائية',
    'motorcycle': 'دراجة نارية',
    'car': 'سيارة',
  };

  bool get _needsPlate => _vehicleType == 'motorcycle' || _vehicleType == 'car';

  @override
  void dispose() {
    _nameController.dispose();
    _nationalIdController.dispose();
    _phoneController.dispose();
    _emailController.dispose();
    _passwordController.dispose();
    _confirmPasswordController.dispose();
    _plateController.dispose();
    _cityController.dispose();
    _emergencyController.dispose();
    super.dispose();
  }

  Future<void> _pickImage({
    required void Function(String path, Uint8List bytes) onPicked,
  }) async {
    final picked = await ImagePicker().pickImage(
      source: ImageSource.gallery,
      maxWidth: 1200,
      imageQuality: 82,
    );
    if (!mounted || picked == null) return;
    final bytes = await picked.readAsBytes();
    if (!mounted) return;
    setState(() => onPicked(picked.path, bytes));
  }

  Future<void> _pickProfileImage() async {
    await _pickImage(
      onPicked: (path, bytes) {
        _profileImagePath = path;
        _profileImageBytes = bytes;
      },
    );
  }

  Future<void> _pickNationalIdImage() async {
    await _pickImage(
      onPicked: (path, bytes) {
        _nationalIdImagePath = path;
        _nationalIdImageBytes = bytes;
      },
    );
  }

  Future<void> _pickVehicleImage() async {
    await _pickImage(
      onPicked: (path, bytes) {
        _vehicleImagePath = path;
        _vehicleImageBytes = bytes;
      },
    );
  }

  String? _required(String? value, String message) {
    if (value == null || value.trim().isEmpty) return message;
    return null;
  }

  String? _emailValidator(String? value) {
    final required = _required(value, 'البريد الإلكتروني مطلوب');
    if (required != null) return required;
    final email = value!.trim();
    final ok = RegExp(r'^[^@\s]+@[^@\s]+\.[^@\s]+$').hasMatch(email);
    return ok ? null : 'يرجى إدخال بريد إلكتروني صحيح';
  }

  String? _passwordValidator(String? value) {
    final required = _required(value, 'كلمة المرور مطلوبة');
    if (required != null) return required;
    return value!.length >= 6
        ? null
        : 'كلمة المرور يجب أن تكون 6 أحرف على الأقل';
  }

  String? _confirmPasswordValidator(String? value) {
    final required = _required(value, 'تأكيد كلمة المرور مطلوب');
    if (required != null) return required;
    return value == _passwordController.text
        ? null
        : 'كلمتا المرور غير متطابقتين';
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    final messenger = ScaffoldMessenger.of(context);
    final navigator = Navigator.of(context);
    final auth = context.read<AuthProvider>();

    final missingImageMessage = _missingImageMessage();
    if (missingImageMessage != null) {
      messenger.showSnackBar(
        SnackBar(
          content: Text(missingImageMessage),
          backgroundColor: Theme.of(context).colorScheme.error,
        ),
      );
      return;
    }

    final ok = await auth.register(
      name: _nameController.text.trim(),
      nationalId: _nationalIdController.text.trim(),
      phone: _phoneController.text.trim(),
      email: _emailController.text.trim(),
      password: _passwordController.text,
      passwordConfirmation: _confirmPasswordController.text,
      vehicleType: _vehicleType,
      vehiclePlateNumber: _plateController.text,
      city: _cityController.text,
      emergencyContactNumber: _emergencyController.text,
      profileImagePath: _profileImagePath!,
      nationalIdImagePath: _nationalIdImagePath!,
      vehicleImagePath: _vehicleImagePath!,
    );

    if (!mounted) return;
    messenger.showSnackBar(
      SnackBar(
        content: Text(
          ok
              ? (auth.successMessage ??
                    'تم إنشاء الحساب. يرجى التحقق من البريد الإلكتروني أولاً')
              : (auth.error ?? 'تعذر إرسال طلب التسجيل'),
        ),
        backgroundColor: ok
            ? AppColors.completed
            : Theme.of(context).colorScheme.error,
      ),
    );

    if (ok) {
      await navigator.push(
        MaterialPageRoute(
          builder: (_) => EmailVerificationScreen(
            initialEmail: _emailController.text.trim(),
          ),
        ),
      );
      if (!mounted) return;
      navigator.pop();
    }
  }

  String? _missingImageMessage() {
    if (_profileImagePath == null) return 'الصورة الشخصية مطلوبة';
    if (_nationalIdImagePath == null) return 'صورة الهوية مطلوبة';
    if (_vehicleImagePath == null) return 'صورة المركبة مطلوبة';
    return null;
  }

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();

    return Scaffold(
      appBar: AppBar(title: const Text('إنشاء حساب سائق')),
      body: SafeArea(
        child: ListView(
          padding: const EdgeInsets.all(20),
          children: [
            Text(
              'انضمام السائقين',
              style: Theme.of(context).textTheme.headlineSmall,
            ),
            const SizedBox(height: 6),
            Text(
              'سيتم إرسال طلبك للإدارة للمراجعة قبل تفعيل الدخول.',
              style: Theme.of(context).textTheme.bodyMedium,
            ),
            const SizedBox(height: 20),
            Form(
              key: _formKey,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  Center(
                    child: InkWell(
                      borderRadius: BorderRadius.circular(44),
                      onTap: auth.isLoading ? null : _pickProfileImage,
                      child: CircleAvatar(
                        radius: 42,
                        backgroundColor: AppColors.incomingGlow,
                        backgroundImage: _profileImageBytes == null
                            ? null
                            : MemoryImage(_profileImageBytes!),
                        child: _profileImagePath == null
                            ? const Icon(
                                Icons.person_add_alt_1,
                                color: AppColors.accent,
                                size: 34,
                              )
                            : null,
                      ),
                    ),
                  ),
                  const SizedBox(height: 12),
                  _documentTile(
                    title: 'الصورة الشخصية',
                    subtitle: 'صورة واضحة للسائق',
                    icon: Icons.account_circle_outlined,
                    imageBytes: _profileImageBytes,
                    onTap: auth.isLoading ? null : _pickProfileImage,
                  ),
                  _documentTile(
                    title: 'صورة الهوية',
                    subtitle: 'صورة الهوية الوطنية أو الشخصية',
                    icon: Icons.badge_outlined,
                    imageBytes: _nationalIdImageBytes,
                    onTap: auth.isLoading ? null : _pickNationalIdImage,
                  ),
                  _documentTile(
                    title: 'صورة المركبة',
                    subtitle: 'صورة الدراجة أو السيارة المستخدمة للتوصيل',
                    icon: Icons.two_wheeler_outlined,
                    imageBytes: _vehicleImageBytes,
                    onTap: auth.isLoading ? null : _pickVehicleImage,
                  ),
                  const SizedBox(height: 18),
                  _field(
                    _nameController,
                    'الاسم الكامل',
                    Icons.badge_outlined,
                    validator: (v) => _required(v, 'الاسم الكامل مطلوب'),
                  ),
                  _field(
                    _nationalIdController,
                    'رقم الهوية',
                    Icons.credit_card,
                    keyboardType: TextInputType.number,
                    validator: (v) => _required(v, 'رقم الهوية مطلوب'),
                  ),
                  _field(
                    _phoneController,
                    'رقم الهاتف',
                    Icons.phone_outlined,
                    keyboardType: TextInputType.phone,
                    validator: (v) => _required(v, 'رقم الهاتف مطلوب'),
                  ),
                  _field(
                    _emailController,
                    'البريد الإلكتروني',
                    Icons.mail_outline,
                    keyboardType: TextInputType.emailAddress,
                    validator: _emailValidator,
                  ),
                  _vehicleTypeField(),
                  if (_needsPlate)
                    _field(
                      _plateController,
                      'رقم لوحة المركبة',
                      Icons.confirmation_number_outlined,
                    ),
                  _field(
                    _cityController,
                    'المدينة / المنطقة',
                    Icons.location_on_outlined,
                  ),
                  _field(
                    _emergencyController,
                    'رقم طوارئ',
                    Icons.contact_phone_outlined,
                    keyboardType: TextInputType.phone,
                  ),
                  _passwordField(),
                  _field(
                    _confirmPasswordController,
                    'تأكيد كلمة المرور',
                    Icons.lock_reset,
                    obscureText: true,
                    validator: _confirmPasswordValidator,
                  ),
                  const SizedBox(height: 18),
                  ElevatedButton(
                    onPressed: auth.isLoading ? null : _submit,
                    child: auth.isLoading
                        ? const SizedBox(
                            height: 22,
                            width: 22,
                            child: CircularProgressIndicator(
                              strokeWidth: 2,
                              color: Colors.white,
                            ),
                          )
                        : const Text('إرسال طلب التسجيل'),
                  ),
                  const SizedBox(height: 10),
                  TextButton(
                    onPressed: auth.isLoading
                        ? null
                        : () => Navigator.of(context).pop(),
                    child: const Text('لديك حساب؟ تسجيل الدخول'),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _documentTile({
    required String title,
    required String subtitle,
    required IconData icon,
    required Uint8List? imageBytes,
    required VoidCallback? onTap,
  }) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: InkWell(
        borderRadius: BorderRadius.circular(14),
        onTap: onTap,
        child: Container(
          padding: const EdgeInsets.all(12),
          decoration: BoxDecoration(
            color: AppColors.surface,
            borderRadius: BorderRadius.circular(14),
            border: Border.all(color: AppColors.borderSubtle),
          ),
          child: Row(
            children: [
              Container(
                width: 54,
                height: 54,
                decoration: BoxDecoration(
                  color: AppColors.incomingGlow,
                  borderRadius: BorderRadius.circular(12),
                  image: imageBytes == null
                      ? null
                      : DecorationImage(
                          image: MemoryImage(imageBytes),
                          fit: BoxFit.cover,
                        ),
                ),
                child: imageBytes == null
                    ? Icon(icon, color: AppColors.accent)
                    : const Icon(Icons.check_circle, color: Colors.white),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      title,
                      style: const TextStyle(
                        fontSize: 14,
                        fontWeight: FontWeight.w800,
                        color: AppColors.textPrimary,
                      ),
                    ),
                    const SizedBox(height: 3),
                    Text(
                      imageBytes == null ? subtitle : 'تم اختيار الصورة',
                      style: const TextStyle(
                        fontSize: 12,
                        color: AppColors.textSecondary,
                      ),
                    ),
                  ],
                ),
              ),
              const Icon(Icons.upload_file, color: AppColors.textMuted),
            ],
          ),
        ),
      ),
    );
  }

  Widget _field(
    TextEditingController controller,
    String label,
    IconData icon, {
    TextInputType? keyboardType,
    bool obscureText = false,
    String? Function(String?)? validator,
  }) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: TextFormField(
        controller: controller,
        keyboardType: keyboardType,
        obscureText: obscureText,
        validator: validator,
        decoration: InputDecoration(
          labelText: label,
          prefixIcon: Icon(icon),
          filled: true,
          fillColor: AppColors.surface,
          border: OutlineInputBorder(borderRadius: BorderRadius.circular(14)),
          enabledBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(14),
            borderSide: const BorderSide(color: AppColors.borderSubtle),
          ),
        ),
      ),
    );
  }

  Widget _passwordField() {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: TextFormField(
        controller: _passwordController,
        obscureText: _obscurePassword,
        validator: _passwordValidator,
        decoration: InputDecoration(
          labelText: 'كلمة المرور',
          prefixIcon: const Icon(Icons.lock_outline),
          suffixIcon: IconButton(
            onPressed: () =>
                setState(() => _obscurePassword = !_obscurePassword),
            icon: Icon(
              _obscurePassword
                  ? Icons.visibility_outlined
                  : Icons.visibility_off_outlined,
            ),
          ),
          filled: true,
          fillColor: AppColors.surface,
          border: OutlineInputBorder(borderRadius: BorderRadius.circular(14)),
          enabledBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(14),
            borderSide: const BorderSide(color: AppColors.borderSubtle),
          ),
        ),
      ),
    );
  }

  Widget _vehicleTypeField() {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: DropdownButtonFormField<String>(
        initialValue: _vehicleType,
        decoration: InputDecoration(
          labelText: 'نوع المركبة',
          prefixIcon: const Icon(Icons.delivery_dining),
          filled: true,
          fillColor: AppColors.surface,
          border: OutlineInputBorder(borderRadius: BorderRadius.circular(14)),
          enabledBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(14),
            borderSide: const BorderSide(color: AppColors.borderSubtle),
          ),
        ),
        items: _vehicleLabels.entries
            .map((e) => DropdownMenuItem(value: e.key, child: Text(e.value)))
            .toList(),
        onChanged: (value) {
          if (value == null) return;
          setState(() => _vehicleType = value);
        },
      ),
    );
  }
}
