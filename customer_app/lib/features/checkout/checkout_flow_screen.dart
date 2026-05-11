import 'dart:io';

import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:image_picker/image_picker.dart';
import 'package:provider/provider.dart';

import '../../core/api/api_client.dart';
import '../../core/models/active_payment_method.dart';
import '../../core/models/address.dart';
import '../../core/services/address_service.dart';
import '../../core/services/auth_service.dart';
import '../../core/services/cart_provider.dart';
import '../../core/services/payment_method_service.dart';
import '../../core/theme/app_theme.dart';
import '../home/main_screen.dart';

/// Full manual checkout: saved address (+ add new), payment method selection, proof image, submit.
class CheckoutFlowScreen extends StatefulWidget {
  CheckoutFlowScreen({super.key});

  @override
  State<CheckoutFlowScreen> createState() => _CheckoutFlowScreenState();
}

class _CheckoutFlowScreenState extends State<CheckoutFlowScreen> {
  bool _loadingBootstrap = true;
  String? _bootstrapError;

  List<Address> _addresses = [];
  List<ActivePaymentMethod> _methods = [];

  int? _selectedAddressId;
  int? _selectedPaymentMethodId;
  String? _proofImagePath;

  bool _submitting = false;

  ActivePaymentMethod? get _selectedMethod {
    if (_selectedPaymentMethodId == null) return null;
    try {
      return _methods.firstWhere((m) => m.id == _selectedPaymentMethodId);
    } catch (_) {
      return null;
    }
  }

  bool get _canSubmit {
    return !_submitting &&
        _selectedAddressId != null &&
        _selectedPaymentMethodId != null &&
        _proofImagePath != null &&
        _proofImagePath!.trim().isNotEmpty;
  }

  @override
  void initState() {
    super.initState();
    if (!AuthService.isLoggedIn()) {
      _bootstrapError = 'يرجى تسجيل الدخول.';
      _loadingBootstrap = false;
      return;
    }
    _bootstrap();
  }

  Future<void> _bootstrap() async {
    setState(() {
      _loadingBootstrap = true;
      _bootstrapError = null;
    });

    try {
      final addresses = await AddressService.getAddresses();
      final methods = await PaymentMethodService.getActivePaymentMethods();

      int? addrId = _selectedAddressId;
      if (addrId == null && addresses.isNotEmpty) {
        Address? preferred;
        for (final a in addresses) {
          if (a.isDefault) {
            preferred = a;
            break;
          }
        }
        preferred ??= addresses.first;
        addrId = preferred.id;
      }

      if (!mounted) return;
      setState(() {
        _addresses = addresses;
        _methods = methods;
        _selectedAddressId = addrId;
        _loadingBootstrap = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _bootstrapError = e.toString().replaceFirst('Exception: ', '');
        _loadingBootstrap = false;
      });
    }
  }

  Future<void> _pickProof() async {
    final picker = ImagePicker();
    final x = await picker.pickImage(
      source: ImageSource.gallery,
      maxWidth: 2000,
      imageQuality: 88,
    );
    if (!mounted || x == null) return;
    setState(() => _proofImagePath = x.path);
  }

  Future<void> _showAddAddressSheet() async {
    final titleCtl = TextEditingController();
    final cityCtl = TextEditingController();
    final streetCtl = TextEditingController();
    final detailsCtl = TextEditingController();

    Future<void> disposeCtrls() async {
      titleCtl.dispose();
      cityCtl.dispose();
      streetCtl.dispose();
      detailsCtl.dispose();
    }

    final createdAddressId = await showModalBottomSheet<int>(
      context: context,
      isScrollControlled: true,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (ctx) {
        return Padding(
          padding: EdgeInsets.only(
            left: AppSpacing.lg,
            right: AppSpacing.lg,
            top: AppSpacing.md,
            bottom: MediaQuery.of(ctx).viewInsets.bottom + AppSpacing.lg,
          ),
          child: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Text(
                  'إضافة عنوان جديد',
                  textAlign: TextAlign.center,
                  style: TextStyle(fontSize: 18, fontWeight: FontWeight.w800),
                ),
                SizedBox(height: AppSpacing.md),
                TextField(
                  controller: titleCtl,
                  decoration: InputDecoration(
                    labelText: 'اسم العنوان (مثل المنزل)',
                    border: OutlineInputBorder(),
                  ),
                ),
                SizedBox(height: AppSpacing.sm),
                TextField(
                  controller: cityCtl,
                  decoration: InputDecoration(
                    labelText: 'المدينة',
                    border: OutlineInputBorder(),
                  ),
                ),
                SizedBox(height: AppSpacing.sm),
                TextField(
                  controller: streetCtl,
                  decoration: InputDecoration(
                    labelText: 'الشارع',
                    border: OutlineInputBorder(),
                  ),
                ),
                SizedBox(height: AppSpacing.sm),
                TextField(
                  controller: detailsCtl,
                  maxLines: 2,
                  decoration: InputDecoration(
                    labelText: 'تفاصيل إضافية',
                    border: OutlineInputBorder(),
                  ),
                ),
                SizedBox(height: AppSpacing.lg),
                FilledButton(
                  onPressed: () async {
                    final title = titleCtl.text.trim();
                    final city = cityCtl.text.trim();
                    final street = streetCtl.text.trim();
                    final detailsRaw = detailsCtl.text.trim();
                    if (title.isEmpty || city.isEmpty || street.isEmpty) {
                      ScaffoldMessenger.of(ctx).showSnackBar(
                        SnackBar(
                          content: Text('أكمل الحقول المطلوبة (الاسم، المدينة، الشارع)'),
                          backgroundColor: AppColors.error,
                        ),
                      );
                      return;
                    }

                    try {
                      final createdAddress = await AddressService.createAddress(
                        title: title,
                        city: city,
                        street: street,
                        details: detailsRaw.isEmpty ? null : detailsRaw,
                        isDefault: _addresses.isEmpty,
                      );
                      if (ctx.mounted) Navigator.of(ctx).pop(createdAddress.id);
                    } catch (e) {
                      if (!ctx.mounted) return;
                      ScaffoldMessenger.of(ctx).showSnackBar(
                        SnackBar(
                          content: Text(e.toString().replaceFirst('Exception: ', '')),
                          backgroundColor: AppColors.error,
                        ),
                      );
                    }
                  },
                  child: Text('حفظ العنوان'),
                ),
              ],
            ),
          ),
        );
      },
    );

    await disposeCtrls();

    if (createdAddressId != null && mounted) {
      await _bootstrap();
      setState(() => _selectedAddressId = createdAddressId);
    }
  }

  Future<void> _submit() async {
    if (!_canSubmit) return;

    final cart = Provider.of<CartProvider>(context, listen: false);
    if (cart.items.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('السلة فارغة')),
      );
      return;
    }

    final restaurantIds = cart.items.map((e) => e.menuItem.restaurantId).toSet();
    if (restaurantIds.length != 1) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('لا يمكن الطلب من أكثر من مطعم')),
      );
      return;
    }

    final restaurantId = restaurantIds.first;
    if (restaurantId <= 0) return;

    setState(() => _submitting = true);

    try {
      final fields = <String, String>{
        'restaurant_id': restaurantId.toString(),
        'address_id': _selectedAddressId!.toString(),
        'payment_method_id': _selectedPaymentMethodId!.toString(),
      };
      for (var i = 0; i < cart.items.length; i++) {
        final item = cart.items[i];
        fields['items[$i][menu_item_id]'] = item.menuItem.id.toString();
        fields['items[$i][quantity]'] = item.quantity.toString();
        // Forward selected options (read-only for now; backend may ignore if not implemented).
        item.selectedOptionValueIdsByGroup.forEach((groupId, valueIds) {
          for (var j = 0; j < valueIds.length; j++) {
            fields['items[$i][options][$groupId][$j]'] = valueIds[j].toString();
          }
        });
      }

      final files = <http.MultipartFile>[
        await http.MultipartFile.fromPath('payment_proof', _proofImagePath!),
      ];

      final response = await ApiClient.postMultipart('/orders', fields: fields, files: files);

      if (!mounted) return;
      setState(() => _submitting = false);

      if (response['success'] == true && response['data'] != null) {
        final dynamic rawOrderId = response['data']['id'];
        final int? orderId = rawOrderId is int
            ? rawOrderId
            : int.tryParse(rawOrderId?.toString() ?? '');
        cart.clearCart();

        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              'تم إرسال طلبك وهو قيد تحقّق الإدارة من الدفع.\n'
              'Your order has been submitted and is pending admin payment verification.',
              style: TextStyle(height: 1.35),
            ),
            backgroundColor: AppColors.success,
          ),
        );

        Navigator.of(context).pushAndRemoveUntil(
          MaterialPageRoute<void>(
            builder: (_) => MainScreen(initialIndex: 2, highlightedOrderId: orderId),
          ),
          (_) => false,
        );
        return;
      }

      String message = response['message']?.toString() ?? 'تعذر إرسال الطلب';
      final errors = response['errors'];
      if (errors is Map && errors.isNotEmpty) {
        final firstKey = errors.keys.first;
        final firstError = errors[firstKey];
        if (firstError is List && firstError.isNotEmpty) {
          message = firstError.first.toString();
        }
      }

      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(message), backgroundColor: AppColors.error),
      );
    } catch (_) {
      if (!mounted) return;
      setState(() => _submitting = false);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('حدث خطأ أثناء الإرسال'),
          backgroundColor: AppColors.error,
        ),
      );
    }
  }

  Widget _sectionTitle(String t) {
    return Padding(
      padding: EdgeInsets.only(bottom: AppSpacing.sm, top: AppSpacing.sm),
      child: Text(
        t,
        style: TextStyle(
          fontSize: 16,
          fontWeight: FontWeight.w800,
          color: Theme.of(context).colorScheme.onSurface,
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    final isDark = Theme.of(context).brightness == Brightness.dark;
    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      appBar: AppBar(
        title: Text('العنوان وطريقة الدفع'),
        backgroundColor: Theme.of(context).colorScheme.surface,
        elevation: 0,
      ),
      body: _loadingBootstrap
          ? Center(child: CircularProgressIndicator(color: AppColors.primary))
          : _bootstrapError != null
              ? Center(
                  child: Padding(
                    padding: EdgeInsets.all(AppSpacing.lg),
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Text(_bootstrapError!, textAlign: TextAlign.center),
                        TextButton(onPressed: _bootstrap, child: Text('إعادة المحاولة')),
                      ],
                    ),
                  ),
                )
              : Stack(
                  children: [
                    ListView(
                      padding: EdgeInsets.all(AppSpacing.lg),
                      children: [
                        _sectionTitle('عنوان التوصيل'),
                        if (_addresses.isEmpty)
                          Card(
                            child: Padding(
                              padding: EdgeInsets.all(AppSpacing.md),
                              child: Column(
                                children: [
                                  Text('لا توجد عناوين محفوظة. أضف عنواناً للمتابعة.'),
                                  TextButton.icon(
                                    onPressed: _showAddAddressSheet,
                                    icon: Icon(Icons.add_location_alt_outlined),
                                    label: Text('إضافة عنوان جديد'),
                                  ),
                                ],
                              ),
                            ),
                          ),
                        ..._addresses.map((a) {
                          final sel = _selectedAddressId == a.id;
                          return Padding(
                            padding: EdgeInsets.only(bottom: AppSpacing.sm),
                            child: Material(
                              color: sel
                                  ? (isDark ? scheme.surfaceContainerHighest : AppColors.secondary)
                                  : scheme.surface,
                              borderRadius: BorderRadius.circular(AppRadius.lg),
                              child: InkWell(
                                onTap: () => setState(() => _selectedAddressId = a.id),
                                borderRadius: BorderRadius.circular(AppRadius.lg),
                                child: Container(
                                  padding: EdgeInsets.all(AppSpacing.md),
                                  decoration: BoxDecoration(
                                    borderRadius: BorderRadius.circular(AppRadius.lg),
                                    border: Border.all(
                                      color: sel ? AppColors.primary : Theme.of(context).dividerColor,
                                      width: sel ? 2 : 1,
                                    ),
                                  ),
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Row(
                                        children: [
                                          Icon(
                                            sel ? Icons.radio_button_checked : Icons.radio_button_off,
                                            color: sel ? AppColors.primary : Theme.of(context).colorScheme.onSurfaceVariant,
                                          ),
                                          SizedBox(width: AppSpacing.sm),
                                          Expanded(
                                            child: Text(
                                              a.title,
                                              style: TextStyle(
                                                fontWeight: FontWeight.w700,
                                                fontSize: 16,
                                              ),
                                            ),
                                          ),
                                        ],
                                      ),
                                      SizedBox(height: 6),
                                      Text(
                                        a.fullAddress,
                                        style: TextStyle(
                                          color: Theme.of(context).colorScheme.onSurfaceVariant,
                                          height: 1.35,
                                        ),
                                      ),
                                    ],
                                  ),
                                ),
                              ),
                            ),
                          );
                        }),
                        if (_addresses.isNotEmpty)
                          Padding(
                            padding: EdgeInsets.only(bottom: AppSpacing.md),
                            child: TextButton.icon(
                              onPressed: _showAddAddressSheet,
                              icon: Icon(Icons.add),
                              label: Text('إضافة عنوان جديد'),
                            ),
                          ),
                        Divider(),
                        _sectionTitle('طرق الدفع المتاحة'),
                        if (_methods.isEmpty)
                          Card(
                            child: Padding(
                              padding: EdgeInsets.all(AppSpacing.md),
                              child: Text('لا توجد طرق دفع مفعّلة حالياً. تواصل مع الدعم.'),
                            ),
                          ),
                        ..._methods.map((m) {
                          final sel = _selectedPaymentMethodId == m.id;
                          return Padding(
                            padding: EdgeInsets.only(bottom: AppSpacing.sm),
                            child: Material(
                              color: sel
                                  ? (isDark ? scheme.surfaceContainerHighest : AppColors.secondary)
                                  : scheme.surface,
                              borderRadius: BorderRadius.circular(AppRadius.lg),
                              child: InkWell(
                                onTap: () => setState(() => _selectedPaymentMethodId = m.id),
                                borderRadius: BorderRadius.circular(AppRadius.lg),
                                child: Container(
                                  padding: EdgeInsets.all(AppSpacing.md),
                                  decoration: BoxDecoration(
                                    borderRadius: BorderRadius.circular(AppRadius.lg),
                                    border: Border.all(
                                      color: sel ? AppColors.primary : Theme.of(context).dividerColor,
                                      width: sel ? 2 : 1,
                                    ),
                                  ),
                                  child: Row(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      if (m.staticImageUrl != null && m.staticImageUrl!.isNotEmpty)
                                        ClipRRect(
                                          borderRadius: BorderRadius.circular(8),
                                          child: Image.network(
                                            m.staticImageUrl!,
                                            width: 56,
                                            height: 56,
                                            fit: BoxFit.cover,
                                            errorBuilder: (_, _, _) => SizedBox(
                                              width: 56,
                                              height: 56,
                                              child: Icon(Icons.account_balance),
                                            ),
                                          ),
                                        )
                                      else
                                        SizedBox(
                                          width: 56,
                                          height: 56,
                                          child: Icon(Icons.payment, size: 36),
                                        ),
                                      SizedBox(width: AppSpacing.md),
                                      Expanded(
                                        child: Column(
                                          crossAxisAlignment: CrossAxisAlignment.start,
                                          children: [
                                            Text(
                                              m.typeLabel,
                                              style: TextStyle(
                                                fontSize: 12,
                                                color: Theme.of(context).colorScheme.onSurfaceVariant,
                                              ),
                                            ),
                                            Text(
                                              m.subtypeName,
                                              style: TextStyle(
                                                fontWeight: FontWeight.w700,
                                                fontSize: 15,
                                              ),
                                            ),
                                            Text(
                                              m.accountHolderName,
                                              style: TextStyle(fontSize: 13),
                                            ),
                                          ],
                                        ),
                                      ),
                                      Icon(
                                        sel ? Icons.radio_button_checked : Icons.radio_button_off,
                                        color: sel ? AppColors.primary : Theme.of(context).colorScheme.onSurfaceVariant,
                                      ),
                                    ],
                                  ),
                                ),
                              ),
                            ),
                          );
                        }),
                        if (_selectedMethod != null) ...[
                          SizedBox(height: AppSpacing.sm),
                          Card(
                            child: Padding(
                              padding: EdgeInsets.all(AppSpacing.md),
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    'تفاصيل الدفع',
                                    style: TextStyle(fontWeight: FontWeight.w800, fontSize: 15),
                                  ),
                                  SizedBox(height: AppSpacing.sm),
                                  Text('${ _selectedMethod!.typeLabel } — ${_selectedMethod!.subtypeName}'),
                                  SizedBox(height: 6),
                                  Text('صاحب الحساب: ${_selectedMethod!.accountHolderName}'),
                                  if (_selectedMethod!.accountNumber != null &&
                                      _selectedMethod!.accountNumber!.isNotEmpty)
                                    Text('رقم الحساب: ${_selectedMethod!.accountNumber}'),
                                  Text('الهاتف: ${_selectedMethod!.phoneNumber}'),
                                  if (_selectedMethod!.staticImageUrl != null &&
                                      _selectedMethod!.staticImageUrl!.isNotEmpty) ...[
                                    SizedBox(height: AppSpacing.sm),
                                    ClipRRect(
                                      borderRadius: BorderRadius.circular(12),
                                      child: Image.network(
                                        _selectedMethod!.staticImageUrl!,
                                        height: 140,
                                        width: double.infinity,
                                        fit: BoxFit.contain,
                                        errorBuilder: (_, _, _) => SizedBox.shrink(),
                                      ),
                                    ),
                                  ],
                                ],
                              ),
                            ),
                          ),
                        ],
                        Divider(height: AppSpacing.xl),
                        _sectionTitle('إثبات الدفع (صورة إلزامية)'),
                        OutlinedButton.icon(
                          onPressed: _submitting ? null : _pickProof,
                          icon: Icon(Icons.upload_file),
                          label: Text(_proofImagePath == null ? 'اختر صورة الإيصال' : 'تغيير الصورة'),
                        ),
                        if (_proofImagePath != null) ...[
                          SizedBox(height: AppSpacing.sm),
                          ClipRRect(
                            borderRadius: BorderRadius.circular(12),
                            child: Image.file(
                              File(_proofImagePath!),
                              height: 160,
                              width: double.infinity,
                              fit: BoxFit.cover,
                            ),
                          ),
                        ],
                        SizedBox(height: 100),
                      ],
                    ),
                    Positioned(
                      left: 0,
                      right: 0,
                      bottom: 0,
                      child: Container(
                        padding: EdgeInsets.all(AppSpacing.lg),
                        decoration: BoxDecoration(
                          color: Theme.of(context).colorScheme.surface,
                          boxShadow: [
                            BoxShadow(
                              color: Colors.black.withValues(alpha: 0.06),
                              blurRadius: 12,
                              offset: Offset(0, -4),
                            ),
                          ],
                        ),
                        child: SafeArea(
                          child: FilledButton(
                            onPressed: _canSubmit ? _submit : null,
                            style: FilledButton.styleFrom(
                              backgroundColor: AppColors.primary,
                              padding: EdgeInsets.symmetric(vertical: 14),
                              shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(AppRadius.lg),
                              ),
                            ),
                            child: _submitting
                                ? SizedBox(
                                    height: 22,
                                    width: 22,
                                    child: CircularProgressIndicator(
                                      strokeWidth: 2,
                                      color: Colors.white,
                                    ),
                                  )
                                : Text(
                                    'لقد دفعت',
                                    style: TextStyle(
                                      fontSize: 16,
                                      fontWeight: FontWeight.bold,
                                    ),
                                  ),
                          ),
                        ),
                      ),
                    ),
                  ],
                ),
    );
  }
}



