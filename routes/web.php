<?php

use Illuminate\Support\Facades\Route;

// This branch contains only the auth + database layer — the frontend views
// that used to be served here (resources/views/app/*) were removed.
Route::get('/', fn () => redirect('/up'));
