<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Central\PlatformAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function create()
    {
        return view('platform.login');
    }

    public function store(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::guard('platform')->attempt($credentials + ['is_active' => true], $request->boolean('remember'))) {
            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        $request->session()->regenerate();
        PlatformAudit::record('signed_in');

        return redirect()->intended(route('platform.dashboard'));
    }

    public function destroy(Request $request)
    {
        Auth::guard('platform')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('platform.login');
    }

    public function locale(Request $request, string $locale)
    {
        if (array_key_exists($locale, config('app.locales'))) {
            $request->session()->put('locale', $locale);
        }

        return back();
    }
}
