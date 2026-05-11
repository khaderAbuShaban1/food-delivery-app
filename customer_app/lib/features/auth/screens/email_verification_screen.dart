import 'dart:async';

import 'package:flutter/material.dart';

import '../../../core/services/auth_service.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/widgets/widgets.dart';

class EmailVerificationScreen extends StatefulWidget {
  final String email;

  EmailVerificationScreen({super.key, required this.email});

  @override
  State<EmailVerificationScreen> createState() =>
      _EmailVerificationScreenState();
}

class _EmailVerificationScreenState extends State<EmailVerificationScreen> {
  final _controllers = List.generate(6, (_) => TextEditingController());
  final _focusNodes = List.generate(6, (_) => FocusNode());

  bool _isVerifying = false;
  bool _isResending = false;
  int _cooldownSeconds = 0;
  Timer? _timer;
  String? _errorMessage;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (_focusNodes.isNotEmpty) {
        _focusNodes.first.requestFocus();
      }
    });
  }

  @override
  void dispose() {
    for (final controller in _controllers) {
      controller.dispose();
    }
    for (final node in _focusNodes) {
      node.dispose();
    }
    _timer?.cancel();
    super.dispose();
  }

  String get _otp => _controllers.map((c) => c.text.trim()).join();

  void _startCooldown([int seconds = 60]) {
    _timer?.cancel();
    setState(() => _cooldownSeconds = seconds);
    _timer = Timer.periodic(Duration(seconds: 1), (timer) {
      if (!mounted) {
        timer.cancel();
        return;
      }
      if (_cooldownSeconds <= 1) {
        timer.cancel();
        setState(() => _cooldownSeconds = 0);
      } else {
        setState(() => _cooldownSeconds -= 1);
      }
    });
  }

  Future<void> _verify() async {
    if (_otp.length != 6) {
      setState(() => _errorMessage = 'يرجى إدخال رمز التحقق كاملاً');
      return;
    }

    setState(() {
      _isVerifying = true;
      _errorMessage = null;
    });

    final result = await AuthService.verifyEmail(widget.email, _otp);

    if (!mounted) return;
    setState(() => _isVerifying = false);

    if (result == 'success') {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('تم التحقق بنجاح، يمكنك تسجيل الدخول الآن'),
        ),
      );
      Navigator.of(context).pop(true);
      return;
    }

    setState(() => _errorMessage = result);
  }

  Future<void> _resend() async {
    if (_cooldownSeconds > 0 || _isResending) return;

    setState(() {
      _isResending = true;
      _errorMessage = null;
    });

    final result = await AuthService.resendVerificationCode(widget.email);

    if (!mounted) return;
    setState(() => _isResending = false);

    if (result == 'success') {
      _startCooldown();
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('تم إعادة إرسال رمز التحقق')),
      );
      return;
    }

    setState(() => _errorMessage = result);
  }

  Widget _otpCell(int index) {
    return SizedBox(
      width: 48,
      height: 58,
      child: TextField(
        controller: _controllers[index],
        focusNode: _focusNodes[index],
        keyboardType: TextInputType.number,
        textAlign: TextAlign.center,
        textAlignVertical: TextAlignVertical.center,
        textInputAction: TextInputAction.next,
        strutStyle: StrutStyle(
          fontSize: 22,
          height: 1.15,
          forceStrutHeight: true,
        ),
        maxLength: 1,
        style: TextStyle(
          fontSize: 22,
          height: 1.0,
          fontWeight: FontWeight.bold,
          color: Theme.of(context).colorScheme.onSurface,
        ),
        decoration: InputDecoration(
          isDense: true,
          contentPadding: EdgeInsets.symmetric(vertical: 16),
          alignLabelWithHint: true,
          constraints: BoxConstraints(minHeight: 58),
          counterText: '',
          filled: true,
          fillColor: Theme.of(context).colorScheme.surface,
          border: OutlineInputBorder(
            borderRadius: BorderRadius.circular(AppRadius.md),
            borderSide: BorderSide(color: Theme.of(context).dividerColor),
          ),
          enabledBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(AppRadius.md),
            borderSide: BorderSide(color: Theme.of(context).dividerColor),
          ),
          focusedBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(AppRadius.md),
            borderSide: BorderSide(color: AppColors.primary),
          ),
        ),
        onChanged: (value) {
          if (value.isNotEmpty && index < _focusNodes.length - 1) {
            _focusNodes[index + 1].requestFocus();
          } else if (value.isEmpty && index > 0) {
            _focusNodes[index - 1].requestFocus();
          }
        },
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Theme.of(context).scaffoldBackgroundColor,
      appBar: AppBar(
        title: Text('تأكيد البريد الإلكتروني'),
        backgroundColor: Colors.transparent,
        elevation: 0,
      ),
      body: SafeArea(
        child: Padding(
          padding: EdgeInsets.all(AppSpacing.xl),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Text(
                'أدخل رمز التحقق',
                style: TextStyle(
                  fontSize: 24,
                  fontWeight: FontWeight.bold,
                  color: Theme.of(context).colorScheme.onSurface,
                ),
              ),
              SizedBox(height: AppSpacing.xs),
              Text(
                'أرسلنا رمزاً مكوناً من 6 أرقام إلى\n${widget.email}',
                style: TextStyle(
                  fontSize: 14,
                  color: Theme.of(context).colorScheme.onSurfaceVariant,
                ),
                textAlign: TextAlign.right,
              ),
              SizedBox(height: AppSpacing.xl),
              LayoutBuilder(
                builder: (context, constraints) {
                  const double minSpacing = 4.0;
                  const double maxSpacing = 12.0;
                  final cellWidth =
                      ((constraints.maxWidth - (5 * minSpacing)) / 6).clamp(
                        42.0,
                        48.0,
                      );
                  final double spacing = ((constraints.maxWidth - (6 * cellWidth)) / 5)
                      .clamp(0.0, maxSpacing);
                  return Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: List.generate(6, (index) {
                      return Padding(
                        padding: EdgeInsetsDirectional.only(
                          end: index == 5 ? 0 : spacing,
                        ),
                        child: SizedBox(
                          width: cellWidth,
                          child: _otpCell(index),
                        ),
                      );
                    }),
                  );
                },
              ),
              if (_errorMessage != null) ...[
                SizedBox(height: AppSpacing.lg),
                Container(
                  padding: EdgeInsets.all(AppSpacing.md),
                  decoration: BoxDecoration(
                    color: AppColors.error.withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(AppRadius.md),
                    border: Border.all(
                      color: AppColors.error.withValues(alpha: 0.3),
                    ),
                  ),
                  child: Text(
                    _errorMessage!,
                    style: TextStyle(
                      color: AppColors.error,
                      fontSize: 14,
                    ),
                  ),
                ),
              ],
              SizedBox(height: AppSpacing.xl),
              AppButton(
                text: 'تأكيد الرمز',
                onPressed: _verify,
                isLoading: _isVerifying,
              ),
              SizedBox(height: AppSpacing.md),
              TextButton(
                onPressed: (_isResending || _cooldownSeconds > 0)
                    ? null
                    : _resend,
                child: _isResending
                    ? SizedBox(
                        width: 20,
                        height: 20,
                        child: CircularProgressIndicator(strokeWidth: 2),
                      )
                    : Text(
                        _cooldownSeconds > 0
                            ? 'إعادة الإرسال خلال $_cooldownSeconds ثانية'
                            : 'إعادة إرسال الرمز',
                      ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}



