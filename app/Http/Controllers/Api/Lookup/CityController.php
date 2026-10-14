<?php

namespace App\Http\Controllers\Api\Lookup;

use App\Models\City;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CityController extends LookupController
{
    protected string $model = City::class;

    public function index()
    {
        return City::with('region:id,name')->orderBy('name')->get(['id', 'region_id', 'name']);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'region_id' => ['required', 'exists:regions,id'],
            'name' => ['required', 'string', 'max:255', Rule::unique('cities')->where('region_id', $request->region_id)],
        ]);

        return response()->json(City::create($data), 201);
    }

    public function update(Request $request, int $id)
    {
        $city = City::findOrFail($id);

        $data = $request->validate([
            'region_id' => ['sometimes', 'exists:regions,id'],
            'name' => [
                'sometimes', 'string', 'max:255',
                Rule::unique('cities')->where('region_id', $request->input('region_id', $city->region_id))->ignore($city->id),
            ],
        ]);

        $city->update($data);

        return $city;
    }
}
