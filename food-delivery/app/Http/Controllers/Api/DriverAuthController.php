<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\DriverEmailVerificationCodeMail;
use App\Models\Driver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class DriverAuthController extends Controller
{
    private const EMAIL_VERIFICATION_TTL_MINUTES = 10;

    private function generateOtpCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    private function driverEmailVerifyResendKey(string $email, string $ip): string
    {
        return 'driver-email-verify-resend:' . Str::lower(trim($email)) . '|' . $ip;
    }

    private function driverEmailVerifyAttemptKey(int $driverId, string $ip): string
    {
        return 'driver-email-verify-attempt:' . $driverId . '|' . $ip;
    }

    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:3|max:255',
            'national_id' => 'required|string|min:5|max:64|unique:drivers,national_id',
            'phone' => 'required|string|min:8|max:32|unique:drivers,phone',
            'email' => 'required|email|max:255|unique:drivers,email',
            'password' => 'required|string|min:6|confirmed',
            'vehicle_type' => ['required', Rule::in(['bicycle', 'electric_bicycle', 'motorcycle', 'car'])],
            'vehicle_plate_number' => 'nullable|string|max:32',
            'city' => 'nullable|string|max:120',
            'emergency_contact_number' => 'nullable|string|max:32',
            'profile_image' => 'required|image|mimes:jpg,jpeg,png|max:4096',
            'national_id_image' => 'required|image|mimes:jpg,jpeg,png|max:4096',
            'vehicle_image' => 'required|image|mimes:jpg,jpeg,png|max:4096',
        ], [
            'name.required' => 'الاسم الكامل مطلوب',
            'name.min' => 'الاسم يجب أن يكون 3 أحرف على الأقل',
            'national_id.required' => 'رقم الهوية مطلوب',
            'national_id.unique' => 'رقم الهوية مستخدم مسبقاً',
            'phone.required' => 'رقم الهاتف مطلوب',
            'phone.min' => 'رقم الهاتف يجب أن يكون 8 أرقام على الأقل',
            'phone.unique' => 'رقم الهاتف مستخدم من قبل',
            'email.required' => 'البريد الإلكتروني مطلوب',
            'email.email' => 'يرجى إدخال بريد إلكتروني صحيح',
            'email.unique' => 'البريد الإلكتروني مستخدم من قبل',
            'password.required' => 'كلمة المرور مطلوبة',
            'password.min' => 'كلمة المرور يجب أن تكون 6 أحرف على الأقل',
            'password.confirmed' => 'كلمتا المرور غير متطابقتين',
            'vehicle_type.required' => 'نوع المركبة مطلوب',
            'vehicle_type.in' => 'نوع المركبة غير صالح',
            'profile_image.required' => 'الصورة الشخصية مطلوبة',
            'national_id_image.required' => 'صورة الهوية مطلوبة',
            'vehicle_image.required' => 'صورة المركبة مطلوبة',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        $profileImagePath = $request->file('profile_image')->store('driver-profiles', 'public');
        $nationalIdImagePath = $request->file('national_id_image')->store('driver-documents/national-ids', 'public');
        $vehicleImagePath = $request->file('vehicle_image')->store('driver-documents/vehicles', 'public');

        $driver = Driver::create([
            'name' => $validated['name'],
            'national_id' => $validated['national_id'],
            'phone' => $validated['phone'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'vehicle_type' => $validated['vehicle_type'],
            'vehicle_plate_number' => $validated['vehicle_plate_number'] ?? null,
            'city' => $validated['city'] ?? null,
            'emergency_contact_number' => $validated['emergency_contact_number'] ?? null,
            'profile_image' => $profileImagePath,
            'national_id_image' => $nationalIdImagePath,
            'vehicle_image' => $vehicleImagePath,
            // Important: don't submit to admin until email is verified.
            'approval_status' => 'draft',
            'is_available' => false,
        ]);

        $code = $this->generateOtpCode();
        $expiresAt = now()->addMinutes(self::EMAIL_VERIFICATION_TTL_MINUTES);
        $driver->forceFill([
            'email_verified_at' => null,
            'email_verification_code_hash' => Hash::make($code),
            'email_verification_expires_at' => $expiresAt,
            'email_verification_last_sent_at' => now(),
        ])->save();

        try {
            Mail::to($driver->email)->send(new DriverEmailVerificationCodeMail(
                code: $code,
                expiresAt: $expiresAt,
                driverName: (string) $driver->name,
            ));
            Log::info('driver_email_verification_sent', [
                'driver_id' => $driver->id,
                'email' => $driver->email,
                'expires_at' => $expiresAt->toDateTimeString(),
            ]);
        } catch (\Throwable $e) {
            Log::error('driver_email_verification_send_failed', [
                'driver_id' => $driver->id,
                'email' => $driver->email,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء الحساب. يرجى التحقق من البريد الإلكتروني أولاً',
            'needs_email_verification' => true,
            'email' => $driver->email,
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $driver = Driver::where('email', $request->email)->first();

        if (! $driver || ! Hash::check($request->password, $driver->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials',
            ], 401);
        }

        if (empty($driver->email_verified_at)) {
            return response()->json([
                'success' => false,
                'message' => 'يرجى التحقق من البريد الإلكتروني أولاً',
                'status' => 'email_not_verified',
            ], 403);
        }

        // Email verified but not submitted yet (should be rare, but keep it explicit).
        if (($driver->approval_status ?? '') === 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'تم التحقق من البريد الإلكتروني. يرجى إرسال طلب التسجيل للإدارة',
                'status' => 'not_submitted',
            ], 403);
        }

        if ($driver->approval_status === 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'تم إرسال طلبك للإدارة وبانتظار الموافقة',
                'status' => 'pending',
            ], 403);
        }

        if ($driver->approval_status === 'rejected') {
            return response()->json([
                'success' => false,
                'message' => 'تم رفض طلب التسجيل، يرجى التواصل مع الإدارة',
                'status' => 'rejected',
            ], 403);
        }

        if (! $driver->isApproved()) {
            return response()->json([
                'success' => false,
                'message' => 'حساب السائق غير مفعل حالياً',
            ], 403);
        }

        $token = $driver->createToken('driver-auth-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Driver logged in successfully',
            'token' => $token,
            'data' => $driver,
        ]);
    }

    public function verifyEmail(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'code' => 'required|string|min:4|max:12',
        ], [
            'email.required' => 'البريد الإلكتروني مطلوب',
            'email.email' => 'يرجى إدخال بريد إلكتروني صحيح',
            'code.required' => 'رمز التحقق مطلوب',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $email = (string) $request->email;
        $driver = Driver::where('email', $email)->first();
        if (! $driver) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
            ], 404);
        }

        if (! empty($driver->email_verified_at)) {
            return response()->json([
                'success' => true,
                'message' => 'تم التحقق من البريد الإلكتروني مسبقاً',
                'status' => $driver->approval_status,
            ]);
        }

        $attemptKey = $this->driverEmailVerifyAttemptKey((int) $driver->id, (string) $request->ip());
        if (RateLimiter::tooManyAttempts($attemptKey, 10)) {
            return response()->json([
                'success' => false,
                'message' => 'تم تجاوز عدد المحاولات. يرجى المحاولة لاحقاً',
            ], 429);
        }

        if (empty($driver->email_verification_code_hash) || empty($driver->email_verification_expires_at)) {
            RateLimiter::hit($attemptKey, 600);
            return response()->json([
                'success' => false,
                'message' => 'يرجى طلب إرسال رمز جديد',
            ], 400);
        }

        if (now()->greaterThan($driver->email_verification_expires_at)) {
            RateLimiter::hit($attemptKey, 600);
            return response()->json([
                'success' => false,
                'message' => 'انتهت صلاحية الرمز. يرجى طلب إرسال رمز جديد',
            ], 400);
        }

        $code = (string) $request->code;
        if (! Hash::check($code, (string) $driver->email_verification_code_hash)) {
            RateLimiter::hit($attemptKey, 600);
            return response()->json([
                'success' => false,
                'message' => 'رمز التحقق غير صحيح',
            ], 400);
        }

        // Email verified: now submit the registration request to admin.
        $driver->forceFill([
            'email_verified_at' => now(),
            'email_verification_code_hash' => null,
            'email_verification_expires_at' => null,
            'approval_status' => $driver->approval_status === 'draft' ? 'pending' : $driver->approval_status,
            'approved_at' => null,
            'rejected_at' => null,
            'is_available' => false,
        ])->save();

        RateLimiter::clear($attemptKey);

        return response()->json([
            'success' => true,
            'message' => 'تم التحقق من البريد الإلكتروني بنجاح. تم إرسال طلبك للإدارة وبانتظار الموافقة',
            'status' => $driver->approval_status,
        ]);
    }

    public function resendEmailVerification(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ], [
            'email.required' => 'البريد الإلكتروني مطلوب',
            'email.email' => 'يرجى إدخال بريد إلكتروني صحيح',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $email = (string) $request->email;
        $driver = Driver::where('email', $email)->first();
        if (! $driver) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
            ], 404);
        }

        if (! empty($driver->email_verified_at)) {
            return response()->json([
                'success' => true,
                'message' => 'تم التحقق من البريد الإلكتروني مسبقاً',
                'status' => $driver->approval_status,
            ]);
        }

        $resendKey = $this->driverEmailVerifyResendKey($email, (string) $request->ip());
        if (RateLimiter::tooManyAttempts($resendKey, 5)) {
            return response()->json([
                'success' => false,
                'message' => 'يرجى الانتظار قبل إعادة الإرسال',
            ], 429);
        }

        if (! empty($driver->email_verification_last_sent_at) && now()->diffInSeconds($driver->email_verification_last_sent_at) < 60) {
            return response()->json([
                'success' => false,
                'message' => 'يرجى الانتظار قبل إعادة الإرسال',
            ], 429);
        }

        $code = $this->generateOtpCode();
        $expiresAt = now()->addMinutes(self::EMAIL_VERIFICATION_TTL_MINUTES);
        $driver->forceFill([
            'email_verification_code_hash' => Hash::make($code),
            'email_verification_expires_at' => $expiresAt,
            'email_verification_last_sent_at' => now(),
        ])->save();

        try {
            Mail::to($driver->email)->send(new DriverEmailVerificationCodeMail(
                code: $code,
                expiresAt: $expiresAt,
                driverName: (string) $driver->name,
            ));
        } catch (\Throwable $e) {
            Log::error('driver_email_verification_resend_failed', [
                'driver_id' => $driver->id,
                'email' => $driver->email,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'تعذر إرسال البريد الإلكتروني حالياً',
            ], 500);
        }

        RateLimiter::hit($resendKey, 600);

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال رمز التحقق إلى بريدك الإلكتروني',
        ]);
    }
}

