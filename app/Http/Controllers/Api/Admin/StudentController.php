<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BanStudentRequest;
use App\Http\Requests\Admin\StudentRequest;
use App\Http\Resources\UserResource;
use App\Models\StudentProfile;
use App\Models\User;

class StudentController extends Controller
{
    private const RELATIONS = [
        'studentProfile.tawjihiBranch', 'studentProfile.preferredRegion',
        'studentProfile.preferredCity', 'studentProfile.preferredFields', 'studentProfile.scholarships',
    ];

    public function index()
    {
        $students = User::where('role', 'student')->with(self::RELATIONS)->paginate(20);

        return UserResource::collection($students);
    }

    public function show(User $student)
    {
        $this->ensureIsStudent($student);

        return new UserResource($student->load(self::RELATIONS));
    }

    public function store(StudentRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
            'role' => 'student',
        ]);

        $profile = StudentProfile::create(array_merge(
            ['user_id' => $user->id],
            $request->safe()->only(['gpa', 'tawjihi_branch_id', 'preferred_region_id', 'preferred_city_id', 'budget_max', 'description'])
        ));

        if ($request->has('preferred_field_ids')) {
            $profile->preferredFields()->sync($request->input('preferred_field_ids', []));
        }
        if ($request->has('scholarship_ids')) {
            $profile->scholarships()->sync($request->input('scholarship_ids', []));
        }

        return new UserResource($user->load(self::RELATIONS));
    }

    public function update(StudentRequest $request, User $student)
    {
        $this->ensureIsStudent($student);

        $student->fill($request->safe()->only(['name', 'email']));
        if ($request->filled('password')) {
            $student->password = $request->password;
        }
        $student->save();

        $profile = $student->studentProfile ?? StudentProfile::create(['user_id' => $student->id]);
        $profile->fill($request->safe()->only(['gpa', 'tawjihi_branch_id', 'preferred_region_id', 'preferred_city_id', 'budget_max', 'description']));
        $profile->save();

        if ($request->has('preferred_field_ids')) {
            $profile->preferredFields()->sync($request->input('preferred_field_ids', []));
        }
        if ($request->has('scholarship_ids')) {
            $profile->scholarships()->sync($request->input('scholarship_ids', []));
        }

        return new UserResource($student->load(self::RELATIONS));
    }

    public function destroy(User $student)
    {
        $this->ensureIsStudent($student);

        $student->delete();

        return response()->json(null, 204);
    }

    public function ban(BanStudentRequest $request, User $student)
    {
        $this->ensureIsStudent($student);

        $student->update([
            'is_banned' => true,
            'banned_reason' => $request->input('reason'),
            'banned_at' => now(),
        ]);

        $student->tokens()->delete();

        return new UserResource($student);
    }

    public function unban(User $student)
    {
        $this->ensureIsStudent($student);

        $student->update([
            'is_banned' => false,
            'banned_reason' => null,
            'banned_at' => null,
        ]);

        return new UserResource($student);
    }

    /**
     * Route model binding resolves any user by id; guard every action here so an
     * admin can't accidentally ban/edit/delete another admin account by id guessing.
     */
    private function ensureIsStudent(User $user): void
    {
        abort_unless($user->role === 'student', 404);
    }
}
