import 'dart:async';

import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../core/services/auth_service.dart';
import '../../core/theme/app_theme.dart';
import '../../core/providers/theme_provider.dart';
import '../auth/screens/login_screen.dart';
import '../home/main_screen.dart';
import 'edit_profile_screen.dart';

class ProfileScreen extends StatefulWidget {
  final VoidCallback? onImageUpdated;
  ProfileScreen({super.key, this.onImageUpdated});

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  bool _isLoading = true;
  String _name = 'ضيف';
  String _email = '';
  String? _profileImage;
  int _imageCacheKey = 0;
  StreamSubscription<Map<String, dynamic>?>? _userSub;

  @override
  void initState() {
    super.initState();
    _subscribeToUser();
    _loadUser();
  }

  void _subscribeToUser() {
    _userSub?.cancel();
    _userSub = AuthService.watchCurrentUser().listen((user) {
      if (!mounted || user == null) return;
      setState(() {
        _name = user['name']?.toString() ?? 'ضيف';
        _email = user['email']?.toString() ?? '';
        _profileImage = user['profile_image']?.toString();
        _imageCacheKey = DateTime.now().millisecondsSinceEpoch;
        _isLoading = false;
      });
    });
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
  Widget build(BuildContext context) {
    final isLoggedIn = AuthService.isLoggedIn();
    final theme = Theme.of(context);
    final colorScheme = theme.colorScheme;
    final themeProvider = context.watch<ThemeProvider>();

    return Scaffold(
      appBar: AppBar(title: Text('الملف الشخصي')),
      body: SingleChildScrollView(
        padding: EdgeInsets.all(AppSpacing.lg),
        child: Column(
          children: [
            Container(
              padding: EdgeInsets.all(AppSpacing.xl),
              decoration: BoxDecoration(
                color: colorScheme.surface,
                borderRadius: BorderRadius.circular(AppRadius.lg),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withValues(
                      alpha: theme.brightness == Brightness.dark ? 0.25 : 0.06,
                    ),
                    blurRadius: 8,
                    offset: Offset(0, 2),
                  ),
                ],
              ),
              child: Column(
                children: [
                  Container(
                    width: 80,
                    height: 80,
                    decoration: BoxDecoration(
                      color: colorScheme.secondaryContainer,
                      shape: BoxShape.circle,
                      image: _profileImage != null
                          ? DecorationImage(
                              image: NetworkImage(
                                _getImageUrlWithCacheBuster(_profileImage)!,
                              ),
                              fit: BoxFit.cover,
                              onError: (_, __) {},
                            )
                          : null,
                    ),
                    child: _profileImage == null
                        ? Center(
                            child: Icon(
                              Icons.person_outline,
                              size: 40,
                              color: colorScheme.primary,
                            ),
                          )
                        : null,
                  ),
                  SizedBox(height: AppSpacing.md),
                  _isLoading
                      ? SizedBox(
                          width: 20,
                          height: 20,
                          child: CircularProgressIndicator(strokeWidth: 2),
                        )
                      : Text(
                          isLoggedIn ? _name : 'زائر',
                          style: TextStyle(
                            fontSize: 20,
                            fontWeight: FontWeight.bold,
                            color: colorScheme.onSurface,
                          ),
                        ),
                  if (_email.isNotEmpty) ...[
                    SizedBox(height: AppSpacing.xs),
                    Text(
                      _email,
                      style: TextStyle(
                        fontSize: 13,
                        color: colorScheme.onSurfaceVariant,
                      ),
                    ),
                  ],
                ],
              ),
            ),
            SizedBox(height: AppSpacing.xl),
            _buildMenuItem(
              context,
              icon: Icons.edit_outlined,
              title: 'تعديل الملف الشخصي',
              onTap: () async {
                await Navigator.push(
                  context,
                  MaterialPageRoute(builder: (_) => EditProfileScreen()),
                );
              },
            ),
            _buildMenuItem(
              context,
              icon: Icons.receipt_long_outlined,
              title: 'طلباتي',
              onTap: () {
                Navigator.push(
                  context,
                  MaterialPageRoute(builder: (_) => MainScreen(initialIndex: 2)),
                );
              },
            ),
            _buildMenuItem(
              context,
              icon: Icons.shopping_cart_outlined,
              title: 'السلة',
              onTap: () {
                Navigator.push(
                  context,
                  MaterialPageRoute(builder: (_) => MainScreen(initialIndex: 1)),
                );
              },
            ),
            _buildMenuItem(
              context,
              icon: Icons.location_on_outlined,
              title: 'العناوين',
              onTap: () => Navigator.pushNamed(context, '/addresses'),
            ),
            _buildThemeModeTile(context, themeProvider),
            _buildMenuItem(
              context,
              icon: Icons.help_outline,
              title: 'المساعدة',
              onTap: () => _showComingSoon(context),
            ),
            SizedBox(height: AppSpacing.xl),
            SizedBox(
              width: double.infinity,
              child: ElevatedButton(
                onPressed: () => _logout(context),
                style: ElevatedButton.styleFrom(
                  backgroundColor: AppColors.error,
                  foregroundColor: Colors.white,
                  padding: EdgeInsets.symmetric(vertical: AppSpacing.lg),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(AppRadius.lg),
                  ),
                ),
                child: Text(
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

  Widget _buildThemeModeTile(BuildContext context, ThemeProvider themeProvider) {
    final colorScheme = Theme.of(context).colorScheme;

    return Container(
      margin: EdgeInsets.only(bottom: AppSpacing.md),
      decoration: BoxDecoration(
        color: colorScheme.surface,
        borderRadius: BorderRadius.circular(AppRadius.lg),
      ),
      child: SwitchListTile.adaptive(
        title: Text(
          'الوضع الداكن',
          style: TextStyle(
            fontSize: 16,
            fontWeight: FontWeight.w500,
            color: colorScheme.onSurface,
          ),
        ),
        subtitle: Text(
          themeProvider.followsSystem ? 'يتبع إعدادات الجهاز' : 'اختيار يدوي محفوظ',
          style: TextStyle(color: colorScheme.onSurfaceVariant),
        ),
        secondary: Container(
          padding: EdgeInsets.all(AppSpacing.sm),
          decoration: BoxDecoration(
            color: colorScheme.secondaryContainer,
            borderRadius: BorderRadius.circular(AppRadius.md),
          ),
          child: Icon(Icons.dark_mode_outlined, color: colorScheme.primary, size: 20),
        ),
        value: themeProvider.isDarkMode,
        onChanged: (enabled) {
          if (enabled != themeProvider.isDarkMode) {
            themeProvider.toggleTheme();
          }
        },
      ),
    );
  }

  void _showComingSoon(BuildContext context) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text('هذه الميزة قيد التطوير')),
    );
  }

  Widget _buildMenuItem(
    BuildContext context, {
    required IconData icon,
    required String title,
    required VoidCallback onTap,
  }) {
    final theme = Theme.of(context);
    final colorScheme = theme.colorScheme;

    return Container(
      margin: EdgeInsets.only(bottom: AppSpacing.md),
      decoration: BoxDecoration(
        color: colorScheme.surface,
        borderRadius: BorderRadius.circular(AppRadius.lg),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(
              alpha: theme.brightness == Brightness.dark ? 0.2 : 0.05,
            ),
            blurRadius: 4,
            offset: Offset(0, 1),
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
              padding: EdgeInsets.all(AppSpacing.sm),
              decoration: BoxDecoration(
                color: colorScheme.secondaryContainer,
                borderRadius: BorderRadius.circular(AppRadius.md),
              ),
              child: Icon(icon, color: colorScheme.primary, size: 20),
            ),
            title: Text(
              title,
              style: TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.w500,
                color: colorScheme.onSurface,
              ),
            ),
            trailing: Icon(Icons.chevron_left, color: colorScheme.onSurfaceVariant),
            contentPadding: EdgeInsets.symmetric(
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
        title: Text('تسجيل الخروج'),
        content: Text('هل أنت متأكد من تسجيل الخروج؟'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx), child: Text('إلغاء')),
          TextButton(
            onPressed: () {
              Navigator.pop(ctx);
              AuthService.logout();
              Navigator.pushAndRemoveUntil(
                context,
                MaterialPageRoute(builder: (_) => LoginScreen()),
                (route) => false,
              );
            },
            child: Text('تسجيل الخروج', style: TextStyle(color: AppColors.error)),
          ),
        ],
      ),
    );
  }

  @override
  void dispose() {
    _userSub?.cancel();
    super.dispose();
  }
}
