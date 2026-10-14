<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\InstitutionRequest;
use App\Models\Institution;

class InstitutionController extends Controller
{
    public function index()
    {
        return Institution::with('campuses.city.region', 'faculties')->paginate(20);
    }

    public function show(Institution $institution)
    {
        return $institution->load('campuses.city.region', 'faculties');
    }

    public function store(InstitutionRequest $request)
    {
        return response()->json(Institution::create($request->validated()), 201);
    }

    public function update(InstitutionRequest $request, Institution $institution)
    {
        $institution->update($request->validated());

        return $institution;
    }

    public function destroy(Institution $institution)
    {
        $institution->delete();

        return response()->json(null, 204);
    }
}
