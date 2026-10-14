<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CampusRequest;
use App\Models\Campus;

class CampusController extends Controller
{
    public function index()
    {
        return Campus::with('institution', 'city.region')->paginate(20);
    }

    public function store(CampusRequest $request)
    {
        return response()->json(Campus::create($request->validated()), 201);
    }

    public function update(CampusRequest $request, Campus $campus)
    {
        $campus->update($request->validated());

        return $campus;
    }

    public function destroy(Campus $campus)
    {
        $campus->delete();

        return response()->json(null, 204);
    }
}
