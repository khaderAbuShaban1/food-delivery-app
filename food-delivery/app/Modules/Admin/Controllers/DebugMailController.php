<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class DebugMailController extends Controller
{
    public function send(Request $request): JsonResponse
    {
        $request->validate([
            'to' => 'nullable|email',
        ]);

        $to = (string) ($request->input('to') ?: config('mail.from.address'));
        $subject = 'Mail Test - ' . (string) config('app.name', 'food-delivery-app');
        $body = "This is a test email from Laravel at " . now()->format('Y-m-d H:i:s');

        try {
            Log::info('admin_debug_mail_test_attempt', [
                'to' => $to,
                'mailer' => config('mail.default'),
                'host' => config('mail.mailers.smtp.host'),
                'port' => config('mail.mailers.smtp.port'),
                'encryption' => config('mail.mailers.smtp.encryption'),
                'username_set' => filled(config('mail.mailers.smtp.username')),
                'password_set' => filled(config('mail.mailers.smtp.password')),
                'from' => config('mail.from.address'),
            ]);

            Mail::raw($body, function ($message) use ($to, $subject) {
                $message->to($to)->subject($subject);
            });
            Log::info('admin_debug_mail_test_sent', ['to' => $to]);
        } catch (\Throwable $e) {
            Log::error('admin_debug_mail_test_failed', [
                'to' => $to,
                'exception' => get_class($e),
                'code' => $e->getCode(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'MAIL_FAILED',
                'error' => $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'MAIL_SENT',
            'to' => $to,
        ]);
    }
}
