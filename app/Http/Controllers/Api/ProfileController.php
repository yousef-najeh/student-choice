<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Requests\UploadPhotoRequest;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function update(UpdateProfileRequest $request)
    {
        $user = $request->user();
        $profile = $user->studentProfile;

        $user->fill($request->only(['name', 'email']));
        if ($request->filled('password')) {
            $user->password = $request->password;
        }
        $user->save();

        $profile->fill($request->only([
            'gpa', 'tawjihi_branch_id', 'preferred_region_id',
            'preferred_city_id', 'budget_max', 'description',
        ]));
        $profile->save();

        if ($request->has('preferred_field_ids')) {
            $profile->preferredFields()->sync($request->input('preferred_field_ids', []));
        }

        $user->load([
            'studentProfile.tawjihiBranch',
            'studentProfile.preferredRegion',
            'studentProfile.preferredCity',
            'studentProfile.preferredFields',
            'studentProfile.scholarships',
        ]);

        return new UserResource($user);
    }

    public function uploadPhoto(UploadPhotoRequest $request)
    {
        $profile = $request->user()->studentProfile;

        if ($profile->photo_path) {
            Storage::disk('public')->delete($profile->photo_path);
        }

        $path = $request->file('photo')->store('photos', 'public');
        $profile->update(['photo_path' => $path]);

        return new UserResource($request->user()->load('studentProfile'));
    }
}
