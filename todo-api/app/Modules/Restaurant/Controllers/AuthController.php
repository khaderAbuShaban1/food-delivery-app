<?php

namespace App\Modules\Restaurant\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Restaurant\Services\ApiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function __construct(
        protected ApiService $api
    ) {}

    public function showLoginForm(): View
    {
        return view('restaurant::login');
    }

    public function login(Request $request): RedirectResponse
    {
        $response = $this->api->post('/login', [
            'email' => $request->email,
            'password' => $request->password,
        ]);

        if ($response && ($response['success'] ?? false)) {
            $token = $response['data']['token'] ?? null;
            if ($token) {
                $this->api->setToken($token);
                session()->put('user', $response['data']['user']);
                return redirect()->route('restaurant.dashboard');
            }
        }

        $error = $response['message'] ?? 'Login failed';
        return back()->with('error', $error)->withInput($request->only('email'));
    }

    public function logout(): RedirectResponse
    {
        $this->api->post('/logout');
        $this->api->clearToken();
        session()->forget('user');
        
        return redirect()->route('restaurant.login');
    }
}