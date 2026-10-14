<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MajorRequest;
use App\Http\Resources\MajorResource;
use App\Models\Major;

class MajorController extends Controller
{
    private const RELATIONS = [
        'institution', 'faculty', 'degreeLevel', 'fieldOfStudy',
        'campuses.city.region', 'tawjihiBranches',
    ];

    public function index()
    {
        return MajorResource::collection(Major::with(self::RELATIONS)->paginate(20));
    }

    public function store(MajorRequest $request)
    {
        $major = Major::create($request->safe()->except(['campus_ids', 'tawjihi_branch_ids']));

        $this->syncPivots($major, $request);

        return new MajorResource($major->load(self::RELATIONS));
    }

    public function update(MajorRequest $request, Major $major)
    {
        $major->update($request->safe()->except(['campus_ids', 'tawjihi_branch_ids']));

        $this->syncPivots($major, $request);

        return new MajorResource($major->load(self::RELATIONS));
    }

    public function destroy(Major $major)
    {
        $major->delete();

        return response()->json(null, 204);
    }

    private function syncPivots(Major $major, MajorRequest $request): void
    {
        if ($request->has('campus_ids')) {
            $major->campuses()->sync($request->input('campus_ids', []));
        }
        if ($request->has('tawjihi_branch_ids')) {
            $major->tawjihiBranches()->sync($request->input('tawjihi_branch_ids', []));
        }
    }
}
