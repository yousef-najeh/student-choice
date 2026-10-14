<?php

use Illuminate\Support\Facades\Route;

// The app's pages — plain Blade + vanilla JS calling the /api/* routes, no
// build step. See resources/views/app/* and docs/technical-flows.md §8.
// Auth is enforced client-side (requireAuth()/requireAdmin() in public/js/api.js)
// since these pages hold their Sanctum token in localStorage, not a cookie the
// server can see on a normal page load.

Route::get('/', fn () => redirect('/login'));

Route::get('/login', fn () => view('app.login'));
Route::get('/dashboard', fn () => view('app.dashboard'));
Route::get('/profile', fn () => view('app.profile'));
Route::get('/majors', fn () => view('app.majors'));
Route::get('/majors/{id}', fn (string $id) => view('app.major', ['id' => $id]));
Route::get('/admin', fn () => view('app.admin'));
