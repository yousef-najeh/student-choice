<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ScholarshipRequest;
use App\Http\Resources\ScholarshipResource;
use App\Models\Scholarship;

class ScholarshipController extends Controller
{
    public function index()
    {
        return ScholarshipResource::collection(Scholarship::with('studentProfiles.user')->paginate(20));
    }

    public function store(ScholarshipRequest $request)
    {
        $scholarship = Scholarship::create($request->validated());

        return new ScholarshipResource($scholarship);
    }

    public function update(ScholarshipRequest $request, Scholarship $scholarship)
    {
        $scholarship->update($request->validated());

        return new ScholarshipResource($scholarship);
    }

    public function destroy(Scholarship $scholarship)
    {
        $scholarship->delete();

        return response()->json(null, 204);
    }
}
