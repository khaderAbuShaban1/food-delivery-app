import 'package:flutter/material.dart';
import '../../core/models/address.dart';
import '../../core/services/address_service.dart';
import '../../core/theme/app_theme.dart';
import '../../core/widgets/widgets.dart';

class AddressFormScreen extends StatefulWidget {
  final Address? address;

  AddressFormScreen({super.key, this.address});

  @override
  State<AddressFormScreen> createState() => _AddressFormScreenState();
}

class _AddressFormScreenState extends State<AddressFormScreen> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _titleController;
  late final TextEditingController _cityController;
  late final TextEditingController _streetController;
  late final TextEditingController _detailsController;
  bool _isDefault = false;
  bool _isSaving = false;

  bool get _isEdit => widget.address != null;

  @override
  void initState() {
    super.initState();
    _titleController = TextEditingController(text: widget.address?.title ?? '');
    _cityController = TextEditingController(text: widget.address?.city ?? '');
    _streetController = TextEditingController(text: widget.address?.street ?? '');
    _detailsController = TextEditingController(text: widget.address?.details ?? '');
    _isDefault = widget.address?.isDefault ?? false;
  }

  @override
  void dispose() {
    _titleController.dispose();
    _cityController.dispose();
    _streetController.dispose();
    _detailsController.dispose();
    super.dispose();
  }

  Future<void> _save() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _isSaving = true);
    try {
      if (_isEdit) {
        await AddressService.updateAddress(
          id: widget.address!.id,
          title: _titleController.text.trim(),
          city: _cityController.text.trim(),
          street: _streetController.text.trim(),
          details: _detailsController.text.trim().isEmpty ? null : _detailsController.text.trim(),
          isDefault: _isDefault,
        );
      } else {
        await AddressService.createAddress(
          title: _titleController.text.trim(),
          city: _cityController.text.trim(),
          street: _streetController.text.trim(),
          details: _detailsController.text.trim().isEmpty ? null : _detailsController.text.trim(),
          isDefault: _isDefault,
        );
      }

      if (!mounted) return;
      Navigator.pop(context, true);
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(e.toString().replaceFirst('Exception: ', ''))),
      );
    } finally {
      if (mounted) setState(() => _isSaving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      appBar: AppBar(
        title: Text(_isEdit ? 'تعديل العنوان' : 'إضافة عنوان جديد'),
      ),
      body: Form(
        key: _formKey,
        child: ListView(
          padding: EdgeInsets.all(AppSpacing.lg),
          children: [
            AppTextField(
              controller: _titleController,
              labelText: 'العنوان (المنزل، العمل...)',
              hintText: 'مثال: المنزل',
              validator: (value) {
                if (value == null || value.trim().isEmpty) {
                  return 'يرجى إدخال عنوان المكان';
                }
                return null;
              },
            ),
            SizedBox(height: AppSpacing.md),
            AppTextField(
              controller: _cityController,
              labelText: 'المدينة',
              hintText: 'أدخل المدينة',
              validator: (value) {
                if (value == null || value.trim().isEmpty) {
                  return 'يرجى إدخال المدينة';
                }
                return null;
              },
            ),
            SizedBox(height: AppSpacing.md),
            AppTextField(
              controller: _streetController,
              labelText: 'الشارع',
              hintText: 'أدخل اسم الشارع',
              validator: (value) {
                if (value == null || value.trim().isEmpty) {
                  return 'يرجى إدخال الشارع';
                }
                return null;
              },
            ),
            SizedBox(height: AppSpacing.md),
            AppTextField(
              controller: _detailsController,
              labelText: 'تفاصيل إضافية (اختياري)',
              hintText: 'رقم المبنى، الشقة، معلم قريب...',
              maxLines: 3,
            ),
            SizedBox(height: AppSpacing.md),
            Container(
              decoration: BoxDecoration(
                color: Theme.of(context).colorScheme.surface,
                borderRadius: BorderRadius.circular(AppRadius.md),
                border: Border.all(color: Theme.of(context).dividerColor),
              ),
              child: SwitchListTile(
                value: _isDefault,
                title: Text('تعيين كعنوان افتراضي'),
                subtitle: Text('سيُستخدم هذا العنوان بشكل افتراضي عند الطلب'),
                onChanged: (value) => setState(() => _isDefault = value),
                activeColor: AppColors.primary,
              ),
            ),
            SizedBox(height: AppSpacing.xl),
            AppButton(
              text: _isEdit ? 'حفظ التعديلات' : 'إضافة العنوان',
              isLoading: _isSaving,
              onPressed: _save,
            ),
          ],
        ),
      ),
    );
  }
}



