<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
            Mail::raw($body, function ($message) use ($to, $subject) {
                $message->to($to)->subject($subject);
            });
        } catch (\Throwable $e) {
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

