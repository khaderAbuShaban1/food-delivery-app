@php
    /** @var string $appName */
    /** @var string $code */
    /** @var \DateTimeInterface $expiresAt */
    /** @var string $driverName */
@endphp
<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>رمز التحقق</title>
</head>
<body style="margin:0;padding:0;background:#f6f7fb;font-family:Arial, Helvetica, sans-serif;">
    <div style="max-width:600px;margin:0 auto;padding:24px;">
        <div style="background:#ffffff;border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;">
            <div style="padding:18px 20px;border-bottom:1px solid #e5e7eb;background:#ffffff;">
                <div style="font-size:16px;font-weight:700;color:#111827;">{{ $appName }}</div>
                <div style="font-size:12px;color:#6b7280;margin-top:4px;">تأكيد البريد الإلكتروني لحساب السائق</div>
            </div>
            <div style="padding:20px;">
                <p style="margin:0 0 12px;color:#111827;line-height:1.8;">
                    مرحباً {{ $driverName }}،
                </p>
                <p style="margin:0 0 16px;color:#374151;line-height:1.9;">
                    رمز التحقق الخاص بك هو:
                </p>
                <div style="text-align:center;margin:18px 0;">
                    <div style="display:inline-block;padding:14px 22px;border-radius:12px;background:#f3f4f6;border:1px solid #e5e7eb;font-size:26px;letter-spacing:6px;font-weight:800;color:#111827;">
                        {{ $code }}
                    </div>
                </div>
                <p style="margin:0;color:#6b7280;line-height:1.9;font-size:13px;">
                    ينتهي هذا الرمز في {{ \Illuminate\Support\Carbon::instance($expiresAt)->format('Y-m-d H:i') }}.
                </p>
                <p style="margin:14px 0 0;color:#9ca3af;line-height:1.9;font-size:12px;">
                    إذا لم تقم بطلب هذا الرمز، يمكنك تجاهل هذه الرسالة.
                </p>
            </div>
        </div>
        <div style="text-align:center;color:#9ca3af;font-size:12px;margin-top:12px;">
            {{ $appName }} - جميع الحقوق محفوظة
        </div>
    </div>
</body>
</html>

