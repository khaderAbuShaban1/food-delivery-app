import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../../../core/services/auth_service.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/widgets/widgets.dart';

class EmailVerificationScreen extends StatefulWidget {
  final String email;
  final bool autoResendOnOpen;

  const EmailVerificationScreen({
    super.key,
    required this.email,
    this.autoResendOnOpen = false,
  });

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
      if (widget.autoResendOnOpen) {
        _resend();
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

  void _setCellValue(int index, String value) {
    _controllers[index].value = TextEditingValue(
      text: value,
      selection: TextSelection.collapsed(offset: value.length),
    );
  }

  void _handleOtpChanged(int index, String value) {
    final digits = value.replaceAll(RegExp(r'\D'), '');

    if (digits.length > 1) {
      final end = (index + digits.length).clamp(0, _controllers.length);
      for (var i = index; i < end; i++) {
        _setCellValue(i, digits[i - index]);
      }
      _focusNodes[(end - 1).clamp(0, _focusNodes.length - 1)].requestFocus();
      return;
    }

    if (digits.isEmpty) {
      _setCellValue(index, '');
      if (index > 0) {
        _focusNodes[index - 1].requestFocus();
      }
      return;
    }

    if (_controllers[index].text != digits) {
      _setCellValue(index, digits);
    }

    if (index < _focusNodes.length - 1) {
      _focusNodes[index + 1].requestFocus();
    } else {
      _focusNodes[index].unfocus();
    }
  }

  KeyEventResult _handleOtpKeyEvent(int index, KeyEvent event) {
    if (event is! KeyDownEvent ||
        event.logicalKey != LogicalKeyboardKey.backspace ||
        _controllers[index].text.isNotEmpty ||
        index == 0) {
      return KeyEventResult.ignored;
    }

    _setCellValue(index - 1, '');
    _focusNodes[index - 1].requestFocus();
    return KeyEventResult.handled;
  }

  void _startCooldown([int seconds = 60]) {
    _timer?.cancel();
    setState(() => _cooldownSeconds = seconds);
    _timer = Timer.periodic(const Duration(seconds: 1), (timer) {
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
        const SnackBar(
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
        const SnackBar(content: Text('تم إعادة إرسال رمز التحقق')),
      );
      return;
    }

    setState(() => _errorMessage = result);
  }

  Widget _otpCell(int index) {
    return SizedBox(
      width: 48,
      height: 58,
      child: Directionality(
        textDirection: TextDirection.ltr,
        child: Focus(
          onKeyEvent: (_, event) => _handleOtpKeyEvent(index, event),
          child: TextField(
            controller: _controllers[index],
            focusNode: _focusNodes[index],
            keyboardType: TextInputType.number,
            inputFormatters: [
              FilteringTextInputFormatter.digitsOnly,
              LengthLimitingTextInputFormatter(6),
            ],
            textDirection: TextDirection.ltr,
            textAlign: TextAlign.center,
            textAlignVertical: TextAlignVertical.center,
            textInputAction: TextInputAction.next,
            strutStyle: const StrutStyle(
              fontSize: 22,
              height: 1.15,
              forceStrutHeight: true,
            ),
            maxLength: 6,
            style: const TextStyle(
              fontSize: 22,
              height: 1.0,
              fontWeight: FontWeight.bold,
              color: AppColors.textPrimary,
            ),
            decoration: InputDecoration(
              isDense: true,
              contentPadding: const EdgeInsets.symmetric(vertical: 16),
              alignLabelWithHint: true,
              constraints: const BoxConstraints(minHeight: 58),
              counterText: '',
              filled: true,
              fillColor: AppColors.surface,
              border: OutlineInputBorder(
                borderRadius: BorderRadius.circular(AppRadius.md),
                borderSide: const BorderSide(color: AppColors.border),
              ),
              enabledBorder: OutlineInputBorder(
                borderRadius: BorderRadius.circular(AppRadius.md),
                borderSide: const BorderSide(color: AppColors.border),
              ),
              focusedBorder: OutlineInputBorder(
                borderRadius: BorderRadius.circular(AppRadius.md),
                borderSide: const BorderSide(color: AppColors.primary),
              ),
            ),
            onChanged: (value) => _handleOtpChanged(index, value),
          ),
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        title: const Text('تأكيد البريد الإلكتروني'),
        backgroundColor: Colors.transparent,
        elevation: 0,
      ),
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(AppSpacing.xl),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              const Text(
                'أدخل رمز التحقق',
                style: TextStyle(
                  fontSize: 24,
                  fontWeight: FontWeight.bold,
                  color: AppColors.textPrimary,
                ),
              ),
              const SizedBox(height: AppSpacing.xs),
              Text(
                'أرسلنا رمزاً مكوناً من 6 أرقام إلى\n${widget.email}',
                style: const TextStyle(
                  fontSize: 14,
                  color: AppColors.textSecondary,
                ),
                textAlign: TextAlign.right,
              ),
              const SizedBox(height: AppSpacing.xl),
              LayoutBuilder(
                builder: (context, constraints) {
                  const minSpacing = 4.0;
                  const maxSpacing = 12.0;
                  final cellWidth =
                      ((constraints.maxWidth - (5 * minSpacing)) / 6).clamp(
                        42.0,
                        48.0,
                      );
                  final spacing = ((constraints.maxWidth - (6 * cellWidth)) / 5)
                      .clamp(0.0, maxSpacing);
                  return Directionality(
                    textDirection: TextDirection.ltr,
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.center,
                      textDirection: TextDirection.ltr,
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
                    ),
                  );
                },
              ),
              if (_errorMessage != null) ...[
                const SizedBox(height: AppSpacing.lg),
                Container(
                  padding: const EdgeInsets.all(AppSpacing.md),
                  decoration: BoxDecoration(
                    color: AppColors.error.withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(AppRadius.md),
                    border: Border.all(
                      color: AppColors.error.withValues(alpha: 0.3),
                    ),
                  ),
                  child: Text(
                    _errorMessage!,
                    style: const TextStyle(
                      color: AppColors.error,
                      fontSize: 14,
                    ),
                  ),
                ),
              ],
              const SizedBox(height: AppSpacing.xl),
              AppButton(
                text: 'تأكيد الرمز',
                onPressed: _verify,
                isLoading: _isVerifying,
              ),
              const SizedBox(height: AppSpacing.md),
              TextButton(
                onPressed: (_isResending || _cooldownSeconds > 0)
                    ? null
                    : _resend,
                child: _isResending
                    ? const SizedBox(
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
