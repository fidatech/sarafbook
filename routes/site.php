<?php

use App\Http\Controllers\SiteController;
use Illuminate\Support\Facades\Route;

/*
| "/" on every address. On the platform's own address it is the public website, where a Sarafi
| finds its own sign-in page; on a Sarafi's address it leads to the dashboard or the sign-in page.
*/

Route::get('/', [SiteController::class, 'home'])->name('site.home');
Route::post('/find-sarafi', [SiteController::class, 'find'])->middleware(['central', 'throttle:12,1'])->name('site.find');
