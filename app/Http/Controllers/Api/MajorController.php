<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MajorResource;
use App\Models\Major;
use App\Services\MajorMatchingService;
use Illuminate\Http\Request;

class MajorController extends Controller
{
    public function index(Request $request)
    {
        $majors = Major::with([
            'institution', 'faculty', 'degreeLevel', 'fieldOfStudy',
            'campuses.city.region', 'tawjihiBranches',
        ])->paginate(20);

        return MajorResource::collection($majors);
    }

    public function show(Major $major)
    {
        $major->load([
            'institution', 'faculty', 'degreeLevel', 'fieldOfStudy',
            'campuses.city.region', 'tawjihiBranches',
        ]);

        return new MajorResource($major);
    }

    public function match(Request $request, MajorMatchingService $matchingService)
    {
        $profile = $request->user()->studentProfile()->with(['preferredFields', 'scholarships'])->firstOrFail();

        $scored = $matchingService->scoredMajorsFor($profile);

        $page = (int) $request->query('page', 1);
        $perPage = (int) $request->query('per_page', 20);

        return MajorResource::collection(
            $scored->slice(($page - 1) * $perPage, $perPage)->values()
        )->additional([
            'meta' => [
                'total' => $scored->count(),
                'page' => $page,
                'per_page' => $perPage,
            ],
        ]);
    }
}
