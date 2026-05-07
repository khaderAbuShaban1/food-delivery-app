<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class DriverAuthController extends Controller
{
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
            'profile_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ], [
            'name.required' => 'الاسم الكامل مطلوب',
            'name.min' => 'الاسم يجب أن يكون 3 أحرف على الأقل',
            'national_id.required' => 'رقم الهوية مطلوب',
            'national_id.unique' => 'رقم الهوية مستخدم من قبل',
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
            'profile_image.image' => 'الصورة الشخصية يجب أن تكون ملف صورة',
            'profile_image.max' => 'حجم الصورة يجب أن يكون أقل من 2 ميجابايت',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        $profileImagePath = null;
        if ($request->hasFile('profile_image')) {
            $profileImagePath = $request->file('profile_image')->store('driver-profiles', 'public');
        }

        Driver::create([
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
            'approval_status' => 'pending',
            'is_available' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال طلبك للإدارة وبانتظار الموافقة',
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

        if (!$driver || !Hash::check($request->password, $driver->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials',
            ], 401);
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
}
