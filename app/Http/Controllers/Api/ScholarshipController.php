<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ScholarshipResource;
use App\Models\Scholarship;

class ScholarshipController extends Controller
{
    public function index()
    {
        // Deliberately not eager-loading studentProfiles here — who holds a
        // given scholarship is private, only exposed via the admin endpoint.
        return ScholarshipResource::collection(Scholarship::paginate(20));
    }

    public function show(Scholarship $scholarship)
    {
        return new ScholarshipResource($scholarship);
    }
}
