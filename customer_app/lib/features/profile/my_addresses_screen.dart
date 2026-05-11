import 'package:flutter/material.dart';
import '../../core/models/address.dart';
import '../../core/services/address_service.dart';
import '../../core/theme/app_theme.dart';
import '../../core/widgets/widgets.dart';
import 'address_form_screen.dart';

class MyAddressesScreen extends StatefulWidget {
  MyAddressesScreen({super.key});

  @override
  State<MyAddressesScreen> createState() => _MyAddressesScreenState();
}

class _MyAddressesScreenState extends State<MyAddressesScreen> {
  bool _isLoading = true;
  List<Address> _addresses = [];

  @override
  void initState() {
    super.initState();
    _loadAddresses();
  }

  Future<void> _loadAddresses({bool showLoading = true}) async {
    if (showLoading) {
      setState(() => _isLoading = true);
    }
    try {
      final data = await AddressService.getAddresses();
      if (!mounted) return;
      setState(() {
        _addresses = data;
        if (showLoading) _isLoading = false;
      });
    } catch (e) {
      if (!mounted) return;
      if (showLoading) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(e.toString().replaceFirst('Exception: ', ''))),
        );
      }
    } finally {
      if (showLoading && mounted) setState(() => _isLoading = false);
    }
  }

  Future<void> _openForm({Address? address}) async {
    final changed = await Navigator.push<bool>(
      context,
      MaterialPageRoute(
        builder: (_) => AddressFormScreen(address: address),
      ),
    );
    if (!mounted || changed != true) return;
    await _loadAddresses(showLoading: false);
  }

  Future<void> _deleteAddress(Address address) async {
    final shouldDelete = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text('حذف العنوان'),
        content: Text('هل أنت متأكد من حذف هذا العنوان؟'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: Text('إلغاء'),
          ),
          TextButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: Text(
              'حذف',
              style: TextStyle(color: AppColors.error),
            ),
          ),
        ],
      ),
    );

    if (shouldDelete != true) return;

    try {
      await AddressService.deleteAddress(address.id);
      if (!mounted) return;
      setState(() {
        _addresses.removeWhere((a) => a.id == address.id);
      });
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(e.toString().replaceFirst('Exception: ', ''))),
      );
    }
  }

  Future<void> _setDefault(Address address) async {
    try {
      await AddressService.updateAddress(
        id: address.id,
        title: address.title,
        city: address.city,
        street: address.street,
        details: address.details,
        isDefault: true,
      );
      if (!mounted) return;
      await _loadAddresses(showLoading: false);
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(e.toString().replaceFirst('Exception: ', ''))),
      );
    }
  }

  Widget _buildAddressCard(Address address) {
    return Container(
      margin: EdgeInsets.only(bottom: AppSpacing.md),
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.surface,
        borderRadius: BorderRadius.circular(AppRadius.xl),
        border: Border.all(
          color: address.isDefault
              ? AppColors.primary.withValues(alpha: 0.45)
              : Theme.of(context).dividerColor,
          width: address.isDefault ? 1.6 : 1,
        ),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.05),
            blurRadius: 14,
            offset: Offset(0, 6),
          ),
        ],
      ),
      child: Padding(
        padding: EdgeInsets.fromLTRB(
          AppSpacing.md,
          AppSpacing.md,
          AppSpacing.md,
          AppSpacing.sm,
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.end,
          children: [
            Row(
              children: [
                _buildActionIcon(
                  icon: Icons.delete_outline_rounded,
                  color: AppColors.error,
                  tooltip: 'حذف',
                  onTap: () => _deleteAddress(address),
                ),
                SizedBox(width: AppSpacing.xs),
                _buildActionIcon(
                  icon: Icons.edit_outlined,
                  tooltip: 'تعديل',
                  onTap: () => _openForm(address: address),
                ),
                SizedBox(width: AppSpacing.sm),
                Expanded(
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.end,
                    children: [
                      Expanded(
                        child: Text(
                          address.title,
                          textAlign: TextAlign.right,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.w800,
                            color: Theme.of(context).colorScheme.onSurface,
                          ),
                        ),
                      ),
                      SizedBox(width: AppSpacing.xs),
                      Icon(
                        Icons.location_on_outlined,
                        size: 18,
                        color: address.isDefault
                            ? AppColors.primary
                            : Theme.of(context).colorScheme.onSurfaceVariant,
                      ),
                    ],
                  ),
                ),
                if (address.isDefault) ...[
                  SizedBox(width: AppSpacing.sm),
                  Container(
                    padding: EdgeInsets.symmetric(
                      horizontal: AppSpacing.sm,
                      vertical: AppSpacing.xs,
                    ),
                    decoration: BoxDecoration(
                      color: AppColors.primary.withValues(alpha: 0.12),
                      borderRadius: BorderRadius.circular(AppRadius.pill),
                    ),
                    child: Text(
                      'الافتراضي',
                      style: TextStyle(
                        color: AppColors.primaryDark,
                        fontWeight: FontWeight.bold,
                        fontSize: 12,
                      ),
                    ),
                  ),
                ],
              ],
            ),
            SizedBox(height: AppSpacing.sm),
            Text(
              address.fullAddress,
              textAlign: TextAlign.right,
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
              style: TextStyle(
                color: Theme.of(context).colorScheme.onSurfaceVariant,
                fontSize: 13,
                height: 1.4,
              ),
            ),
            SizedBox(height: AppSpacing.md),
            Divider(
              color: Theme.of(context).dividerColor.withValues(alpha: 0.7),
              height: 1,
            ),
            SizedBox(height: AppSpacing.xs),
            Row(
              children: [
                if (!address.isDefault)
                  TextButton.icon(
                    onPressed: () => _setDefault(address),
                    style: TextButton.styleFrom(
                      padding: EdgeInsets.symmetric(
                        horizontal: AppSpacing.sm,
                      ),
                      minimumSize: Size.zero,
                      tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                    ),
                    icon: Icon(
                      Icons.star_outline_rounded,
                      size: 17,
                      color: AppColors.primary,
                    ),
                    label: Text(
                      'تعيين افتراضي',
                      style: TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.w700,
                        color: AppColors.primary,
                      ),
                    ),
                  )
                else
                  Text(
                    'العنوان الافتراضي الحالي',
                    style: TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.w600,
                      color: AppColors.primaryDark,
                    ),
                  ),
                Spacer(),
                Icon(
                  Icons.chevron_left_rounded,
                  size: 20,
                  color: Theme.of(context).colorScheme.onSurfaceVariant,
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildActionIcon({
    required IconData icon,
    required VoidCallback onTap,
    required String tooltip,
    Color? color,
  }) {
    return Tooltip(
      message: tooltip,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(AppRadius.md),
        child: Container(
          width: 32,
          height: 32,
          decoration: BoxDecoration(
            color: Theme.of(context).scaffoldBackgroundColor,
            borderRadius: BorderRadius.circular(AppRadius.md),
            border: Border.all(color: Theme.of(context).dividerColor),
          ),
          child: Icon(
            icon,
            size: 17,
            color: color ?? Theme.of(context).colorScheme.onSurfaceVariant,
          ),
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      appBar: AppBar(
        title: Text('عناويني'),
      ),
      body: RefreshIndicator(
        onRefresh: () => _loadAddresses(showLoading: false),
        child: _isLoading
            ? ListView(
                physics: AlwaysScrollableScrollPhysics(),
                children: [
                  SizedBox(
                    height: MediaQuery.of(context).size.height * 0.75,
                    child: Center(child: CircularProgressIndicator()),
                  ),
                ],
              )
            : _addresses.isEmpty
                ? ListView(
                    physics: AlwaysScrollableScrollPhysics(),
                    children: [
                      SizedBox(
                        height: MediaQuery.of(context).size.height * 0.7,
                        child: EmptyState(
                          icon: Icons.location_off_outlined,
                          title: 'لا توجد عناوين محفوظة',
                          subtitle: 'أضف عنوانك الأول لتسريع عملية الطلب',
                          action: AppButton(
                            text: 'إضافة عنوان جديد',
                            isFullWidth: false,
                            icon: Icons.add,
                            onPressed: () => _openForm(),
                          ),
                        ),
                      ),
                    ],
                  )
                : ListView.builder(
                    physics: AlwaysScrollableScrollPhysics(),
                    padding: EdgeInsets.fromLTRB(
                      AppSpacing.lg,
                      AppSpacing.lg,
                      AppSpacing.lg,
                      AppSpacing.huge,
                    ),
                    itemCount: _addresses.length,
                    itemBuilder: (context, index) =>
                        _buildAddressCard(_addresses[index]),
                  ),
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () => _openForm(),
        backgroundColor: AppColors.primary,
        foregroundColor: Colors.white,
        elevation: 4,
        icon: Icon(Icons.add),
        label: Text('إضافة عنوان'),
      ),
    );
  }

}



