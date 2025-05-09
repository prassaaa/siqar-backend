<?php

namespace App\Filament\Pages\Auth;

use Filament\Pages\Auth\Login as BaseLogin;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Facades\Filament;

class CustomLogin extends BaseLogin
{
    public function authenticate(): LoginResponse
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            throw ValidationException::withMessages([
                'email' => __('filament-panels::pages/auth/login.messages.throttled', [
                    'seconds' => $exception->secondsUntilAvailable,
                    'minutes' => ceil($exception->secondsUntilAvailable / 60),
                ]),
            ]);
        }

        $data = $this->form->getState();

        if (! Auth::attempt([
            'email' => $data['email'],
            'password' => $data['password'],
        ], $data['remember'] ?? false)) {
            throw ValidationException::withMessages([
                'email' => __('filament-panels::pages/auth/login.messages.failed'),
            ]);
        }

        // Verifikasi user adalah admin
        $user = Auth::user();
        if (!$user || $user->peran !== 'admin' || $user->status !== 'aktif') {
            Auth::logout();
            
            throw ValidationException::withMessages([
                'email' => __('Hanya admin yang dapat mengakses panel ini.'),
            ]);
        }

        // Update terakhir login
        $user->terakhir_login = now();
        $user->save();

        session()->regenerate();
        
        return app(LoginResponse::class);
    }
}