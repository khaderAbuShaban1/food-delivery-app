<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\CustomerEmailVerificationCodeMail;
use App\Models\User;
use App\Services\SystemSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    private const EMAIL_VERIFICATION_TTL_MINUTES = 10;

    private function generateOtpCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    private function userEmailVerifyResendKey(string $email, string $ip): string
    {
        return 'user-email-verify-resend:' . Str::lower(trim($email)) . '|' . $ip;
    }

    private function userEmailVerifyAttemptKey(int $userId, string $ip): string
    {
        return 'user-email-verify-attempt:' . $userId . '|' . $ip;
    }

    public function register(Request $request): JsonResponse
    {
        /** @var SystemSettingsService $settings */
        $settings = app(SystemSettingsService::class);
        if (!(bool) $settings->get('platform', 'platform_open', true)) {
            return response()->json([
                'success' => false,
                'message' => 'Platform is currently closed',
            ], 503);
        }

        if (!(bool) $settings->get('platform', 'registration_enabled', true)) {
            return response()->json([
                'success' => false,
                'message' => 'Registration is currently disabled',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:3|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|min:8|unique:users,phone',
            'password' => 'required|string|min:6|confirmed',
        ], [
            'name.required' => 'الاسم مطلوب',
            'name.string' => 'الاسم يجب أن يكون نصاً',
            'name.min' => 'الاسم يجب أن يكون 3 أحرف على الأقل',
            'email.required' => 'البريد الإلكتروني مطلوب',
            'email.email' => 'يرجى إدخال بريد إلكتروني صحيح',
            'email.unique' => 'البريد الإلكتروني مستخدم من قبل',
            'phone.required' => 'رقم الهاتف مطلوب',
            'phone.string' => 'رقم الهاتف يجب أن يكون نصاً',
            'phone.min' => 'رقم الهاتف يجب أن يكون 8 أرقام على الأقل',
            'phone.unique' => 'رقم الهاتف مستخدم من قبل',
            'password.required' => 'كلمة المرور مطلوبة',
            'password.min' => 'كلمة المرور يجب أن تكون 6 أحرف على الأقل',
            'password.confirmed' => 'كلمات المرور غير متطابقة',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
        ]);

        $code = $this->generateOtpCode();
        $expiresAt = now()->addMinutes(self::EMAIL_VERIFICATION_TTL_MINUTES);
        $user->forceFill([
            'email_verified_at' => null,
            'email_verification_code_hash' => Hash::make($code),
            'email_verification_expires_at' => $expiresAt,
            'email_verification_last_sent_at' => now(),
        ])->save();

        try {
            Mail::to($user->email)->send(new CustomerEmailVerificationCodeMail(
                code: $code,
                expiresAt: $expiresAt,
                customerName: (string) $user->name,
            ));
            Log::info('customer_email_verification_sent', [
                'user_id' => $user->id,
                'email' => $user->email,
                'expires_at' => $expiresAt->toDateTimeString(),
            ]);
        } catch (\Throwable $e) {
            Log::error('customer_email_verification_send_failed', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء الحساب بنجاح. يرجى التحقق من البريد الإلكتروني أولاً',
            'needs_email_verification' => true,
            'email' => $user->email,
        ], 201);
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
        $user = User::where('email', $email)->first();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
            ], 404);
        }

        if (!empty($user->email_verified_at)) {
            return response()->json([
                'success' => true,
                'message' => 'تم التحقق من البريد الإلكتروني مسبقاً',
            ]);
        }

        $attemptKey = $this->userEmailVerifyAttemptKey((int) $user->id, (string) $request->ip());
        if (RateLimiter::tooManyAttempts($attemptKey, 10)) {
            return response()->json([
                'success' => false,
                'message' => 'تم تجاوز عدد المحاولات. يرجى المحاولة لاحقاً',
            ], 429);
        }

        if (empty($user->email_verification_code_hash) || empty($user->email_verification_expires_at)) {
            RateLimiter::hit($attemptKey, 600);
            return response()->json([
                'success' => false,
                'message' => 'يرجى طلب إرسال رمز جديد',
            ], 400);
        }

        if (now()->greaterThan($user->email_verification_expires_at)) {
            RateLimiter::hit($attemptKey, 600);
            return response()->json([
                'success' => false,
                'message' => 'انتهت صلاحية الرمز. يرجى طلب إرسال رمز جديد',
            ], 400);
        }

        if (!Hash::check((string) $request->code, (string) $user->email_verification_code_hash)) {
            RateLimiter::hit($attemptKey, 600);
            return response()->json([
                'success' => false,
                'message' => 'رمز التحقق غير صحيح',
            ], 400);
        }

        $user->forceFill([
            'email_verified_at' => now(),
            'email_verification_code_hash' => null,
            'email_verification_expires_at' => null,
        ])->save();

        RateLimiter::clear($attemptKey);

        return response()->json([
            'success' => true,
            'message' => 'تم التحقق من البريد الإلكتروني بنجاح',
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
        $user = User::where('email', $email)->first();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
            ], 404);
        }

        if (!empty($user->email_verified_at)) {
            return response()->json([
                'success' => true,
                'message' => 'تم التحقق من البريد الإلكتروني مسبقاً',
            ]);
        }

        $resendKey = $this->userEmailVerifyResendKey($email, (string) $request->ip());
        if (RateLimiter::tooManyAttempts($resendKey, 5)) {
            return response()->json([
                'success' => false,
                'message' => 'يرجى الانتظار قبل إعادة الإرسال',
            ], 429);
        }

        if (!empty($user->email_verification_last_sent_at) && now()->diffInSeconds($user->email_verification_last_sent_at) < 60) {
            return response()->json([
                'success' => false,
                'message' => 'يرجى الانتظار قبل إعادة الإرسال',
            ], 429);
        }

        $code = $this->generateOtpCode();
        $expiresAt = now()->addMinutes(self::EMAIL_VERIFICATION_TTL_MINUTES);
        $user->forceFill([
            'email_verification_code_hash' => Hash::make($code),
            'email_verification_expires_at' => $expiresAt,
            'email_verification_last_sent_at' => now(),
        ])->save();

        try {
            Mail::to($user->email)->send(new CustomerEmailVerificationCodeMail(
                code: $code,
                expiresAt: $expiresAt,
                customerName: (string) $user->name,
            ));
        } catch (\Throwable $e) {
            Log::error('customer_email_verification_resend_failed', [
                'user_id' => $user->id,
                'email' => $user->email,
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

    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ], [
            'email.required' => 'البريد الإلكتروني مطلوب',
            'email.email' => 'يرجى إدخال بريد إلكتروني صحيح',
            'password.required' => 'كلمة المرور مطلوبة',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 401);
        }

        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid password',
            ], 401);
        }

        if (empty($user->email_verified_at)) {
            return response()->json([
                'success' => false,
                'message' => 'يرجى التحقق من البريد الإلكتروني أولاً',
                'status' => 'email_not_verified',
            ], 403);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل الدخول بنجاح',
            'token' => $token,
            'data' => $user,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل الخروج بنجاح',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $request->user(),
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:3|max:255',
            'phone' => 'sometimes|string|min:8',
        ], [
            'name.required' => 'الاسم مطلوب',
            'name.string' => 'الاسم يجب أن يكون نصاً',
            'name.min' => 'الاسم يجب أن يكون 3 أحرف على الأقل',
            'phone.string' => 'رقم الهاتف يجب أن يكون نصاً',
            'phone.min' => 'رقم الهاتف يجب أن يكون 8 أرقام على الأقل',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user->update([
            'name' => $request->name,
            'phone' => $request->phone ?? $user->phone,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الملف الشخصي بنجاح',
            'data' => $user,
        ]);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6|confirmed',
        ], [
            'current_password.required' => 'كلمة المرور الحالية مطلوبة',
            'new_password.required' => 'كلمة المرور الجديدة مطلوبة',
            'new_password.min' => 'كلمة المرور يجب أن تكون 6 أحرف على الأقل',
            'new_password.confirmed' => 'كلمات المرور غير متطابقة',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect',
            ], 400);
        }

        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم تغيير كلمة المرور بنجاح',
        ]);
    }

    public function uploadProfileImage(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$request->hasFile('image')) {
            return response()->json([
                'success' => false,
                'message' => 'لم يتم تحديد صورة',
            ], 400);
        }

        $image = $request->file('image');

        $validator = Validator::make(['image' => $image], [
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ], [
            'image.required' => 'الصورة مطلوبة',
            'image.image' => 'يجب أن تكون ملف صورة',
            'image.max' => 'حجم الصورة يجب أن يكون أقل من 2 ميجابايت',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $oldImage = $user->profile_image ?? null;

        $filename = 'profile_' . $user->id . '_' . time() . '.' . $image->getClientOriginalExtension();
        $destinationPath = public_path('storage/profile-images/' . $filename);

        copy($image->getRealPath(), $destinationPath);
        unlink($image->getRealPath());

        $imageUrl = asset('storage/profile-images/' . $filename);
        $user->update(['profile_image' => $imageUrl]);

        if ($oldImage) {
            $oldPath = public_path(str_replace(asset('/'), '', $oldImage));
            if (file_exists($oldPath)) {
                unlink($oldPath);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'تم رفع الصورة بنجاح',
            'data' => [
                'profile_image' => $imageUrl,
            ],
        ]);
    }
}

