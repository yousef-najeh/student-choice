<?php

use App\Http\Controllers\Api\Admin\CampusController as AdminCampusController;
use App\Http\Controllers\Api\Admin\FacultyController as AdminFacultyController;
use App\Http\Controllers\Api\Admin\InstitutionController as AdminInstitutionController;
use App\Http\Controllers\Api\Admin\MajorController as AdminMajorController;
use App\Http\Controllers\Api\Admin\ScholarshipController as AdminScholarshipController;
use App\Http\Controllers\Api\Admin\StudentController;
use App\Http\Controllers\Api\Admin\SupportTicketController as AdminSupportTicketController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Lookup\CityController;
use App\Http\Controllers\Api\Lookup\DegreeLevelController;
use App\Http\Controllers\Api\Lookup\FieldOfStudyController;
use App\Http\Controllers\Api\Lookup\RegionController;
use App\Http\Controllers\Api\Lookup\TawjihiBranchController;
use App\Http\Controllers\Api\MajorController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ScholarshipController;
use App\Http\Controllers\Api\SupportTicketController;
use Illuminate\Support\Facades\Route;

// --- Auth ---
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware(['auth:sanctum', 'not_banned'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // --- Profile ---
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::post('/profile/photo', [ProfileController::class, 'uploadPhoto']);

    // --- Student-facing major matching & scholarships ---
    Route::get('/majors/match', [MajorController::class, 'match']);
    Route::get('/support-tickets', [SupportTicketController::class, 'index']);
    Route::post('/support-tickets', [SupportTicketController::class, 'store']);

    // --- Admin ---
    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::apiResource('institutions', AdminInstitutionController::class);
        Route::apiResource('faculties', AdminFacultyController::class);
        Route::apiResource('campuses', AdminCampusController::class);
        Route::apiResource('majors', AdminMajorController::class);
        Route::apiResource('scholarships', AdminScholarshipController::class);

        // Explicit {id} (not apiResource) so the shared LookupController's
        // update(Request $request, int $id) signature binds correctly for all five.
        foreach ([
            'regions' => RegionController::class,
            'cities' => CityController::class,
            'degree-levels' => DegreeLevelController::class,
            'fields-of-study' => FieldOfStudyController::class,
            'tawjihi-branches' => TawjihiBranchController::class,
        ] as $uri => $controller) {
            Route::post($uri, [$controller, 'store']);
            Route::put("{$uri}/{id}", [$controller, 'update']);
            Route::delete("{$uri}/{id}", [$controller, 'destroy']);
        }

        Route::get('students', [StudentController::class, 'index']);
        Route::get('students/{student}', [StudentController::class, 'show']);
        Route::post('students', [StudentController::class, 'store']);
        Route::put('students/{student}', [StudentController::class, 'update']);
        Route::delete('students/{student}', [StudentController::class, 'destroy']);
        Route::post('students/{student}/ban', [StudentController::class, 'ban']);
        Route::post('students/{student}/unban', [StudentController::class, 'unban']);

        Route::get('support-tickets', [AdminSupportTicketController::class, 'index']);
        Route::put('support-tickets/{supportTicket}', [AdminSupportTicketController::class, 'update']);
    });
});

// --- Public browsing (majors, scholarships, dropdown lookups) ---
Route::get('/majors', [MajorController::class, 'index']);
Route::get('/majors/{major}', [MajorController::class, 'show']);
Route::get('/scholarships', [ScholarshipController::class, 'index']);
Route::get('/scholarships/{scholarship}', [ScholarshipController::class, 'show']);

Route::get('/regions', [RegionController::class, 'index']);
Route::get('/cities', [CityController::class, 'index']);
Route::get('/degree-levels', [DegreeLevelController::class, 'index']);
Route::get('/fields-of-study', [FieldOfStudyController::class, 'index']);
Route::get('/tawjihi-branches', [TawjihiBranchController::class, 'index']);
