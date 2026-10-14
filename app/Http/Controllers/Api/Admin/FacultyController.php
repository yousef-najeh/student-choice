<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FacultyRequest;
use App\Models\Faculty;

class FacultyController extends Controller
{
    public function index()
    {
        return Faculty::with('institution')->paginate(20);
    }

    public function store(FacultyRequest $request)
    {
        return response()->json(Faculty::create($request->validated()), 201);
    }

    public function update(FacultyRequest $request, Faculty $faculty)
    {
        $faculty->update($request->validated());

        return $faculty;
    }

    public function destroy(Faculty $faculty)
    {
        $faculty->delete();

        return response()->json(null, 204);
    }
}
