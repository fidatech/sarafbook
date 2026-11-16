<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

Route::middleware(['tenant', 'set_locale'])->group(function () {
    Route::post('/logout-ping', function () {
        if (Auth::check()) {
            Cache::forget('user-online-' . Auth::id());
            Cache::forget('user-last-seen-' . Auth::id());
        }
        return response()->noContent();
    });

    // web.php
    Route::post('/user/ping', function () {
        if (auth()->check()) {
            Cache::forever('user-online-' . auth()->id(), true);
            Cache::put('user-last-seen-' . auth()->id(), now(), now()->addHours(12));
        }
        return response()->noContent();
    })->name('user.ping');


    // Locale switch route
    Route::get('/lang/{locale}', function ($locale) {
        $available = array_keys(config('app.locales'));
        if (in_array($locale, $available)) {
            session(['locale' => $locale]);
        }
        return redirect()->back();
    })->name('lang.switch');

    Route::middleware('auth')->group(function () {
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    });

    require __DIR__ . '/admin.php';
});

Route::middleware(['tenant', 'set_locale'])->group(function () {
    require __DIR__ . '/auth.php';

    // Platform support signs in as the Sarafi's administrator through a short-lived signed link.
    Route::get('/_platform/enter', [\App\Http\Controllers\TenantFileController::class, 'enter'])
        ->middleware('signed')->name('tenant.enter');

    // Uploaded files of the Sarafi keep the addresses they always had. The web server answers
    // for real files in public/; anything else under these paths comes from the Sarafi's own folder.
    Route::get('/images/{path}', [\App\Http\Controllers\TenantFileController::class, 'open'])
        ->where('path', '.*')->defaults('folder', 'images')->name('tenant.file.image');
    Route::middleware('auth')->group(function () {
        Route::get('/attachments/{path}', [\App\Http\Controllers\TenantFileController::class, 'show'])
            ->where('path', '.*')->defaults('folder', 'attachments');
        Route::get('/assets/attachments/{path}', [\App\Http\Controllers\TenantFileController::class, 'show'])
            ->where('path', '.*')->defaults('folder', 'assets/attachments');
        Route::get('/storage/{path}', [\App\Http\Controllers\TenantFileController::class, 'disk'])
            ->where('path', '.*');
    });
});

