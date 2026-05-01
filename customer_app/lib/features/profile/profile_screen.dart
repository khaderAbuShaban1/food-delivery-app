import 'package:flutter/material.dart';
import '../../core/theme/app_theme.dart';
import '../../core/services/auth_service.dart';
import '../auth/screens/login_screen.dart';
import '../home/main_screen.dart';
import 'edit_profile_screen.dart';

class ProfileScreen extends StatefulWidget {
  final VoidCallback? onImageUpdated;
  const ProfileScreen({super.key, this.onImageUpdated});

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  bool _isLoading = true;
  String _name = 'ضيف';
  String _email = '';
  String? _profileImage;
  int _imageCacheKey = 0;

  @override
  void initState() {
    super.initState();
    _loadUser();
  }

  String? _getImageUrlWithCacheBuster(String? url) {
    if (url == null || url.isEmpty) return null;
    final separator = url.contains('?') ? '&' : '?';
    return '$url${separator}v=$_imageCacheKey';
  }

  Future<void> _loadUser() async {
    setState(() => _isLoading = true);
    await AuthService.fetchCurrentUser();
    if (!mounted) return;
    setState(() {
      _name = AuthService.currentUserName;
      _email = AuthService.currentUserEmail;
      _profileImage = AuthService.currentUserImage;
      _imageCacheKey = DateTime.now().millisecondsSinceEpoch;
      _isLoading = false;
    });
  }

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    _loadUser();
  }

  @override
  Widget build(BuildContext context) {
    final isLoggedIn = AuthService.isLoggedIn();

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: const Text('الملف الشخصي'),
        backgroundColor: AppColors.background,
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(AppSpacing.lg),
        child: Column(
          children: [
            // Profile header
            Container(
              padding: const EdgeInsets.all(AppSpacing.xl),
              decoration: BoxDecoration(
                color: AppColors.surface,
                borderRadius: BorderRadius.circular(AppRadius.lg),
                boxShadow: [
                  BoxShadow(
                    color: AppColors.shadow,
                    blurRadius: 8,
                    offset: const Offset(0, 2),
                  ),
                ],
              ),
              child: Column(
                children: [
Container(
                  width: 80,
                  height: 80,
                  decoration: BoxDecoration(
                    color: AppColors.secondary,
                    shape: BoxShape.circle,
                    image: _profileImage != null
                        ? DecorationImage(
                            image: NetworkImage(_getImageUrlWithCacheBuster(_profileImage)!),
                            fit: BoxFit.cover,
                            onError: (_, __) {},
                          )
                        : null,
                  ),
                  child: _profileImage == null
                      ? const Center(
                          child: Icon(
                            Icons.person_outline,
                            size: 40,
                            color: AppColors.primary,
                          ),
                        )
                      : null,
                ),
                  const SizedBox(height: AppSpacing.md),
                  _isLoading
                      ? const SizedBox(
                          width: 20,
                          height: 20,
                          child: CircularProgressIndicator(strokeWidth: 2),
                        )
                      : Text(
                          isLoggedIn ? _name : 'زائر',
                          style: const TextStyle(
                            fontSize: 20,
                            fontWeight: FontWeight.bold,
                            color: AppColors.textPrimary,
                          ),
                        ),
                  if (_email.isNotEmpty) ...[
                    const SizedBox(height: AppSpacing.xs),
                    Text(
                      _email,
                      style: const TextStyle(
                        fontSize: 13,
                        color: AppColors.textSecondary,
                      ),
                    ),
                  ],
                ],
              ),
            ),
            const SizedBox(height: AppSpacing.xl),
            // Menu items
            _buildMenuItem(
              icon: Icons.edit_outlined,
              title: 'تعديل الملف الشخصي',
              onTap: () async {
                await Navigator.push(
                  context,
                  MaterialPageRoute(
                    builder: (_) => const EditProfileScreen(),
                  ),
                );
                _loadUser();
              },
            ),
            _buildMenuItem(
              icon: Icons.receipt_long_outlined,
              title: 'طلباتي',
              onTap: () {
                Navigator.push(
                  context,
                  MaterialPageRoute(
                    builder: (_) => const MainScreen(initialIndex: 2),
                  ),
                );
              },
            ),
            _buildMenuItem(
              icon: Icons.shopping_cart_outlined,
              title: 'السلة',
              onTap: () {
                Navigator.push(
                  context,
                  MaterialPageRoute(
                    builder: (_) => const MainScreen(initialIndex: 1),
                  ),
                );
              },
            ),
            _buildMenuItem(
              icon: Icons.location_on_outlined,
              title: 'العناوين',
              onTap: () {
                Navigator.pushNamed(context, '/addresses');
              },
            ),
            _buildMenuItem(
              icon: Icons.payment_outlined,
              title: 'طرق الدفع',
              onTap: () => _showComingSoon(context),
            ),
            _buildMenuItem(
              icon: Icons.notifications_outlined,
              title: 'الإشعارات',
              onTap: () => _showComingSoon(context),
            ),
            _buildMenuItem(
              icon: Icons.help_outline,
              title: 'المساعدة',
              onTap: () => _showComingSoon(context),
            ),
            _buildMenuItem(
              icon: Icons.info_outline,
              title: 'عن التطبيق',
              onTap: () => _showComingSoon(context),
            ),
            const SizedBox(height: AppSpacing.xl),
            // Logout button
            SizedBox(
              width: double.infinity,
              child: ElevatedButton(
                onPressed: () => _logout(context),
                style: ElevatedButton.styleFrom(
                  backgroundColor: AppColors.error,
                  foregroundColor: Colors.white,
                  padding: const EdgeInsets.symmetric(vertical: AppSpacing.lg),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(AppRadius.lg),
                  ),
                  elevation: 2,
                ),
                child: const Text(
                  'تسجيل الخروج',
                  style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  void _showComingSoon(BuildContext context) {
    ScaffoldMessenger.of(
      context,
    ).showSnackBar(const SnackBar(content: Text('هذه الميزة قيد التطوير')));
  }

  Widget _buildMenuItem({
    required IconData icon,
    required String title,
    required VoidCallback onTap,
  }) {
    return Container(
      margin: const EdgeInsets.only(bottom: AppSpacing.md),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(AppRadius.lg),
        boxShadow: [
          BoxShadow(
            color: AppColors.shadow,
            blurRadius: 4,
            offset: const Offset(0, 1),
          ),
        ],
      ),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          borderRadius: BorderRadius.circular(AppRadius.lg),
          onTap: onTap,
          child: ListTile(
            leading: Container(
              padding: const EdgeInsets.all(AppSpacing.sm),
              decoration: BoxDecoration(
                color: AppColors.secondary,
                borderRadius: BorderRadius.circular(AppRadius.md),
              ),
              child: Icon(icon, color: AppColors.primary, size: 20),
            ),
            title: Text(
              title,
              style: const TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.w500,
                color: AppColors.textPrimary,
              ),
            ),
            trailing: const Icon(Icons.chevron_left, color: AppColors.textHint),
            contentPadding: const EdgeInsets.symmetric(
              horizontal: AppSpacing.lg,
              vertical: AppSpacing.xs,
            ),
          ),
        ),
      ),
    );
  }

  void _logout(BuildContext context) {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('تسجيل الخروج'),
        content: const Text('هل أنت متأكد من تسجيل الخروج؟'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: const Text('إلغاء'),
          ),
          TextButton(
            onPressed: () {
              Navigator.pop(ctx);
              AuthService.logout();
              Navigator.pushAndRemoveUntil(
                context,
                MaterialPageRoute(builder: (_) => const LoginScreen()),
                (route) => false,
              );
            },
            child: const Text(
              'تسجيل الخروج',
              style: TextStyle(color: AppColors.error),
            ),
          ),
        ],
      ),
    );
  }
}
