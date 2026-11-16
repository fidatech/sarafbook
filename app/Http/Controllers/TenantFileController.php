<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * Serves the files a Sarafi uploaded (logo, transaction and expense attachments) from its own
 * folder, at the same addresses the single-Sarafi system used, so stored paths keep working.
 */
class TenantFileController extends Controller
{
    /** Logo and other images: open to everyone, because the sign-in page shows the logo. */
    public function open(Request $request, string $path): Response
    {
        $file = $this->locate($request->route('folder'), $path);
        if (! $file) {
            // no logo uploaded yet: the SarafBook mark
            return response()->file(public_path('img/logo.svg'), ['Content-Type' => 'image/svg+xml', 'Cache-Control' => 'public, max-age=300']);
        }

        return response()->file($file, ['Cache-Control' => 'public, max-age=86400']);
    }

    /** Attachments: only for signed-in users of this Sarafi. */
    public function show(Request $request, string $path): Response
    {
        $file = $this->locate($request->route('folder'), $path);
        abort_unless($file, 404);

        return response()->file($file, ['Cache-Control' => 'private, max-age=3600', 'X-Content-Type-Options' => 'nosniff']);
    }

    /** Files of the "public" disk (asset('storage/...')). */
    public function disk(string $path): Response
    {
        abort_if(str_contains($path, '..'), 404);
        abort_unless(Storage::disk('public')->exists($path), 404);

        return Storage::disk('public')->response($path, null, ['Cache-Control' => 'private, max-age=3600', 'X-Content-Type-Options' => 'nosniff']);
    }

    /** Platform support arrives here from the platform panel (signed link, valid for two minutes). */
    public function enter(Request $request)
    {
        abort_unless(tenant() && (int) $request->query('tenant') === (int) tenant('id'), 403);

        $user = User::query()->where('is_active', true)->where('account_type', 'admin')->orderBy('id')->first()
            ?? User::query()->where('is_active', true)->orderBy('id')->firstOrFail();

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put('platform_support', (string) $request->query('by', 'platform'));

        return redirect('/admin/dashboard');
    }

    protected function locate(?string $folder, string $path): ?string
    {
        if ($path === '' || str_contains($path, '..') || str_contains($path, "\0")) {
            return null;
        }
        $file = tenant_public_path(trim((string) $folder, '/').'/'.$path);

        return is_file($file) ? $file : null;
    }
}
